<?php
error_log("=== DÉBUT CHECKOUT.PHP ===");
require('../admin/include/config.php');
require('./includes/ipn_handler.php');

error_log("POST data reçues: " . print_r($_POST, true));
error_log("GET data reçues: " . print_r($_GET, true));

try {
    // Décoder les données sérialisées
    if (isset($_POST['array'])) {
        $decodedData = urldecode($_POST['array']);
        $requestData = unserialize($decodedData);
        error_log("Données désérialisées: " . print_r($requestData, true));
        
        // Récupérer l'ID inséré
        $insertedId = $_POST['inserted_id'] ?? null;
        error_log("ID inséré: " . $insertedId);
        
        if ($insertedId) {
            // Générer un ID de transaction unique
            $transactionId = 'OVRI-' . date('Ymd') . time() . '-' . uniqid();
            error_log("Transaction ID généré: " . $transactionId);
            
            // Mettre à jour ovri_logs avec le transaction_id
            $updateQuery = "UPDATE ovri_logs 
                          SET transaction_id = ?,
                              response_body = ?,
                              http_code = ? 
                          WHERE id = ?";
            
            $responseData = [
                "status" => "success",
                "transaction_id" => $transactionId,
                "timestamp" => date('Y-m-d H:i:s')
            ];
            
            $responseJson = json_encode($responseData);
            $httpCode = 200;
            
            $stmt = $connection->prepare($updateQuery);
            $stmt->bind_param("ssii", $transactionId, $responseJson, $httpCode, $insertedId);
            
            if ($stmt->execute()) {
                error_log("Transaction ID mis à jour dans ovri_logs");
                
                // Envoi de l'IPN
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
                
                // Redirection simple sans paramètres
                $redirectUrl = $httpCode === 200 ? $requestData['urlOK'] : $requestData['urlKO'];
                error_log("Redirection vers: " . $redirectUrl);
                header("Location: " . $redirectUrl);
                exit;
            } else {
                error_log("Erreur lors de la mise à jour de ovri_logs: " . $stmt->error);
                throw new Exception("Erreur lors de la mise à jour");
            }
        }
    } else {
        error_log("Aucune donnée sérialisée reçue");
        throw new Exception("Données manquantes");
    }
} catch (Exception $e) {
    error_log("Erreur dans checkout.php: " . $e->getMessage());
    header("Location: failed.php");
    exit;
}

error_log("=== FIN CHECKOUT.PHP ===");
