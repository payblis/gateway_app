<?php

require('../admin/include/config.php');


// Get values from URL parameters
$MerchantRef = $_GET['MerchantRef'];
$amount = $_GET['Amount'];
$TransId = $_GET['TransId'];
$Status = $_GET['Status'];


$query = "SELECT * FROM transactions WHERE `ref_order` = '$MerchantRef'";
$run = mysqli_query($connection, $query);

if (mysqli_num_rows($run) > 0) {

    $row = mysqli_fetch_assoc($run);
    // echo $row['ref_order'];

    $update = "UPDATE `transactions` SET `status`= 'paid' WHERE `ref_order` = '{$row['ref_order']}'";
    $result = mysqli_query($connection, $update) or die("failed to update query.");

    // echo '<pre>';
    // print_r($_GET);
    // echo '</pre>';
} else {
    echo "No records found for Transaction ID: " . htmlspecialchars($MerchantRef);
}



//Getting SuccessUrl
$getdata = "SELECT * FROM ovri_logs WHERE `transaction_id` = '$TransId'";
$exec = mysqli_query($connection, $getdata);

if (mysqli_num_rows($exec) > 0) {

    $get = mysqli_fetch_assoc($exec);
    $user_Req = $get['request_body'];
    $dataDecoded = json_decode($user_Req, true);
    $success_Url = $dataDecoded['urlOK'];

    header('Location: ' . $success_Url . '?MerchantRef=' . urlencode($MerchantRef) . '&Amount=' . urlencode($amount) . '&TransId=' . urlencode($TransId) . '&Status=Success');

} else {
    echo "<p>No records found for Transaction ID: " . htmlspecialchars($TransId) . "</p>";
}



// Close connection
mysqli_close($connection);
