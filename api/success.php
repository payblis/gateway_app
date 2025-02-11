<?php

require('../admin/include/config.php');

function sendIpnNotification($transaction_data, $ovri_response) {
    global $connection;
    
    // Log file path
    $log_file = __DIR__ . '/../logs/ipn_callbacks.log';
    
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

    // Log the notification data
    error_log(date('[Y-m-d H:i:s]') . " Preparing IPN notification for transaction " . $transaction_data['TransId'] . "\n", 3, $log_file);
    error_log("IPN URL: " . $initial_request['urlIPN'] . "\n", 3, $log_file);
    error_log("Notification Data: " . json_encode($notificationData, JSON_PRETTY_PRINT) . "\n", 3, $log_file);

    // Générer la signature
    $signature = hash_hmac('sha256', json_encode($notificationData), 'YOUR_SECRET_KEY');

    // Log the signature
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

// Get values from URL parameters
$MerchantRef = $_GET['MerchantRef'];
$amount = $_GET['Amount'];
$TransId = $_GET['TransId'];
$Status = $_GET['Status'];


$query = "SELECT * FROM transactions WHERE `ref_order` = '$MerchantRef'";
$run = mysqli_query($connection, $query);

if (mysqli_num_rows($run) > 0) {

    $row = mysqli_fetch_assoc($run);
    // echo $row['ref_order'];

    $update = "UPDATE `transactions` SET `status`= 'paid' WHERE `ref_order` = '{$row['ref_order']}'";
    $result = mysqli_query($connection, $update) or die("failed to update query.");

    // echo '<pre>';
    // print_r($_GET);
    // echo '</pre>';
} else {
    echo "No records found for Transaction ID: " . htmlspecialchars($MerchantRef);
}



//Getting SuccessUrl
$getdata = "SELECT * FROM ovri_logs WHERE `transaction_id` = '$TransId'";
$exec = mysqli_query($connection, $getdata);

if (mysqli_num_rows($exec) > 0) {

    $get = mysqli_fetch_assoc($exec);
    $user_Req = $get['request_body'];
    $dataDecoded = json_decode($user_Req, true);
    $success_Url = $dataDecoded['urlOK'];

    header('Location: ' . $success_Url . '?MerchantRef=' . urlencode($MerchantRef) . '&Amount=' . urlencode($amount) . '&TransId=' . urlencode($TransId) . '&Status=Success');

} else {
    echo "<p>No records found for Transaction ID: " . htmlspecialchars($TransId) . "</p>";
}



// Close connection
mysqli_close($connection);
