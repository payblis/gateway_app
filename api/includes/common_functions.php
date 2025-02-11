<?php

function logOvriFlow($type, $data) {
    error_log("Flow type: " . $type . ", Data: " . print_r($data, true));
}

function logIpnAttempt($transactionId, $payload, $httpCode, $message) {
    global $connection;
    
    $stmt = $connection->prepare("
        INSERT INTO ipn_logs 
        (transaction_id, payload, http_code, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $payloadJson = json_encode($payload);
    $stmt->bind_param("ssis", $transactionId, $payloadJson, $httpCode, $message);
    
    try {
        $stmt->execute();
        error_log("IPN logged successfully for transaction: " . $transactionId);
        return true;
    } catch (Exception $e) {
        error_log("Error logging IPN: " . $e->getMessage());
        return false;
    }
}

function sendIpnNotification($transactionData) {
    if (empty($transactionData['ipnURL'])) {
        error_log("Pas d'URL IPN dans les données de transaction");
        return false;
    }

    // Reformater l'ID de transaction
    $originalTransId = $transactionData['TransId'] ?? '';
    $formattedTransId = preg_replace('/^OVRI-(\d+).*$/', '$1', $originalTransId);
    
    // Déterminer le statut en fonction du type de réponse
    $status = 'DECLINED';
    if (isset($transactionData['Status']) && $transactionData['Status'] === '2') {
        // Cas 3DS
        $status = 'APPROVED';
    } elseif (isset($transactionData['status']) && $transactionData['status'] === 'APPROVED') {
        // Cas non-3DS
        $status = 'APPROVED';
    }
    
    // Préparer les données pour le marchand
    $ipnData = [
        'code' => 'success',
        'status' => $status,
        'TransactionId' => $formattedTransId,
        'RefOrder' => $transactionData['MerchantRef']
    ];
    
    error_log("Données IPN à envoyer: " . print_r($ipnData, true));
    
    try {
        $ch = curl_init($transactionData['ipnURL']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($ipnData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("Réponse IPN reçue - Code: $httpCode, Corps: $response");
        return $httpCode >= 200 && $httpCode < 300;
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
        return false;
    }
}

function logIpnAttemptToDb($transactionId, $payload, $responseCode, $response) {
    global $connection;
    
    $stmt = $connection->prepare("
        INSERT INTO ipn_logs 
        (transaction_id, payload, response_code, response, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $payloadJson = json_encode($payload);
    $status = ($responseCode >= 200 && $responseCode < 300) ? 'success' : 'failed';
    
    $stmt->bind_param("ssis", $transactionId, $payloadJson, $responseCode, $response);
    
    try {
        $stmt->execute();
        
        // Mise à jour du statut basé sur le code de réponse
        if ($stmt->affected_rows > 0) {
            $insertId = $stmt->insert_id;
            $updateStmt = $connection->prepare("
                UPDATE ipn_logs 
                SET status = ? 
                WHERE id = ?
            ");
            $updateStmt->bind_param("si", $status, $insertId);
            $updateStmt->execute();
        }
        
        error_log("IPN logged successfully for transaction: " . $transactionId);
        return true;
    } catch (Exception $e) {
        error_log("Error logging IPN: " . $e->getMessage());
        return false;
    }
} 