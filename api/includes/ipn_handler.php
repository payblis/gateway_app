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
    
    try {
        // Préparer les données simplifiées
        $notificationData = [
            'status' => $transactionData['Status'] === 'SUCCESS' ? 'APPROVED' : 'DECLINED',
            'TransactionId' => str_replace('ovri-', '', $transactionData['TransId']),
            'reforder' => $transactionData['MerchantRef']
        ];

        error_log("[IPN] Données à envoyer: " . print_r($notificationData, true));
        
        // Envoi de la notification
        $ch = curl_init($transactionData['ipnURL']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($notificationData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
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