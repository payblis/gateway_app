<?php
error_log("=== DÉBUT CHECKOUT.PHP ===");
require('../admin/include/config.php');
require('./includes/ipn_handler.php');

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

// Récupération des données POST
$inserted_id = $_POST['inserted_id'];
$cardHoldername = $_POST['cardHolderName'];
$cardno = $_POST['cardno'];
$expMonth = $_POST['expMonth'];
$expYear = $_POST['expYear'];
$CVN = $_POST['CVN'];

$userdata = $_POST['array'];
$decodedData = urldecode($userdata);
$MyVars = unserialize($decodedData);

// Construction des URLs
$ipnUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/includes/ipn_handler.php';
$successUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/success.php';
$failureUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/failed.php';

error_log("[Ovri Checkout] URLs configured - IPN: $ipnUrl, Success: $successUrl, Failure: $failureUrl");

// Préparation de la requête vers Ovri
$myrequest = array(
    'capture' => true,
    'amount' => $MyVars['amount'],
    'reforder' => $MyVars['RefOrder'],
    'cardHolderName' => $cardHoldername,
    'cardHolderEmail' => $MyVars['Customer_Email'],
    'cardno' => $cardno,
    'edMonth' => $expMonth,
    'edYear' => $expYear,
    'cvv' => $CVN,
    'customerIP' => $MyVars['userIP'],
    'urlIPN' => $ipnUrl,
    'urlOK' => $successUrl,
    'urlKO' => $failureUrl,
    'browserUserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'browserLanguage' => 'en-US',
    'browserAcceptHeader' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'browserJavaEnabled' => true,
    'browserColorDepth' => '24',
    'browserScreenHeight' => '1080',
    'browserScreenWidth' => '1920',
    'browserTimeZone' => -5,
);

// Envoi de la requête à Ovri
$ch = curl_init(apiEndPoint);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'apikey: ' . myApiKeyPos,
    'secretkey: ' . mySecretKeyPos
));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($myrequest));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($ch);
$resultDecode = json_decode($result, true);

error_log("[Ovri Checkout] Response from Ovri: " . print_r($resultDecode, true));

if ($resultDecode['code'] == 'success') {
    $http_code = 200;
    updatelogs($MyVars, $resultDecode, $http_code);

    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "paid";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();

    header('Location: ' . $successUrl);
    exit;
} elseif ($resultDecode['code'] == 'pending3ds') {
    $http_code = 101;
    updatelogs($MyVars, $resultDecode, $http_code);

    // Pour 3DS, on met à jour le statut en pending
    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "pending";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();

    // On affiche le formulaire 3DS
    $embeddedB64 = base64_decode($resultDecode['embeddedB64']);
    echo $embeddedB64;
    exit;
} else {
    // Cas d'erreur
    $http_code = 500;
    updatelogs($MyVars, $resultDecode, $http_code);

    $stmt = $connection->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $status = "failed";
    $stmt->bind_param("si", $status, $inserted_id);
    $stmt->execute();

    header('Location: ' . $failureUrl);
    exit;
}

error_log("=== FIN CHECKOUT.PHP ===");
