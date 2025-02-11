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
        
        // bind_param avec seulement les champs nécessaires
        // s = string, i = integer, s = string, s = string, s = string (status)
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
        // Récupérer les données de la transaction avec une attente maximale
        $maxAttempts = 3;
        $attempt = 0;
        $logData = null;
        
        while ($attempt < $maxAttempts) {
            $query = "SELECT request_body, response_body FROM ovri_logs WHERE transaction_id = ?";
            $stmt = $connection->prepare($query);
            
            if (!$stmt) {
                error_log("[IPN] Erreur préparation requête ovri_logs: " . $connection->error);
                return false;
            }
            
            $stmt->bind_param("s", $transactionData['TransId']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $logData = $result->fetch_assoc();
                break;
            }
            
            $attempt++;
            if ($attempt < $maxAttempts) {
                error_log("[IPN] Tentative {$attempt} - Données non trouvées, nouvelle tentative dans 100ms");
                usleep(100000); // 100ms pause
            }
        }
        
        if (!$logData) {
            error_log("[IPN] Données non trouvées après {$maxAttempts} tentatives");
            // Continuer avec des données minimales
            $requestData = [
                'ipnURL' => $transactionData['ipnURL'] ?? null,
                'MerchantKey' => $transactionData['MerchantKey'] ?? null
            ];
            $responseData = [];
        } else {
            $requestData = json_decode($logData['request_body'], true);
            $responseData = json_decode($logData['response_body'], true);
        }
        
        error_log("[IPN] Données de requête: " . print_r($requestData, true));
        
        if (empty($requestData['ipnURL'])) {
            error_log("[IPN] Pas d'URL IPN définie");
            return false;
        }
        
        // Déterminer l'événement en fonction du statut
        $event = 'payment.';
        switch ($transactionData['Status']) {
            case 'Success':
                $event .= 'completed';
                break;
            case 'Failed':
                $event .= 'failed';
                break;
            case 'Declined':
                $event .= 'declined';
                break;
            case 'Error':
                $event .= 'error';
                break;
            case 'Pending3DS':
                $event .= 'pending_3ds';
                break;
            default:
                $event .= 'unknown';
        }
        
        // Préparer les données de notification
        $notificationData = [
            'event' => $event,
            'merchant_reference' => $transactionData['MerchantRef'],
            'transaction_id' => $transactionData['TransId'],
            'amount' => $transactionData['Amount'],
            'status' => $transactionData['Status'],
            'payment_details' => [
                'card_brand' => $responseData['receipt']['cardbrand'] ?? 'VISA',
                'card_last4' => substr($responseData['receipt']['cardpan'] ?? '', -4),
                'authorization_code' => $responseData['receipt']['authorization'] ?? '000000',
                'transaction_date' => date('Y-m-d H:i:s')
            ]
        ];

        // Ajouter des détails supplémentaires selon le statut
        if ($transactionData['Status'] === 'Failed' || $transactionData['Status'] === 'Error') {
            $notificationData['error'] = [
                'code' => $responseData['code'] ?? 'unknown',
                'message' => $responseData['message'] ?? 'Transaction failed'
            ];
        }

        if ($transactionData['Status'] === 'Pending3DS') {
            $notificationData['threeds'] = [
                'status' => 'pending',
                'required' => true
            ];
        }
        
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
                'X-Payblis-Event: ' . $event
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