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
$merchantRef = $_GET['MerchantRef'] ?? null;
$amount = $_GET['Amount'] ?? null;
$transId = $_GET['TransId'] ?? null;
$status = $_GET['Status'] ?? null;

if ($merchantRef && $transId) {
    try {
        // 1. Mettre à jour le statut de la transaction
        $stmt = $connection->prepare("SELECT * FROM transactions WHERE ref_order = ?");
        $stmt->bind_param("s", $merchantRef);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();

        if ($transaction) {
            // 2. Vérifier si le statut est pending3ds et si le Status est 6 (succès 3DS)
            if ($transaction['status'] === 'pending3ds' && $status === '6') {
                $updateStmt = $connection->prepare("UPDATE transactions SET status = ?, transaction_id = ? WHERE ref_order = ?");
                $newStatus = "paid";
                $updateStmt->bind_param("sss", $newStatus, $transId, $merchantRef);
                $updateStmt->execute();

                error_log("Transaction mise à jour avec succès - Status: paid, TransId: " . $transId);

                // 3. Récupérer les données utilisateur pour l'IPN
                $userData = unserialize($transaction['user_data']);
                
                // 4. Préparer et envoyer l'IPN avec les détails 3DS
                $ipnData = [
                    'TransId' => $transId,
                    'MerchantRef' => $merchantRef,
                    'Amount' => $amount,
                    'Status' => 'Success',
                    'ipnURL' => $userData['ipnURL'] ?? null,
                    'MerchantKey' => $userData['MerchantKey'] ?? null,
                    'threeds' => [
                        'status' => 'success',
                        'code' => $status,
                        'authentication_status' => 'Y'
                    ]
                ];

                error_log("Tentative d'envoi IPN depuis success.php après 3DS - Status: Success");
                error_log("Données IPN: " . print_r($ipnData, true));
                
                try {
                    $ipnResult = sendIpnNotification($ipnData);
                    error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
                } catch (Exception $e) {
                    error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
                }

                // 5. Rediriger vers l'URL de succès du marchand
                if (isset($userData['urlOK'])) {
                    header('Location: ' . $userData['urlOK']);
                    exit;
                }
            } else {
                error_log("Statut invalide - Current status: " . $transaction['status'] . ", 3DS Status: " . $status);
                if (isset($userData['urlKO'])) {
                    header('Location: ' . $userData['urlKO']);
                    exit;
                }
            }
        } else {
            error_log("Transaction non trouvée pour le MerchantRef: " . $merchantRef);
        }
    } catch (Exception $e) {
        error_log("Erreur lors du traitement du retour 3DS: " . $e->getMessage());
    }
}

error_log("=== FIN SUCCESS.PHP ===");

// Fermer la connexion
mysqli_close($connection);
