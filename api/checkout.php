<?php
error_log("=== DÉBUT CHECKOUT.PHP ===");
require('../admin/include/config.php');
require('./includes/common_functions.php');

error_log("POST data reçues: " . print_r($_POST, true));
error_log("GET data reçues: " . print_r($_GET, true));

function updatelogs($reqbody, $resbody, $http_code)
{
    global $connection;

    // Assuming 'TransactionId' is a key in $resbody
     $transaction_id = $resbody['TransactionId'] ?? $resbody['transactionId'] ?? null;

    $request_type = 'via card';
    $request_body = json_encode($reqbody);
    $response_body = json_encode($resbody); // Now properly encoding response body
    $usertoken = $reqbody['MerchantKey'];

    // Prepare the query to avoid SQL injection
    $stmt = $connection->prepare("INSERT INTO `ovri_logs` (`transaction_id`, `request_type`, `request_body`, `response_body`, `http_code`, `token`) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Failed to prepare query.");
    }

    // Bind parameters to the prepared statement
    $stmt->bind_param("ssssss", $transaction_id, $request_type, $request_body, $response_body, $http_code, $usertoken);

    // Execute the query
    $stmt->execute();

    // Close the statement
    $stmt->close();
}

// Getting POST data
$inserted_id = $_POST['inserted_id'];
$cardHoldername = $_POST['cardHolderName'];
$cardno = $_POST['cardno'];
$expMonth = $_POST['expMonth'];
$expYear = $_POST['expYear'];
$CVN = $_POST['CVN'];

$userdata = $_POST['array'];
$decodedData = urldecode($userdata);
$MyVars = unserialize($decodedData);

// Need customer IP too later
$MerchantKey = $MyVars['MerchantKey'];
$amount = $MyVars['amount'];
$RefOrder = $MyVars['RefOrder'];
$Customer_Email = $MyVars['Customer_Email'];
$Customer_Phone = $MyVars['Customer_Phone'];
$Customer_FirstName = $MyVars['Customer_FirstName'];
$lang = $MyVars['lang'];
$UserIP = $MyVars['userIP'];
$urlOK = $MyVars['urlOK'];
$urlKO = $MyVars['urlKO'];

// API endpoint and credentials
define('apiEndPoint', 'https://api.ovri.app/payment/authorization');
define('myApiKeyPos', '695066a9312825a06API66a9312825a09');
define('mySecretKeyPos', 'YjMxZGVkNjk4MjY1OWI1ODg0MzhiY2RiNmY4YTI0M2U=');

// Prepare the request data
$myrequest = array(
    'capture' => true,
    'amount' => $amount,
    'reforder' => $RefOrder,
    'cardHolderName' => $cardHoldername,
    'cardHolderEmail' => $Customer_Email,
    'cardno' => $cardno,
    'edMonth' => $expMonth,
    'edYear' => $expYear,
    'cvv' => $CVN,
    'customerIP' => $UserIP,
    'urlIPN' => 'https://pay.payblis.com/api/ipn.php',
    'urlOK' => 'https://pay.payblis.com/api/success.php',
    'urlKO' => 'https://pay.payblis.com/api/failed.php',
    'browserUserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'browserLanguage' => 'en-US',
    'browserAcceptHeader' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'browserJavaEnabled' => true,
    'browserColorDepth' => '24',
    'browserScreenHeight' => '1080',
    'browserScreenWidth' => '1920',
    'browserTimeZone' => -5,
);

// Log de la requête
error_log("Requête envoyée à OVRI: " . print_r($myrequest, true));

function GenerateSignature(array $jsondata)
{
    $stringSign = base64_encode(hash('sha512', json_encode($jsondata) . mySecretKeyPos));
    return myApiKeyPos . '.' . $stringSign;
}

$signature = GenerateSignature($myrequest);

// Sending the request with CURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, apiEndPoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($myrequest));
$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Authorization: Bearer ' . $signature;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

curl_close($ch);

// Decode the result
$resultDecode = json_decode($result, true);

// Handle the result
if ($resultDecode['code'] == 'success') {
    $http_code = 200;
    updatelogs($MyVars, $resultDecode, $http_code);

    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "paid";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();
    
    error_log("Tentative d'envoi IPN depuis checkout.php - Status: Success");
    
    // Décodage correct des données
    $decodedData = unserialize(urldecode($userdata));
    error_log("Données décodées: " . print_r($decodedData, true));
    
    if (isset($decodedData['ipnURL'])) {
        $ipnData = [
            'TransId' => $resultDecode['TransactionId'],
            'MerchantRef' => $RefOrder,
            'status' => 'APPROVED',
            'ipnURL' => $decodedData['ipnURL']
        ];
        
        try {
            usleep(100000); // 100ms pause
            $ipnResult = sendIpnNotification($ipnData);
            error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
            
            // Préparer la réponse pour le log
            $responseData = [
                'success' => $ipnResult,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            logIpnAttemptToDb(
                $resultDecode['TransactionId'],
                $ipnData,
                $ipnResult ? 200 : 500,
                json_encode($responseData)
            );
            
            // Redirection vers l'URL du marchand
            if (isset($decodedData['urlOK'])) {
                error_log("Redirection vers: " . $decodedData['urlOK']);
                header("Location: " . $decodedData['urlOK']);
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
            logIpnAttemptToDb(
                $resultDecode['TransactionId'], 
                $ipnData, 
                500, 
                $e->getMessage()
            );
            
            // Redirection vers l'URL d'échec en cas d'erreur
            if (isset($decodedData['urlKO'])) {
                error_log("Redirection vers: " . $decodedData['urlKO']);
                header("Location: " . $decodedData['urlKO']);
                exit();
            }
        }
    }
} elseif ($resultDecode['code'] == '000006') {
    $http_code = 402;
    updatelogs($MyVars, $resultDecode, $http_code);

    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "failed";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();

    error_log("Tentative d'envoi IPN depuis checkout.php - Status: Declined");
    $ipnData = [
        'TransId' => $resultDecode['TransactionId'] ?? null,
        'MerchantRef' => $RefOrder,
        'Amount' => $amount,
        'Status' => 'Declined'
    ];
    
    try {
        $ipnResult = sendIpnNotification($ipnData);
        error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
    }

    $decodedData = unserialize(urldecode($userdata));
    if (isset($decodedData['urlKO'])) {
        error_log("Redirection vers: " . $decodedData['urlKO']);
        header("Location: " . $decodedData['urlKO']);
        exit();
    }
} elseif ($resultDecode['code'] == 'FATAL-500') {
    $http_code = 500;
    updatelogs($MyVars, $resultDecode, $http_code);

    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "failed";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();

    error_log("Tentative d'envoi IPN depuis checkout.php - Status: Error");
    $ipnData = [
        'TransId' => $resultDecode['TransactionId'] ?? null,
        'MerchantRef' => $RefOrder,
        'Amount' => $amount,
        'Status' => 'Error'
    ];
    
    try {
        $ipnResult = sendIpnNotification($ipnData);
        error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
    }

    $decodedData = unserialize(urldecode($userdata));
    if (isset($decodedData['urlKO'])) {
        error_log("Redirection vers: " . $decodedData['urlKO']);
        header("Location: " . $decodedData['urlKO']);
        exit();
    }
} elseif ($resultDecode['code'] == 'failed') {
    $http_code = 500;
    updatelogs($MyVars, $resultDecode, $http_code);

    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "failed";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();

    // Décoder les données utilisateur pour obtenir ipnURL et MerchantKey
    $decodedData = unserialize(urldecode($userdata));
    
    error_log("Tentative d'envoi IPN depuis checkout.php - Status: Failed");
    $ipnData = [
        'TransId' => $resultDecode['TransactionId'] ?? $resultDecode['transactionId'] ?? uniqid('FAILED-'),
        'MerchantRef' => $RefOrder,
        'Amount' => $amount,
        'Status' => 'Failed',
        'ipnURL' => $decodedData['ipnURL'] ?? null,
        'MerchantKey' => $decodedData['MerchantKey'] ?? null
    ];
    
    try {
        usleep(100000); // 100ms pause
        $ipnResult = sendIpnNotification($ipnData);
        error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
    }

    $decodedData = unserialize(urldecode($userdata));
    if (isset($decodedData['urlKO'])) {
        error_log("Redirection vers: " . $decodedData['urlKO']);
        header("Location: " . $decodedData['urlKO']);
        exit();
    }
} elseif ($resultDecode['code'] == 'pending3ds') {
    $http_code = 101;
    updatelogs($MyVars, $resultDecode, $http_code);

    error_log("=== TRAITEMENT 3DS ===");
    error_log("Données complètes 3DS: " . print_r($resultDecode, true));
    
    // Décodage des données
    $decodedData = unserialize(urldecode($userdata));
    error_log("Données merchant décodées: " . print_r($decodedData, true));
    
    // Récupérer le TransactionId depuis la réponse OVRI
    $transactionId = $resultDecode['transactionId'] ?? 
                    $resultDecode['TransactionId'] ?? 
                    $resultDecode['transaction_id'] ?? 
                    null;
    
    error_log("TransactionId extrait: " . ($transactionId ?? 'NON TROUVÉ'));
    
    $ipnData = [
        'TransId' => $transactionId,
        'MerchantRef' => $RefOrder,
        'Amount' => $amount,
        'Status' => 'Pending3DS',
        'ipnURL' => $decodedData['ipnURL'] ?? null,
        'raw_response' => $result // Stockage de la réponse brute
    ];
    
    error_log("Données IPN préparées: " . print_r($ipnData, true));
    
    try {
        // Mettre à jour le statut dans transactions
        $stmt = $connection->prepare("
            UPDATE transactions 
            SET status = 'pending'
            WHERE ref_order = ?
        ");
        $stmt->bind_param("s", $RefOrder);
        $stmt->execute();
        error_log("Statut transaction mis à jour: pending");
        
        // Logger dans ovri_logs
        $logStmt = $connection->prepare("
            INSERT INTO ovri_logs 
            (transaction_id, request_body, status_code, response, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $requestBody = json_encode($ipnData);
        $statusCode = 101; // Code spécial pour 3DS
        $response = "Transaction en attente de 3DS - " . $result;
        
        $logStmt->bind_param("ssis", $transactionId, $requestBody, $statusCode, $response);
        $logResult = $logStmt->execute();
        error_log("Résultat log OVRI: " . ($logResult ? "Succès" : "Échec"));
        
        // Afficher la page 3DS
        if (isset($resultDecode['embeddedB64'])) {
            error_log("Affichage page 3DS");
            $embeddedB64 = base64_decode($resultDecode['embeddedB64']);
            echo $embeddedB64;
            exit();
        } else {
            error_log("ERREUR: embeddedB64 manquant dans la réponse 3DS");
            throw new Exception("Données 3DS manquantes");
        }
    } catch (Exception $e) {
        error_log("ERREUR lors du traitement 3DS: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Redirection vers l'URL d'échec
        if (isset($decodedData['urlKO'])) {
            header("Location: " . $decodedData['urlKO']);
            exit();
        }
    }
}

error_log("=== FIN CHECKOUT.PHP ===");
