<?php

// Set up the request parameters
$data = array(
    'MerchantKey' => 'c1LOsEW1wvFMnK0r9lWxend5VItyCLOm', // Merchant Key
    'amount' => '15.00', // Amount to be paid
    'RefOrder' => 're11f22782', // Reference Order number (must be unique)
    'Customer_Email' => 'customer@email.com', // Customer's email
    'Customer_Phone' => '33123456789123', // Customer's phone number
    'Customer_Name' => 'John',
    'Customer_FirstName' => 'Doe',
    'country' => 'France',
    'userIP' => '192.168.1.1',
    'lang' => 'en', // Language
    'urlOK' => 'https://www.your-domain.com/paymentSuccess.php', // Success URL
    'urlKO' => 'https://www.your-domain.com/paymentFailed.php' // Failure URL (this seems like a placeholder, you might want to change it)
);

// Serialize the data (convert array to string format)
$serializedData = serialize($data);

// Base64 encode the serialized data directly (no URL encoding)
$encoded = base64_encode($serializedData);

// Prepare the custom payment URL
$url = "https://pay.payblis.com/api/payment.php?token=".$encoded; // Redirect URL to your custom page

// Redirect the user to your custom endpoint (payment page)
header("Location: " . $url);
exit; // Make sure no further code is executed after the redirect
?>
