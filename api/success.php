<?php

require('../admin/include/config.php');
require('./includes/ipn_handler.php');

// Log au début du script
error_log("=== Début success.php ===");
error_log("GET params: " . print_r($_GET, true));

// Fonction pour envoyer la notification IPN
function sendIpnNotification($transactionData) {
    global $connection;
    
    // Log de début
    error_log("Début de sendIpnNotification pour TransId: " . $transactionData['TransId']);
    
    // Récupérer les informations de la transaction
    $stmt = $connection->prepare("SELECT request_body, response_body FROM ovri_logs WHERE transaction_id = ?");
    $stmt->bind_param("s", $transactionData['TransId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $logData = $result->fetch_assoc();
    
    if (!$logData) {
        error_log("Aucune donnée trouvée dans ovri_logs pour TransId: " . $transactionData['TransId']);
        return false;
    }

    $requestData = json_decode($logData['request_body'], true);
    $responseData = json_decode($logData['response_body'], true);
    
    // Vérifier l'URL IPN
    if (empty($requestData['ipnURL'])) {
        error_log("Pas d'ipnURL trouvée pour TransId: " . $transactionData['TransId']);
        return false;
    }

    error_log("IPN URL trouvée: " . $requestData['ipnURL']);
    
    // Préparer les données de notification
    $notificationData = [
        'event' => 'payment.completed',
        'merchant_reference' => $transactionData['MerchantRef'],
        'transaction_id' => $transactionData['TransId'],
        'amount' => $transactionData['Amount'],
        'status' => $transactionData['Status'],
        'payment_details' => [
            'card_brand' => $responseData['receipt']['cardbrand'] ?? null,
            'card_last4' => substr($responseData['receipt']['cardpan'] ?? '', -4),
            'authorization_code' => $responseData['receipt']['authorization'] ?? null,
            'transaction_date' => date('Y-m-d H:i:s')
        ]
    ];

    // Générer la signature
    $signature = hash_hmac('sha256', json_encode($notificationData), $requestData['MerchantKey']);

    // Envoyer la notification
    $ch = curl_init($requestData['ipnURL']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($notificationData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Payblis-Signature: ' . $signature,
            'X-Payblis-Event: payment.completed'
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    // Log de la tentative d'envoi
    error_log("Tentative d'envoi IPN à " . $requestData['ipnURL']);
    error_log("Données envoyées: " . json_encode($notificationData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log de la réponse
    error_log("Réponse IPN - Code HTTP: " . $httpCode);
    error_log("Réponse IPN - Contenu: " . $response);
    
    curl_close($ch);

    // Enregistrer la tentative de notification
    logIpnAttempt($transactionData['TransId'], $notificationData, $httpCode, $response);

    return $httpCode === 200;
}

// Fonction pour enregistrer les tentatives de notification
function logIpnAttempt($transactionId, $payload, $httpCode, $response) {
    global $connection;
    
    $stmt = $connection->prepare("INSERT INTO ipn_logs (transaction_id, payload, response_code, response) VALUES (?, ?, ?, ?)");
    $payloadJson = json_encode($payload);
    $stmt->bind_param("ssis", $transactionId, $payloadJson, $httpCode, $response);
    $stmt->execute();
}

// Récupérer les paramètres
$MerchantRef = $_GET['MerchantRef'] ?? null;
$amount = $_GET['Amount'] ?? null;
$TransId = $_GET['TransId'] ?? null;
$Status = $_GET['Status'] ?? null;

error_log("Paramètres récupérés: " . json_encode([
    'MerchantRef' => $MerchantRef,
    'Amount' => $amount,
    'TransId' => $TransId,
    'Status' => $Status
]));

if (!$MerchantRef || !$TransId) {
    error_log("Paramètres manquants dans success.php");
    die("Paramètres manquants");
}

// Vérifier la transaction
$query = "SELECT * FROM transactions WHERE `ref_order` = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $MerchantRef);
$stmt->execute();
$result = $stmt->get_result();

error_log("Recherche transaction pour ref_order: " . $MerchantRef);

if ($result->num_rows > 0) {
    error_log("Transaction trouvée");
    $row = $result->fetch_assoc();
    
    // Mettre à jour le statut
    $updateStmt = $connection->prepare("UPDATE transactions SET status = 'paid' WHERE ref_order = ?");
    $updateStmt->bind_param("s", $MerchantRef);
    $updateStmt->execute();
    error_log("Statut mis à jour pour ref_order: " . $MerchantRef);

    // Préparer les données pour l'IPN
    $transactionData = [
        'MerchantRef' => $MerchantRef,
        'Amount' => $amount,
        'TransId' => $TransId,
        'Status' => $Status
    ];

    error_log("Tentative d'envoi IPN avec données: " . json_encode($transactionData));
    
    try {
        $ipnResult = sendIpnNotification($transactionData);
        error_log("Résultat IPN: " . ($ipnResult ? "Succès" : "Échec"));
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
    }

    // Récupérer l'URL de succès
    $getdata = "SELECT request_body FROM ovri_logs WHERE transaction_id = ?";
    $stmt = $connection->prepare($getdata);
    $stmt->bind_param("s", $TransId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $get = $result->fetch_assoc();
        $user_Req = $get['request_body'];
        $dataDecoded = json_decode($user_Req, true);
        $success_Url = $dataDecoded['urlOK'];

        error_log("Redirection vers: " . $success_Url);
        
        header('Location: ' . $success_Url . '?MerchantRef=' . urlencode($MerchantRef) . 
               '&Amount=' . urlencode($amount) . 
               '&TransId=' . urlencode($TransId) . 
               '&Status=Success');
        exit;
    }
} else {
    error_log("Aucune transaction trouvée pour ref_order: " . $MerchantRef);
    echo "No records found for Transaction ID: " . htmlspecialchars($MerchantRef);
}

error_log("=== Fin success.php ===");

// Fermer la connexion
mysqli_close($connection);
