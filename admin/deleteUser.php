<?php

require("./include/config.php");

if ($_GET['id']) {
    $id = mysqli_real_escape_string($connection, $_GET['id']);

    $delete = "DELETE FROM `users` where id = '$id';";

    $result = mysqli_query($connection, $delete) or die("failed to delete query.");

    if ($result) {
        echo "<script>alert('Form row deleted successfully.')</script>";
        header("location: viewUsers.php");
    } else {
        echo "<script>alert('sorry failed to delete it')</script>";
    }
}
