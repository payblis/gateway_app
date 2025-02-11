<aside class="sidebar">
  <button type="button" class="sidebar-close-btn">
    <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
  </button>
  <div>
    <a href="dashboard" class="sidebar-logo">
      <img src="../assets/images/logo.jpeg" alt="site logo" class="light-logo">
      <img src="../assets/images/light-logo.png" alt="site logo" class="dark-logo">
      <img src="../assets/images/logo-icon.jpeg" alt="site logo" class="logo-icon">
    </a>
  </div>
  <div class="sidebar-menu-area">

    <ul class="sidebar-menu" id="sidebar-menu">
      <?php
      if ($_SESSION['role'] == "merchant"):
      ?>

        <li>
          <a href="dashboard">
            <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
            <span>DashBoard</span>
          </a>
        </li>

      <?php
      endif;
      ?>

      <!-- <li class="sidebar-menu-group-title">Pages</li> -->
      <li>
        <a href="viewTransactions">
          <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
          <span>View Transactions</span>
        </a>
      </li>

    </ul>
  </div>
</aside>