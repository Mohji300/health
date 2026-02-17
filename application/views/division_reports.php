<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
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
    <link rel="stylesheet" href="<?= base_url('assets/css/division-reports.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>
      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">

          <!-- Reports Header -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <h1 class="h2 font-weight-bold mb-2">Nutritional Assessment Reports</h1>
              <p class="mb-0 opacity-8">View and analyze all submitted nutritional assessment data</p>
            </div>
          </div>

          <!-- Statistics Cards -->
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
                        <?php echo number_format($total_assessments); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                        <?php echo number_format($baseline_count); ?>
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
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        Endline Assessments
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($endline_count); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <span class="badge badge-endline rounded-pill px-3 py-2">
                        <i class="fas fa-flag-checkered"></i>
                      </span>
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
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                        Total Schools
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($total_schools); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-school fa-2x text-gray-300"></i>
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
              <a href="<?php echo site_url('division/reports'); ?>" class="btn btn-outline-secondary btn-sm">
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
                  <form method="get" action="<?php echo site_url('division/reports'); ?>" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-landmark me-1"></i> Legislative District
                      </label>
                      <select name="legislative_district" class="form-select">
                        <option value="">All Districts</option>
                        <?php foreach ($legislative_districts as $district): ?>
                          <option value="<?php echo htmlspecialchars($district->legislative_district ?? ''); ?>" 
                            <?php echo ($current_filters['legislative_district'] == $district->legislative_district) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($district->legislative_district ?? 'N/A'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-map-marker-alt me-1"></i> School District
                      </label>
                      <select name="school_district" class="form-select">
                        <option value="">All School Districts</option>
                        <?php foreach ($school_districts as $district): ?>
                          <option value="<?php echo htmlspecialchars($district->school_district ?? ''); ?>" 
                            <?php echo ($current_filters['school_district'] == $district->school_district) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($district->school_district ?? 'N/A'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-school me-1"></i> School Name
                      </label>
                      <select name="school_name" class="form-select">
                        <option value="">All Schools</option>
                        <?php foreach ($school_names as $school): ?>
                          <option value="<?php echo htmlspecialchars($school->school_name ?? ''); ?>" 
                            <?php echo ($current_filters['school_name'] == $school->school_name) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($school->school_name ?? 'N/A'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-flag me-1"></i> Assessment Type
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
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-calendar-day me-1"></i> Date From
                      </label>
                      <input type="date" name="date_from" class="form-control" 
                             value="<?php echo htmlspecialchars($current_filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold text-dark">
                        <i class="fas fa-calendar-check me-1"></i> Date To
                      </label>
                      <input type="date" name="date_to" class="form-control" 
                             value="<?php echo htmlspecialchars($current_filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                      <button type="submit" class="btn btn-primary w-100 py-2" id="applyFiltersBtn">
                        <i class="fas fa-filter me-1"></i> Apply Filters
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
                <?php echo number_format(count($reports)); ?> Reports
            </span>
            <div>
                <a href="<?php echo site_url('division/reports/export?' . http_build_query($current_filters)); ?>" 
                   class="btn btn-success btn-sm me-2 export-btn">
                    <i class="fas fa-file-export me-1"></i> Export to CSV
                </a>
                <a href="<?php echo site_url('division/reports/statistics?' . http_build_query($current_filters)); ?>" 
                   class="btn btn-info btn-sm stats-btn">
                    <i class="fas fa-chart-pie me-1"></i> View Statistics
                </a>
                
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($reports)): ?>
            <div class="text-center py-5" id="noReportsMessage">
                <i class="fas fa-inbox fa-4x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500 mb-2">No reports found</h5>
                <p class="text-gray-500 mb-4">Try adjusting your filters or check back later for new submissions.</p>
                <a href="<?php echo site_url('division/reports'); ?>" class="btn btn-primary">
                    <i class="fas fa-redo me-1"></i> Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="reportsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>School Name</th>
                            <th>School ID</th>
                            <th>Legislative District</th>
                            <th>School District</th>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Assessment Type</th>
                            <th class="text-center">Students</th>
                            <th>Date Submitted</th>
                            <th class="text-center">Export</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <?php 
                            $assessment_type_class = ($report->assessment_type == 'baseline') ? 'badge-baseline' : 'badge-endline';
                            $assessment_icon = ($report->assessment_type == 'baseline') ? 'flag' : 'flag-checkered';
                        ?>
                        <tr>
                            <td class="fw-bold text-primary">
                                <i class="fas fa-school me-1"></i> <?php echo htmlspecialchars($report->school_name ?? 'N/A'); ?>
                            </td>
                            <td>
                                <span class="badge bg-dark">
                                    <i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($report->school_id ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    <i class="fas fa-landmark me-1"></i> <?php echo htmlspecialchars($report->legislative_district ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($report->school_district ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($report->grade_level ?? 'N/A'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($report->section ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge <?php echo $assessment_type_class; ?> text-white">
                                    <i class="fas fa-<?php echo $assessment_icon; ?> me-1"></i> 
                                    <?php echo ucfirst($report->assessment_type ?? 'baseline'); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill py-2 px-3">
                                    <i class="fas fa-users me-1"></i> <?php echo $report->student_count ?? 0; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($report->first_submission)): ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-calendar-day me-1"></i> <?php echo date('M j, Y', strtotime($report->first_submission)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?php echo site_url('division/reports/export_detail?' . http_build_query([
                                    'legislative_district' => $report->legislative_district ?? '',
                                    'school_district' => $report->school_district ?? '',
                                    'school_name' => $report->school_name ?? '',
                                    'school_id' => $report->school_id ?? '',
                                    'grade_level' => $report->grade_level ?? '',
                                    'section' => $report->section ?? '',
                                    'assessment_type' => $report->assessment_type ?? 'baseline'
                                ])); ?>" 
                                   class="btn btn-success btn-sm export-detail-btn" 
                                   title="Export to Excel/CSV" 
                                   data-bs-toggle="tooltip">
                                    <i class="fas fa-file-export me-1"></i> Export
                                </a>
                            </td>
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
        // Pass PHP variables to JavaScript
        const reportsConfig = {
            totalReports: <?php echo count($reports); ?>,
            currentFilters: <?php echo json_encode($current_filters); ?>,
            baseUrl: '<?php echo site_url('division/reports'); ?>'
        };
    </script>
    <script src="<?= base_url('assets/js/division-reports.js'); ?>"></script>
  </body>
</html>