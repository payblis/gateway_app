<?php

include("./include/config.php");

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$username = $_SESSION['username'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: changePassword.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New password and confirm password do not match.";
        header("Location: changePassword.php");
        exit();
    }

    // Password strength validation (optional but recommended)
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "New password must be at least 8 characters long.";
        header("Location: changePassword.php");
        exit();
    }

    // Fetch the current password from the database
    $query = "SELECT password FROM users WHERE username = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: changePassword.php");
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $update_query = "UPDATE users SET password = ? WHERE username = ?";
    $update_stmt = $connection->prepare($update_query);
    $update_stmt->bind_param("ss", $hashed_password, $username);

    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Password changed successfully.";
        header("Location: view-profile.php"); // Redirect to profile page
        exit();
    } else {
        $_SESSION['error'] = "Failed to update password. Please try again.";
        header("Location: changePassword.php");
        exit();
    }
}
?>
