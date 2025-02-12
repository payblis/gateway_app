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
        // Requête correspondant exactement à la structure de la table
        $query = "INSERT INTO ipn_logs 
                 (transaction_id, payload, response_code, response, status, retry_count) 
                 VALUES (?, ?, ?, ?, ?, 0)";
        
        $stmt = $connection->prepare($query);
        if (!$stmt) {
            error_log("[IPN] Erreur préparation requête: " . $connection->error);
            return false;
        }
        
        $payloadJson = json_encode($payload);
        $status = ($httpCode == 200) ? 'success' : 'failed';
        
        error_log("[IPN] Données à insérer:");
        error_log("[IPN] - transaction_id: " . $transactionId);
        error_log("[IPN] - payload: " . $payloadJson);
        error_log("[IPN] - response_code: " . $httpCode);
        error_log("[IPN] - response: " . $response);
        error_log("[IPN] - status: " . $status);
        
        if (!$stmt->bind_param("ssiss", 
            $transactionId,
            $payloadJson,
            $httpCode,
            $response,
            $status
        )) {
            error_log("[IPN] Erreur bind_param: " . $stmt->error);
            return false;
        }
        
        if (!$stmt->execute()) {
            error_log("[IPN] Erreur execution: " . $stmt->error . 
                      "\nDernière requête: " . $query);
            return false;
        }
        
        error_log("[IPN] Log enregistré avec succès. ID: " . $connection->insert_id);
        return true;
        
    } catch (Exception $e) {
        error_log("[IPN] Exception lors de l'enregistrement: " . $e->getMessage());
        error_log("[IPN] Trace: " . $e->getTraceAsString());
        return false;
    }
}

function sendIpnNotification($transactionData) {
    global $connection;
    
    error_log("[IPN] Début sendIpnNotification");
    error_log("[IPN] Données reçues: " . print_r($transactionData, true));
    
    try {
        // Récupérer la réponse Ovri depuis ovri_logs
        $query = "SELECT response_body FROM ovri_logs WHERE transaction_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $connection->prepare($query);
        
        if (!$stmt) {
            error_log("[IPN] Erreur préparation requête ovri_logs: " . $connection->error);
            return false;
        }
        
        $stmt->bind_param("s", $transactionData['TransId']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows === 0) {
            error_log("[IPN] Aucune donnée trouvée dans ovri_logs pour " . $transactionData['MerchantRef']);
            return false;
        }

        $logData = $result->fetch_assoc();
        $responseData = json_decode($logData['response_body'], true);
        
        error_log("[IPN] Données Ovri récupérées: " . print_r($responseData, true));

        // Préparer les données de notification
        $notificationData = [
            'event' => 'payment.success',
            'merchant_reference' => $transactionData['MerchantRef'],
            'transaction_id' => $responseData['TransactionId'] ?? $transactionData['TransId'],
            'amount' => $transactionData['Amount'],
            'status' => 'SUCCESS',
            'payment_details' => [
                'card_brand' => $responseData['receipt']['cardbrand'] ?? 'UNKNOWN',
                'card_last4' => $responseData['receipt']['cardpan'] ?? '****',
                'authorization_code' => $responseData['receipt']['authorization'] ?? '000000',
                'transaction_date' => $responseData['receipt']['date'] . ' ' . $responseData['receipt']['time'],
                'threeds' => [
                    'status' => $responseData['threesecure']['status'] ?? 'N',
                    'warranty' => $responseData['threesecure']['warranty_success'] ?? false,
                    'warranty_details' => $responseData['threesecure']['warranty_details'] ?? null
                ]
            ]
        ];

        // Ajouter des informations supplémentaires si disponibles
        if (isset($responseData['receipt']['arn'])) {
            $notificationData['payment_details']['arn'] = $responseData['receipt']['arn'];
        }
        if (isset($responseData['receipt']['archive'])) {
            $notificationData['payment_details']['archive'] = $responseData['receipt']['archive'];
        }

        error_log("[IPN] Données à envoyer: " . print_r($notificationData, true));
        
        // Envoyer la notification
        $ch = curl_init($transactionData['ipnURL']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($notificationData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Payblis-Signature: ' . hash_hmac('sha256', json_encode($notificationData), $transactionData['MerchantKey']),
                'X-Payblis-Event: payment.success'
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
        
        // Enregistrer la tentative avec le TransId d'Ovri
        logIpnAttempt($transactionData['TransId'], $notificationData, $httpCode, $response);
        
        return $httpCode === 200;
        
    } catch (Exception $e) {
        error_log("[IPN] Exception: " . $e->getMessage());
        return false;
    }
} 