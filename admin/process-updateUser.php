<?php
require("./include/config.php");

if (isset($_POST['form_sbt'])) {

    $id = mysqli_real_escape_string($connection, $_POST['userid']);
    $updName = mysqli_real_escape_string($connection, $_POST['merchant_Uname']);
    $updstatus = mysqli_real_escape_string($connection, $_POST['status']);

    if (empty($id) || empty($updName)) {
        $_SESSION['error'] = "Please make sure all fields are filled.";
        header("Location: viewUsers.php");
        exit();
    }

    $update = "UPDATE `users` set `username`='$updName',`status`='$updstatus' WHERE id= '$id';";

    $result = mysqli_query($connection, $update) or die("failed to update query.");
    if ($result) {
        echo "<script>alert('Merchant`s Details Updated.')</script>;";
        header("location: viewUsers");
    } else {
        echo "<script>alert('Sorry, Failed to update this record.')</script>";
    }
}
