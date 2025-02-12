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
    // 1. Récupérer le contenu brut de la requête
    $rawInput = file_get_contents('php://input');
    logCallback("Callback 3DS reçu - Raw input", $rawInput);

    // 2. Vérifier que c'est bien du JSON
    if (!$rawData = json_decode($rawInput, true)) {
        logCallback("Erreur: Données JSON invalides");
        http_response_code(400);
        exit('Invalid JSON');
    }

    // 3. Logger les headers pour debug
    $headers = getallheaders();
    logCallback("Headers reçus", $headers);

    // 4. Logger les données décodées
    logCallback("Données décodées", $rawData);

    // 5. Enregistrer le callback dans ovri_logs
    $query = "INSERT INTO ovri_logs 
              (transaction_id, request_type, request_body, response_body, http_code, token) 
              VALUES (?, '3DS_CALLBACK', ?, ?, 200, ?)";
              
    $stmt = $connection->prepare($query);
    
    // Récupérer le transaction_id depuis les données (ajuster selon la structure exacte d'Ovri)
    $transactionId = $rawData['TransId'] ?? 'UNKNOWN';
    $headersJson = json_encode($headers);
    $token = $rawData['token'] ?? ''; // Si Ovri envoie un token dans le callback
    
    $stmt->bind_param("ssss", 
        $transactionId,
        $headersJson,    // request_body contient les headers pour debug
        $rawInput,       // response_body contient la réponse JSON d'Ovri
        $token
    );
    
    if (!$stmt->execute()) {
        logCallback("Erreur lors de l'enregistrement en BD: " . $stmt->error);
    }

    // 6. Traiter le statut
    if (isset($rawData['Status'])) {
        logCallback("Traitement du statut: " . $rawData['Status']);
        
        // Convertir le status Ovri en status de notre table
        $newStatus = 'pending'; // Statut par défaut
        if ($rawData['Status'] == '2') {
            $newStatus = 'paid';
        } elseif ($rawData['Status'] == '6') {
            $newStatus = 'pending'; // Pour 3DS
        } elseif ($rawData['Status'] == '3') {
            $newStatus = 'failed';
        }
        
        // Mettre à jour le statut de la transaction
        $updateQuery = "UPDATE transactions 
                       SET status = ?
                       WHERE ref_order = ?";
        
        $updateStmt = $connection->prepare($updateQuery);
        $updateStmt->bind_param("ss", $newStatus, $rawData['MerchantRef']);
        
        if (!$updateStmt->execute()) {
            logCallback("Erreur lors de la mise à jour du statut: " . $updateStmt->error);
        } else {
            logCallback("Statut de la transaction mis à jour: " . $newStatus . " pour ref_order: " . $rawData['MerchantRef']);
            
            // Si le paiement est confirmé (status 2 = paid), déclencher l'IPN vers le marchand
            if ($newStatus === 'paid') {
                // Récupérer les données complètes de la transaction
                $transQuery = "SELECT * FROM transactions WHERE ref_order = ?";
                $transStmt = $connection->prepare($transQuery);
                $transStmt->bind_param("s", $rawData['MerchantRef']);
                $transStmt->execute();
                $transResult = $transStmt->get_result();
                
                if ($transData = $transResult->fetch_assoc()) {
                    logCallback("Envoi de l'IPN pour transaction réussie");
                    // Ajouter les données supplémentaires d'Ovri
                    $transData['TransId'] = $rawData['TransId'];
                    $transData['CardType'] = $rawData['CardType'];
                    $transData['CardNumber'] = $rawData['CardNumber'];
                    sendIpnNotification($transData);
                }
            }
        }
    }

    // 7. Répondre à Ovri
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    logCallback("Exception: " . $e->getMessage());
    logCallback("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
} 