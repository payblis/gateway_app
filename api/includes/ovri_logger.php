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
            // Convertir le status OVRI en status de transaction
            $transactionStatus = $this->convertStatus($status);
            
            // Mettre à jour la transaction existante
            $stmt = $this->connection->prepare("
                UPDATE transactions 
                SET status = ?
                WHERE ref_order = ?
            ");
            
            $stmt->bind_param("ss", $transactionStatus, $merchantRef);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("Transaction status updated successfully for ref_order: " . $merchantRef);
                
                // Logger aussi dans ovri_logs
                $logStmt = $this->connection->prepare("
                    INSERT INTO ovri_logs 
                    (transaction_id, request_body, response_code, response, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                $requestBody = json_encode($data);
                $responseCode = 200;
                $response = "Transaction status updated to: " . $transactionStatus;
                
                $logStmt->bind_param("ssis", $transactionId, $requestBody, $responseCode, $response);
                $logStmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            return false;
        }
    }
    
    private function convertStatus($ovriStatus) {
        // Convertir le status OVRI en status de transaction
        switch ($ovriStatus) {
            case '2':
            case 'APPROVED':
                return 'paid';
            case '6':
            case 'DECLINED':
                return 'failed';
            case 'REFUNDED':
                return 'refunded';
            default:
                return 'pending';
        }
    }
} 