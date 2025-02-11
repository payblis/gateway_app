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
    // Récupérer la référence de commande depuis la réponse OVRI
    $merchantRef = $ovri_response['MerchantRef'] ?? null;
    error_log("MerchantRef extrait: " . ($merchantRef ?? 'null'));
    
    if ($merchantRef) {
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
        
        error_log("Transaction trouvée: " . print_r($transaction ?? 'null', true));
        
        if ($transaction) {
            // Extraire l'URL IPN du request_body
            $requestBody = json_decode($transaction['request_body'], true);
            $merchantIpnUrl = $requestBody['ipnURL'] ?? null;
            
            error_log("URL IPN du marchand: " . ($merchantIpnUrl ?? 'null'));
            
            // Mettre à jour le statut de la transaction si nécessaire
            if ($ovri_response['Status'] === '2') {
                $updateStmt = $connection->prepare("UPDATE transactions SET status = 'paid' WHERE ref_order = ?");
                $updateStmt->bind_param("s", $merchantRef);
                $updateResult = $updateStmt->execute();
                error_log("Mise à jour du statut transaction: " . ($updateResult ? "Succès" : "Échec"));
            }
            
            if ($merchantIpnUrl) {
                // Préparer les données pour le marchand
                $ipnData = [
                    'TransId' => $ovri_response['TransId'] ?? null,
                    'MerchantRef' => $merchantRef,
                    'Amount' => $ovri_response['Amount'] ?? null,
                    'Status' => $ovri_response['Status'] ?? null,
                    'CardType' => $ovri_response['CardType'] ?? null,
                    'CardNumber' => $ovri_response['CardNumber'] ?? null,
                    'BankTrxID' => $ovri_response['BankTrxID'] ?? null
                ];
                
                error_log("Données à envoyer au marchand: " . print_r($ipnData, true));
                
                // Envoyer à l'URL IPN du marchand
                try {
                    $ch = curl_init($merchantIpnUrl);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ipnData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    error_log("Réponse IPN (HTTP $httpCode): " . $response);
                    $ipnResult = ($httpCode >= 200 && $httpCode < 300);
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