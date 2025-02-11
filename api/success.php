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

    if (!$merchantRef || !$status) {
        error_log("ERREUR: Paramètres manquants");
        throw new Exception("Paramètres manquants");
    }

    error_log("Traitement transaction: TransId=$transId, MerchantRef=$merchantRef, Status=$status");

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
    
    // Status 2 = APPROVED, 6 = DECLINED
    $transactionStatus = ($status == '2') ? 'paid' : 'failed';
    error_log("Status déterminé: $transactionStatus (basé sur status=$status)");
    
    $stmt->bind_param("ss", $transactionStatus, $merchantRef);
    $updateResult = $stmt->execute();
    
    if (!$updateResult) {
        error_log("ERREUR MySQL (execute update): " . $stmt->error);
        throw new Exception("Erreur lors de la mise à jour du statut");
    }
    
    error_log("Mise à jour transaction réussie. Affected rows: " . $stmt->affected_rows);

    // Récupération de la transaction
    $urlStmt = $connection->prepare("
        SELECT token, merchant_key 
        FROM transactions 
        WHERE ref_order = ?
    ");
    
    if (!$urlStmt) {
        error_log("ERREUR MySQL (prepare select): " . $connection->error);
        throw new Exception("Erreur de préparation de la requête de sélection");
    }
    
    $urlStmt->bind_param("s", $merchantRef);
    $selectResult = $urlStmt->execute();
    
    if (!$selectResult) {
        error_log("ERREUR MySQL (execute select): " . $urlStmt->error);
        throw new Exception("Erreur lors de la récupération de la transaction");
    }
    
    $result = $urlStmt->get_result();
    error_log("Nombre de résultats trouvés: " . $result->num_rows);
    
    if ($row = $result->fetch_assoc()) {
        error_log("Données transaction trouvées:");
        error_log("Token: " . ($row['token'] ?? 'NULL'));
        error_log("Merchant Key: " . ($row['merchant_key'] ?? 'NULL'));
        
        $decodedData = unserialize(base64_decode($row['token']));
        error_log("Données décodées: " . print_r($decodedData, true));
        
        $redirectUrl = ($transactionStatus == 'paid') ? 
            ($decodedData['urlOK'] ?? null) : 
            ($decodedData['urlKO'] ?? null);
        
        error_log("URL de redirection déterminée: " . ($redirectUrl ?? 'NULL'));
        
        if ($redirectUrl) {
            error_log("Redirection vers: $redirectUrl");
            header("Location: " . $redirectUrl);
            exit();
        }
    } else {
        error_log("ERREUR: Transaction non trouvée pour ref_order: $merchantRef");
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
