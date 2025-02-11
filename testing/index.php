<?php
// Set up the request parameters
$MyVars = array(
    'MerchantKey' => 'lje7XqXlupzgROJRQDmkRyiyhBQXdulb', // Merchant Key
    'amount' => '76.00', // Amount to be paid
    'RefOrder' => 'ref-6748', // Reference Order number (must be unique)
    'Customer_Email' => 'jonathan@test.com', // Customer's email
    'Customer_Phone' => '33123456789123', // Customer's phone number
    'Customer_Name' => 'Jonathan', // Customer's Name
    'Customer_FirstName' => 'Petit', // Customer's First Name
    'country' => 'France', // Customer's country
    'userIP' => '192.168.1.1', // Customer's IP
    'lang' => 'en', // Language
    'urlOK' => 'http://localhost/testPayment/success.php',
    'urlKO' => 'http://localhost/testPayment/failed.php',
    'urlIPN' => 'http://localhost/testPayment/ipn.php'
);

// Serialize the data (convert array to string format)
$serializedData = serialize($MyVars);

// Base64 encode the serialized data directly (no URL encoding)
$encoded = base64_encode($serializedData);

// Prepare the custom payment URL
$myCustomLink = "https://pay.payblis.com/api/payment.php?token=".$encoded; // Redirect URL to your custom page

// Redirect the user to your custom endpoint (payment page)
header("Location: " . $myCustomLink);
exit; 
?>
