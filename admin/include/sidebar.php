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
      if ($_SESSION['role'] == "admin"):
      ?>

        <li>
          <a href="dashboard">
            <iconify-icon icon="mage:home" class="menu-icon"></iconify-icon>
            <span>Dashboard</span>
          </a>
        </li>

        <li>
          <a href="ovri_logs">
            <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
            <span>View Logs</span>
          </a>
        </li>

        <li class="dropdown">
          <a href="javascript:void(0)">
            <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
            <span>Transations</span>
          </a>
          <ul class="sidebar-submenu">
            <li>
              <a href="viewTransactions"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> View Transations</a>
            </li>
          </ul>
        </li>

     

        <li class="dropdown">
          <a href="javascript:void(0)">
            <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
            <span>Manage Accounts</span>
          </a>
          <ul class="sidebar-submenu">
            <li>
              <a href="createUser"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Create User</a>
            </li>

            <li>
              <a href="viewUsers"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> View Users</a>
            </li>

          </ul>
        </li>


      <?php
      endif;
      ?>


    </ul>
  </div>
</aside>