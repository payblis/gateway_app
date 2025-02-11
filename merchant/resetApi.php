<?php

require("../admin/include/config.php");

function generateToken($length = 32)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $token;
}

$updAPI = generateToken();

// Use prepared statements to prevent SQL injection
$query = "UPDATE `users` SET `api_key` = ? WHERE `username` = ?";
$stmt = mysqli_prepare($connection, $query);

if ($stmt === false) {
    die('MySQL prepare error: ' . mysqli_error($connection));
}

// Bind parameters: 's' denotes strings
mysqli_stmt_bind_param($stmt, 'ss', $updAPI, $_SESSION['username']);

$result = mysqli_stmt_execute($stmt);

if ($result) {
    // Set success message in session and redirect
    $_SESSION['success'] = "API Key Updated Successfully";
    header("Location: view-profile");  // Redirect to the profile page
    exit();
} else {
    // Show error message
    $_SESSION['error'] = "Fail to API Key";
    header("Location: view-profile"); 
    exit();
}

?>
