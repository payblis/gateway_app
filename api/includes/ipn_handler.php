<?php
// Fonction de logging dédiée
function logDebug($message, $data = null) {
    $logMessage = "[IPN Debug] " . date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage);
}

function logIpnAttempt($transactionId, $payload, $httpCode, $response) {
    global $connection;
    
    error_log("[IPN] Tentative d'enregistrement dans ipn_logs");
    error_log("[IPN] TransactionId: " . $transactionId);
    
    try {
        $query = "INSERT INTO ipn_logs (transaction_id, payload, response_code, response, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        
        if (!$stmt) {
            error_log("[IPN] Erreur préparation requête: " . $connection->error);
            return false;
        }
        
        $payloadJson = json_encode($payload);
        $status = ($httpCode == 200) ? 'success' : 'failed';
        
        $stmt->bind_param("ssis", $transactionId, $payloadJson, $httpCode, $response, $status);
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("[IPN] Erreur insertion: " . $stmt->error);
            return false;
        }
        
        error_log("[IPN] Log enregistré avec succès");
        return true;
        
    } catch (Exception $e) {
        error_log("[IPN] Exception lors de l'enregistrement: " . $e->getMessage());
        return false;
    }
}

function sendIpnNotification($transactionData) {
    global $connection;
    
    error_log("[IPN] Début sendIpnNotification");
    error_log("[IPN] Données reçues: " . print_r($transactionData, true));
    
    try {
        // Récupérer les données de la transaction
        $query = "SELECT request_body, response_body FROM ovri_logs WHERE transaction_id = ?";
        $stmt = $connection->prepare($query);
        
        if (!$stmt) {
            error_log("[IPN] Erreur préparation requête ovri_logs: " . $connection->error);
            return false;
        }
        
        $stmt->bind_param("s", $transactionData['TransId']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            error_log("[IPN] Aucune donnée trouvée dans ovri_logs");
            return false;
        }
        
        $logData = $result->fetch_assoc();
        $requestData = json_decode($logData['request_body'], true);
        $responseData = json_decode($logData['response_body'], true);
        
        error_log("[IPN] Données de requête: " . print_r($requestData, true));
        
        if (empty($requestData['ipnURL'])) {
            error_log("[IPN] Pas d'URL IPN définie");
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
        
        error_log("[IPN] Données à envoyer: " . print_r($notificationData, true));
        
        // Envoyer la notification
        $ch = curl_init($requestData['ipnURL']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($notificationData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Payblis-Signature: ' . hash_hmac('sha256', json_encode($notificationData), $requestData['MerchantKey']),
                'X-Payblis-Event: payment.completed'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            error_log("[IPN] Erreur CURL: " . curl_error($ch));
        } else {
            error_log("[IPN] Réponse reçue - Code: " . $httpCode . ", Corps: " . $response);
        }
        
        curl_close($ch);
        
        // Enregistrer la tentative
        logIpnAttempt($transactionData['TransId'], $notificationData, $httpCode, $response);
        
        return $httpCode === 200;
        
    } catch (Exception $e) {
        error_log("[IPN] Exception: " . $e->getMessage());
        return false;
    }
} 