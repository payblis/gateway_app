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

    // Stocker la réponse OVRI dans ovri_logs
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
        $requestType = '3DS_RESPONSE';
        $requestBody = json_encode($_GET);
        $responseBody = json_encode($_GET);  // On stocke la réponse OVRI complète
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
        $logResult = $logStmt->execute();
        error_log("Log de la réponse OVRI: " . ($logResult ? "Succès" : "Échec"));
    }

    // Mise à jour du statut de la transaction
    $transactionStatus = ($status == '2') ? 'paid' : 'failed';
    $updateStmt = $connection->prepare("
        UPDATE transactions 
        SET status = ? 
        WHERE ref_order = ?
    ");
    
    if (!$updateStmt) {
        error_log("ERREUR MySQL (prepare update): " . $connection->error);
        throw new Exception("Erreur de préparation de la requête update");
    }
    
    $updateStmt->bind_param("ss", $transactionStatus, $merchantRef);
    $updateResult = $updateStmt->execute();
    
    if (!$updateResult) {
        error_log("ERREUR MySQL (execute update): " . $updateStmt->error);
        throw new Exception("Erreur lors de la mise à jour du statut");
    }
    
    error_log("Mise à jour transaction réussie. Affected rows: " . $updateStmt->affected_rows);

    // Récupération des URLs depuis la première requête
    $urlStmt = $connection->prepare("
        SELECT request_body 
        FROM ovri_logs 
        WHERE transaction_id = ? 
        AND request_type = 'via card'
        ORDER BY created_at ASC 
        LIMIT 1
    ");
    
    $urlStmt->bind_param("s", $transId);
    $urlStmt->execute();
    $urlResult = $urlStmt->get_result();
    $urlRow = $urlResult->fetch_assoc();

    if ($urlRow) {
        $requestData = json_decode($urlRow['request_body'], true);
        $merchantData = json_decode(urldecode($requestData['array']), true);
        
        $redirectUrl = ($transactionStatus == 'paid') ? 
            ($merchantData['urlOK'] ?? null) : 
            ($merchantData['urlKO'] ?? null);
        
        error_log("URL de redirection: " . ($redirectUrl ?? 'NULL'));

        if ($redirectUrl) {
            header("Location: " . $redirectUrl);
            exit();
        }
    }
    
    throw new Exception("URL de redirection non trouvée");
    
} catch (Exception $e) {
    error_log("ERREUR CRITIQUE dans success.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header("Location: /error.php");
    exit();
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
