<?php
$page = isset($_GET['url']) ? $_GET['url'] : "dashboard"; 
$folder = "";
$files = glob($folder . "*.php");
$file_name = $folder .$page.".php";

if (in_array($file_name, $files)) {
    include($file_name);
} else {
    include($folder . "404.php");
}

?>