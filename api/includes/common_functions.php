<?php

function logOvriFlow($type, $data) {
    error_log("Flow type: " . $type . ", Data: " . print_r($data, true));
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