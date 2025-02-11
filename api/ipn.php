<?php
error_log("=== DÉBUT IPN.PHP ===");

require('../admin/include/config.php');
require('./includes/common_functions.php');

// Fonction de logging dédiée
function logDebug($message, $data = null) {
    $logMessage = "[IPN Debug] " . date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage);
}

function logIpnAttempt($transactionId, $payload, $httpCode, $response) {
    global $connection;
    
    try {
        $query = "INSERT INTO ipn_logs (transaction_id, payload, response_code, response, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        
        $payloadJson = json_encode($payload);
        $status = ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'failed';
        
        $stmt->bind_param("ssiss", $transactionId, $payloadJson, $httpCode, $response, $status);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("[IPN] Exception lors de l'enregistrement: " . $e->getMessage());
        return false;
    }
}

// Vérifier si un IPN a déjà été envoyé avec succès pour cette transaction
function hasSuccessfulIpnBeenSent($transactionId, $merchantRef) {
    global $connection;
    
    $stmt = $connection->prepare("
        SELECT il.payload, il.status
        FROM ipn_logs il
        WHERE il.transaction_id = ?
        AND il.status = 'success'
        AND il.created_at >= NOW() - INTERVAL 5 MINUTE
        ORDER BY il.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $payload = json_decode($row['payload'], true);
        // Si on a déjà envoyé un IPN "APPROVED", on n'en renvoie pas d'autre
        if ($payload['status'] === 'APPROVED') {
            error_log("IPN précédent trouvé avec statut APPROVED");
            return true;
        }
        // Si le précédent était "DECLINED", on permet un nouvel IPN
        error_log("IPN précédent trouvé avec statut DECLINED - Permettre un nouvel essai");
        return false;
    }
    
    error_log("Aucun IPN précédent trouvé");
    return false;
}

// Capturer la réponse brute
$raw_post_data = file_get_contents('php://input');
error_log("Raw POST data reçue: " . $raw_post_data);

// Décoder la réponse JSON
$ovri_response = json_decode($raw_post_data, true);
error_log("Réponse décodée: " . print_r($ovri_response, true));

if ($ovri_response) {
    // Vérifier si c'est une notification IPN ou une réponse de transaction
    $isIpnNotification = isset($ovri_response['Status']) && !isset($ovri_response['redirectomerchant']);
    
    if ($isIpnNotification) {
        $merchantRef = $ovri_response['MerchantRef'] ?? null;
        $transId = $ovri_response['TransId'] ?? null;
        error_log("MerchantRef extrait: " . ($merchantRef ?? 'null'));
        
        if ($merchantRef) {
            // Enregistrer la réponse OVRI dans ovri_logs
            $stmt = $connection->prepare("
                INSERT INTO ovri_logs (transaction_id, response_body, status)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $transId, $raw_post_data, $ovri_response['Status']);
            $stmt->execute();

            // Mettre à jour le statut de la transaction
            $stmt = $connection->prepare("
                UPDATE transactions 
                SET status = ?, 
                    updated_at = NOW() 
                WHERE ref_order = ?
            ");
            $stmt->bind_param("ss", $ovri_response['Status'], $merchantRef);
            $stmt->execute();

            // Récupérer l'URL IPN du marchand
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
                $requestBody = json_decode($transaction['request_body'], true);
                $merchantIpnUrl = $requestBody['ipnURL'] ?? null;
                
                if ($merchantIpnUrl && !hasSuccessfulIpnBeenSent($transId, $merchantRef)) {
                    // Préparer et envoyer l'IPN au marchand
                    $ipnData = [
                        'TransId' => $transId,
                        'MerchantRef' => $merchantRef,
                        'Status' => $ovri_response['Status'],
                        'Amount' => $ovri_response['Amount'] ?? null,
                        'Currency' => $ovri_response['Currency'] ?? null,
                        'PaymentMethod' => $ovri_response['PaymentMethod'] ?? null,
                        'TransactionDate' => $ovri_response['TransactionDate'] ?? date('Y-m-d H:i:s')
                    ];
                    
                    sendIpnNotification($ipnData);
                }
            }
        }
    } else {
        // C'est une réponse de transaction, on l'enregistre simplement
        error_log("Réponse de transaction reçue - pas d'envoi d'IPN nécessaire");
    }
}

// Répondre à OVRI
http_response_code(200);
echo "OK";

error_log("=== FIN IPN.PHP ==="); 