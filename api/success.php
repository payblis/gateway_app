<?php
error_log("=== DÉBUT SUCCESS.PHP ===");
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

require('../admin/include/config.php');
error_log("Config chargée");

require('./includes/ipn_handler.php');
error_log("Handler IPN chargé");

// Vérifier tous les paramètres reçus
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));

// Récupérer les paramètres de l'URL
$TransId = $_GET['transactionId'] ?? null; // Changé pour matcher le paramètre réel
$Status = $_GET['status'] ?? null;         // Changé pour matcher le paramètre réel

error_log("TransId: " . $TransId);
error_log("Status: " . $Status);

// Récupérer les informations de la transaction depuis ovri_logs
if ($TransId) {
    $query = "SELECT * FROM ovri_logs WHERE transaction_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $TransId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        error_log("Transaction trouvée dans ovri_logs");
        $logData = $result->fetch_assoc();
        $requestData = json_decode($logData['request_body'], true);
        
        // Récupérer MerchantRef et Amount depuis les données stockées
        $MerchantRef = $requestData['RefOrder'] ?? null;
        $amount = $requestData['amount'] ?? null;
        
        error_log("MerchantRef: " . $MerchantRef);
        error_log("Amount: " . $amount);
        
        if ($MerchantRef) {
            // Mettre à jour le statut dans transactions
            $updateQuery = "UPDATE transactions SET status = 'paid' WHERE ref_order = ?";
            $updateStmt = $connection->prepare($updateQuery);
            $updateStmt->bind_param("s", $MerchantRef);
            $updateStmt->execute();
            error_log("Statut mis à jour dans transactions");
            
            // Préparer les données pour l'IPN
            $transactionData = [
                'MerchantRef' => $MerchantRef,
                'Amount' => $amount,
                'TransId' => $TransId,
                'Status' => $Status
            ];
            
            error_log("Données IPN préparées: " . print_r($transactionData, true));
            
            // Appeler sendIpnNotification
            try {
                error_log("Tentative d'envoi IPN");
                $ipnResult = sendIpnNotification($transactionData);
                error_log("Résultat IPN: " . ($ipnResult ? "Succès" : "Échec"));
            } catch (Exception $e) {
                error_log("Erreur IPN: " . $e->getMessage());
            }
            
            // Redirection
            if (isset($requestData['urlOK'])) {
                $redirectUrl = $requestData['urlOK'] . 
                             '?MerchantRef=' . urlencode($MerchantRef) . 
                             '&Amount=' . urlencode($amount) . 
                             '&TransId=' . urlencode($TransId) . 
                             '&Status=Success';
                
                error_log("Redirection vers: " . $redirectUrl);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    } else {
        error_log("Aucune transaction trouvée pour TransId: " . $TransId);
    }
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
