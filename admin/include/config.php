<?php

// $server = "localhost";
// $username = "root";
// $dbpass = "";
// $dbname = "payblis"; 

$server = "localhost";
$username = "admin_pay_payblis_user";
$dbname = "admin_pay_payblis"; 
$dbpass = "h6rx2?9E2";

$connection = mysqli_connect($server, $username, $dbpass, $dbname);

if(!$connection){
    die("Failed to connect");
}
else{
    //remains empty
}

session_start();

?>

