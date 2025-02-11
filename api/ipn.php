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
    $merchantRef = $ovri_response['MerchantRef'] ?? null;
    $transId = $ovri_response['TransId'] ?? null;
    
    // Vérifier si cette transaction a déjà été traitée
    $stmt = $connection->prepare("
        SELECT COUNT(*) as count 
        FROM ovri_logs 
        WHERE transaction_id = ? 
        AND created_at >= NOW() - INTERVAL 1 MINUTE
    ");
    $stmt->bind_param("s", $transId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        error_log("Transaction déjà traitée dans la dernière minute: " . $transId);
        http_response_code(200);
        echo "OK - Already processed";
        exit;
    }
    
    error_log("MerchantRef extrait: " . ($merchantRef ?? 'null'));
    
    if ($merchantRef) {
        // Récupérer la transaction et les logs OVRI associés
        $stmt = $connection->prepare("
            SELECT t.*, o.request_body, o.id as ovri_log_id 
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
            // Extraire l'URL IPN du request_body
            $requestBody = json_decode($transaction['request_body'], true);
            $merchantIpnUrl = $requestBody['ipnURL'] ?? null;
            
            // Mettre à jour le statut de la transaction si nécessaire
            if ($ovri_response['Status'] === '2') {
                $updateStmt = $connection->prepare("UPDATE transactions SET status = 'paid' WHERE ref_order = ?");
                $updateStmt->bind_param("s", $merchantRef);
                $updateResult = $updateStmt->execute();
                error_log("Mise à jour du statut transaction: " . ($updateResult ? "Succès" : "Échec"));
                
                // Mettre à jour ovri_logs avec la réponse IPN
                $updateOvriLogStmt = $connection->prepare("
                    UPDATE ovri_logs 
                    SET response_body = ?, 
                        http_code = 200 
                    WHERE id = ?
                ");
                $ovriResponse = json_encode($ovri_response);
                $ovriLogId = $transaction['ovri_log_id'];
                $updateOvriLogStmt->bind_param("si", $ovriResponse, $ovriLogId);
                $updateOvriLogStmt->execute();
            }
            
            if ($merchantIpnUrl) {
                // Reformater l'ID de transaction
                $originalTransId = $ovri_response['TransId'] ?? '';
                $formattedTransId = preg_replace('/^OVRI-(\d+).*$/', '$1', $originalTransId);
                
                // Préparer les données pour le marchand
                $ipnData = [
                    'code' => 'success',
                    'status' => $ovri_response['Status'] === '2' ? 'APPROVED' : 'DECLINED',
                    'TransactionId' => $formattedTransId,
                    'RefOrder' => $merchantRef
                ];
                
                error_log("Données à envoyer au marchand: " . print_r($ipnData, true));
                
                // Envoyer à l'URL IPN du marchand
                try {
                    $ch = curl_init($merchantIpnUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($ipnData),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    error_log("Réponse IPN (HTTP $httpCode): " . $response);
                    
                    // Enregistrer la tentative
                    logIpnAttempt($ovri_response['TransId'], $ipnData, $httpCode, $response);
                    
                } catch (Exception $e) {
                    error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
                    logIpnAttempt($ovri_response['TransId'], $ipnData, 500, $e->getMessage());
                }
            }
        }
    }
}

// Répondre à OVRI
http_response_code(200);
echo "OK";

error_log("=== FIN IPN.PHP ==="); 