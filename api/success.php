<?php
error_log("=== DÉBUT SUCCESS.PHP ===");
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

require('../admin/include/config.php');
error_log("Config chargée");

require('./includes/ipn_handler.php');
error_log("Handler IPN chargé");

// Vérifier tous les paramètres reçus
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));

// Récupérer les paramètres de l'URL
$TransId = $_GET['transactionId'] ?? null;
$Status = $_GET['status'] ?? null;

error_log("TransId: " . $TransId);
error_log("Status: " . $Status);

// Récupérer les informations de la transaction depuis ovri_logs
if ($TransId) {
    $query = "SELECT request_body FROM ovri_logs WHERE transaction_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $TransId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        error_log("Transaction trouvée dans ovri_logs");
        $row = $result->fetch_assoc();
        $requestData = json_decode($row['request_body'], true);
        
        // Redirection simple vers urlOK sans paramètres
        if (isset($requestData['urlOK'])) {
            error_log("Redirection vers: " . $requestData['urlOK']);
            header('Location: ' . $requestData['urlOK']);
            exit;
        }
    } else {
        error_log("Aucune transaction trouvée pour TransId: " . $TransId);
    }
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
