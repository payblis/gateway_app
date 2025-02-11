<?php
require('admin/include/config.php');

// Log file path
$log_file = __DIR__ . '/logs/ipn_callbacks.log';
error_log(date('[Y-m-d H:i:s]') . " Script started. Log file path: " . $log_file . "\n", 3, $log_file);

function sendIpnNotification($transaction_data, $ovri_response) {
    global $connection;
    
    // Log file path
    $log_file = __DIR__ . '/logs/ipn_callbacks.log';
    
    // Test logging
    error_log(date('[Y-m-d H:i:s]') . " Starting IPN notification process\n", 3, $log_file);
    
    // Dump transaction data for debugging
    error_log("Transaction Data: " . print_r($transaction_data, true) . "\n", 3, $log_file);
    error_log("OVRI Response: " . print_r($ovri_response, true) . "\n", 3, $log_file);

    // Récupérer l'URL IPN depuis les logs
    $query = "SELECT request_body FROM ovri_logs 
              WHERE transaction_id = ? 
              AND request_type = 'via card' 
              ORDER BY created_at DESC LIMIT 1";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $transaction_data['TransId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $initial_request = json_decode($result->fetch_assoc()['request_body'], true);
    
    if (!isset($initial_request['urlIPN'])) {
        error_log(date('[Y-m-d H:i:s]') . " No IPN URL found for transaction " . $transaction_data['TransId'] . "\n", 3, $log_file);
        return false;
    }

    // Préparer les données de notification
    $notificationData = [
        'status' => 'success',
        'merchantRef' => $transaction_data['ref_order'],
        'amount' => $transaction_data['amount'],
        'transactionId' => $transaction_data['TransId'],
        'receipt' => $ovri_response['receipt'] ?? null,
        'threeDSecure' => $ovri_response['threesecure'] ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    error_log("IPN URL: " . $initial_request['urlIPN'] . "\n", 3, $log_file);
    error_log("Notification Data: " . json_encode($notificationData, JSON_PRETTY_PRINT) . "\n", 3, $log_file);

    // Générer la signature
    $signature = hash_hmac('sha256', json_encode($notificationData), 'YOUR_SECRET_KEY');
    error_log("Generated Signature: " . $signature . "\n", 3, $log_file);

    // Envoyer la notification
    $ch = curl_init($initial_request['urlIPN']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($notificationData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Signature: ' . $signature
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log the response
    error_log("HTTP Response Code: " . $httpCode . "\n", 3, $log_file);
    error_log("Response Body: " . $response . "\n", 3, $log_file);
    if ($curlError) {
        error_log("Curl Error: " . $curlError . "\n", 3, $log_file);
    }
    error_log("----------------------------------------\n", 3, $log_file);

    // Enregistrer la notification dans les logs
    $stmt = $connection->prepare("INSERT INTO ovri_logs (
        transaction_id,
        request_type,
        request_body,
        response_body,
        http_code,
        token
    ) VALUES (?, ?, ?, ?, ?, ?)");

    $request_type = 'IPN notification';
    $stmt->bind_param(
        "ssssss",
        $transaction_data['TransId'],
        $request_type,
        json_encode($notificationData),
        $response,
        $httpCode,
        $transaction_data['token']
    );
    $stmt->execute();

    return $httpCode >= 200 && $httpCode < 300;
}

// Récupération des paramètres de l'URL
$transactionId = $_GET['transactionId'];
$status = $_GET['status'];

error_log(date('[Y-m-d H:i:s]') . " Received parameters - TransactionId: $transactionId, Status: $status\n", 3, $log_file);

// Récupération des données de transaction
$query = "SELECT t.*, o.response_body as ovri_response 
          FROM transactions t 
          JOIN ovri_logs o ON t.ref_order = o.transaction_id 
          WHERE o.transaction_id = ? AND o.request_type = 'via card'";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $transactionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $transaction_data = $result->fetch_assoc();
    $ovri_response = json_decode($transaction_data['ovri_response'], true);
    
    error_log(date('[Y-m-d H:i:s]') . " Transaction found, preparing to send IPN\n", 3, $log_file);
    
    // Mise à jour du statut
    $update = "UPDATE transactions SET status = 'paid' WHERE ref_order = ?";
    $stmt = $connection->prepare($update);
    $stmt->bind_param("s", $transactionId);
    $stmt->execute();

    // Envoi de la notification IPN
    $ipn_result = sendIpnNotification($transaction_data, $ovri_response);
    
    error_log(date('[Y-m-d H:i:s]') . " IPN send result: " . ($ipn_result ? "Success" : "Failed") . "\n", 3, $log_file);

    // Affichage des paramètres reçus (votre code existant)
    echo $_GET['code'];
    echo '<br>';
    echo $_GET['transactionId'];
}


?>