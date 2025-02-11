<?php

require('../admin/include/config.php');

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

// Get values from URL parameters
$MerchantRef = $_GET['MerchantRef'];
$amount = $_GET['Amount'];
$TransId = $_GET['TransId'];
$Status = $_GET['Status'];

// Vérifier et mettre à jour le statut de la transaction
$query = "SELECT * FROM transactions WHERE `ref_order` = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $MerchantRef);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Mettre à jour le statut
    $update = "UPDATE `transactions` SET `status`= 'paid' WHERE `ref_order` = ?";
    $updateStmt = $connection->prepare($update);
    $updateStmt->bind_param("s", $row['ref_order']);
    $updateStmt->execute();

    // Préparer les données pour la notification
    $transactionData = [
        'MerchantRef' => $MerchantRef,
        'Amount' => $amount,
        'TransId' => $TransId,
        'Status' => $Status
    ];

    // Envoyer la notification IPN
    sendIpnNotification($transactionData);

    // Récupérer l'URL de succès et rediriger
    $getdata = "SELECT * FROM ovri_logs WHERE `transaction_id` = ?";
    $stmt = $connection->prepare($getdata);
    $stmt->bind_param("s", $TransId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $get = $result->fetch_assoc();
        $user_Req = $get['request_body'];
        $dataDecoded = json_decode($user_Req, true);
        $success_Url = $dataDecoded['urlOK'];

        header('Location: ' . $success_Url . '?MerchantRef=' . urlencode($MerchantRef) . 
               '&Amount=' . urlencode($amount) . 
               '&TransId=' . urlencode($TransId) . 
               '&Status=Success');
    }
} else {
    echo "No records found for Transaction ID: " . htmlspecialchars($MerchantRef);
}

// Close connection
mysqli_close($connection);
