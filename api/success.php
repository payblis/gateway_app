<?php
error_log("=== DÉBUT SUCCESS.PHP ===");
error_log("GET params reçus: " . print_r($_GET, true));
error_log("POST params reçus: " . print_r($_POST, true));

require_once('../admin/include/config.php');
require_once('./includes/common_functions.php');

try {
    // Récupérer les paramètres
    $transId = $_GET['TransId'] ?? null;
    $merchantRef = $_GET['MerchantRef'] ?? null;
    $status = $_GET['Status'] ?? null;
    $amount = $_GET['Amount'] ?? null;

    error_log("Paramètres extraits:");
    error_log("TransId: " . ($transId ?? 'NULL'));
    error_log("MerchantRef: " . ($merchantRef ?? 'NULL'));
    error_log("Status: " . ($status ?? 'NULL'));
    error_log("Amount: " . ($amount ?? 'NULL'));

    if (!$merchantRef || !$status || !$transId) {
        error_log("ERREUR: Paramètres manquants");
        throw new Exception("Paramètres manquants");
    }

    // Vérifier d'abord le statut dans ovri_logs
    error_log("Vérification du statut dans ovri_logs pour TransId: $transId");
    $checkStatusStmt = $connection->prepare("
        SELECT response_body 
        FROM ovri_logs 
        WHERE transaction_id = ? 
        AND request_type = 'ipn'
        ORDER BY created_at DESC 
        LIMIT 1
    ");

    if (!$checkStatusStmt) {
        error_log("ERREUR MySQL (prepare check status): " . $connection->error);
        throw new Exception("Erreur de préparation de la requête de vérification");
    }

    $checkStatusStmt->bind_param("s", $transId);
    $checkStatusStmt->execute();
    $statusResult = $checkStatusStmt->get_result();
    $statusRow = $statusResult->fetch_assoc();

    error_log("Résultat de la vérification du statut: " . print_r($statusRow, true));

    // Déterminer le statut final
    $ipnData = $statusRow ? json_decode($statusRow['response_body'], true) : null;
    $transactionStatus = ($ipnData && $ipnData['status'] === 'APPROVED') ? 'paid' : 'failed';
    error_log("Status final déterminé: $transactionStatus");

    // Mise à jour du statut de la transaction
    $stmt = $connection->prepare("
        UPDATE transactions 
        SET status = ? 
        WHERE ref_order = ?
    ");
    
    if (!$stmt) {
        error_log("ERREUR MySQL (prepare update): " . $connection->error);
        throw new Exception("Erreur de préparation de la requête");
    }
    
    $stmt->bind_param("ss", $transactionStatus, $merchantRef);
    $updateResult = $stmt->execute();
    
    if (!$updateResult) {
        error_log("ERREUR MySQL (execute update): " . $stmt->error);
        throw new Exception("Erreur lors de la mise à jour du statut");
    }
    
    error_log("Mise à jour transaction réussie. Affected rows: " . $stmt->affected_rows);

    // Récupération des URLs depuis ovri_logs
    error_log("Récupération des URLs depuis ovri_logs");
    $urlStmt = $connection->prepare("
        SELECT request_body 
        FROM ovri_logs 
        WHERE transaction_id = ? 
        AND request_type = 'via card'
        ORDER BY created_at ASC 
        LIMIT 1
    ");
    
    if (!$urlStmt) {
        error_log("ERREUR MySQL (prepare select URLs): " . $connection->error);
        throw new Exception("Erreur de préparation de la requête URLs");
    }
    
    $urlStmt->bind_param("s", $transId);
    $urlStmt->execute();
    $result = $urlStmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        error_log("Données request_body trouvées: " . $row['request_body']);
        $requestData = json_decode($row['request_body'], true);
        
        // Décoder les données du marchand
        $merchantData = json_decode(urldecode($requestData['array']), true);
        error_log("Données merchant décodées: " . print_r($merchantData, true));
        
        $redirectUrl = ($transactionStatus == 'paid') ? 
            ($merchantData['urlOK'] ?? null) : 
            ($merchantData['urlKO'] ?? null);
        
        error_log("URL de redirection déterminée: " . ($redirectUrl ?? 'NULL'));
        
        // Log de la réponse dans ovri_logs
        $logStmt = $connection->prepare("
            INSERT INTO ovri_logs (
                transaction_id, 
                request_type,
                request_body,
                response_body,
                http_code,
                token
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($logStmt) {
            $requestType = 'callback';
            $requestBody = json_encode($_GET);
            $responseBody = json_encode(['status' => $transactionStatus, 'redirect' => $redirectUrl]);
            $httpCode = 200;
            $token = $merchantRef;

            $logStmt->bind_param("ssssss", 
                $transId,
                $requestType,
                $requestBody,
                $responseBody,
                $httpCode,
                $token
            );
            $logStmt->execute();
            error_log("Log de la réponse enregistré dans ovri_logs");
        } else {
            error_log("ATTENTION: Impossible de logger la réponse: " . $connection->error);
        }

        if ($redirectUrl) {
            error_log("Redirection vers: $redirectUrl");
            header("Location: " . $redirectUrl);
            exit();
        }
    } else {
        error_log("ERREUR: Données URLs non trouvées pour TransId: $transId");
    }
    
    error_log("ERREUR: Aucune URL de redirection trouvée");
    throw new Exception("URL de redirection non trouvée");
    
} catch (Exception $e) {
    error_log("ERREUR CRITIQUE dans success.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("Redirection vers page d'erreur");
    
    header("Location: /error.php");
    exit();
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
