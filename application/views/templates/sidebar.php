<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- SIDEBAR + TOGGLE (self-contained) -->
<style>
/* --- Sidebar base --- */
#mainSidebar {
  background: #ffffff !important;
  color: #1f2937 !important; /* dark gray text */
  z-index: 1100;
  width: 260px;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: transform .25s ease, width .25s ease;
}

/* collapse (desktop) */
#mainSidebar.collapsed {
  transform: translateX(-100%);
}

/* main content spacer */
#sidebarSpacer {
  width: 260px;
  flex-shrink: 0;
  transition: width .25s ease;
}
#sidebarSpacer.collapsed {
  width: 0 !important;
}

/* text fade on collapse */
.main-sidebar-text {
  transition: opacity .15s ease;
}
#mainSidebar.collapsed .main-sidebar-text {
  opacity: 0;
  visibility: hidden;
}

/* NAV LINKS */
#mainSidebar .nav-link {
  color: #4b5563; /* slate-600 */
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 500;
  padding: 10px 12px;
  border-radius: 8px;
  transition: all .15s ease;
}

#mainSidebar .nav-link:hover {
  background: #f3f4f6; /* light hover */
  color: #111827; /* dark hover text */
  transform: translateX(4px);
}

#mainSidebar .nav-link.active {
  background: #e0e7ff; /* light indigo */
  color: #4338ca; /* indigo-700 */
  border-left: 3px solid #6366f1;
}

/* section titles */
#mainSidebar h6 {
  color: #6b7280 !important; /* gray-500 */
}

/* header avatar badge */
.bg-gradient-primary {
  background: linear-gradient(135deg,#6366f1,#4f46e5) !important;
}

/* avatar circle for header image */
.avatar-circle { width:150px; height:150px; border-radius:50%; object-fit:cover; display:inline-block; }

/* bottom profile */
.sidebar-bottom {
  margin-top: auto;
  padding: 14px 18px;
  border-top: 1px solid #e5e7eb; /* gray-200 */
}

/* profile text colors */
.sidebar-bottom h6 {
  color: #1f2937 !important;
}
.sidebar-bottom small {
  color: #6b7280 !important;
}

/* scrollbar */
#mainSidebar nav::-webkit-scrollbar {
  width: 5px;
}
#mainSidebar nav::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 8px;
}

/* Mobile */
@media (max-width: 767.98px) {
  #mainSidebar {
    transform: translateX(-100%);
  }
  #mainSidebar.show {
    transform: translateX(0);
  }
  #sidebarSpacer {
    display: none;
  }
}
</style>


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
      <h5 class="fw-semibold text-gray-400 mb-1">School System</h5>
      <small class="text-gray-400">Academic Management</small>
    </div>
  </div>

  <?php
  $user_role = $this->session->userdata('role') ?? 'user';
  $is_admin = in_array($user_role, ['super_admin','admin']);
  $is_district = ($user_role == 'district');
  $is_division = ($user_role == 'division');
  $is_regular_user = ($user_role == 'user');

  // Get the current URI segment for better active state detection
  $current_uri = $this->uri->uri_string();
  $current_url = current_url();

  // FIX: Check if function already exists before declaring
  if (!function_exists('is_active_page')) {
      function is_active_page($uri_segment, $current_uri) {
          if (empty($uri_segment)) {
              return $current_uri == '' || $current_uri == $uri_segment;
          }
          return strpos($current_uri, $uri_segment) === 0 || $current_uri == $uri_segment;
      }
  }
  ?>

  <!-- NAV -->
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
        <a href="<?php echo site_url('user'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('user', $current_uri) ? 'active' : ''; ?>">
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

        <!-- SBFP Beneficiaries - Show to ALL ROLES (Admin, District, Division, Regular User) -->
        <a href="<?php echo site_url('sbfp-beneficiaries'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('sbfp-beneficiaries', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-users"></i>
          <span class="main-sidebar-text"> SBFP Beneficiaries</span>
        </a>

        <a href="<?php echo site_url('archive'); ?>" 
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('archive', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-archive"></i>
          <span class="main-sidebar-text"> Archive</span>
        </a>

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
          <a href="<?php echo site_url('reports'); ?>"
             class="nav-link rounded-2 mb-1 <?php echo is_active_page('reports', $current_uri) ? 'active' : ''; ?>">
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
    </ul>
  </nav>

    <!-- Profile -->
  <div class="px-3 py-3 border-bottom border-gray-700 d-flex align-items-center">

    <div class="flex-grow-1 main-sidebar-text">
      <h6 class="mb-0 text-black fw-medium">
        <?php 
          // Get school name from auth_user data or session
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

    <div class="dropdown main-sidebar-text">
      <button class="btn btn-sm btn-link text-gray-400 p-0" data-bs-toggle="dropdown">
        <i class="fas fa-ellipsis-v"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-dark border border-gray-700 shadow">
        <li><a class="dropdown-item" href="<?php echo site_url('profile'); ?>"><i class="fas fa-user me-2"></i>Profile</a></li>
        <li><hr class="dropdown-divider border-gray-700"></li>
        <li><a class="dropdown-item text-danger" href="<?php echo site_url('logout'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Spacer for main content -->
<div id="sidebarSpacer"></div>

<!-- Minimal JS -->
<script>
(function(){
  const btn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('mainSidebar');
  const spacer = document.getElementById('sidebarSpacer');

  if(!btn || !sidebar) return;

  btn.addEventListener('click', function(){

    if(window.innerWidth < 768){
      sidebar.classList.toggle('show');
      return;
    }

    sidebar.classList.toggle('collapsed');
    spacer.classList.toggle('collapsed');
  });

  document.addEventListener('click', function(ev){
    if(window.innerWidth >= 768) return;
    if(!sidebar.classList.contains('show')) return;

    if(!sidebar.contains(ev.target) && !btn.contains(ev.target)){
      sidebar.classList.remove('show');
    }
  });

  window.addEventListener('resize', function(){
    if(window.innerWidth >= 768){
      sidebar.classList.remove('show');
    }
  });
})();
</script>