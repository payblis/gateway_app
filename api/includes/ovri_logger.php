<?php

class OvriLogger {
    private $connection;
    
    public function __construct($connection) {
        $this->connection = $connection;
    }
    
    public function logTransaction($data) {
        $transactionId = $data['TransId'] ?? null;
        $merchantRef = $data['MerchantRef'] ?? null;
        $status = $data['Status'] ?? null;
        $amount = $data['Amount'] ?? null;
        
        error_log("Logging transaction: " . print_r($data, true));
        
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO transaction_logs 
                (transaction_id, merchant_ref, status, amount, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("sssd", $transactionId, $merchantRef, $status, $amount);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            return false;
        }
    }
} 