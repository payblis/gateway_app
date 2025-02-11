<?php
error_log("=== DÉBUT CHECKOUT.PHP ===");
require('../admin/include/config.php');
require('./includes/ipn_handler.php');

error_log("POST data reçues: " . print_r($_POST, true));
error_log("GET data reçues: " . print_r($_GET, true));

try {
    // Récupération des données de la transaction
    $transactionId = $_POST['transaction_id'] ?? null;
    error_log("Transaction ID: " . $transactionId);

    if ($transactionId) {
        $query = "SELECT * FROM ovri_logs WHERE transaction_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            error_log("Transaction trouvée dans ovri_logs");
            $transaction = $result->fetch_assoc();
            $requestData = json_decode($transaction['request_body'], true);
            
            error_log("Données de la transaction: " . print_r($requestData, true));
            
            // Mise à jour du statut
            $updateQuery = "UPDATE ovri_logs 
                          SET response_body = ?, http_code = ? 
                          WHERE transaction_id = ?";
            $stmt = $connection->prepare($updateQuery);
            
            // Simuler la réponse d'OVRI
            $responseData = [
                "status" => "success",
                "transaction_id" => $transactionId,
                "timestamp" => date('Y-m-d H:i:s')
            ];
            
            $responseJson = json_encode($responseData);
            $httpCode = 200;
            
            $stmt->bind_param("sis", $responseJson, $httpCode, $transactionId);
            $stmt->execute();
            
            error_log("Réponse enregistrée dans ovri_logs");
            
            // Envoi de l'IPN
            if ($httpCode === 200) {
                error_log("Tentative d'envoi IPN depuis checkout.php");
                $ipnData = [
                    'TransId' => $transactionId,
                    'MerchantRef' => $requestData['RefOrder'],
                    'Amount' => $requestData['amount'],
                    'Status' => 'Success'
                ];
                
                try {
                    $ipnResult = sendIpnNotification($ipnData);
                    error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
                } catch (Exception $e) {
                    error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
                }
            }
            
            // Redirection simple sans paramètres
            $redirectUrl = $httpCode === 200 ? $requestData['urlOK'] : $requestData['urlKO'];
            error_log("Redirection vers: " . $redirectUrl);
            header("Location: " . $redirectUrl);
            exit;
        } else {
            error_log("Transaction non trouvée dans ovri_logs");
            // Redirection vers la page d'erreur par défaut
            header("Location: failed.php");
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Erreur dans checkout.php: " . $e->getMessage());
    header("Location: failed.php");
    exit;
}

error_log("=== FIN CHECKOUT.PHP ===");
