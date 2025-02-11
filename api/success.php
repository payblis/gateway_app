<?php
error_log("=== DÉBUT SUCCESS.PHP ===");

require_once('../admin/include/config.php');
require_once('./includes/common_functions.php');

try {
    // Récupérer les paramètres
    $transId = $_GET['TransId'] ?? null;
    $merchantRef = $_GET['MerchantRef'] ?? null;
    $status = $_GET['Status'] ?? null;
    $amount = $_GET['Amount'] ?? null;

    if (!$merchantRef || !$status) {
        throw new Exception("Paramètres manquants");
    }

    error_log("Traitement transaction: TransId=$transId, MerchantRef=$merchantRef, Status=$status");

    // Mise à jour du statut de la transaction
    $stmt = $connection->prepare("
        UPDATE transactions 
        SET status = ? 
        WHERE ref_order = ?
    ");
    
    // Status 2 = APPROVED, 6 = DECLINED
    $transactionStatus = ($status == '2') ? 'paid' : 'failed';
    
    $stmt->bind_param("ss", $transactionStatus, $merchantRef);
    $stmt->execute();
    
    error_log("Statut de la transaction mis à jour: $transactionStatus pour ref_order: $merchantRef");

    // Redirection vers l'URL marchande appropriée
    $urlStmt = $connection->prepare("
        SELECT token 
        FROM transactions 
        WHERE ref_order = ?
    ");
    
    $urlStmt->bind_param("s", $merchantRef);
    $urlStmt->execute();
    $result = $urlStmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $decodedData = unserialize(base64_decode($row['token']));
        $redirectUrl = ($transactionStatus == 'paid') ? 
            ($decodedData['urlOK'] ?? null) : 
            ($decodedData['urlKO'] ?? null);
        
        if ($redirectUrl) {
            error_log("Redirection vers: $redirectUrl");
            header("Location: " . $redirectUrl);
            exit();
        }
    }
    
    throw new Exception("URL de redirection non trouvée");
    
} catch (Exception $e) {
    error_log("Error in success.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Redirection par défaut en cas d'erreur
    header("Location: /error.php");
    exit();
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
