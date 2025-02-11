<?php
include("./include/config.php");

if (isset($_POST['form_sbt'])) {

    $merchantName = mysqli_real_escape_string($connection, $_POST['merchant_Uname']);
    $merchantPass = mysqli_real_escape_string($connection, $_POST['merchant_Pass']);

    if (empty($merchantName) || empty($merchantPass)) {
        $_SESSION['error'] = "Please make sure both fields are filled.";
        header("Location: createUser.php");
        exit();
    }

    $encrypedPassword = password_hash($merchantPass, PASSWORD_BCRYPT);

    function generateToken($length = 32)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $token;
    }

    $token = generateToken();


    $check = "SELECT * FROM users WHERE username='$merchantName';";
    $res = mysqli_query($connection, $check) or die("failed");


    if (mysqli_num_rows($res) > 0) {
        $_SESSION['error'] = "Username Already Exists";
        header("Location: createUser.php");
    } else {
        $insert = "INSERT INTO `users`( `username`,`password`,`api_key`) VALUES ('$merchantName','$encrypedPassword','$token');";
        $result = mysqli_query($connection, $insert) or die("failed to insert query.");
        if ($result) {
            $_SESSION['success'] = "Account Created Successfully";
            header("Location: viewUsers.php");
        }
    }
}
