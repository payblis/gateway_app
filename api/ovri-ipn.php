<?php
error_log("=== DÉBUT OVRI-IPN.PHP ===");
require('../admin/include/config.php');
require('./includes/ipn_handler.php');
require('./includes/ovri_logger.php');

// Capturer la réponse brute d'OVRI
$raw_post_data = file_get_contents('php://input');
$headers = getallheaders();

// Log la réponse d'OVRI
logOvriFlow('ovri_ipn_received', [
    'raw_data' => $raw_post_data,
    'headers' => $headers,
    'method' => $_SERVER['REQUEST_METHOD']
]);

// Décoder la réponse JSON d'OVRI
$ovri_response = json_decode($raw_post_data, true);

if ($ovri_response) {
    // Récupérer l'URL IPN du marchand depuis la base de données
    $merchantRef = $ovri_response['reforder'] ?? null;
    if ($merchantRef) {
        $stmt = $connection->prepare("SELECT user_data FROM transactions WHERE ref_order = ?");
        $stmt->bind_param("s", $merchantRef);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        if ($transaction) {
            $userData = unserialize(urldecode($transaction['user_data']));
            $merchantIpnUrl = $userData['ipnURL'] ?? null;
            
            if ($merchantIpnUrl) {
                // Préparer les données pour le marchand
                $ipnData = [
                    'TransId' => $ovri_response['TransactionId'] ?? null,
                    'MerchantRef' => $merchantRef,
                    'Amount' => $ovri_response['amount'] ?? null,
                    'Status' => $ovri_response['status'] ?? null
                ];
                
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
}

// Répondre à OVRI
http_response_code(200);
echo "OK";

error_log("=== FIN OVRI-IPN.PHP ==="); 