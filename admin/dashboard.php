<?php
//including header
include("./include/header.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$allMerchantQuery =   mysqli_query($connection,"SELECT COUNT(*) AS user_count FROM `users` where role='merchant'");
$allMerchant = mysqli_fetch_assoc($allMerchantQuery)['user_count'] ?? 0;

// Fetch total transaction count
$allCountQuery = mysqli_query($connection, "SELECT COUNT(*) AS allCount 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key");
$allCount = mysqli_fetch_assoc($allCountQuery)['allCount'] ?? 0;

// Fetch total paid amount
$paidAmountQuery = mysqli_query($connection, "SELECT SUM(amount) AS totalPaid 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE t.status = 'paid'");
$paidAmount = mysqli_fetch_assoc($paidAmountQuery)['totalPaid'] ?? 0;

// Fetch total pending amount
$pendingAmountQuery = mysqli_query($connection, "SELECT SUM(amount) AS totalPending 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE t.status = 'pending'");
$pendingAmount = mysqli_fetch_assoc($pendingAmountQuery)['totalPending'] ?? 0;

// Fetch total failed amount
$failedAmountQuery = mysqli_query($connection, "SELECT SUM(amount) AS totalFailed 
    FROM `transactions` AS t 
    INNER JOIN `users` AS u ON t.token = u.api_key 
    WHERE t.status = 'failed'");
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
                <p class="fw-medium text-primary-light mb-1">Total Merchants</p>
                <h6 class="mb-0"><?php echo $allMerchant?></h6>
              </div>
              <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Transactions</p>
                <h6 class="mb-0"><?php echo $allCount?></h6>
              </div>
              <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
               <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 48 48" viewBox="0 0 48 48" id="dollar" fill="#ffffff">
                  <path d="M28,19c0.55,0,1-0.45,1-1s-0.45-1-1-1h-3v-1c0-0.55-0.45-1-1-1s-1,0.45-1,1v1c-2.21,0-4,1.79-4,4s1.79,4,4,4v4h-3
                		c-0.55,0-1,0.45-1,1s0.45,1,1,1h3v1c0,0.55,0.45,1,1,1s1-0.45,1-1v-1c2.21,0,4-1.79,4-4s-1.79-4-4-4v-4H28z M23,23
                		c-1.1,0-2-0.9-2-2s0.9-2,2-2V23z M27,27c0,1.1-0.9,2-2,2v-4C26.1,25,27,25.9,27,27z"></path>
                  <path d="M24,3C12.42,3,3,12.42,3,24s9.42,21,21,21c11.58,0,21-9.42,21-21S35.58,3,24,3z M24,43C13.52,43,5,34.48,5,24S13.52,5,24,5
                		s19,8.52,19,19S34.48,43,24,43z"></path>
                  <path d="M24,7C14.63,7,7,14.63,7,24s7.63,17,17,17s17-7.63,17-17S33.37,7,24,7z M24,39c-8.27,0-15-6.73-15-15S15.73,9,24,9
                		c8.27,0,15,6.73,15,15S32.27,39,24,39z"></path>
                </svg>
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