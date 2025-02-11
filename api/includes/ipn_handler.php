<?php
require_once 'init.php';
require_once 'TransactionManager.php';

$transactionManager = new TransactionManager($db);

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
        // Vérifier d'abord si nous avons une URL IPN
        if (empty($transactionData['ipnURL'])) {
            error_log("[IPN] Pas d'URL IPN dans les données de transaction");
            return false;
        }

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
            // Utiliser les données directement depuis transactionData
            $requestData = [
                'ipnURL' => $transactionData['ipnURL'],
                'MerchantKey' => $transactionData['MerchantKey']
            ];
            $responseData = [];
        } else {
            $requestData = json_decode($logData['request_body'], true);
            $responseData = json_decode($logData['response_body'], true);
        }
        
        error_log("[IPN] Données de requête: " . print_r($requestData, true));
        
        // Préparer les données de notification
        $notificationData = [
            'event' => 'payment.' . strtolower($transactionData['Status']),
            'merchant_reference' => $transactionData['MerchantRef'],
            'transaction_id' => $transactionData['TransId'],
            'amount' => $transactionData['Amount'],
            'status' => $transactionData['Status'],
            'payment_details' => [
                'card_brand' => $responseData['receipt']['cardbrand'] ?? 'UNKNOWN',
                'card_last4' => $responseData['receipt']['cardpan'] ?? '****',
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
        $ch = curl_init($transactionData['ipnURL']); // Utiliser l'URL directement depuis transactionData
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($notificationData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Payblis-Signature: ' . hash_hmac('sha256', json_encode($notificationData), $transactionData['MerchantKey']),
                'X-Payblis-Event: payment.' . strtolower($transactionData['Status'])
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

// Récupérer le contenu brut du POST
$input = file_get_contents('php://input');
error_log("[Ovri IPN] Received raw input: " . $input);

// Décoder l'URL encoding
$decoded = urldecode($input);
error_log("[Ovri IPN] URL decoded input: " . $decoded);

// Extraire le tableau depuis le paramètre 'array='
if (strpos($decoded, 'array=') === 0) {
    // Séparer les différentes parties des données
    $parts = explode('&', $decoded);
    $serialized = substr($parts[0], 6); // Enlever 'array=' du premier élément
    $serialized = urldecode($serialized); // Décoder une seconde fois
    
    try {
        $response = unserialize($serialized);
        if ($response === false) {
            error_log("[Ovri IPN] Unserialization failed");
            http_response_code(400);
            exit;
        }
        
        // Récupérer les données additionnelles
        $additionalData = [];
        for ($i = 1; $i < count($parts); $i++) {
            $pair = explode('=', $parts[$i]);
            if (count($pair) == 2) {
                $additionalData[$pair[0]] = urldecode($pair[1]);
            }
        }
        
        error_log("[Ovri IPN] Decoded data: " . print_r($response, true));
        
        if (isset($response['RefOrder'])) {
            $refOrder = $response['RefOrder'];
            $transaction = $transactionManager->getTransaction($refOrder);
            
            if ($transaction) {
                // Mise à jour du statut si présent
                if (isset($additionalData['Status'])) {
                    $transactionManager->updateTransactionStatus($refOrder, $additionalData['Status']);
                    error_log("[Ovri IPN] Status updated for RefOrder: {$refOrder}");
                }
            } else {
                // Nouvelle transaction
                $transactionManager->createTransaction($response);
                error_log("[Ovri IPN] New transaction created for RefOrder: {$refOrder}");
            }
            
            http_response_code(200);
            echo "OK";
        } else {
            error_log("[Ovri IPN] Missing RefOrder in response");
            http_response_code(400);
        }
        
    } catch (Exception $e) {
        error_log("[Ovri IPN] Error processing payment: " . $e->getMessage());
        http_response_code(500);
    }
} else {
    error_log("[Ovri IPN] Invalid input format - missing 'array=' prefix");
    http_response_code(400);
} 