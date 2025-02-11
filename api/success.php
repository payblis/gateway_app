<?php
error_log("=== DÉBUT SUCCESS.PHP ===");

require('./includes/ovri_logger.php');
require('../admin/include/config.php');

// Log au début du fichier
logOvriFlow('ovri_callback', [
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'No referer',
    'headers' => getallheaders(),
    'server' => $_SERVER
]);

// Récupérer le MerchantRef depuis l'URL
$merchantRef = $_GET['MerchantRef'] ?? null;

if ($merchantRef) {
    try {
        // Récupérer la transaction et les logs OVRI associés
        $stmt = $connection->prepare("
            SELECT t.*, o.request_body 
            FROM transactions t
            INNER JOIN ovri_logs o ON t.ref_order = JSON_UNQUOTE(JSON_EXTRACT(o.request_body, '$.RefOrder'))
            WHERE t.ref_order = ?
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("s", $merchantRef);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();

        if ($transaction) {
            // Extraire l'URL de succès du request_body
            $requestBody = json_decode($transaction['request_body'], true);
            $urlOK = $requestBody['urlOK'] ?? null;
            
            if ($urlOK) {
                header('Location: ' . $urlOK);
                exit;
            }
        } else {
            error_log("Transaction non trouvée pour le MerchantRef: " . $merchantRef);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la redirection: " . $e->getMessage());
    }
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
