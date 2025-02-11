<?php

function sendIpnNotification($transactionData) {
    if (empty($transactionData['ipnURL'])) {
        error_log("Pas d'URL IPN dans les données de transaction");
        return false;
    }

    // Reformater l'ID de transaction
    $originalTransId = $transactionData['TransId'] ?? '';
    $formattedTransId = preg_replace('/^OVRI-(\d+).*$/', '$1', $originalTransId);
    
    // Préparer les données pour le marchand
    $ipnData = [
        'code' => 'success',
        'status' => $transactionData['Status'] === '2' ? 'APPROVED' : 'DECLINED',
        'TransactionId' => $formattedTransId,
        'RefOrder' => $transactionData['MerchantRef']
    ];
    
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
        
        return $httpCode >= 200 && $httpCode < 300;
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
        return false;
    }
} 