<?php
error_log("=== DÉBUT SUCCESS.PHP ===");

require_once('../admin/include/config.php');
require_once('./includes/common_functions.php');

// Log des données reçues
$data = [
    'TransId' => $_GET['TransId'] ?? null,
    'MerchantRef' => $_GET['MerchantRef'] ?? null,
    'Status' => $_GET['Status'] ?? null,
    'Amount' => $_GET['Amount'] ?? null
];
error_log("Logging transaction: " . print_r($data, true));

try {
    // 1. Mise à jour du statut de la transaction
    $stmt = $connection->prepare("
        UPDATE transactions 
        SET status = ? 
        WHERE ref_order = ?
    ");
    
    $status = ($data['Status'] == '2' || $data['Status'] == 'APPROVED') ? 'paid' : 'failed';
    $stmt->bind_param("ss", $status, $data['MerchantRef']);
    $stmt->execute();
    
    error_log("Transaction status updated successfully for ref_order: " . $data['MerchantRef']);
    
    // 2. Log dans ovri_logs
    $logStmt = $connection->prepare("
        INSERT INTO ovri_logs 
        (transaction_id, request_type, request_body, response_body, http_code, token, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $requestType = 'SUCCESS_CALLBACK';
    $requestBody = json_encode($data);
    $responseBody = "Transaction completed with status: " . $status;
    $httpCode = 200;
    $token = ''; // À remplir si nécessaire
    
    $logStmt->bind_param("ssssss",
        $data['TransId'],
        $requestType,
        $requestBody,
        $responseBody,
        $httpCode,
        $token
    );
    
    $logStmt->execute();
    
    // 3. Redirection
    $stmt = $connection->prepare("
        SELECT request_body 
        FROM ovri_logs 
        WHERE transaction_id = ? 
        ORDER BY created_at ASC 
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $data['TransId']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $originalRequest = json_decode($row['request_body'], true);
        if (isset($originalRequest['urlOK'])) {
            header("Location: " . $originalRequest['urlOK']);
            exit();
        }
    }
    
} catch (Exception $e) {
    error_log("Error in success.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
