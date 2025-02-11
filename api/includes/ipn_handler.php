<?php
// Fonction de logging dédiée
function logDebug($message, $data = null) {
    $logMessage = "[IPN Debug] " . date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage);
}

function sendIpnNotification($transactionData) {
    global $connection;
    
    error_log("=== Début sendIpnNotification ===");
    error_log("TransactionData reçue: " . print_r($transactionData, true));
    
    try {
        // Récupérer les informations de la transaction
        $stmt = $connection->prepare("SELECT request_body, response_body FROM ovri_logs WHERE transaction_id = ?");
        $stmt->bind_param("s", $transactionData['TransId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $logData = $result->fetch_assoc();
        
        if (!$logData) {
            error_log("Aucune donnée dans ovri_logs pour TransId: " . $transactionData['TransId']);
            return false;
        }

        error_log("Données trouvées dans ovri_logs: " . print_r($logData, true));

        $requestData = json_decode($logData['request_body'], true);
        $responseData = json_decode($logData['response_body'], true);
        
        if (!isset($requestData['ipnURL'])) {
            error_log("Pas d'ipnURL trouvée dans les données de requête");
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

        logDebug("Données de notification préparées", $notificationData);

        // Générer la signature
        $signature = hash_hmac('sha256', json_encode($notificationData), $requestData['MerchantKey']);

        // Avant l'envoi CURL
        error_log("Tentative d'envoi CURL à: " . $requestData['ipnURL']);
        error_log("Données à envoyer: " . json_encode($notificationData));
        
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
            error_log("Erreur CURL: " . curl_error($ch));
        } else {
            error_log("Réponse CURL - Code: " . $httpCode . ", Réponse: " . $response);
        }
        
        curl_close($ch);
        
        error_log("=== Fin sendIpnNotification ===");
        
        // Enregistrer la tentative de notification
        logIpnAttempt($transactionData['TransId'], $notificationData, $httpCode, $response);

        return $httpCode === 200;
        
    } catch (Exception $e) {
        error_log("Exception dans sendIpnNotification: " . $e->getMessage());
        return false;
    }
}

function logIpnAttempt($transactionId, $payload, $httpCode, $response) {
    global $connection;
    
    $stmt = $connection->prepare("INSERT INTO ipn_logs (transaction_id, payload, response_code, response) VALUES (?, ?, ?, ?)");
    $payloadJson = json_encode($payload);
    $stmt->bind_param("ssis", $transactionId, $payloadJson, $httpCode, $response);
    $stmt->execute();
} 