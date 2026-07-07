<?php defined('BASEPATH') OR exit('No direct script access allowed'); 

$assessment_type = isset($assessment_type) ? $assessment_type : 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$is_midline = ($assessment_type == 'midline');
$is_endline = ($assessment_type == 'endline');

$baseline_count = isset($baseline_count) ? $baseline_count : 0;
$midline_count = isset($midline_count) ? $midline_count : 0;
$endline_count = isset($endline_count) ? $endline_count : 0;

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
    <link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/sbfp_beneficiaries.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>
      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">

          <!-- Header Card -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h1 class="h2 font-weight-bold mb-2">SBFP Beneficiaries Master List<?php echo !empty($selected_school) ? ' — ' . htmlspecialchars($selected_school) : ''; ?></h1>
                  <p class="mb-0 opacity-8">School-Based Feeding Program - <?php echo ucfirst($assessment_type); ?> Data</p>
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
                  <?php if ($is_baseline): ?>
                    | Baseline Assessment
                  <?php elseif ($is_midline): ?>
                    | Midline Assessment
                  <?php else: ?>
                    | Endline Assessment
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
                                  <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($normal_count) ? $normal_count : 0 ?></div>
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
                                  <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($intervention_count) ? $intervention_count : 0 ?></div>
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
                          <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Height for Age: Tall</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($tall_count) ? $tall_count : 0 ?></div>
                        </div>
                        <div class="col-auto">
                          <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
          </div>

          <!-- Filter Section -->
          <div class="card shadow mb-4">
              <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">
                      <i class="fas fa-filter me-2"></i>Filters
                  </h6>
              </div>
            <div class="card-body">
              <div class="row">
                  <!-- Assessment Type Column -->
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold">
                          <i class="fas fa-chart-line me-1"></i> 
                          Assessment Type
                      </label>
                      <select id="assessmentTypeSelect" class="form-select">
                          <option value="baseline" <?= $is_baseline ? 'selected' : '' ?>>
                              Baseline
                          </option>
                          <option value="midline" <?= $is_midline ? 'selected' : '' ?>>
                              Midline
                          </option>
                          <option value="endline" <?= $is_endline ? 'selected' : '' ?>>
                              Endline
                          </option>
                      </select>
                  </div>

                  <!-- Grade Level Filter -->
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold">
                          <i class="fas fa-graduation-cap me-1"></i>Grade Level
                      </label>
                      <select id="gradeLevelFilter" class="form-select">
                          <option value="">All Grade Levels</option>
                          <?php foreach ($available_grade_levels as $grade): ?>
                              <option value="<?= htmlspecialchars($grade['grade_level']) ?>" 
                                  <?= ($grade_level_filter == $grade['grade_level']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($grade['grade_level']) ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>

                  <!-- School Name Filter (for district/division/admin) -->
                  <?php if (in_array($user_role, ['district', 'division', 'admin'])): ?>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold">
                          <i class="fas fa-school me-1"></i>School Name
                      </label>
                      <select id="schoolNameFilter" class="form-select">
                          <option value="">All Schools</option>
                          <?php foreach ($available_schools as $school): ?>
                              <option value="<?= htmlspecialchars($school['school_name']) ?>" 
                                  <?= ($school_name_filter == $school['school_name']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($school['school_name']) ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <?php endif; ?>

                  <!-- District Filter (for division/admin) -->
                  <?php if (in_array($user_role, ['division', 'admin'])): ?>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold">
                          <i class="fas fa-map-marker-alt me-1"></i>District
                      </label>
                      <select id="districtFilter" class="form-select">
                          <option value="">All Districts</option>
                          <?php foreach ($available_districts as $district_item): ?>
                              <option value="<?= htmlspecialchars($district_item['school_district']) ?>" 
                                  <?= ($district_filter == $district_item['school_district']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($district_item['school_district']) ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <?php endif; ?>

                  <!-- Section Filter -->
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold">
                          <i class="fas fa-layer-group me-1"></i> Section
                      </label>
                      <select id="sectionFilter" class="form-select">
                          <option value="">All Sections</option>
                          <?php foreach ($sections as $sec): ?>
                              <option value="<?= $sec->id ?>" 
                                  <?= ($section_id == $sec->id) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($sec->section) ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>

                  <!-- Filter Buttons -->
                  <div class="col-md-12 mt-2">
                      <button type="button" id="applyFiltersBtn" class="btn btn-primary btn-sm">
                          <i class="fas fa-search me-1"></i> Apply Filters
                      </button>
                      <button type="button" id="clearFiltersBtn" class="btn btn-secondary btn-sm">
                          <i class="fas fa-eraser me-1"></i> Clear Filters
                      </button>
                  </div>
                  </div>
                  
                  <!-- Active Filters Display -->
                  <?php if (!empty($grade_level_filter) || !empty($school_name_filter) || !empty($district_filter)): ?>
                  <div class="mt-3">
                      <span class="text-muted small">Active filters:</span>
                      <?php if (!empty($grade_level_filter)): ?>
                          <span class="badge bg-primary ms-1">
                              Grade: <?= htmlspecialchars($grade_level_filter) ?>
                              <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeFilter('grade')" style="font-size: 8px;"></button>
                          </span>
                      <?php endif; ?>
                      <?php if (!empty($school_name_filter) && in_array($user_role, ['district', 'division', 'admin'])): ?>
                          <span class="badge bg-info ms-1">
                              School: <?= htmlspecialchars($school_name_filter) ?>
                              <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeFilter('school')" style="font-size: 8px;"></button>
                          </span>
                      <?php endif; ?>
                      <?php if (!empty($district_filter) && in_array($user_role, ['division', 'admin'])): ?>
                          <span class="badge bg-success ms-1">
                              District: <?= htmlspecialchars($district_filter) ?>
                              <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeFilter('district')" style="font-size: 8px;"></button>
                          </span>
                      <?php endif; ?>
                  </div>
                  <?php endif; ?>
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
                                $display_level = '';
                                if ($school_level === 'elementary') {
                                    $display_level = 'Elementary';
                                } elseif ($school_level === 'secondary') {
                                    $display_level = 'Secondary';
                                } elseif ($school_level === 'integrated') {
                                    $display_level = 'Integrated';  
                                } elseif ($school_level === 'integrated_elementary') {
                                    $display_level = 'Integrated (Elementary)';
                                } elseif ($school_level === 'integrated_secondary') {
                                    $display_level = 'Integrated (Secondary)';
                                } elseif ($school_level === 'Stand Alone SHS') {
                                    $display_level = 'Stand Alone SHS';
                                } else {
                                    $display_level = ucfirst($school_level);
                                }
                                echo $display_level;
                            ?>
                        </span>
                    <?php endif; ?>
                </h6>
                <div class="no-print">
                    <!-- Export buttons -->
                    <div class="btn-group me-2" role="group">
                      <button type="button" id="exportExcelBtn" class="btn btn-success btn-sm">
                          <i class="fas fa-file-excel me-1"></i> Export to Excel
                      </button>
                      <?php 
                      $print_url = site_url('sbfp_beneficiaries_controller/print_report');
                      $params = [];
                      if (!empty($grade_level_filter)) $params['grade_level'] = $grade_level_filter;
                      if (!empty($school_name_filter)) $params['school_name'] = $school_name_filter;
                      if (!empty($district_filter)) $params['district'] = $district_filter;
                      if (!empty($section_id)) $params['section_id'] = $section_id;
                      if ($params) $print_url .= '?' . http_build_query($params);
                      ?>
                      <form id="printForm" method="POST" action="<?= $print_url ?>" target="_blank" style="display:inline;">
                          <input type="hidden" name="local_flags" id="printLocalFlags" value="">
                          <button type="submit" class="btn btn-outline-info btn-sm no-print">
                              <i class="fas fa-print me-1"></i> Print Form
                          </button>
                      </form>
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
                      <th class="col-classification">Classification of Beneficiary (Primary or Secondary)</th>
                      <th class="col-pregnant">Pregnant (Yes or No)</th>
                      <th class="col-child">With 0-1 Year-Old Child/ren (Yes or No)</th>
                      <th class="col-dewormed">Dewormed (Yes or No)</th>
                      <th class="col-consent">Parent's Consent for Milk</th>
                      <th class="col-4ps">Participation in 4Ps</th>
                      <th class="col-previous">Beneficiary of SBFP in Previous Years</th>
                    </tr>
                  </thead>
                  <tbody>
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
            <p>This form contains the complete list of beneficiaries for the School-Based Feeding Program (SBFP)</p>
            
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
    window.SbfpBeneficiariesConfig = {
        urls: {
            datatable: '<?= site_url("sbfp_beneficiaries_controller/datatable") ?>',
            set_assessment_type: '<?= site_url("sbfp_beneficiaries_controller/set_assessment_type"); ?>',
            set_school_level: '<?= site_url("sbfp_beneficiaries_controller/set_school_level"); ?>',
            set_selected_school: '<?= site_url("sbfp_beneficiaries_controller/set_selected_school"); ?>',
            set_grade_level_filter: '<?= site_url("sbfp_beneficiaries_controller/set_grade_level_filter"); ?>',
            set_school_name_filter: '<?= site_url("sbfp_beneficiaries_controller/set_school_name_filter"); ?>',
            set_district_filter: '<?= site_url("sbfp_beneficiaries_controller/set_district_filter"); ?>',
            clear_filters: '<?= site_url("sbfp_beneficiaries_controller/clear_filters"); ?>',
            export_excel: '<?= site_url("sbfp_beneficiaries_controller/export_excel"); ?>',
            print_report: '<?= site_url("sbfp_beneficiaries_controller/print_report"); ?>',
            update_flag: '<?= site_url("sbfp_beneficiaries_controller/update_flag"); ?>',
            get_sections_by_grade: '<?= site_url("sbfp_beneficiaries_controller/get_sections_by_grade") ?>'
        },
        assessment_type: '<?= $assessment_type; ?>',
        hasData: <?= ($baseline_count > 0) ? 'true' : 'false'; ?>,
        user_role: '<?= isset($user_role) ? $user_role : 'school'; ?>',
        school_id: '<?= isset($school_id) ? $school_id : ''; ?>',
        district: '<?= isset($district) ? $district : ''; ?>',
        school_name: '<?= isset($school_name) ? $school_name : ''; ?>',
        school_level: '<?= isset($school_level) ? $school_level : 'all'; ?>',
        available_schools: <?= json_encode($available_schools); ?>
    };
    </script>
    <script src="<?= base_url(ASSETS_PATH . '/js/sbfp_beneficiaries.js'); ?>"></script>
  </body>
</html>