<?php
// Fonction de logging dédiée
function logDebug($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

function sendIpnNotification($transactionData) {
    global $connection;
    
    logDebug("Début sendIpnNotification", $transactionData);
    
    // Récupérer les informations de la transaction
    $stmt = $connection->prepare("SELECT request_body, response_body FROM ovri_logs WHERE transaction_id = ?");
    $stmt->bind_param("s", $transactionData['TransId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $logData = $result->fetch_assoc();
    
    if (!$logData) {
        logDebug("Aucune donnée trouvée dans ovri_logs", $transactionData['TransId']);
        return false;
    }

    $requestData = json_decode($logData['request_body'], true);
    $responseData = json_decode($logData['response_body'], true);
    
    logDebug("Données de la requête", $requestData);
    
    // Vérifier si une URL IPN est définie
    if (empty($requestData['ipnURL'])) {
        logDebug("Pas d'ipnURL trouvée");
        return false;
    }

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

    logDebug("Données de notification préparées", $notificationData);

    // Générer la signature
    $signature = hash_hmac('sha256', json_encode($notificationData), $requestData['MerchantKey']);

    logDebug("Tentative d'envoi IPN à", $requestData['ipnURL']);

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
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, // Pour le test uniquement
        CURLOPT_SSL_VERIFYHOST => 0      // Pour le test uniquement
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        logDebug("Erreur CURL", curl_error($ch));
    }
    
    logDebug("Réponse IPN", ['code' => $httpCode, 'response' => $response]);
    
    curl_close($ch);

    // Enregistrer la tentative de notification
    logIpnAttempt($transactionData['TransId'], $notificationData, $httpCode, $response);

    return $httpCode === 200;
}

function logIpnAttempt($transactionId, $payload, $httpCode, $response) {
    global $connection;
    
    $stmt = $connection->prepare("INSERT INTO ipn_logs (transaction_id, payload, response_code, response) VALUES (?, ?, ?, ?)");
    $payloadJson = json_encode($payload);
    $stmt->bind_param("ssis", $transactionId, $payloadJson, $httpCode, $response);
    $stmt->execute();
} 