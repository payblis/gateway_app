<?php
// Start the session at the top

require("../admin/include/config.php");

if (isset($_POST['signin']) && $_SERVER['REQUEST_METHOD'] == "POST") {
    $username = mysqli_real_escape_string($connection, $_POST['username']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);

    // Check if either username or password is empty
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please make sure both fields are filled."; // Error message
        // Redirect to login page
        header("Location: login");
        exit();
    }

    // Query to check if the username exists
    $check = "SELECT * FROM users WHERE username='$username';";
    $result = mysqli_query($connection, $check);

    if ($result) {
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $userRole = $row['role'];
            $userStatus = $row['status'];
            $regUsername = $row['username'];
            $id = $row['id'];
            $regPass = $row['password']; // hashed password
            $verifyPass = password_verify($password, $regPass); // Verify password

            if ($verifyPass) {
               

                if ($userRole == "admin" && $userStatus == 1) {
                     // Successful login
                        $_SESSION['role'] = $userRole;
                        $_SESSION['username'] = $regUsername;
                        $_SESSION['id'] = $id;
                    // Redirect to admin dashboard
                    echo "<script>window.location.href='../admin/dashboard';</script>";
                    exit();
                }

                if ($userRole == "merchant" && $userStatus == 1) {
                    
                     // Successful login
                    $_SESSION['role'] = $userRole;
                    $_SESSION['username'] = $regUsername;
                    $_SESSION['id'] = $id;
                    // Redirect to merchant dashboard
                    echo "<script>window.location.href='../merchant/dashboard';</script>";
                    exit();
                }

                if ($userRole == "merchant" && $userStatus == 0) {
                    // Redirect to merchant dashboard
                    $_SESSION['error'] = "Your account is blocked. Please contact support for assistance.";
                    header("Location: login");

                }

            } else {
                // Invalid password
                $_SESSION['error'] = "Invalid credentials. Please try again.";
                header("Location: login");
                exit();
            }
        } else {
            // Username not found
            $_SESSION['error'] = "User not found. Please check your username or sign up.";
            header("Location: login");
            exit();
        }
    } else {
        // Error with the SQL query
        $_SESSION['error'] = "An error occurred while processing your request. Please try again.";
        header("Location: login");
        exit();
    }
}
?>
