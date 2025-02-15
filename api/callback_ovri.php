<?php
require_once '/var/www/vhosts/payblis.com/pay.payblis.com/admin/include/config.php';  // Chemin absolu complet
require_once 'includes/ipn_handler.php';  // Pour utiliser sendIpnNotification si nécessaire

// Fonction de logging dédiée pour les callbacks
function logCallback($message, $data = null) {
    $logMessage = "[OVRI Callback] " . date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage);
}

try {
    // Récupérer les headers
    $headers = getallheaders();
    logCallback("Headers reçus", $headers);

    // Vérifier le Content-Type
    if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'multipart/form-data') !== false) {
        // Utiliser $_POST pour les données multipart/form-data
        $rawData = $_POST;
        logCallback("Données POST reçues", $rawData);
        
        // Si $_POST est vide, essayer de lire les données brutes
        if (empty($rawData)) {
            $rawInput = file_get_contents('php://input');
            logCallback("Données brutes reçues", $rawInput);
            
            // Extraire les données du format multipart
            $boundary = substr($headers['Content-Type'], strpos($headers['Content-Type'], 'boundary=') + 9);
            $parts = array_slice(explode('--' . $boundary, $rawInput), 1, -1);
            $rawData = [];
            
            foreach ($parts as $part) {
                // Extraire le nom du champ et sa valeur
                if (preg_match('/name="([^"]+)"\s*\r\n\r\n([^\r\n]+)/i', $part, $matches)) {
                    $rawData[$matches[1]] = $matches[2];
                }
            }
            logCallback("Données extraites du multipart", $rawData);
        }
    } else {
        // Pour les autres types de contenu, lire les données brutes
        $rawInput = file_get_contents('php://input');
        logCallback("Données brutes reçues", $rawInput);
        
        $rawData = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Impossible de décoder les données JSON: " . json_last_error_msg());
        }
    }

    if (empty($rawData)) {
        throw new Exception("Aucune donnée reçue après traitement");
    }

    // Traiter les données
    logCallback("Données finales à traiter", $rawData);
    
    if (!empty($rawData)) {
        // Mettre à jour ovri_logs avec les données du callback
        $query = "UPDATE ovri_logs 
                 SET response_body = ?,
                     http_code = ?,
                     transaction_id = ?
                 WHERE response_body LIKE ? 
                 OR transaction_id = ?";

        $callbackResponse = json_encode($rawData);
        $httpCode = 200;
        $transId = $rawData['TransId'];
        $searchPattern = '%"transactionId":"' . $transId . '"%';

        $stmt = $connection->prepare($query);
        if (!$stmt) {
            throw new Exception("Erreur préparation requête: " . $connection->error);
        }

        $stmt->bind_param("sisss", 
            $callbackResponse,
            $httpCode,
            $transId,
            $searchPattern,
            $transId
        );

        if (!$stmt->execute()) {
            throw new Exception("Erreur mise à jour ovri_logs: " . $stmt->error);
        }

        $affectedRows = $stmt->affected_rows;
        logCallback("ovri_logs mis à jour avec les données du callback pour TransId: " . $transId . " (Lignes affectées: " . $affectedRows . ")");

        if ($affectedRows === 0) {
            // Si aucune ligne n'est mise à jour, on vérifie si la transaction existe
            $checkQuery = "SELECT id FROM ovri_logs WHERE response_body LIKE ? OR transaction_id = ?";
            $checkStmt = $connection->prepare($checkQuery);
            $checkStmt->bind_param("ss", $searchPattern, $transId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                throw new Exception("Aucune transaction trouvée pour TransId: " . $transId);
            }
        }

        // Mettre à jour le statut de la transaction
        if (isset($rawData['Status'])) {
            $newStatus = ($rawData['Status'] == '2') ? 'paid' : 'failed';
            $updateQuery = "UPDATE transactions SET status = ? WHERE ref_order = ?";
            $updateStmt = $connection->prepare($updateQuery);
            $updateStmt->bind_param("ss", $newStatus, $rawData['MerchantRef']);
            
            if (!$updateStmt->execute()) {
                logCallback("Erreur lors de la mise à jour du statut: " . $updateStmt->error);
            } else {
                logCallback("Statut de la transaction mis à jour: " . $newStatus . " pour ref_order: " . $rawData['MerchantRef']);
            }
        }
    }

    // Répondre avec succès
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    logCallback("Exception: " . $e->getMessage());
    logCallback("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 