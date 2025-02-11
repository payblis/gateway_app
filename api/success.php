<?php

require('../admin/include/config.php');

function sendIpnNotification($transaction_data, $ovri_response) {
    global $connection;
    
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

    // Générer la signature
    $signature = hash_hmac('sha256', json_encode($notificationData), 'YOUR_SECRET_KEY');

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
    curl_close($ch);

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

// Récupération des paramètres
$MerchantRef = $_GET['MerchantRef'];
$TransId = $_GET['TransId'];

// Mise à jour du statut et envoi de l'IPN
$query = "SELECT t.*, o.response_body as ovri_response 
          FROM transactions t 
          JOIN ovri_logs o ON t.ref_order = o.transaction_id 
          WHERE t.ref_order = ? AND o.request_type = 'via card'";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $MerchantRef);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $transaction_data = $result->fetch_assoc();
    $ovri_response = json_decode($transaction_data['ovri_response'], true);
    
    // Mise à jour du statut
    $update = "UPDATE transactions SET status = 'paid' WHERE ref_order = ?";
    $stmt = $connection->prepare($update);
    $stmt->bind_param("s", $MerchantRef);
    $stmt->execute();

    // Envoi de la notification IPN
    sendIpnNotification($transaction_data, $ovri_response);

    // Redirection client
    header('Location: ' . $transaction_data['urlOK']);
    exit();
}

// Getting SuccessUrl
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
