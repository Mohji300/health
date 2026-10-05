<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Assessment Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/nutritional-reports.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>
      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">

          <?php 
          // Determine the base URL based on user role
          $role = $this->session->userdata('role');
          $reports_base = (in_array($role, ['admin', 'super_admin', 'district', 'division'])) 
              ? 'admin/reports' 
              : 'user/reports';
          
          // Check if user is a regular user (non-admin)
          $is_regular_user = !in_array($role, ['admin', 'super_admin', 'district', 'division']);
          ?>

          <!-- Reports Header -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <h1 class="h2 font-weight-bold mb-2">Nutritional Assessment Reports</h1>
              <?php if (!empty($current_filters['school_name'])): ?>
                <p class="mb-0 opacity-8">Showing reports for <strong><?php echo htmlspecialchars($current_filters['school_name']); ?></strong></p>
              <?php else: ?>
                <p class="mb-0 opacity-8">View and analyze all submitted nutritional assessment data</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- School Information Card -->
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
                            <?php 
                            // Get the school district from the first report if available
                            $school_district_display = !empty($reports) ? ($reports[0]->school_district ?? 'N/A') : 'N/A';
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
                            <?php 
                            // Get the legislative district from the first report if available
                            $legislative_district_display = !empty($reports) ? ($reports[0]->legislative_district ?? 'N/A') : 'N/A';
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

          <!-- Statistics Cards -->
          <?php
          $use_filtered_stats = !empty($current_filters['school_name']);
          if ($use_filtered_stats) {
            $filtered_total_assessments = 0;
            $filtered_baseline_count = 0;
            $filtered_midline_count = 0;
            $filtered_endline_count = 0;
            $unique_schools = [];

            if (!empty($reports) && is_array($reports)) {
              foreach ($reports as $r) {
                $filtered_total_assessments++;
                $type = strtolower(trim($r->assessment_type ?? ''));
                if ($type === 'baseline') $filtered_baseline_count++;
                if ($type === 'midline') $filtered_midline_count++;
                if ($type === 'endline') $filtered_endline_count++;
                if (!empty($r->school_name)) $unique_schools[$r->school_name] = true;
              }
            }

            $filtered_total_schools = count($unique_schools);
          }
          ?>

          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        Total Assessments
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($use_filtered_stats ? ($filtered_total_assessments ?? 0) : ($total_assessments ?? 0)); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-clipboard-list fa-2x text-black"></i>
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
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        Baseline Assessments
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($use_filtered_stats ? ($filtered_baseline_count ?? 0) : ($baseline_count ?? 0)); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <span class="badge badge-baseline rounded-pill px-3 py-2">
                        <i class="fas fa-flag"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-mid shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-mid text-uppercase mb-1">
                        Midline Assessments
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($use_filtered_stats ? ($filtered_midline_count ?? 0) : ($midline_count ?? 0)); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <span class="badge badge-midline rounded-pill px-3 py-2">
                        <i class="fas fa-flag"></i>
                      </span>
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
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        Endline Assessments
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($use_filtered_stats ? ($filtered_endline_count ?? 0) : ($endline_count ?? 0)); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <span class="badge badge-endline rounded-pill px-3 py-2">
                        <i class="fas fa-flag"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Filters Card -->
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i> Filter Reports
              </h6>
              <a href="<?php echo site_url($reports_base); ?>" class="btn btn-outline-secondary btn-sm" id="resetFiltersBtn">
                <i class="fas fa-redo me-1"></i> Reset Filters
              </a>
            </div>
            <div class="card-body">
              <div class="card border mb-4">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="fas fa-search me-1"></i> Search & Filter Criteria
                  </h6>
                </div>
                <div class="card-body">
                  <form method="get" action="<?php echo site_url($reports_base); ?>" class="row g-3" id="filterForm">
                    
                    <!-- For regular users, show hidden inputs with their school info -->
                    <?php if ($is_regular_user && !empty($current_filters['school_name'])): ?>
                      <input type="hidden" name="school_name" value="<?php echo htmlspecialchars($current_filters['school_name']); ?>">
                      <?php if (!empty($reports)): ?>
                        <input type="hidden" name="legislative_district" value="<?php echo htmlspecialchars($reports[0]->legislative_district ?? ''); ?>">
                        <input type="hidden" name="school_district" value="<?php echo htmlspecialchars($reports[0]->school_district ?? ''); ?>">
                      <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Assessment Type Filter -->
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-chart-line me-1"></i> Assessment Type
                      </label>
                      <select name="assessment_type" class="form-select">
                        <?php foreach ($assessment_types as $value => $label): ?>
                          <option value="<?php echo htmlspecialchars($value); ?>" 
                            <?php echo ($current_filters['assessment_type'] == $value) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    
                    <!-- Grade Level Filter -->
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-graduation-cap me-1"></i> Grade Level
                      </label>
                      <select name="grade_level" class="form-select">
                        <option value="">All Grades</option>
                        <?php foreach ($grade_levels as $grade): ?>
                          <option value="<?php echo htmlspecialchars($grade->grade_level ?? ''); ?>" 
                            <?php echo ($current_filters['grade_level'] == $grade->grade_level) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grade->grade_level ?? 'N/A'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    
                    <!-- Date From Filter -->
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-calendar-day me-1"></i> Date From
                      </label>
                      <input type="date" name="date_from" class="form-control" id="dateFrom"
                             value="<?php echo htmlspecialchars($current_filters['date_from'] ?? ''); ?>">
                    </div>
                    
                    <!-- Date To Filter -->
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-calendar-check me-1"></i> Date To
                      </label>
                      <input type="date" name="date_to" class="form-control" id="dateTo"
                             value="<?php echo htmlspecialchars($current_filters['date_to'] ?? ''); ?>">
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
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Reports Table -->
          <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-chart-bar me-1"></i> Nutritional Assessment Reports
              </h6>
              <div class="d-flex align-items-center">
                <span class="badge bg-primary rounded-pill me-3" id="reportCount">
                  <?php echo number_format(count($student_reports ?? [])); ?> Records
                </span>
                <div>
                  <a href="<?php echo site_url($reports_base . '/export?' . http_build_query($current_filters)); ?>" 
                    class="btn btn-success btn-sm me-2 export-btn">
                      <i class="fas fa-file-export me-1"></i> Export to CSV
                  </a>
                </div>
              </div>
            </div>
            <div class="card-body">
              <?php if (empty($student_reports)): ?>
                <div class="text-center py-5" id="noReportsMessage">
                  <i class="fas fa-inbox fa-4x text-gray-300 mb-3"></i>
                  <h5 class="text-gray-500 mb-2">No reports found</h5>
                  <p class="text-gray-500 mb-4">Try adjusting your filters or check back later for new submissions.</p>
                  <a href="<?php echo site_url($reports_base); ?>" class="btn btn-primary" id="clearFiltersBtn">
                    <i class="fas fa-redo me-1"></i> Clear Filters
                  </a>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover" id="reportsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                      <tr>
                        <th>Assessment Type</th>
                        <th>School Name</th>
                        <th>School ID</th>
                        <th>District</th>
                        <th>Grade</th>
                        <th>Student Name</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Weight (kg)</th>
                        <th>Height (m)</th>
                        <th>BMI</th>
                        <th>Status</th>
                        <th>SBFP</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (($student_reports ?? []) as $report): ?>
                        <?php
                          $assessment_type_class = ($report->assessment_type ?? '') == 'baseline' ? 'bg-primary' : (($report->assessment_type ?? '') == 'midline' ? 'bg-warning text-dark' : 'bg-success');
                          $status = strtolower($report->nutritional_status ?? '');
                          $status_class = $status == 'severely wasted' ? 'bg-danger' : ($status == 'wasted' ? 'bg-warning text-dark' : ($status == 'normal' ? 'bg-success' : ($status == 'overweight' ? 'bg-info text-dark' : ($status == 'obese' ? 'bg-primary' : 'bg-secondary'))));
                        ?>
                        <tr>
                          <td>
                            <span class="badge <?php echo $assessment_type_class; ?>">
                              <?php echo htmlspecialchars(ucfirst($report->assessment_type ?? 'N/A')); ?>
                            </span>
                          </td>
                          <td>
                            <?php echo htmlspecialchars($report->school_name ?? 'N/A'); ?>
                          </td>
                          <td>
                            <?php echo htmlspecialchars($report->school_id ?? 'N/A'); ?>
                          </td>
                          <td>
                            <?php echo htmlspecialchars($report->school_district ?? 'N/A'); ?>
                          </td>
                          <td><?php echo htmlspecialchars($report->grade_level ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($report->name ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($report->age ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($report->sex ?? 'N/A'); ?></td>
                          <td><?php echo number_format((float)($report->weight ?? 0), 2); ?></td>
                          <td><?php echo number_format((float)($report->height ?? 0), 2); ?></td>
                          <td class="fw-bold"><?php echo number_format((float)($report->bmi ?? 0), 2); ?></td>
                          <td>
                            <span class="badge <?php echo $status_class; ?>">
                              <?php echo htmlspecialchars($report->nutritional_status ?? 'N/A'); ?>
                            </span>
                          </td>
                          <td>
                            <span class="badge <?php echo ($report->sbfp_beneficiary ?? 'No') == 'Yes' ? 'bg-success' : 'bg-secondary'; ?>">
                              <?php echo htmlspecialchars($report->sbfp_beneficiary ?? 'No'); ?>
                            </span>
                          </td>
                          <td><?php echo !empty($report->date_of_weighing) ? date('M j, Y', strtotime($report->date_of_weighing)) : 'N/A'; ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const reportsConfig = {
            totalReports: <?php echo count($student_reports ?? []); ?>,
            currentFilters: <?php echo json_encode($current_filters); ?>,
            baseUrl: '<?php echo site_url($reports_base); ?>',
            hasReports: <?php echo !empty($student_reports) ? 'true' : 'false'; ?>,
            isRegularUser: <?php echo $is_regular_user ? 'true' : 'false'; ?>
        };
    </script>
    <script src="<?= base_url(ASSETS_PATH . '/js/nutritional-reports.js'); ?>"></script>
  </body>
</html>