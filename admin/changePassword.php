<?php
include("./include/header.php");
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Change Password</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="dashboard" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Change Password</li>
        </ul>
    </div>

    <div class="row gy-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <?php
                    // Display success or error messages
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success" role="alert">' . $_SESSION['success'] . '</div>';
                        unset($_SESSION['success']);
                    } elseif (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>

                    <form action="processChangePass" method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("./include/footer.php"); ?>
