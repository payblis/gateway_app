<?php
//including header
include("./include/header.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = $_SESSION['id'];
// Fetch total transaction count
$allCountQuery = mysqli_query($connection, "SELECT COUNT(*) AS allCount 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE u.id = $user_id");
$allCount = mysqli_fetch_assoc($allCountQuery)['allCount'] ?? 0;

// Fetch total paid amount
$paidAmountQuery = mysqli_query($connection, "SELECT SUM(amount) AS totalPaid 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE t.status = 'paid' AND u.id = $user_id");
$paidAmount = mysqli_fetch_assoc($paidAmountQuery)['totalPaid'] ?? 0;

// Fetch total pending amount
$pendingAmountQuery = mysqli_query($connection, "SELECT SUM(amount) AS totalPending 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE t.status = 'pending' AND u.id = $user_id");
$pendingAmount = mysqli_fetch_assoc($pendingAmountQuery)['totalPending'] ?? 0;

// Fetch total failed amount
$failedAmountQuery = mysqli_query($connection, "SELECT SUM(amount) AS totalFailed 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE t.status = 'failed' AND u.id = $user_id");
$failedAmount = mysqli_fetch_assoc($failedAmountQuery)['totalFailed'] ?? 0;

?>

  
  <div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
  <h6 class="fw-semibold mb-0">Dashboard</h6>
  <ul class="d-flex align-items-center gap-2">
    <li class="fw-medium">
      <a href="dashboard" class="d-flex align-items-center gap-1 hover-text-primary">
        <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
        Dashboard
      </a>
    </li>
    <li>-</li>
    <li class="fw-medium">Home</li>
  </ul>
</div>
    <div class="row row-cols-xxxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Transactions</p>
                <h6 class="mb-0"><?php echo $allCount?></h6>
              </div>
              <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Amount</p>
                <h6 class="mb-0">€<?php echo $paidAmount?></h6>
              </div>
              <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="fa-solid:award" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            
          </div>
        </div><!-- card end -->
      </div>
    </div>
<!-- </div> -->
  </div>
<?php

//including header
include("./include/footer.php");

?>