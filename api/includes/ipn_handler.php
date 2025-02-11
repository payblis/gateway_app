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
        // Vérifions d'abord la structure de la table
        $checkTable = $connection->query("SHOW CREATE TABLE ipn_logs");
        error_log("[IPN] Structure de la table: " . print_r($checkTable->fetch_row(), true));
        
        // Simplifions la requête pour éviter les problèmes de bind_param
        $query = "INSERT INTO ipn_logs SET 
                 transaction_id = ?,
                 payload = ?,
                 response_code = ?,
                 response = ?,
                 status = ?";
        
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
        
        // Vérifions que nous avons le bon nombre de paramètres
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
            error_log("[IPN] Erreur execution: " . $stmt->error);
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
        // Récupérer les données de la transaction depuis ovri_logs
        $query = "SELECT request_body FROM ovri_logs WHERE transaction_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $transactionData['TransId']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("[IPN] Transaction non trouvée dans ovri_logs");
            return false;
        }
        
        $row = $result->fetch_assoc();
        $requestData = json_decode($row['request_body'], true);
        
        if (!isset($requestData['ipnURL'])) {
            error_log("[IPN] URL IPN non définie");
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
                'card_brand' => 'VISA',
                'card_last4' => substr($_POST['cardno'] ?? '', -4),
                'authorization_code' => '000000',
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
        
        error_log("[IPN] Réponse reçue - Code: " . $httpCode . ", Corps: " . $response);
        
        curl_close($ch);
        
        // Enregistrer la tentative d'IPN
        logIpnAttempt($transactionData['TransId'], $notificationData, $httpCode, $response);
        
        return $httpCode === 200;
        
    } catch (Exception $e) {
        error_log("[IPN] Exception: " . $e->getMessage());
        return false;
    }
} 