<?php
error_log("=== DÉBUT FAILED.PHP ===");
require('../admin/include/config.php');

// Log de toutes les données reçues
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

// Récupération des paramètres
$transactionId = $_GET['transactionId'] ?? null;
$error = $_GET['error'] ?? 'Unknown error';

error_log("Transaction ID: " . ($transactionId ?? 'Non défini'));
error_log("Error message: " . $error);

if ($transactionId) {
    try {
        // Mise à jour du statut dans ovri_logs
        $query = "UPDATE ovri_logs SET 
                  http_code = 500,
                  response_body = ? 
                  WHERE transaction_id = ?";
                  
        $errorResponse = json_encode([
            'status' => 'failed',
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param("ss", $errorResponse, $transactionId);
        
        if ($stmt->execute()) {
            error_log("Statut d'erreur mis à jour dans ovri_logs");
        } else {
            error_log("Erreur lors de la mise à jour du statut: " . $stmt->error);
        }
        
        // Récupérer l'URL KO depuis ovri_logs
        $query = "SELECT request_body FROM ovri_logs WHERE transaction_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $requestData = json_decode($row['request_body'], true);
            
            if (isset($requestData['urlKO'])) {
                $redirectUrl = $requestData['urlKO'];
                if (strpos($redirectUrl, '?') === false) {
                    $redirectUrl .= '?';
                } else {
                    $redirectUrl .= '&';
                }
                $redirectUrl .= 'error=' . urlencode($error) . 
                               '&transactionId=' . urlencode($transactionId);
                
                error_log("Redirection vers URL KO: " . $redirectUrl);
                header("Location: " . $redirectUrl);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Exception dans failed.php: " . $e->getMessage());
    }
}

// Si on arrive ici, c'est qu'on n'a pas pu rediriger vers l'URL KO
error_log("=== FIN FAILED.PHP - Affichage page d'erreur par défaut ===");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
</head>
<body>
    <h1>Payment Failed</h1>
    <p>Error: <?php echo htmlspecialchars($error); ?></p>
    <?php if ($transactionId): ?>
        <p>Transaction ID: <?php echo htmlspecialchars($transactionId); ?></p>
    <?php endif; ?>
</body>
</html> 