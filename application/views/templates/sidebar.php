<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<link rel="stylesheet" href="<?php echo base_url('assets/css/sidebar.css'); ?>">

<!-- Toggle Button -->
<button id="sidebarToggle"
        class="btn btn-dark position-fixed top-0 start-0 m-2"
        style="z-index: 1200;">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar HTML -->
<div id="mainSidebar" role="navigation" aria-label="Main sidebar">

  <!-- Header -->
  <div class="text-center py-4 border-bottom border-gray-700 px-2" style="margin-top:0.5in;">
        <img src="<?php echo base_url('assets/images/sdo.png'); ?>" 
          alt="School Logo" 
          class="mb-3 avatar-circle">
    <div class="main-sidebar-text">
      <h5 class="fw-semibold text-gray-400 mb-1">School Health Management <br> Information System</h5>
    </div>
  </div>

  <?php
  $user_role = $this->session->userdata('role') ?? 'user';
  $is_admin = in_array($user_role, ['super_admin','admin']);
  $is_district = ($user_role == 'district');
  $is_division = ($user_role == 'division');
  $is_regular_user = ($user_role == 'user');

  $current_uri = $this->uri->uri_string();
  $current_url = current_url();

  if (!function_exists('is_active_page')) {
      function is_active_page($uri_segment, $current_uri) {
          if (empty($uri_segment)) {
              return $current_uri == '' || $current_uri == $uri_segment;
          }
          return strpos($current_uri, $uri_segment) === 0 || $current_uri == $uri_segment;
      }
  }
  ?>

  <nav class="flex-grow-1 overflow-auto py-3">
    <ul class="nav flex-column px-2">
        

      <?php if ($is_admin): ?>
      <li class="nav-item mb-2 px-2">
        <h6 class="text-uppercase text-gray-400 small fw-semibold mb-2 main-sidebar-text">Administration</h6>
        <a href="<?php echo site_url('superadmin'); ?>"
           class="nav-link rounded-2 <?php echo is_active_page('superadmin', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-tachometer-alt"></i>
          <span class="main-sidebar-text"> Admin Dashboard</span>
        </a>
      </li>
      <?php endif; ?>

      <li class="nav-item mb-2 px-2">
        <h6 class="text-uppercase text-gray-400 small fw-semibold mb-2 main-sidebar-text">Quick Access</h6>

        <!-- Show to Admin and Regular Users only -->
        <?php if ($is_admin || $is_regular_user): ?>
        <a href="<?php echo site_url('users'); ?>"
          class="nav-link rounded-2 mb-1 <?php echo is_active_page('users', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-user"></i>
          <span class="main-sidebar-text"> User Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- Assessments - Only for Admin -->
        <?php if ($is_admin): ?>
        <a href="<?php echo site_url('assessments'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('assessments', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-clipboard-list"></i>
          <span class="main-sidebar-text"> Assessments</span>
        </a>
        <?php endif; ?>

        <!-- SBFP Dashboard - Show to Admin and Regular Users only -->
        <?php if ($is_admin || $is_regular_user): ?>
        <a href="<?php echo site_url('sbfp'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo $current_uri == 'sbfp' || $current_uri == '' ? 'active' : ''; ?>">
          <i class="fas fa-chart-pie"></i>
          <span class="main-sidebar-text"> SBFP Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- District Dashboard - Show to Admin and District only -->
        <?php if ($is_admin || $is_district): ?>
        <a href="<?php echo site_url('district_dashboard'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('district_dashboard', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-building"></i>
          <span class="main-sidebar-text"> District Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- Division Dashboard - Show to Admin and Division only -->
        <?php if ($is_admin || $is_division): ?>
        <a href="<?php echo site_url('division_dashboard'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('division_dashboard', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-sitemap"></i>
          <span class="main-sidebar-text"> Division Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- REPORTS - Role Specific -->
        <?php if ($is_admin): ?>
          <!-- Admin sees Admin Reports -->
          <a href="<?php echo site_url('admin/reports'); ?>"
             class="nav-link rounded-2 mb-1 <?php echo is_active_page('admin/reports', $current_uri) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span class="main-sidebar-text"> Admin Reports</span>
          </a>
        <?php elseif ($is_regular_user): ?>
          <!-- Regular User sees User Reports -->
          <a href="<?php echo site_url('user/reports'); ?>"
             class="nav-link rounded-2 mb-1 <?php echo is_active_page('user/reports', $current_uri) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span class="main-sidebar-text"> Reports</span>
          </a>
        <?php elseif ($is_district): ?>
          <!-- District sees District Reports -->
          <a href="<?php echo site_url('district/reports'); ?>"
             class="nav-link rounded-2 mb-1 <?php echo is_active_page('district/reports', $current_uri) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span class="main-sidebar-text"> District Reports</span>
          </a>
        <?php elseif ($is_division): ?>
          <!-- Division sees Division Reports -->
          <a href="<?php echo site_url('division/reports'); ?>"
             class="nav-link rounded-2 mb-1 <?php echo is_active_page('division/reports', $current_uri) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span class="main-sidebar-text"> Division Reports</span>
          </a>
        <?php endif; ?>
      </li>

      <!-- DATA MANAGEMENT - Admin Only -->
      <?php if ($is_admin): ?>
      <li class="nav-item mb-2 px-2">
        <h6 class="text-uppercase text-gray-400 small fw-semibold mb-2 main-sidebar-text">Data Management</h6>

        <a href="<?php echo site_url('excel_upload'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('excel_upload', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-file-excel"></i>
          <span class="main-sidebar-text"> Excel Upload</span>
        </a>

        <a href="<?php echo site_url('superadmin/add-user'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('superadmin/add-user', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-user-plus"></i>
          <span class="main-sidebar-text"> Add User</span>
        </a>

        <a href="<?php echo site_url('nutritional_upload'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('nutritional_upload', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-upload"></i>
          <span class="main-sidebar-text"> Nutritional Data</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- ADMIN REPORT TOOLS - Admin Only -->
      <?php if ($is_admin): ?>
      <li class="nav-item mb-2 px-2">
        <h6 class="text-uppercase text-gray-400 small fw-semibold mb-2 main-sidebar-text">Report Tools</h6>
        
        <!-- Admin Statistics -->
        <a href="<?php echo site_url('admin/reports/statistics'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('admin/reports/statistics', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-chart-bar"></i>
          <span class="main-sidebar-text"> Statistics</span>
        </a>
        
        <!-- Admin Export -->
        <a href="<?php echo site_url('admin/reports/export'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('admin/reports/export', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-download"></i>
          <span class="main-sidebar-text"> Export Data</span>
        </a>
        
        <!-- Admin Export All Students -->
        <a href="<?php echo site_url('admin/reports/export_all_students'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('admin/reports/export_all_students', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-file-export"></i>
          <span class="main-sidebar-text"> Export All Students</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- SBFP Beneficiaries - Show to ALL ROLES (Admin, District, Division, Regular User) -->
      <li class="nav-item mb-2 px-2">
        <a href="<?php echo site_url('sbfp_beneficiaries'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('sbfp_beneficiaries', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-users"></i>
          <span class="main-sidebar-text"> SBFP Beneficiaries</span>
        </a>

        <a href="<?php echo site_url('archive'); ?>" 
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('archive', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-archive"></i>
          <span class="main-sidebar-text"> Archive</span>
        </a>
      </li>

    </ul>
  </nav>

  <!-- Profile -->
  <div class="px-3 py-3 border-bottom border-gray-700">
    <button class="profile-button w-100 mb-3" onclick="window.location.href='<?php echo site_url('profile'); ?>'" style="background: none; border: none; text-align: left; padding: 0;">
      <div class="d-flex align-items-center justify-content-between">
        <div class="main-sidebar-text">
          <h6 class="mb-0 text-black fw-medium">
            <?php 
              $school_name = '';
              if (isset($auth_user) && isset($auth_user['school_name'])) {
                $school_name = $auth_user['school_name'];
              } elseif ($this->session->userdata('school_name')) {
                $school_name = $this->session->userdata('school_name');
              } elseif (isset($school) && isset($school->name)) {
                $school_name = $school->name;
              }
              
              echo htmlspecialchars($school_name ?: 'School');
            ?>
          </h6>
          <small class="text-gray-400 d-block">
            <?php echo ucfirst(str_replace('_',' ',$this->session->userdata('role') ?? 'user')); ?>
          </small>
        </div>
        <i class="fas fa-chevron-right text-gray-400"></i>
      </div>
    </button>

    <a class="btn btn-outline-danger w-100 text-start" href="<?php echo site_url('logout'); ?>" style="border-radius: 8px;">
      <i class="fas fa-sign-out-alt me-2"></i>Logout
    </a>
  </div>
</div>

<div id="sidebarSpacer"></div>

<script src="<?php echo base_url('assets/js/sidebar.js'); ?>"></script>