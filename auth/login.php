<?php
session_start(); // Start the session at the very beginning

// Check if there's an error message in the session and display it

if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header('location: ../admin/dashboard');
    exit();
}
elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'merchant') {
    header('location: ../merchant/dashboard');
    exit();
}

?>

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

    <section class="auth bg-base d-flex flex-wrap">
        <div class="auth-left d-lg-block d-none">
            <div class="d-flex align-items-center flex-column h-100 justify-content-center">
                <img src="../assets/images/auth/auth-img.png" alt="">
            </div>
        </div>
        <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
            <div class="max-w-464-px mx-auto w-100">
                <div>
                    <a href="#" class="mb-40 max-w-290-px">
                        <img src="../assets/images/logo.jpeg" alt="">
                    </a>
                    <h4 class="mb-12">Sign In to your Account</h4>
                    <p class="mb-32 text-secondary-light text-lg">Welcome back! Please enter your details</p>
                    <?php
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>

                </div>
                <form action="process-login.php" method="post">
                    <div class="icon-field mb-16">
                        <span class="icon top-50 translate-middle-y">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="text" name="username" class="form-control h-56-px bg-neutral-50 radius-12" placeholder="Username">
                    </div>
                    <div class="position-relative mb-20">
                        <div class="icon-field">
                            <span class="icon top-50 translate-middle-y">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span>
                            <input type="password" name="password" class="form-control h-56-px bg-neutral-50 radius-12" id="your-password" placeholder="Password">
                        </div>
                        <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light" data-toggle="#your-password"></span>
                    </div>
                    <div class="">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input border border-neutral-300" type="checkbox" value="" id="remember">
                                <label class="form-check-label" for="remember">Remember me </label>
                            </div>
                            <!-- <a href="javascript:void(0)" class="text-primary-600 fw-medium">Forgot Password?</a> -->
                        </div>
                    </div>

                    <input type="submit" name="signin" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32">

                    
                </form>
            </div>
        </div>
    </section>

    <!-- jQuery library js -->
    <script src="../assets/js/lib/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap js -->
    <script src="../assets/js/lib/bootstrap.bundle.min.js"></script>
    <!-- Apex Chart js -->
    <script src="../assets/js/lib/apexcharts.min.js"></script>
    <!-- Data Table js -->
    <script src="../assets/js/lib/dataTables.min.js"></script>
    <!-- Iconify Font js -->
    <script src="../assets/js/lib/iconify-icon.min.js"></script>
    <!-- jQuery UI js -->
    <script src="../assets/js/lib/jquery-ui.min.js"></script>
    <!-- Vector Map js -->
    <script src="../assets/js/lib/jquery-jvectormap-2.0.5.min.js"></script>
    <script src="../assets/js/lib/jquery-jvectormap-world-mill-en.js"></script>
    <!-- Popup js -->
    <script src="../assets/js/lib/magnifc-popup.min.js"></script>
    <!-- Slick Slider js -->
    <script src="../assets/js/lib/slick.min.js"></script>
    <!-- prism js -->
    <script src="../assets/js/lib/prism.js"></script>
    <!-- file upload js -->
    <script src="../assets/js/lib/file-upload.js"></script>
    <!-- audioplayer -->
    <script src="../assets/js/lib/audioplayer.js"></script>
    <!-- main js -->
    <script src="../assets/js/app.js"></script>

    <script>
        // ================== Password Show Hide Js Start ========== 
        function initializePasswordToggle(toggleSelector) {
            $(toggleSelector).on('click', function() {
                $(this).toggleClass("ri-eye-off-line");
                var input = $($(this).attr("data-toggle"));
                if (input.attr("type") === "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }
            });
        }
        // Call the function
        initializePasswordToggle('.toggle-password');
        // ========================= Password Show Hide Js End ===========================
    </script>

</body>

</html>