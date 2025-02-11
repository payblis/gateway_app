<?php
error_log("=== DÉBUT SUCCESS.PHP ===");

require('../admin/include/config.php');
require('./includes/common_functions.php');
require('./includes/ovri_logger.php');

$logger = new OvriLogger($connection);

// Récupérer les paramètres
$merchantRef = $_GET['MerchantRef'] ?? null;
$amount = $_GET['Amount'] ?? null;
$transId = $_GET['TransId'] ?? null;
$status = $_GET['Status'] ?? null;

// Logger la transaction
$logger->logTransaction([
    'TransId' => $transId,
    'MerchantRef' => $merchantRef,
    'Status' => $status,
    'Amount' => $amount
]);

// Récupérer l'URL de redirection du marchand
$stmt = $connection->prepare("
    SELECT t.*, o.request_body 
    FROM transactions t
    INNER JOIN ovri_logs o ON t.ref_order = JSON_UNQUOTE(JSON_EXTRACT(o.request_body, '$.RefOrder'))
    WHERE t.ref_order = ?
    ORDER BY o.created_at DESC
    LIMIT 1
");

$stmt->bind_param("s", $merchantRef);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();

if ($transaction) {
    $requestBody = json_decode($transaction['request_body'], true);
    $urlOK = $requestBody['urlOK'] ?? null;
    
    if ($urlOK) {
        header("Location: " . $urlOK);
        exit();
    }
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
