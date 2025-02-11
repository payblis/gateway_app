<?php
// including header
include("./include/header.php");

$username = $_SESSION['username'];
$getdata = "SELECT * FROM `users` WHERE username = '" . $_SESSION['username'] . "';";
$result = mysqli_query($connection, $getdata) or die("fail to run query");

if (mysqli_num_rows($result) == 1) {
  $row = mysqli_fetch_assoc($result);

  $name = $row['username'];
  $role = $row['role'];
  $status = $row['status'];
  $api_key = $row['api_key'];
?>

  <div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
      <h6 class="fw-semibold mb-0">View Profile</h6>
      <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
          <a href="dashboard" class="d-flex align-items-center gap-1 hover-text-primary">
            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
            Dashboard
          </a>
        </li>
        <li>-</li>
        <li class="fw-medium">View Profile</li>
      </ul>
    </div>

    <div class="row gy-4 ">
      <div class="col-lg-6">
        <div class="user-grid-card position-relative border radius-16 overflow-hidden bg-base h-100">
          <img src="../assets/images/user-grid/user-grid-bg1.png" alt="" class="w-100 object-fit-cover">
          <div class="pb-24 ms-16 mb-24 me-16 mt--100">
            <div class="text-center border border-top-0 border-start-0 border-end-0">
              <img src="../assets/images/avatar/avatar1.png" alt="" class="border br-white border-width-2-px w-200-px h-200-px rounded-circle object-fit-cover">
              <h6 class="mb-0 mt-16"><?php echo $name ?></h6>
              <span class="text-secondary-light mb-16"><?php echo $role ?></span>
            </div>
            <div class="mt-24">
              <h6 class="text-xl mb-16">Personal Info</h6>
              <ul>
                <li class="d-flex align-items-center gap-1 mb-12">
                  <span class="w-30 text-md fw-semibold text-primary-light">Username :</span>
                  <span class="w-70 text-secondary-light fw-medium"><?php echo $name ?></span>
                </li>

            

                <?php
                if (isset($_SESSION['success'])) {
                  echo '<div class="alert alert-success" role="alert">' . $_SESSION['success'] . '</div>';
                  unset($_SESSION['success']);
                } elseif (isset($_SESSION['error'])) {
                  echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
                  unset($_SESSION['error']);
                }
                ?>


                <li class="d-flex align-items-center gap-1 mb-12">
                  <a href="./changePassword" class="btn btn-danger">Change Password</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



<?php
  // including footer
  include("./include/footer.php");
}
?>