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
                <p class="fw-medium text-primary-light mb-1">Paid Amount</p>
                <h6 class="mb-0">$<?php echo $paidAmount?></h6>
              </div>
              <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="fa-solid:award" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Pending Amount</p>
                <h6 class="mb-0">$<?php echo $pendingAmount?></h6>
              </div>
              <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 50 50" id="pending" fill="#ffffff">
                      <path d="M25 1c-2.872 0-5.68.502-8.348 1.492l.696 1.875A21.921 21.921 0 0 1 25 3c12.131 0 22 9.869 22 22s-9.869 22-22 22S3 37.131 3 25a22.001 22.001 0 0 1 8-16.958V15h2V5H3v2h6.126A24.005 24.005 0 0 0 1 25c0 13.233 10.767 24 24 24s24-10.767 24-24S38.233 1 25 1z"></path>
                      <path d="M19 33h-2v2h16v-2h-2v-3.414L26.414 25 31 20.414V17h2v-2H17v2h2v3.414L23.586 25 19 29.586V33zm2-13.414V17h8v2.586l-4 4-4-4zm4 6.828 4 4V33h-8v-2.586l4-4zM19 39h2v2h-2zM24 39h2v2h-2zM29 39h2v2h-2z"></path>
                    </svg>
              </div>
            </div>
            
          </div>
        </div><!-- card end -->
      </div>
   
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-5 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Failed Amount</p>
                <h6 class="mb-0">$<?php echo $failedAmount?></h6>
              </div>
              <div class="w-50-px h-50-px bg-red rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="fa6-solid:file-invoice-dollar" class="text-white text-2xl mb-0"></iconify-icon>
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