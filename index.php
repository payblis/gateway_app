<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('location: auth/login');
    exit();
}
elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header('location: ./admin/dashboard');
    exit();
}
elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'merchant') {
    header('location: ./merchant');
    exit();
}
