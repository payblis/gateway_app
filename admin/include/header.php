<?php
require("config.php");

if(!isset($_SESSION['username']) ){
    header('location: ../auth/login');
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('location: ../404.php');
    exit();
}

?>

<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payblis</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.jpeg" sizes="16x16">
    <!-- remix icon font css  -->
    <link rel="stylesheet" href="../assets/css/remixicon.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
   
    <!-- BootStrap css -->
    <link rel="stylesheet" href="../assets/css/lib/bootstrap.min.css">
    <!-- Apex Chart css -->
    <link rel="stylesheet" href="../assets/css/lib/apexcharts.css">
    <!-- Data Table css -->
    <link rel="stylesheet" href="../assets/css/lib/dataTables.min.css">
    <!-- Text Editor css -->
    <link rel="stylesheet" href="../assets/css/lib/editor-katex.min.css">
    <link rel="stylesheet" href="../assets/css/lib/editor.atom-one-dark.min.css">
    <link rel="stylesheet" href="../assets/css/lib/editor.quill.snow.css">
    <!-- Date picker css -->
    <link rel="stylesheet" href="../assets/css/lib/flatpickr.min.css">
    <!-- Calendar css -->
    <link rel="stylesheet" href="../assets/css/lib/full-calendar.css">
    <!-- Vector Map css -->
    <link rel="stylesheet" href="../assets/css/lib/jquery-jvectormap-2.0.5.css">
    <!-- Popup css -->
    <link rel="stylesheet" href="../assets/css/lib/magnific-popup.css">
    <!-- Slick Slider css -->
    <link rel="stylesheet" href="../assets/css/lib/slick.css">
    <!-- prism css -->
    <link rel="stylesheet" href="../assets/css/lib/prism.css">
    <!-- file upload css -->
    <link rel="stylesheet" href="../assets/css/lib/file-upload.css">

    <link rel="stylesheet" href="../assets/css/lib/audioplayer.css">
    <!-- main css -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>


<body>


    <?php
    // including sidebar
    include("sidebar.php");
    // including navbar
    include("navbar.php");
    ?>