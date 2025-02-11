<?php
error_log("=== DÉBUT CHECKOUT.PHP ===");
require('../admin/include/config.php');

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

    // Décoder les données utilisateur pour obtenir ipnURL et MerchantKey
    $decodedData = unserialize(urldecode($userdata));
    
    error_log("Tentative d'envoi IPN depuis checkout.php - Status: Success");
    $ipnData = [
        'TransId' => $resultDecode['TransactionId'],
        'MerchantRef' => $RefOrder,
        'Amount' => $amount,
        'Status' => 'Success',
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

    // Avant la redirection vers OVRI
    logOvriFlow('pre_redirect_ovri', [
        'url' => $urlOK ?? 'No URL',
        'params' => [],
        'headers' => getallheaders(),
        'session' => $_SESSION ?? [],
        'method' => $_SERVER['REQUEST_METHOD']
    ]);

    header('Location: ' . $urlOK);
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

    header('Location: ' . $urlKO);
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

    header('Location: ' . $urlKO);
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

    header('Location: ' . $urlKO);
} elseif ($resultDecode['code'] == 'pending3ds') {
    $http_code = 101;
    updatelogs($MyVars, $resultDecode, $http_code);

    error_log("Tentative d'envoi IPN depuis checkout.php - Status: Pending3DS");
    $ipnData = [
        'TransId' => $resultDecode['TransactionId'] ?? null,
        'MerchantRef' => $RefOrder,
        'Amount' => $amount,
        'Status' => 'Pending3DS'
    ];
    
    try {
        $ipnResult = sendIpnNotification($ipnData);
        error_log("Résultat envoi IPN: " . ($ipnResult ? "Succès" : "Échec"));
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi IPN: " . $e->getMessage());
    }

    $embeddedB64 = base64_decode($resultDecode['embeddedB64']);
    echo $embeddedB64;
}

error_log("=== FIN CHECKOUT.PHP ===");
