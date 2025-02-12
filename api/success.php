<?php
error_log("=== DÉBUT SUCCESS.PHP ===");
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

require('../admin/include/config.php');
error_log("✓ Config chargée");

require('./includes/ipn_handler.php');
error_log("✓ Handler IPN chargé");

// Récupérer les paramètres de l'URL
$TransId = $_GET['transactionId'] ?? null;
$Status = $_GET['status'] ?? null;

error_log("Paramètres extraits:");
error_log("- TransId: " . ($TransId ?? 'non défini'));
error_log("- Status: " . ($Status ?? 'non défini'));

// Récupérer les informations de la transaction depuis ovri_logs
if ($TransId) {
    error_log("🔍 Recherche de la transaction dans ovri_logs pour TransId: " . $TransId);
    $query = "SELECT * FROM ovri_logs WHERE transaction_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $TransId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        error_log("✓ Transaction trouvée dans ovri_logs");
        $logData = $result->fetch_assoc();
        error_log("Données brutes de ovri_logs: " . print_r($logData, true));
        
        $requestData = json_decode($logData['request_body'], true);
        error_log("Données request_body décodées: " . print_r($requestData, true));
        
        // Récupérer les données nécessaires
        $MerchantRef = $requestData['RefOrder'] ?? null;
        $amount = $requestData['amount'] ?? null;
        $ipnURL = $requestData['ipnURL'] ?? null;
        $urlOK = $requestData['urlOK'] ?? null;
        $merchantKey = $requestData['MerchantKey'] ?? null;
        
        error_log("Données extraites du request_body:");
        error_log("- MerchantRef: " . ($MerchantRef ?? 'non défini'));
        error_log("- Amount: " . ($amount ?? 'non défini'));
        error_log("- IPN URL: " . ($ipnURL ?? 'non défini'));
        error_log("- URL OK: " . ($urlOK ?? 'non défini'));
        error_log("- MerchantKey: " . ($merchantKey ? 'présent' : 'non défini'));
        
        if ($MerchantRef) {
            error_log("🔄 Préparation de l'envoi IPN");
            // Préparer les données pour l'IPN
            $ipnData = [
                'MerchantRef' => $MerchantRef,
                'Amount' => $amount,
                'TransId' => $TransId,
                'Status' => 'Success',
                'ipnURL' => $ipnURL,
                'MerchantKey' => $merchantKey
            ];
            error_log("Données IPN préparées: " . print_r($ipnData, true));
            
            // Envoyer l'IPN
            error_log("📤 Tentative d'envoi IPN...");
            $ipnResult = sendIpnNotification($ipnData);
            error_log("Résultat envoi IPN: " . ($ipnResult ? "✓ Succès" : "❌ Échec"));
            
            // Redirection finale
            if ($urlOK) {
                error_log("➡️ Redirection vers l'URL du marchand: " . $urlOK);
                header('Location: ' . $urlOK);
                error_log("=== FIN SUCCESS.PHP - Redirection effectuée ===");
                exit;
            } else {
                error_log("❌ Erreur: URL OK non définie, impossible de rediriger");
            }
        } else {
            error_log("❌ Erreur: MerchantRef non trouvé dans les données de la transaction");
        }
    } else {
        error_log("❌ Aucune transaction trouvée dans ovri_logs pour TransId: " . $TransId);
    }
} else {
    error_log("❌ Erreur: TransId non fourni dans les paramètres GET");
}

error_log("=== FIN SUCCESS.PHP - Sans redirection ===");

// Fermer la connexion
mysqli_close($connection);
