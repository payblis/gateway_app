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

    echo '<pre>';
    print_r($_GET);
    echo '</pre>';

} else {
    echo "No records found for Transaction ID: " . htmlspecialchars($MerchantRef);
}



// Close connection
mysqli_close($connection);
