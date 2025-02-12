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
        
        // Récupérer les données nécessaires
        $MerchantRef = $requestData['RefOrder'] ?? null;
        $amount = $requestData['amount'] ?? null;
        $ipnURL = $requestData['ipnURL'] ?? null;
        $urlOK = $requestData['urlOK'] ?? null;
        $merchantKey = $requestData['MerchantKey'] ?? null;
        
        error_log("MerchantRef: " . $MerchantRef);
        error_log("Amount: " . $amount);
        error_log("IPN URL: " . $ipnURL);
        error_log("URL OK: " . $urlOK);
        
        if ($MerchantRef) {
            // Préparer les données pour l'IPN
            $ipnData = [
                'MerchantRef' => $MerchantRef,
                'Amount' => $amount,
                'TransId' => $TransId,
                'Status' => 'Success',
                'ipnURL' => $ipnURL,
                'MerchantKey' => $merchantKey
            ];
            
            // Envoyer l'IPN en utilisant la fonction existante
            $ipnResult = sendIpnNotification($ipnData);
            error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
            
            // Redirection finale vers l'URL du marchand
            if ($urlOK) {
                error_log("Redirection vers l'URL du marchand: " . $urlOK);
                header('Location: ' . $urlOK);
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
