<?php
error_log("=== DÉBUT FAILED.PHP ===");
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
        
        // Préparer les données pour l'IPN
        $ipnData = [
            'MerchantRef' => $refOrder,
            'Amount' => $amount,
            'TransId' => $TransId,
            'Status' => 'Failed',
            'ipnURL' => $ipnURL,
            'MerchantKey' => $merchantKey
        ];
        
        error_log("📤 Envoi de l'IPN avec données: " . print_r($ipnData, true));
        $ipnResult = sendIpnNotification($ipnData);
        error_log("Résultat envoi IPN: " . ($ipnResult ? "✓ Succès" : "❌ Échec"));
        
        // Redirection vers urlKO
        if ($urlKO) {
            error_log("➡️ Redirection vers urlKO: " . $urlKO);
            header('Location: ' . $urlKO);
            error_log("=== FIN FAILED.PHP - Redirection effectuée ===");
            exit;
        } else {
            error_log("❌ Erreur: urlKO non trouvée dans request_body");
        }
    } else {
        error_log("❌ Aucune transaction trouvée dans ovri_logs pour TransId: " . $TransId);
    }
} else {
    error_log("❌ Erreur: TransId non fourni dans les paramètres GET");
}

error_log("=== FIN FAILED.PHP - Sans redirection ===");

// Si on arrive ici, afficher une page d'erreur par défaut
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
</head>
<body>
    <h1>Payment Failed</h1>
    <p>An error occurred during the payment process.</p>
</body>
</html> 