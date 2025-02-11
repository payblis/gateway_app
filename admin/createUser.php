<?php
include("./include/header.php");
?>

<div class="dashboard-main-body">
    <div class="row gy-4">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Create User</h6>
                </div>
                <div class="card-body">

                    <?php
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>
                    <div class="row gy-3">
                        <form action="process-CreateUser" method="post">
                            <div class="col-12">
                                <label class="form-label">Merchant Username</label>
                                <input type="text" name="merchant_Uname" class="form-control" placeholder="Enter Merchant Username" require>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="form-label">Merchant Password</label>
                                <input type="password" name="merchant_Pass" class="form-control" placeholder="Enter Password" require>
                            </div>

                            <div class="col-12 mt-3">
                                <input type="submit" name="form_sbt" class="btn btn-primary">
                            </div>

                        </form>
                    </div>
                </div>
            </div><!-- card end -->
        </div>
    </div>
</div>

<?php
//including header
include("./include/footer.php");
?>