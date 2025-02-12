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
    $headers = getallheaders();
    
    logCallback("Callback 3DS reçu - Raw input", $rawInput);
    logCallback("Headers reçus", $headers);

    // 2. Décoder les données JSON
    $rawData = json_decode($rawInput, true);
    if (!$rawData) {
        throw new Exception("Impossible de décoder les données JSON");
    }
    logCallback("Données décodées", $rawData);

    // 3. Traiter le statut
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
        
        // 4. Mettre à jour le statut de la transaction
        $updateQuery = "UPDATE transactions 
                       SET status = ?
                       WHERE ref_order = ?";
        
        $updateStmt = $connection->prepare($updateQuery);
        $updateStmt->bind_param("ss", $newStatus, $rawData['MerchantRef']);
        
        if (!$updateStmt->execute()) {
            logCallback("Erreur lors de la mise à jour du statut: " . $updateStmt->error);
        } else {
            logCallback("Statut de la transaction mis à jour: " . $newStatus . " pour ref_order: " . $rawData['MerchantRef']);
        }
    }

    // 5. Stocker le callback dans ovri_logs
    $query = "INSERT INTO ovri_logs 
              (transaction_id, request_type, request_body, response_body, http_code, token) 
              VALUES (?, '3DS_CALLBACK', ?, ?, 200, ?)";
              
    $stmt = $connection->prepare($query);
    $headersJson = json_encode($headers);
    $token = $rawData['token'] ?? '';
    
    $stmt->bind_param("ssss", 
        $rawData['MerchantRef'],
        $headersJson,
        $rawInput,
        $token
    );
    
    if (!$stmt->execute()) {
        logCallback("Erreur lors de l'enregistrement du callback: " . $stmt->error);
    }

    // 6. Répondre à Ovri
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    logCallback("Exception: " . $e->getMessage());
    logCallback("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
} 