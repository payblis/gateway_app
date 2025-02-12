<?php
error_log("=== DÉBUT SUCCESS.PHP ===");
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

require('../admin/include/config.php');
error_log("✓ Config chargée");

require('./includes/ipn_handler.php');
error_log("✓ Handler IPN chargé");

// Récupérer les paramètres de l'URL
$TransId = $_GET['TransId'] ?? null;
error_log("TransId reçu: " . ($TransId ?? 'non défini'));

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
        
        // Décoder la réponse d'Ovri
        $responseData = json_decode($logData['response_body'], true);
        error_log("Réponse Ovri décodée: " . print_r($responseData, true));
        
        // Décoder les données de la requête originale
        $requestData = json_decode($logData['request_body'], true);
        error_log("Données requête originale: " . print_r($requestData, true));
        
        // Extraire les URLs et autres données nécessaires
        $urlOK = $requestData['urlOK'] ?? null;
        $urlKO = $requestData['urlKO'] ?? null;
        $ipnURL = $requestData['ipnURL'] ?? null;
        $merchantKey = $requestData['MerchantKey'] ?? null;
        $refOrder = $requestData['RefOrder'] ?? null;
        $amount = $requestData['amount'] ?? null;

        error_log("URLs extraites:");
        error_log("- URL OK: " . ($urlOK ?? 'non défini'));
        error_log("- URL KO: " . ($urlKO ?? 'non défini'));
        error_log("- IPN URL: " . ($ipnURL ?? 'non défini'));
        
        // Vérifier le statut de la transaction
        if (isset($responseData['Status']) && $responseData['Status'] === '2') {
            error_log("✓ Statut de paiement validé (Status = 2)");
            
            // Préparer les données pour l'IPN
            $ipnData = [
                'MerchantRef' => $refOrder,
                'Amount' => $amount,
                'TransId' => $TransId,
                'Status' => 'Success',
                'ipnURL' => $ipnURL,
                'MerchantKey' => $merchantKey
            ];
            
            error_log("📤 Envoi de l'IPN avec données: " . print_r($ipnData, true));
            $ipnResult = sendIpnNotification($ipnData);
            error_log("Résultat envoi IPN: " . ($ipnResult ? "✓ Succès" : "❌ Échec"));
            
            // Redirection vers urlOK
            if ($urlOK) {
                error_log("➡️ Redirection vers urlOK: " . $urlOK);
                header('Location: ' . $urlOK);
                error_log("=== FIN SUCCESS.PHP - Redirection effectuée ===");
                exit;
            } else {
                error_log("❌ Erreur: urlOK non trouvée dans request_body");
            }
        } else {
            error_log("❌ Statut de paiement invalide: " . ($responseData['Status'] ?? 'non défini'));
            
            // Redirection vers urlKO en cas de statut invalide
            if ($urlKO) {
                error_log("➡️ Redirection vers urlKO (statut invalide): " . $urlKO);
                header('Location: ' . $urlKO);
                error_log("=== FIN SUCCESS.PHP - Redirection vers KO effectuée ===");
                exit;
            } else {
                error_log("❌ Erreur: urlKO non trouvée dans request_body");
            }
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
