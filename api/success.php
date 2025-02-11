<?php
require('../admin/include/config.php');
require('./includes/ipn_handler.php');

// Log au début du script
error_log("=== Début success.php ===");
error_log("GET params: " . print_r($_GET, true));

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
