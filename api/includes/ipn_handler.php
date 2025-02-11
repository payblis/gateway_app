<?php
require_once dirname(__FILE__) . '/../../admin/include/config.php';
// ou
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/include/config.php';

// Log du début de la requête IPN
error_log("[Ovri IPN] Début du traitement IPN");

// Récupération du contenu brut de la requête
$input = file_get_contents('php://input');
error_log("[Ovri IPN] Données brutes reçues: " . $input);

// Si les données sont en JSON, on les décode
if ($input) {
    $ipnData = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        error_log("[Ovri IPN] Données JSON décodées: " . print_r($ipnData, true));
    } else {
        // Si ce n'est pas du JSON, on essaie de parser les données POST
        $ipnData = $_POST;
        error_log("[Ovri IPN] Données POST reçues: " . print_r($ipnData, true));
    }
} else {
    $ipnData = $_POST;
    error_log("[Ovri IPN] Données POST reçues: " . print_r($ipnData, true));
}

// Vérification des données nécessaires
if (isset($ipnData['reforder']) || isset($ipnData['MerchantRef'])) {
    $refOrder = $ipnData['reforder'] ?? $ipnData['MerchantRef'];
    $status = $ipnData['status'] ?? $ipnData['Status'] ?? null;
    $transactionId = $ipnData['transactionId'] ?? $ipnData['TransactionId'] ?? null;

    error_log("[Ovri IPN] Traitement de la transaction - RefOrder: $refOrder, Status: $status, TransactionId: $transactionId");

    try {
        // Récupération de la transaction
        $stmt = $connection->prepare("SELECT * FROM transactions WHERE ref_order = ?");
        $stmt->bind_param("s", $refOrder);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();

        if ($transaction) {
            // Mapping des statuts Ovri vers nos statuts
            $dbStatus = '';
            switch (strtolower($status)) {
                case 'success':
                    $dbStatus = 'paid';
                    break;
                case 'failed':
                    $dbStatus = 'failed';
                    break;
                case 'pending':
                case 'pending3ds':
                    $dbStatus = 'pending';
                    break;
                default:
                    $dbStatus = 'failed';
                    break;
            }

            // Mise à jour du statut de la transaction
            $updateStmt = $connection->prepare("UPDATE transactions SET status = ? WHERE ref_order = ?");
            $updateStmt->bind_param("ss", $dbStatus, $refOrder);
            $updateStmt->execute();

            error_log("[Ovri IPN] Statut de la transaction mis à jour - RefOrder: $refOrder, Nouveau statut: $dbStatus");

            // Log de la notification IPN
            $logStmt = $connection->prepare("INSERT INTO ovri_logs (transaction_id, request_type, request_body, response_body, http_code, token) VALUES (?, 'IPN', ?, ?, ?, ?)");
            $requestBody = json_encode($ipnData);
            $responseBody = json_encode(['status' => 'processed', 'newStatus' => $dbStatus]);
            $httpCode = '200';
            $token = $transaction['token'];
            $logStmt->bind_param("sssss", $transactionId, $requestBody, $responseBody, $httpCode, $token);
            $logStmt->execute();

            // Réponse OK à Ovri
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            error_log("[Ovri IPN] Transaction non trouvée - RefOrder: $refOrder");
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
        }
    } catch (Exception $e) {
        error_log("[Ovri IPN] Erreur lors du traitement: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    }
} else {
    error_log("[Ovri IPN] Données manquantes dans la notification");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
}

error_log("[Ovri IPN] Fin du traitement IPN"); 