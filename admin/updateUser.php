<?php
include("./include/header.php");

if ($_GET['id']) {
    // echo "id found";
    $id = $_GET['id'];
    $getdata = "SELECT * FROM `users` WHERE id='$id';";

    $result = mysqli_query($connection, $getdata) or die("fail to run query");

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $name = $row['username'];
        $status = $row['status'];


?>

        <div class="dashboard-main-body">
            <div class="row gy-4">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Edit User</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            if (isset($_SESSION['error'])) {
                                echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
                                unset($_SESSION['error']);
                            }
                            ?>

                            <div class="row gy-3">
                                <form action="process-updateUser" method="post">
                                    <div class="col-12">
                                        <!-- <label class="form-label">Id</label> -->
                                        <input type="hidden" name="userid" class="form-control" value="<?php echo $id ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Merchant Username</label>
                                        <input type="text" name="merchant_Uname" class="form-control" value="<?php echo $name ?>">
                                    </div>
                                    <!-- <div class="col-12 mt-3">
                                        <label class="form-label">Merchant Password</label>
                                        <input type="password" name="merchant_Pass" class="form-control">
                                    </div> -->
                                  
                                    <!-- <div class="col-12 mt-3">
                                        <label class="form-label">API Key</label>
                                        <input type="text" name="merchant_Pass" class="form-control">
                                    </div> -->
                                    <div class="col-12 mt-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>Approve</option>
                                            <option value="0" <?php echo ($status == 0) ? 'selected' : ''; ?>>Block</option>
                                        </select>
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

    }
}
//including header
include("./include/footer.php");
?>