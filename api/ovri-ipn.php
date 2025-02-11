<?php
error_log("=== DÉBUT OVRI-IPN.PHP ===");
require('../admin/include/config.php');
require('./includes/ipn_handler.php');
require('./includes/ovri_logger.php');

// Capturer la réponse brute d'OVRI
$raw_post_data = file_get_contents('php://input');
error_log("Raw POST data reçue: " . $raw_post_data);

// Capturer les headers
$headers = getallheaders();
error_log("Headers reçus: " . print_r($headers, true));

// Log la méthode de requête
error_log("Méthode de requête: " . $_SERVER['REQUEST_METHOD']);

// Log toutes les variables globales
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));
error_log("REQUEST params: " . print_r($_REQUEST, true));

// Vérifier le Content-Type
$contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? 'none';
error_log("Content-Type: " . $contentType);

// Log l'URL complète
error_log("URL complète: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'No referer'));

// Décoder la réponse JSON d'OVRI
$ovri_response = json_decode($raw_post_data, true);
error_log("Réponse OVRI décodée: " . print_r($ovri_response, true));

if ($ovri_response) {
    // Récupérer l'URL IPN du marchand depuis la base de données
    $merchantRef = $ovri_response['reforder'] ?? null;
    error_log("MerchantRef extrait: " . ($merchantRef ?? 'null'));
    
    if ($merchantRef) {
        $stmt = $connection->prepare("SELECT user_data FROM transactions WHERE ref_order = ?");
        $stmt->bind_param("s", $merchantRef);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        error_log("Transaction trouvée: " . print_r($transaction ?? 'null', true));
        
        if ($transaction) {
            $userData = unserialize(urldecode($transaction['user_data']));
            $merchantIpnUrl = $userData['ipnURL'] ?? null;
            
            error_log("URL IPN du marchand: " . ($merchantIpnUrl ?? 'null'));
            
            if ($merchantIpnUrl) {
                // Préparer les données pour le marchand
                $ipnData = [
                    'TransId' => $ovri_response['TransactionId'] ?? null,
                    'MerchantRef' => $merchantRef,
                    'Amount' => $ovri_response['amount'] ?? null,
                    'Status' => $ovri_response['status'] ?? null
                ];
                
                error_log("Données à envoyer au marchand: " . print_r($ipnData, true));
                
                // Envoyer à l'URL IPN du marchand
                try {
                    $ipnResult = sendIpnNotification($ipnData);
                    error_log("Résultat envoi IPN au marchand: " . ($ipnResult ? "Succès" : "Échec"));
                } catch (Exception $e) {
                    error_log("Erreur lors de l'envoi IPN au marchand: " . $e->getMessage());
                }
            }
        }
    }
} else {
    error_log("Erreur de décodage JSON: " . json_last_error_msg());
}

// Répondre à OVRI
http_response_code(200);
echo "OK";

error_log("=== FIN OVRI-IPN.PHP ==="); 