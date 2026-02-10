<?php defined('BASEPATH') OR exit('No direct script access allowed'); 

// Get current assessment type from session or default to baseline
$assessment_type = isset($assessment_type) ? $assessment_type : 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$is_midline = ($assessment_type == 'midline');
$is_endline = ($assessment_type == 'endline');

// Get counts
$baseline_count = isset($baseline_count) ? $baseline_count : 0;
$midline_count = isset($midline_count) ? $midline_count : 0;
$endline_count = isset($endline_count) ? $endline_count : 0;

// Get school filter
$school_level = isset($school_level) ? $school_level : 'all';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SBFP Beneficiaries Master List - <?php echo ucfirst($assessment_type); ?><?php echo !empty($selected_school) ? ' - ' . htmlspecialchars($selected_school) : ''; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <style>
      #wrapper { display: flex; width: 100%; }
      #sidebar-wrapper { min-width: 220px; max-width: 260px; background: #f8f9fa; border-right: 1px solid #e3e6ea; }
      #page-content-wrapper { flex: 1 1 auto; padding: 20px; }
      @media (max-width: 767px) { #sidebar-wrapper { display: none; } }

      .card { border: none; border-radius: 0.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
      .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
      .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
      .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
      .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
      
      /* Enhanced table styling to match reports */
      .table th { border-top: 1px solid #e3e6f0; font-weight: 600; background-color: #f8f9fc; }
      .table-bordered th, .table-bordered td { border: 1px solid #000000; }
      
      /* Statistics card borders */
      .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
      .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
      .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
      .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
      .text-gray-800 { color: #5a5c69 !important; }
      .text-gray-300 { color: #dddfeb !important; }
      
      /* Assessment switcher styling */
      .assessment-switcher {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 50px;
        padding: 2px;
        display: inline-flex;
        align-items: center;
        margin-left: 20px;
      }
      .assessment-switcher .btn {
        border-radius: 50px;
        padding: 5px 15px;
        font-size: 0.9rem;
        transition: all 0.3s;
        min-width: 90px;
      }
      .assessment-switcher .btn.active {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      }
      .assessment-switcher .btn:not(.active) {
        background: transparent;
        border-color: transparent;
      }
      .assessment-switcher .btn:not(.active):hover {
        background: rgba(0,0,0,0.05);
      }

      /* Fix switcher visibility */
      .assessment-switcher .btn {
        font-weight: 600;
      }

      /* Baseline button colors */
      .assessment-switcher .btn.btn-primary {
        color: #0d6efd;
      }

      .assessment-switcher .btn.btn-info {
      color: #0dcaf0;
      }

      /* Endline button colors */
      .assessment-switcher .btn.btn-success {
        color: #198754;
      }

      /* Active state keeps white text */
      .assessment-switcher .btn.active.btn-primary,
      .assessment-switcher .btn.active.btn-info,
      .assessment-switcher .btn.active.btn-success {
        color: #ffffff !important;
      }
      
      /* Assessment type badge */
      .assessment-badge {
        font-size: 0.8rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
        margin-left: 10px;
      }
      .badge-baseline {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
      }

      /* Add midline badge style */
      .badge-midline {
          background: linear-gradient(45deg, #5ae1f6, #11c2dd);
          color: white;
      }

      .badge-endline {
        background: linear-gradient(45deg, #1cc88a, #13855c);
        color: white;
      }
      
      /* Status badges */
      .badge-severely-wasted { background-color: #dc3545; color: white; }
      .badge-wasted { background-color: #fd7e14; color: white; }
      .badge-normal { background-color: #198754; color: white; }
      .badge-overweight { background-color: #0dcaf0; color: white; }
      .badge-obese { background-color: #6f42c1; color: white; }
      
      /* Print layout for A4 landscape */
      @page { size: A4 landscape; margin: 8mm; }
      @media print {
        html, body { background: #fff; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: Arial, Helvetica, sans-serif; font-size: 8px; margin: 0; padding: 0; line-height: 1.2; }
        .no-print, .btn, .assessment-switcher, .modal, #sidebar-wrapper, .btn-group, .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate { display: none !important; }
        #wrapper { display: block !important; }
        #page-content-wrapper { padding: 4px !important; }
        .card { box-shadow: none !important; border: none !important; padding: 0 !important; }
        table { width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; font-size: 7px !important; margin: 0 !important; }
        th, td { padding: 2px !important; border: 0.5px solid #dee2e6 !important; word-wrap: break-word; white-space: nowrap; line-height: 1.1; }
        .table thead { background: #f8f9fc !important; -webkit-print-color-adjust: exact; }
        .table-primary { background-color: #f1f5f9 !important; }
        h3 { font-size: 10px; margin: 0 0 2px 0; font-weight: bold; }
        p { font-size: 7px; margin: 0 0 4px 0; }
      }

      /* DataTable custom styles */
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_filter,
      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        padding: 10px;
      }
      
      /* Compact table view */
      .compact-table td, .compact-table th {
        padding: 4px 8px;
        font-size: 0.85rem;
      }
      
      /* Column specific widths */
      .col-no { width: 50px; }
      .col-name { width: 200px; }
      .col-sex { width: 60px; }
      .col-grade { width: 120px; }
      .col-dob { width: 100px; }
      .col-weighing { width: 100px; }
      .col-age { width: 80px; }
      .col-weight { width: 80px; }
      .col-height { width: 80px; }
      .col-bmi { width: 80px; }
      .col-ns { width: 120px; }
      .col-hfa { width: 120px; }
      .col-consent { width: 100px; }
      .col-4ps { width: 100px; }
      .col-previous { width: 100px; }
    </style>
  </head>
  <body class="bg-light">
    <div id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>
      <div id="page-content-wrapper">
        <div class="container-fluid py-4">

          <!-- Header Card -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h1 class="h2 font-weight-bold mb-2">SBFP Beneficiaries Master List<?php echo !empty($selected_school) ? ' — ' . htmlspecialchars($selected_school) : ''; ?></h1>
                  <p class="mb-0 opacity-8">School-Based Feeding Program (SY 2025-2026) - <?php echo ucfirst($assessment_type); ?> Data</p>
                </div>
                <div class="d-flex align-items-center">
                  <div class="assessment-switcher">
                      <button class="btn btn-primary btn-sm <?php echo $is_baseline ? 'active' : ''; ?>" 
                              id="switchToBaseline">
                          <i class="fas fa-flag me-1"></i> Baseline
                          <span class="badge bg-light text-primary ms-1"><?= $baseline_count ?></span>
                      </button>
                      <button class="btn btn-info btn-sm <?php echo $is_midline ? 'active' : ''; ?>" 
                              id="switchToMidline">
                          <i class="fas fa-flag me-1"></i> Midline
                          <span class="badge bg-light text-info ms-1"><?= $midline_count ?></span>
                      </button>
                      <button class="btn btn-success btn-sm <?php echo $is_endline ? 'active' : ''; ?>" 
                              id="switchToEndline">
                          <i class="fas fa-flag-checkered me-1"></i> Endline
                          <span class="badge bg-light text-success ms-1"><?= $endline_count ?></span>
                      </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assessment Info Card -->
          <div class="alert alert-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="alert-heading mb-1">
                  <i class="fas fa-clipboard-list me-2"></i>
                  Master List Beneficiaries
                  <span class="badge <?php 
                      if ($is_baseline) {
                          echo 'badge-baseline';
                      } elseif ($is_midline) {
                          echo 'badge-midline';
                      } else {
                          echo 'badge-endline';
                      }
                  ?> assessment-badge">
                      <?php echo ucfirst($assessment_type); ?>
                  </span>
                </h5>
                <p class="mb-0">
                  Department of Education | Region V-Bicol | Division: MASBATE PROVINCE
                  <?php if ($is_baseline): ?>
                    | Baseline Assessment (SY 2025-2026)
                  <?php elseif ($is_midline): ?>
                    | Midline Assessment (SY 2025-2026)
                  <?php else: ?>
                    | Endline Assessment (SY 2025-2026)
                  <?php endif; ?>
                </p>
              </div>
              <div class="text-end">
                <div class="btn-group">
                  <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="fas fa-question-circle me-1"></i> Help
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Statistics Cards -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Beneficiaries</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php 
                          if ($is_baseline) echo $baseline_count;
                          elseif ($is_midline) echo $midline_count;
                          else echo $endline_count;
                        ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Normal Nutritional Status</div>
                      <?php 
                        $normal_count = 0;
                        $stats = $this->Sbfp_Beneficiaries_model->get_nutritional_stats($assessment_type);
                        foreach ($stats as $stat) {
                          if ($stat['nutritional_status'] == 'Normal') {
                            $normal_count = $stat['count'];
                            break;
                          }
                        }
                      ?>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $normal_count ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-heart fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Needs Intervention</div>
                      <?php 
                        $intervention_count = 0;
                        foreach ($stats as $stat) {
                          if (in_array($stat['nutritional_status'], ['Severely Wasted', 'Wasted', 'Overweight', 'Obese'])) {
                            $intervention_count += $stat['count'];
                          }
                        }
                      ?>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $intervention_count ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Schools Covered</div>
                      <?php 
                        $schools = $this->Sbfp_Beneficiaries_model->get_schools();
                        $school_count = count($schools);
                      ?>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $school_count ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-school fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Content Card -->
          <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Master List of Beneficiaries
                    <span class="badge <?php 
                        if ($is_baseline) {
                            echo 'badge-baseline';
                        } elseif ($is_midline) {
                            echo 'badge-midline';
                        } else {
                            echo 'badge-endline';
                        }
                    ?> ms-2">
                        <?php echo ucfirst($assessment_type); ?>
                    </span>
                    <?php if ($school_level !== 'all'): ?>
                    <span class="badge bg-secondary ms-2">
                        <i class="fas fa-filter me-1"></i>
                        <?php 
                            if ($school_level === 'elementary') echo 'Elementary';
                            elseif ($school_level === 'secondary') echo 'Secondary';
                            elseif ($school_level === 'integrated_elementary') echo 'Integrated (Elementary)';
                            elseif ($school_level === 'integrated_secondary') echo 'Integrated (Secondary)';
                            else echo 'All Schools'; 
                        ?>
                    </span>
                    <?php endif; ?>
                </h6>
                <div class="no-print">
                    <!-- Export buttons -->
                    <div class="btn-group me-2" role="group">
                        <a href="<?= site_url('sbfp_beneficiaries/export_excel') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i> Export to Excel
                        </a>
                        <a href="<?= site_url('sbfp_beneficiaries/print_report') ?>" target="_blank" class="btn btn-outline-info btn-sm no-print">
                          <i class="fas fa-print me-1"></i> Print Form
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
              <div id="tableContainer" class="table-responsive p-3">
                <!-- SBFP Form Table -->
                <table id="beneficiariesTable" class="table table-bordered table-sm compact-table" style="width:100%">
                  <thead class="table-light">
                    <tr>
                      <th class="col-no">No.</th>
                      <th class="col-name">Name</th>
                      <th class="col-sex">Sex</th>
                      <th class="col-grade">Grade/Section</th>
                      <th class="col-dob">Date of Birth (MM/DD/YYYY)</th>
                      <th class="col-weighing">Date of Weighing (MM/DD/YYYY)</th>
                      <th class="col-age">Age (Years/Months)</th>
                      <th class="col-weight">Weight (Kg)</th>
                      <th class="col-height">Height (cm)</th>
                      <th class="col-bmi">BMI</th>
                      <th class="col-ns">Nutritional Status</th>
                      <th class="col-hfa">Height for Age</th>
                      <th class="col-consent">Parent's Consent for Milk</th>
                      <th class="col-4ps">Participation in 4Ps</th>
                      <th class="col-previous">Beneficiary of SBFP in Previous Years</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $counter = 1;
                    if (!empty($beneficiaries)):
                    foreach ($beneficiaries as $student): 
                      // Determine nutritional status badge class
                      $ns_class = '';
                      switch($student['nutritional_status']) {
                        case 'Severely Wasted': $ns_class = 'badge-severely-wasted'; break;
                        case 'Wasted': $ns_class = 'badge-wasted'; break;
                        case 'Normal': $ns_class = 'badge-normal'; break;
                        case 'Overweight': $ns_class = 'badge-overweight'; break;
                        case 'Obese': $ns_class = 'badge-obese'; break;
                        default: $ns_class = 'badge-secondary';
                      }
                      
                      // Determine HFA badge class
                      $hfa_class = '';
                      switch($student['height_for_age']) {
                        case 'Severely Stunted': $hfa_class = 'badge-severely-wasted'; break;
                        case 'Stunted': $hfa_class = 'badge-wasted'; break;
                        case 'Normal': $hfa_class = 'badge-normal'; break;
                        case 'Tall': $hfa_class = 'badge-info'; break;
                        default: $hfa_class = 'badge-secondary';
                      }
                      
                      // Format dates
                      $dob = date('m/d/Y', strtotime($student['birthday']));
                      $weighing_date = date('m/d/Y', strtotime($student['date_of_weighing']));
                    ?>
                    <tr>
                      <td class="text-center"><?= $counter++ ?></td>
                      <td><?= htmlspecialchars($student['name']) ?></td>
                      <td class="text-center"><?= $student['sex'] ?></td>
                      <td><?= htmlspecialchars($student['grade_level']) ?>/<?= htmlspecialchars($student['section']) ?></td>
                      <td class="text-center"><?= $dob ?></td>
                      <td class="text-center"><?= $weighing_date ?></td>
                      <td class="text-center"><?= htmlspecialchars($student['age']) ?></td>
                      <td class="text-center"><?= number_format($student['weight'], 1) ?></td>
                      <td class="text-center"><?= number_format($student['height'], 1) ?></td>
                      <td class="text-center"><?= number_format($student['bmi'], 1) ?></td>
                      <td class="text-center">
                        <span class="badge <?= $ns_class ?>"><?= $student['nutritional_status'] ?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge <?= $hfa_class ?>"><?= $student['height_for_age'] ?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-info"></span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-warning"></span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-success"></span>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php else: ?>
                    <!-- Empty state with correct number of columns (15) -->
                    <tr>
                      <td colspan="15" class="text-center text-muted py-4">
                        <i class="fas fa-database fa-2x mb-3 d-block"></i>
                        No <?php echo $assessment_type; ?> data found for the selected filter.
                        <?php if ($assessment_type == 'midline'): ?>
                          You need to create midline assessments first.
                        <?php elseif ($assessment_type == 'endline'): ?>
                          You need to create endline assessments first.
                        <?php else: ?>
                          You need to create baseline assessments first.
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="helpModalLabel">
              <i class="fas fa-question-circle me-2"></i>SBFP Form Help Guide
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <h6><i class="fas fa-list-alt me-2"></i>SBFP Master List Beneficiaries</h6>
            <p>This form contains the complete list of beneficiaries for the School-Based Feeding Program (SBFP) for SY 2025-2026.</p>
            
            <div class="row mt-3">
              <div class="col-md-6">
                <h6><span class="badge badge-baseline me-2">Baseline</span> Assessment</h6>
                <p>Initial measurements taken at the beginning of the SBFP. Used as reference point.</p>
                
                <h6><span class="badge badge-midline me-2">Midline</span> Assessment</h6>
                <p>Intermediate measurements to track progress during the SBFP.</p>
                
                <h6><span class="badge badge-endline me-2">Endline</span> Assessment</h6>
                <p>Final measurements to evaluate the SBFP's effectiveness.</p>
              </div>
              <div class="col-md-6">
                <h6><i class="fas fa-columns me-2"></i>Column Definitions:</h6>
                <ul class="small">
                  <li><strong>Nutritional Status:</strong> Based on BMI-for-age classification</li>
                  <li><strong>Height for Age:</strong> Stunting assessment indicator</li>
                  <li><strong>Parent's Consent:</strong> Permission for milk supplementation</li>
                  <li><strong>4Ps Participation:</strong> Pantawid Pamilyang Pilipino Program beneficiary</li>
                  <li><strong>Previous SBFP:</strong> Beneficiary in previous school years</li>
                </ul>
              </div>
            </div>
            
            <div class="alert alert-info mt-3">
              <i class="fas fa-lightbulb me-2"></i>
              <strong>Export Tip:</strong> Use the "Export to Excel" button to download data in SBFP Form 1A format, or "Print List" for physical copies.
            </div>
          </div>  
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
      $(document).ready(function() {
          // Check if table has data
          var hasData = <?php echo !empty($beneficiaries) ? 'true' : 'false'; ?>;
          
          if (hasData) {
              // Initialize DataTable only if there's data
              $('#beneficiariesTable').DataTable({
                  "pageLength": 25,
                  "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                  "order": [[0, 'asc']],
                  "responsive": true,
                  "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                  "language": {
                      "search": "Search beneficiary:",
                      "lengthMenu": "Show _MENU_ entries",
                      "info": "Showing _START_ to _END_ of _TOTAL_ beneficiaries",
                      "paginate": {
                          "previous": "<i class='fas fa-chevron-left'></i>",
                          "next": "<i class='fas fa-chevron-right'></i>"
                      },
                      "emptyTable": "No <?php echo $assessment_type; ?> data available"
                  }
              });
          } else {
              // If no data, just show the table without DataTables
              // This prevents the column count error
              $('#beneficiariesTable').addClass('table-striped');
          }
          
          // Switch between Baseline, Midline, Endline views
          $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
          $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
          $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
          
          function switchAssessmentType(type) {
              // Show loading state
              var activeBtn;
              if (type === 'baseline') {
                  activeBtn = $('#switchToBaseline');
                  $('#switchToMidline').prop('disabled', true);
                  $('#switchToEndline').prop('disabled', true);
              } else if (type === 'midline') {
                  activeBtn = $('#switchToMidline');
                  $('#switchToBaseline').prop('disabled', true);
                  $('#switchToEndline').prop('disabled', true);
              } else {
                  activeBtn = $('#switchToEndline');
                  $('#switchToBaseline').prop('disabled', true);
                  $('#switchToMidline').prop('disabled', true);
              }
              
              var originalHtml = activeBtn.html();
              activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
              activeBtn.prop('disabled', true);
              
              $.ajax({
                  url: '<?php echo site_url("sbfp_beneficiaries/set_assessment_type"); ?>',
                  method: 'POST',
                  data: { assessment_type: type },
                  dataType: 'json',
                  success: function(response) {
                      if (response.success) {
                          window.location.reload();
                      } else {
                          alert('Error: ' + response.message);
                          activeBtn.html(originalHtml);
                          activeBtn.prop('disabled', false);
                          $('.assessment-switcher .btn').prop('disabled', false);
                      }
                  },
                  error: function(xhr, status, error) {
                      alert('Error switching assessment type. Please try again.');
                      activeBtn.html(originalHtml);
                      activeBtn.prop('disabled', false);
                      $('.assessment-switcher .btn').prop('disabled', false);
                  }
              });
          }
          
          // Print functionality
          $('#btnPrint').click(function() {
              window.print();
          });
      });
    </script>
  </body>
</html>