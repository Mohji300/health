<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Management System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- FontAwesome for sidebar & header icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        /* Additional custom styles for enrollment tracker */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Roboto, system-ui;
            padding-bottom: 2rem;
        }
        .tracker-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
        .circle-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            height: 100%;
        }
        .circle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .grade-circle {
            width: 140px;
            height: 140px;
            margin: 0 auto 1rem;
            background: #f8f9fc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 3px solid #667eea;
            transition: all 0.2s;
        }
        .circle-numbers {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.2rem;
            font-size: 1.8rem;
            font-weight: 800;
            color: #2c3e66;
        }
        .circle-numbers .zero {
            color: #6c757d;
        }
        .circle-numbers .total {
            color: #2c3e66;
        }
        .circle-numbers .divider {
            font-size: 1.6rem;
            font-weight: 600;
            color: #adb5bd;
            margin: 0 0.1rem;
        }
        .grade-label {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        .btn-action {
            border-radius: 40px;
            padding: 0.3rem 1rem;
            margin: 0 0.2rem;
            font-weight: 600;
        }
        .progress-status {
            font-size: 0.8rem;
            background: #eef2ff;
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            margin-top: 0.5rem;
        }
        .summary-card {
            background: white;
            border-radius: 24px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .chart-container, .beneficiary-container {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        .filter-btn-group .btn {
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            margin: 0 0.3rem;
        }
        .filter-btn-group .btn-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .modal-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.5rem 1.8rem;
        }
        .beneficiary-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-card {
            flex: 1;
            min-width: 150px;
            background: #f8f9fa;
            border-radius: 20px;
            padding: 1rem;
            text-align: center;
            border-left: 5px solid;
        }
        .student-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .list-group-item {
            background-color: #f8f9fa;
            margin-bottom: 5px;
            border-radius: 8px;
            cursor: pointer;
        }
        .list-group-item.active {
            background-color: #667eea;
            color: white;
        }
        /* Adjust main content to account for sidebar toggle button */
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s;
            padding: 1rem;
        }
        @media (min-width: 768px) {
            .main-content.shifted {
                margin-left: 250px;
            }
        }
        .card.bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>

<!-- Sidebar (exactly as provided) -->
<?php
// This is the sidebar snippet – it will be included exactly.
// Note: The sidebar uses $this->session, base_url, etc. So it must be inside the CI view.
?>
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

        <!-- Enrollment -->
         <?php if ($is_admin || $is_regular_user): ?>
        <a href="<?php echo site_url('enrollment'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('enrollment', $current_uri) ? 'active' : ''; ?>">
          <i class="fas fa-user-graduate"></i>
          <span class="main-sidebar-text"> Enrollment</span>
        </a>
        <?php endif; ?>

        <!-- Show to Admin and Regular Users only -->
        <?php if ($is_admin || $is_regular_user): ?>
        <a href="<?php echo site_url('users'); ?>"
          class="nav-link rounded-2 mb-1 <?php echo is_active_page('users', $current_uri) ? 'active' : ''; ?>">
          <i class="fa-solid fa-table-cells"></i>
          <span class="main-sidebar-text"> Consolidated</span>
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
          <i class="fa-solid fa-file-arrow-up"></i>
          <span class="main-sidebar-text"> Upload Nutritional Assessment Template</span>
        </a>
        <?php endif; ?>

        <!-- District Dashboard - Show to Admin and District only -->
        <?php if ($is_admin || $is_district): ?>
        <a href="<?php echo site_url('district_dashboard'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('district_dashboard', $current_uri) ? 'active' : ''; ?>">
          <i class="fa-solid fa-table-cells"></i>
          <span class="main-sidebar-text"> District Consolidated</span>
        </a>
        <?php endif; ?>

        <!-- Division Dashboard - Show to Admin and Division only -->
        <?php if ($is_admin || $is_division): ?>
        <a href="<?php echo site_url('division_dashboard'); ?>"
           class="nav-link rounded-2 mb-1 <?php echo is_active_page('division_dashboard', $current_uri) ? 'active' : ''; ?>">
          <i class="fa-solid fa-table-cells"></i>
          <span class="main-sidebar-text"> Division Consolidated</span>
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

<!-- Main content area -->
<div class="main-content" id="mainContent">
    <!-- Header (reports header template) -->
    <?php
    // Set variables for the header
    $title = 'Enrollment Tracker';
    $current_filters = [];  // no filters needed for enrollment page
    $reports = [];
    ?>
    <?php
    // Include the header template (the code provided by user)
    // We'll embed it directly as it uses PHP variables.
    ?>
    <?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
    <?php
    // Expected variables (optional):
    // $title (string), $current_filters (array), $reports (array)
    $title = isset($title) ? $title : (isset($title_text) ? $title_text : 'Reports');
    $current_filters = isset($current_filters) ? $current_filters : ([]);
    $reports = isset($reports) ? $reports : ([]);

    // Determine role and whether regular user
    $role = $this->session->userdata('role');
    $reports_base = (in_array($role, ['admin', 'super_admin', 'district', 'division'])) ? 'admin/reports' : 'user/reports';
    $is_regular_user = !in_array($role, ['admin', 'super_admin', 'district', 'division']);
    ?>

    <!-- Reports Header -->
    <div class="card bg-gradient-primary text-white mb-4">
      <div class="card-body">
        <h1 class="h2 font-weight-bold mb-2"><?php echo htmlspecialchars($title); ?></h1>
        <?php if (!empty($current_filters['school_name'])): ?>
          <p class="mb-0 opacity-8">Showing reports for <strong><?php echo htmlspecialchars($current_filters['school_name']); ?></strong></p>
        <?php else: ?>
          <p class="mb-0 opacity-8">View and manage enrollment data across all grades</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($is_regular_user && !empty($current_filters['school_name'])): ?>
      <div class="row mb-4">
        <div class="col-12">
          <div class="card shadow border-left-info">
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                      <i class="fas fa-school text-info fa-2x"></i>
                    </div>
                    <div>
                      <small class="text-muted text-uppercase">School</small>
                      <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($current_filters['school_name']); ?></h5>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                      <i class="fas fa-map-marker-alt text-success fa-2x"></i>
                    </div>
                    <div>
                      <small class="text-muted text-uppercase">School District</small>
                      <h5 class="mb-0 fw-bold">
                        <?php $school_district_display = !empty($reports) ? ($reports[0]->school_district ?? 'N/A') : 'N/A';
                        echo htmlspecialchars($school_district_display);
                        ?>
                      </h5>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                      <i class="fas fa-landmark text-primary fa-2x"></i>
                    </div>
                    <div>
                      <small class="text-muted text-uppercase">Legislative District</small>
                      <h5 class="mb-0 fw-bold">
                        <?php $legislative_district_display = !empty($reports) ? ($reports[0]->legislative_district ?? 'N/A') : 'N/A';
                        echo htmlspecialchars($legislative_district_display);
                        ?>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Enrollment Tracker Content -->
    <div class="tracker-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div>
                <h2 class="fw-bold"><i class="bi bi-person-arms-up"></i> Enrollment Tracker</h2>
                <span class="badge bg-secondary fs-6" id="selectedSchoolYearDisplay">School year: –</span>
            </div>
            <button class="btn btn-outline-secondary" id="resetTotalBtn">
                <i class="bi bi-arrow-repeat"></i> Change Total Enrollment
            </button>
        </div>

        <div id="overallSummary" class="summary-card"></div>
        <div id="circlesGrid" class="row g-4"></div>

        <!-- SBFP Filter -->
        <div class="beneficiary-container mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="fw-semibold"><i class="bi bi-people-fill"></i> SBFP Beneficiaries by Nutrition Status</h4>
                <select id="beneficiaryGradeFilter" class="form-select w-auto">
                    <option value="all">All Grades</option>
                    <option value="kindergarten">Kindergarten</option><option value="sped">SPED</option>
                    <option value="grade_1">Grade 1</option><option value="grade_2">Grade 2</option>
                    <option value="grade_3">Grade 3</option><option value="grade_4">Grade 4</option>
                    <option value="grade_5">Grade 5</option><option value="grade_6">Grade 6</option>
                    <option value="grade_7">Grade 7</option><option value="grade_8">Grade 8</option>
                    <option value="grade_9">Grade 9</option><option value="grade_10">Grade 10</option>
                    <option value="grade_11">Grade 11</option><option value="grade_12">Grade 12</option>
                </select>
            </div>
            <div id="beneficiaryStats" class="beneficiary-stats"></div>
        </div>

        <!-- Comparison Chart -->
        <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="fw-semibold"><i class="bi bi-bar-chart-steps"></i> Nutritional Status Comparison</h4>
                <div class="filter-btn-group" id="filterButtons">
                    <button class="btn btn-outline-primary" data-period="baseline">Baseline</button>
                    <button class="btn btn-outline-primary" data-period="midline">Midline</button>
                    <button class="btn btn-outline-primary" data-period="endline">Endline</button>
                </div>
            </div>
            <canvas id="nutritionChart" width="400" height="200" style="max-height: 400px; width: 100%;"></canvas>
        </div>
        <footer class="text-center mt-4 text-muted small">
            <i class="bi bi-info-circle"></i> Add/Remove changes total enrollment. The "0" is a placeholder for future logic.
        </footer>
    </div>
</div>

<!-- All modals (School Year, Total Enrollment, Add, Remove, Notes) -->
<!-- MODAL: School Year -->
<div class="modal fade" id="schoolYearModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title"><i class="bi bi-calendar"></i> Select School Year</h5></div>
        <div class="modal-body"><input type="text" class="form-control" id="schoolYearInput" placeholder="2025-2026" value="2025-2026"></div>
        <div class="modal-footer"><button class="btn btn-primary btn-primary-custom" id="confirmSchoolYearBtn">Continue</button></div>
    </div></div>
</div>

<!-- MODAL: Total Enrollment -->
<div class="modal fade" id="totalModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Set Total Enrollment per Grade</h5></div>
        <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
            <div class="row g-3">
                <div class="col-md-4"><label>Kindergarten</label><input type="number" class="form-control total-input" id="total_kindergarten" min="0" value="0"></div>
                <div class="col-md-4"><label>SPED</label><input type="number" class="form-control total-input" id="total_sped" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 1</label><input type="number" class="form-control total-input" id="total_grade_1" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 2</label><input type="number" class="form-control total-input" id="total_grade_2" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 3</label><input type="number" class="form-control total-input" id="total_grade_3" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 4</label><input type="number" class="form-control total-input" id="total_grade_4" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 5</label><input type="number" class="form-control total-input" id="total_grade_5" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 6</label><input type="number" class="form-control total-input" id="total_grade_6" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 7</label><input type="number" class="form-control total-input" id="total_grade_7" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 8</label><input type="number" class="form-control total-input" id="total_grade_8" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 9</label><input type="number" class="form-control total-input" id="total_grade_9" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 10</label><input type="number" class="form-control total-input" id="total_grade_10" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 11</label><input type="number" class="form-control total-input" id="total_grade_11" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 12</label><input type="number" class="form-control total-input" id="total_grade_12" min="0" value="0"></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary btn-primary-custom" id="confirmTotalBtn">Set Enrollment</button></div>
    </div></div>
</div>

<!-- MODAL: Add Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Add Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="addGradeId">
            <div class="mb-3"><label>Student Name</label><input type="text" class="form-control" id="studentName" required></div>
            <div class="mb-3"><label>Reason for adding</label><textarea class="form-control" id="addReason" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="confirmAddBtn">Add Student</button></div>
    </div></div>
</div>

<!-- MODAL: Remove Student -->
<div class="modal fade" id="removeStudentModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Remove Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="removeGradeId">
            <p>Select a student to remove:</p>
            <div id="removeStudentList" class="student-list"></div>
            <div class="mt-3"><label>Reason for removal (optional)</label><textarea id="removeReason" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" id="confirmRemoveBtn">Remove Selected</button></div>
    </div></div>
</div>

<!-- MODAL: Notes -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Student Notes</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div id="notesContent"></div></div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ------------------------------------------------------------
    // Enrollment Tracker JavaScript (same as before, but with sidebar integration)
    // ------------------------------------------------------------
    const grades = [
        { id: 'kindergarten', label: 'Kindergarten' }, { id: 'sped', label: 'SPED' },
        { id: 'grade_1', label: 'Grade 1' }, { id: 'grade_2', label: 'Grade 2' },
        { id: 'grade_3', label: 'Grade 3' }, { id: 'grade_4', label: 'Grade 4' },
        { id: 'grade_5', label: 'Grade 5' }, { id: 'grade_6', label: 'Grade 6' },
        { id: 'grade_7', label: 'Grade 7' }, { id: 'grade_8', label: 'Grade 8' },
        { id: 'grade_9', label: 'Grade 9' }, { id: 'grade_10', label: 'Grade 10' },
        { id: 'grade_11', label: 'Grade 11' }, { id: 'grade_12', label: 'Grade 12' }
    ];
    let totals = {};
    let students = {};
    let currentSchoolYear = '';

    function getTotalCount(gradeId) { return totals[gradeId] || 0; }
    function getOverallTotal() { let sum = 0; for (let g of grades) sum += getTotalCount(g.id); return sum; }

    function saveToLocalStorage() {
        localStorage.setItem('enrollment_school_year', currentSchoolYear);
        localStorage.setItem('enrollment_totals', JSON.stringify(totals));
        localStorage.setItem('enrollment_students', JSON.stringify(students));
    }
    function loadFromLocalStorage() {
        currentSchoolYear = localStorage.getItem('enrollment_school_year') || '';
        const savedTotals = localStorage.getItem('enrollment_totals');
        const savedStudents = localStorage.getItem('enrollment_students');
        if (savedTotals) totals = JSON.parse(savedTotals);
        if (savedStudents) students = JSON.parse(savedStudents);
        if (currentSchoolYear) document.getElementById('selectedSchoolYearDisplay').innerText = `School year: ${currentSchoolYear}`;
        return currentSchoolYear !== '' && savedTotals !== null;
    }

    function renderOverallSummary() {
        document.getElementById('overallSummary').innerHTML = `<div class="d-flex justify-content-between"><strong>Total Enrolled Students:</strong> ${getOverallTotal()}</div>`;
    }

    function renderCircles() {
        const container = document.getElementById('circlesGrid');
        if (!currentSchoolYear || Object.keys(totals).length === 0) {
            container.innerHTML = `<div class="col-12 text-center text-muted py-5"><i class="bi bi-calendar-x"></i><br>Set school year and total enrollment first.</div>`;
            renderOverallSummary();
            return;
        }
        let html = '';
        for (let grade of grades) {
            const id = grade.id;
            const total = getTotalCount(id);
            html += `
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="circle-card">
                        <div class="grade-circle">
                            <div class="circle-numbers">
                                <span class="zero">0</span>
                                <span class="divider">/</span>
                                <span class="total">${total}</span>
                            </div>
                        </div>
                        <div class="grade-label">${grade.label}</div>
                        <div class="progress-status"><i class="bi bi-person-check"></i> ${total} enrolled</div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-success btn-action add-btn" data-grade="${id}"><i class="bi bi-plus-circle"></i> Add</button>
                            <button class="btn btn-sm btn-warning btn-action remove-btn" data-grade="${id}"><i class="bi bi-dash-circle"></i> Remove</button>
                            <button class="btn btn-sm btn-info btn-action notes-btn" data-grade="${id}"><i class="bi bi-journal-bookmark-fill"></i> Notes</button>
                        </div>
                    </div>
                </div>
            `;
        }
        container.innerHTML = html;
        document.querySelectorAll('.add-btn').forEach(btn => btn.addEventListener('click', () => openAddModal(btn.getAttribute('data-grade'))));
        document.querySelectorAll('.remove-btn').forEach(btn => btn.addEventListener('click', () => openRemoveModal(btn.getAttribute('data-grade'))));
        document.querySelectorAll('.notes-btn').forEach(btn => btn.addEventListener('click', () => openNotesModal(btn.getAttribute('data-grade'))));
        renderOverallSummary();
    }

    let currentAddGrade = null;
    function openAddModal(gradeId) {
        currentAddGrade = gradeId;
        document.getElementById('addGradeId').value = gradeId;
        document.getElementById('studentName').value = '';
        document.getElementById('addReason').value = '';
        new bootstrap.Modal(document.getElementById('addStudentModal')).show();
    }
    document.getElementById('confirmAddBtn').addEventListener('click', () => {
        const gradeId = document.getElementById('addGradeId').value;
        const name = document.getElementById('studentName').value.trim();
        const reason = document.getElementById('addReason').value.trim();
        if (!name) { alert('Please enter student name.'); return; }
        totals[gradeId] = (totals[gradeId] || 0) + 1;
        if (!students[gradeId]) students[gradeId] = [];
        students[gradeId].push({ name, reason, action: 'added', timestamp: new Date().toLocaleString(), removed: false });
        saveToLocalStorage();
        renderCircles();
        updateBeneficiaryStats();
        bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
    });

    let currentRemoveGrade = null;
    let selectedRemoveStudentIndex = null;
    function openRemoveModal(gradeId) {
        currentRemoveGrade = gradeId;
        const listContainer = document.getElementById('removeStudentList');
        const activeStudents = (students[gradeId] || []).filter(s => !s.removed);
        if (activeStudents.length === 0) {
            listContainer.innerHTML = '<div class="alert alert-warning">No active students to remove.</div>';
            document.getElementById('confirmRemoveBtn').disabled = true;
        } else {
            let html = '<div class="list-group">';
            activeStudents.forEach((s, idx) => {
                html += `<button type="button" class="list-group-item list-group-item-action student-remove-item" data-index="${idx}">
                            <strong>${escapeHtml(s.name)}</strong><br><small>Added: ${s.timestamp} - Reason: ${escapeHtml(s.reason || 'N/A')}</small>
                         </button>`;
            });
            html += '</div>';
            listContainer.innerHTML = html;
            document.getElementById('confirmRemoveBtn').disabled = false;
            document.querySelectorAll('.student-remove-item').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.student-remove-item').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    selectedRemoveStudentIndex = parseInt(btn.getAttribute('data-index'));
                });
            });
        }
        document.getElementById('removeReason').value = '';
        selectedRemoveStudentIndex = null;
        new bootstrap.Modal(document.getElementById('removeStudentModal')).show();
    }
    document.getElementById('confirmRemoveBtn').addEventListener('click', () => {
        if (selectedRemoveStudentIndex === undefined || selectedRemoveStudentIndex === null) { alert('Select a student.'); return; }
        const gradeId = currentRemoveGrade;
        const activeStudents = (students[gradeId] || []).filter(s => !s.removed);
        if (selectedRemoveStudentIndex >= activeStudents.length) return;
        const originalIndex = students[gradeId].findIndex(s => s === activeStudents[selectedRemoveStudentIndex]);
        if (originalIndex !== -1) {
            if ((totals[gradeId] || 0) <= 0) { alert('Total enrollment already zero.'); return; }
            totals[gradeId] = (totals[gradeId] || 0) - 1;
            students[gradeId][originalIndex].removed = true;
            students[gradeId][originalIndex].removalReason = document.getElementById('removeReason').value.trim() || 'No reason provided';
            students[gradeId][originalIndex].removalTimestamp = new Date().toLocaleString();
        }
        saveToLocalStorage();
        renderCircles();
        updateBeneficiaryStats();
        bootstrap.Modal.getInstance(document.getElementById('removeStudentModal')).hide();
    });

    function openNotesModal(gradeId) {
        const gradeLabel = grades.find(g => g.id === gradeId)?.label || gradeId;
        const allRecords = students[gradeId] || [];
        if (allRecords.length === 0) {
            document.getElementById('notesContent').innerHTML = '<div class="alert alert-info">No records.</div>';
        } else {
            let html = `<h6>${gradeLabel} - History</h6><div class="list-group">`;
            allRecords.forEach(record => {
                const status = record.removed ? '<span class="badge bg-danger">Removed</span>' : '<span class="badge bg-success">Active</span>';
                html += `<div class="list-group-item"><strong>${escapeHtml(record.name)}</strong> ${status}<br>
                         <small>Action: ${record.action} on ${record.timestamp}<br>Reason: ${escapeHtml(record.reason || 'N/A')}</small>`;
                if (record.removed) html += `<br><small>Removal reason: ${escapeHtml(record.removalReason)} (${record.removalTimestamp})</small>`;
                html += `</div>`;
            });
            html += `</div>`;
            document.getElementById('notesContent').innerHTML = html;
        }
        new bootstrap.Modal(document.getElementById('notesModal')).show();
    }

    function escapeHtml(str) { return str.replace(/[&<>]/g, m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;' }[m])); }

    function openTotalModal() {
        for (let grade of grades) {
            const input = document.getElementById(`total_${grade.id}`);
            if (input) input.value = totals[grade.id] || 0;
        }
        new bootstrap.Modal(document.getElementById('totalModal'), { backdrop: 'static', keyboard: false }).show();
    }
    function setTotalsFromModal() {
        const newTotals = {};
        for (let grade of grades) {
            const val = parseInt(document.getElementById(`total_${grade.id}`).value, 10);
            newTotals[grade.id] = isNaN(val) ? 0 : val;
        }
        totals = newTotals;
        students = {};
        saveToLocalStorage();
        renderCircles();
        updateBeneficiaryStats();
        bootstrap.Modal.getInstance(document.getElementById('totalModal')).hide();
    }

    // SBFP mock data
    const beneficiaryData = {
        kindergarten: [5,12,18,4], sped: [3,8,12,4],
        grade_1:[8,15,22,6], grade_2:[7,14,20,5], grade_3:[6,13,19,5], grade_4:[9,16,24,7],
        grade_5:[8,14,21,6], grade_6:[7,12,18,5], grade_7:[10,18,26,8], grade_8:[9,17,24,7],
        grade_9:[8,15,22,6], grade_10:[7,14,20,5], grade_11:[6,12,18,4], grade_12:[5,10,15,3]
    };
    const categories = ['Severely Wasted', 'Wasted', 'Stunted', 'Severely Stunted'];
    function updateBeneficiaryStats() {
        const filter = document.getElementById('beneficiaryGradeFilter').value;
        let data;
        if (filter === 'all') {
            data = [0,0,0,0];
            for (let g in beneficiaryData) for (let i=0;i<4;i++) data[i] += beneficiaryData[g][i];
        } else data = beneficiaryData[filter] || [0,0,0,0];
        document.getElementById('beneficiaryStats').innerHTML = categories.map((cat, idx) => `<div class="stat-card" style="border-left-color: ${['#dc3545','#fd7e14','#ffc107','#6f42c1'][idx]}"><h5>${data[idx]}</h5><small>${cat}</small></div>`).join('');
    }

    let nutritionChart;
    const periodData = { baseline: [45,112,203,78], midline: [32,98,187,54], endline: [18,65,142,31] };
    function updateChart(period) {
        if (nutritionChart) nutritionChart.destroy();
        const ctx = document.getElementById('nutritionChart').getContext('2d');
        nutritionChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: categories, datasets: [{ label: period.toUpperCase(), data: periodData[period], backgroundColor: ['#dc3545','#fd7e14','#ffc107','#6f42c1'], borderRadius: 8 }] },
            options: { responsive: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Students Count' } } } }
        });
    }
    function setActiveFilter(activePeriod) {
        document.querySelectorAll('#filterButtons .btn').forEach(btn => {
            btn.classList.remove('btn-active', 'btn-primary'); btn.classList.add('btn-outline-primary');
            if (btn.getAttribute('data-period') === activePeriod) btn.classList.add('btn-active', 'btn-primary');
        });
    }

    // Initialization
    function showSchoolYearModal() {
        new bootstrap.Modal(document.getElementById('schoolYearModal'), { backdrop: 'static', keyboard: false }).show();
    }
    document.getElementById('confirmSchoolYearBtn').addEventListener('click', () => {
        const sy = document.getElementById('schoolYearInput').value.trim();
        if (!sy) { alert('Enter school year'); return; }
        currentSchoolYear = sy;
        document.getElementById('selectedSchoolYearDisplay').innerText = `School year: ${currentSchoolYear}`;
        bootstrap.Modal.getInstance(document.getElementById('schoolYearModal')).hide();
        openTotalModal();
    });
    document.getElementById('resetTotalBtn').addEventListener('click', openTotalModal);
    document.getElementById('confirmTotalBtn').addEventListener('click', setTotalsFromModal);
    document.getElementById('beneficiaryGradeFilter').addEventListener('change', updateBeneficiaryStats);
    document.querySelectorAll('#filterButtons .btn').forEach(btn => btn.addEventListener('click', () => { updateChart(btn.getAttribute('data-period')); setActiveFilter(btn.getAttribute('data-period')); }));

    function init() {
        const hasData = loadFromLocalStorage();
        if (hasData && currentSchoolYear && Object.keys(totals).length > 0) {
            renderCircles();
            updateBeneficiaryStats();
            updateChart('baseline'); setActiveFilter('baseline');
        } else {
            showSchoolYearModal();
            updateChart('baseline'); setActiveFilter('baseline');
            renderCircles();
        }
    }
    init();
</script>

<!-- Optional: adjust main content margin when sidebar toggles (depends on sidebar.js) -->
<script>
    // This small script ensures the main content shifts when sidebar is opened (if needed)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.getElementById('mainContent');
    if (sidebarToggle && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            setTimeout(() => {
                if (document.getElementById('mainSidebar').classList.contains('show')) {
                    mainContent.classList.add('shifted');
                } else {
                    mainContent.classList.remove('shifted');
                }
            }, 100);
        });
    }
</script>
</body>
</html>