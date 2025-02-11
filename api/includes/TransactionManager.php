<?php
class TransactionManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createTransaction($data) {
        try {
            $sql = "INSERT INTO transactions (
                name,
                email,
                ref_order,
                first_name,
                amount,
                country,
                status,
                token
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['Customer_Name'],
                $data['Customer_Email'],
                $data['RefOrder'],
                $data['Customer_FirstName'],
                $data['amount'],
                $data['country'],
                'pending', // Status initial
                $data['MerchantKey'] // Utilisation du MerchantKey comme token
            ]);

            error_log("[Transaction] Created transaction for RefOrder: " . $data['RefOrder']);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("[Transaction] Error creating transaction: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateTransactionStatus($refOrder, $status) {
        try {
            // Conversion du statut Ovri vers le format de la base de données
            $dbStatus = $this->convertStatus($status);

            $sql = "UPDATE transactions SET status = ? WHERE ref_order = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dbStatus, $refOrder]);

            error_log("[Transaction] Updated status for RefOrder {$refOrder} to {$dbStatus}");
            return true;
        } catch (Exception $e) {
            error_log("[Transaction] Error updating transaction: " . $e->getMessage());
            throw $e;
        }
    }

    public function getTransaction($refOrder) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM transactions WHERE ref_order = ?");
            $stmt->execute([$refOrder]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[Transaction] Error fetching transaction: " . $e->getMessage());
            throw $e;
        }
    }

    private function convertStatus($ovriStatus) {
        // Conversion des statuts Ovri vers les statuts de la base de données
        switch ($ovriStatus) {
            case 'Success':
                return 'paid';
            case 'Failed':
                return 'failed';
            case 'Pending3DS':
                return 'pending';
            case 'Refunded':
                return 'refunded';
            default:
                return 'pending';
        }
    }
} 