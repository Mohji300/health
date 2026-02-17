<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Statistics - Wasted & Severely Wasted</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/division_view_statics.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>

      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-3">
          
          <!-- Reports Header -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <h1 class="h2 font-weight-bold mb-2">Nutritional Statistics Analysis</h1>
              <p class="mb-0 opacity-8">Detailed analysis of wasted and severely wasted students across all assessments</p>
            </div>
          </div>

          <!-- Statistics Overview -->
          <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <div>
                <h6 class="m-0 font-weight-bold text-primary">
                  <i class="fas fa-chart-pie me-1"></i> Nutritional Status Overview
                </h6>
                <small class="text-muted">Shows ALL data (unfiltered)</small>
              </div>
              <span class="badge bg-primary rounded-pill">
                <?php echo number_format(count($nutritional_stats)); ?> Records
              </span>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                  <h6 class="fw-bold text-dark mb-3">Distribution by Nutritional Status</h6>
                  
                  <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-bold">Severely Wasted</span>
                      <span class="fw-bold text-danger"><?php echo $total_severely_wasted; ?> (<?php echo $total_students > 0 ? round(($total_severely_wasted / $total_students) * 100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar bg-danger" role="progressbar" 
                           style="width: <?php echo $total_students > 0 ? ($total_severely_wasted / $total_students * 100) : 0; ?>%"></div>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-bold">Wasted</span>
                      <span class="fw-bold text-warning"><?php echo $total_wasted; ?> (<?php echo $total_students > 0 ? round(($total_wasted / $total_students) * 100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar bg-warning" role="progressbar" 
                           style="width: <?php echo $total_students > 0 ? ($total_wasted / $total_students * 100) : 0; ?>%"></div>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-bold">Normal</span>
                      <span class="fw-bold text-success"><?php echo $total_normal; ?> (<?php echo $total_students > 0 ? round(($total_normal / $total_students) * 100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar bg-success" role="progressbar" 
                           style="width: <?php echo $total_students > 0 ? ($total_normal / $total_students * 100) : 0; ?>%"></div>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-bold">Overweight</span>
                      <span class="fw-bold text-info"><?php echo $total_overweight; ?> (<?php echo $total_students > 0 ? round(($total_overweight / $total_students) * 100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar bg-info" role="progressbar" 
                           style="width: <?php echo $total_students > 0 ? ($total_overweight / $total_students * 100) : 0; ?>%"></div>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-bold">Obese</span>
                      <span class="fw-bold text-primary"><?php echo $total_obese; ?> (<?php echo $total_students > 0 ? round(($total_obese / $total_students) * 100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar bg-primary" role="progressbar" 
                           style="width: <?php echo $total_students > 0 ? ($total_obese / $total_students * 100) : 0; ?>%"></div>
                    </div>
                  </div>
                </div>
                
                <div class="col-lg-6">
                  <h6 class="fw-bold text-dark mb-3">Quick Statistics</h6>
                  <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                      <div class="card border-left-danger h-100">
                        <div class="card-body text-center py-3">
                          <div class="h4 mb-0 fw-bold text-danger"><?php echo $total_severely_wasted; ?></div>
                          <div class="text-xs text-muted">Severely Wasted</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                      <div class="card border-left-warning h-100">
                        <div class="card-body text-center py-3">
                          <div class="h4 mb-0 fw-bold text-warning"><?php echo $total_wasted; ?></div>
                          <div class="text-xs text-muted">Wasted</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                      <div class="card border-left-success h-100">
                        <div class="card-body text-center py-3">
                          <div class="h4 mb-0 fw-bold text-success"><?php echo $total_normal; ?></div>
                          <div class="text-xs text-muted">Normal</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                      <div class="card border-left-info h-100">
                        <div class="card-body text-center py-3">
                          <div class="h4 mb-0 fw-bold text-info"><?php echo $total_overweight; ?></div>
                          <div class="text-xs text-muted">Overweight</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                      <div class="card border-left-primary h-100">
                        <div class="card-body text-center py-3">
                          <div class="h4 mb-0 fw-bold text-primary"><?php echo $total_obese; ?></div>
                          <div class="text-xs text-muted">Obese</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4">
                      <div class="card border-left-secondary h-100">
                        <div class="card-body text-center py-3">
                          <div class="h4 mb-0 fw-bold text-secondary"><?php echo $total_students; ?></div>
                          <div class="text-xs text-muted">Total Students</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Filters Card -->
          <div class="card shadow mt-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i> Filter Statistics
              </h6>
              <a href="<?php echo site_url('admin/reports/statistics'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-redo me-1"></i> Reset Filters
              </a>
            </div>
            <div class="card-body">
              <form method="get" action="<?php echo site_url('admin/reports/statistics'); ?>" class="filter-form row g-3">
                <div class="col-md-4">
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
                
                <div class="col-md-4">
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
                
                <div class="col-md-4">
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
                    <i class="fas fa-heartbeat me-1"></i> Nutritional Status
                  </label>
                  <select name="nutritional_status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="sbfp_beneficiary" <?php echo ($current_filters['nutritional_status'] ?? '') == 'sbfp_beneficiary' ? 'selected' : ''; ?>>SBFP Beneficiary</option>
                    <option value="severely wasted" <?php echo ($current_filters['nutritional_status'] ?? '') == 'severely wasted' ? 'selected' : ''; ?>>Severely Wasted</option>
                    <option value="wasted" <?php echo ($current_filters['nutritional_status'] ?? '') == 'wasted' ? 'selected' : ''; ?>>Wasted</option>
                    <option value="normal" <?php echo ($current_filters['nutritional_status'] ?? '') == 'normal' ? 'selected' : ''; ?>>Normal</option>
                    <option value="overweight" <?php echo ($current_filters['nutritional_status'] ?? '') == 'overweight' ? 'selected' : ''; ?>>Overweight</option>
                    <option value="obese" <?php echo ($current_filters['nutritional_status'] ?? '') == 'obese' ? 'selected' : ''; ?>>Obese</option>
                  </select>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label fw-bold text-dark">
                    <i class="fas fa-flag me-1"></i> Assessment Type
                  </label>
                  <select name="assessment_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="baseline" <?php echo ($current_filters['assessment_type'] ?? '') == 'baseline' ? 'selected' : ''; ?>>Baseline</option>
                    <option value="endline" <?php echo ($current_filters['assessment_type'] ?? '') == 'endline' ? 'selected' : ''; ?>>Endline</option>
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
                
                <div class="col-md-3">
                  <label class="form-label d-none d-md-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Detailed Nutritional Status Students Table -->
          <div class="card shadow mt-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
              <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-users me-1"></i> 
                <?php 
                $status_filter = $current_filters['nutritional_status'] ?? '';
                if ($status_filter === '') {
                  echo 'All Students (All Statuses)';
                } else if ($status_filter === 'sbfp_beneficiary') {
                  echo 'SBFP Beneficiaries';
                } else {
                  echo ucfirst($status_filter) . ' Students';
                }
                ?>
              </h6>
              <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0">
                <?php 
                $count = count($filtered_students ?? []);
                $status_class = '';
                if ($status_filter === '') {
                  $status_class = 'primary';
                } else if ($status_filter === 'sbfp_beneficiary') {
                  $status_class = 'success';
                } else {
                  $status_class = $status_filter == 'severely wasted' ? 'danger' : 
                                 ($status_filter == 'wasted' ? 'warning' : 
                                 ($status_filter == 'normal' ? 'success' : 
                                 ($status_filter == 'overweight' ? 'info' : 'primary'))); 
                }
                ?>
                <span class="badge bg-<?php echo $status_class; ?> rounded-pill me-3 mb-2 mb-md-0">
                  <i class="fas fa-user me-1"></i> <?php echo $count; ?> Records
                </span>
                <div>
                  <a href="<?php echo site_url('admin/reports/export_statistics?' . http_build_query($current_filters)); ?>" 
                     class="btn btn-success btn-sm me-2 mb-2 mb-md-0">
                    <i class="fas fa-file-export me-1"></i> Export CSV
                  </a>
                  <a href="<?php echo site_url('admin/reports'); ?>" 
                     class="btn btn-info btn-sm mb-2 mb-md-0">
                    <i class="fas fa-arrow-left me-1"></i> Back
                  </a>
                </div>
              </div>
            </div>
            <div class="card-body">
              <?php 
              $students_to_display = $filtered_students ?? [];
              ?>
              
              <?php if (empty($students_to_display)): ?>
                <div class="text-center py-5">
                  <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                  <p class="text-muted">No students found with current filters</p>
                  <p class="text-muted small">Try adjusting your filter criteria</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover" id="nutritionalStatusTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                      <tr>
                        <th class="min-w-100">Assessment Type</th>
                        <th class="min-w-150">School Name</th>
                        <th class="min-w-80">School ID</th>
                        <th class="min-w-120">District</th>
                        <th class="min-w-80">Grade</th>
                        <th class="min-w-150">Student Name</th>
                        <th class="min-w-80">Age</th>
                        <th class="min-w-80">Sex</th>
                        <th class="min-w-80">Weight (kg)</th>
                        <th class="min-w-80">Height (m)</th>
                        <th class="min-w-80">BMI</th>
                        <th class="min-w-120">Status</th>
                        <th class="min-w-100">SBFP</th>
                        <th class="min-w-100">Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($students_to_display as $student): ?>
                      <?php 
                      $status = strtolower($student->nutritional_status ?? '');
                      $row_class = '';
                      
                      if ($status_filter === '' || $status_filter === 'sbfp_beneficiary') {
                        $row_class = $status == 'severely wasted' ? 'table-danger' : 
                                   ($status == 'wasted' ? 'table-warning' : 
                                   ($status == 'normal' ? 'table-success' : 
                                   ($status == 'overweight' ? 'table-info' : 
                                   ($status == 'obese' ? 'table-primary' : ''))));
                      } else {
                        $row_class = $status_filter == 'severely wasted' ? 'table-danger' : 
                                   ($status_filter == 'wasted' ? 'table-warning' : 
                                   ($status_filter == 'normal' ? 'table-success' : 
                                   ($status_filter == 'overweight' ? 'table-info' : 'table-primary')));
                      }
                      ?>
                      <tr class="<?php echo $row_class; ?>">
                        <td>
                          <span class="badge <?php echo ($student->assessment_type ?? 'baseline') == 'baseline' ? 'bg-primary' : 'bg-success'; ?>">
                            <?php echo ($student->assessment_type ?? 'baseline') == 'baseline' ? 'Baseline' : 'Endline'; ?>
                          </span>
                        </td>
                        <td>
                          <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                title="<?php echo htmlspecialchars($student->school_name ?? 'N/A'); ?>">
                            <?php echo htmlspecialchars($student->school_name ?? 'N/A'); ?>
                          </span>
                        </td>
                        <td><?php echo htmlspecialchars($student->school_id ?? 'N/A'); ?></td>
                        <td>
                          <div class="text-truncate" style="max-width: 120px;" 
                               title="<?php echo htmlspecialchars($student->legislative_district ?? 'N/A'); ?>">
                            <?php echo htmlspecialchars($student->legislative_district ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($student->grade_level ?? 'N/A'); ?></td>
                        <td>
                          <div class="text-truncate" style="max-width: 150px;" 
                               title="<?php echo htmlspecialchars($student->name ?? 'N/A'); ?>">
                            <?php echo htmlspecialchars($student->name ?? 'N/A'); ?>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($student->age ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($student->sex ?? 'N/A'); ?></td>
                        <td><?php echo number_format($student->weight ?? 0, 2); ?></td>
                        <td><?php echo number_format($student->height ?? 0, 2); ?></td>
                        <td class="fw-bold"><?php echo number_format($student->bmi ?? 0, 2); ?></td>
                        <td>
                          <span class="badge badge-<?php 
                              echo $status == 'severely wasted' ? 'severely-wasted' : 
                                   ($status == 'wasted' ? 'wasted' : 
                                   ($status == 'normal' ? 'normal' : 
                                   ($status == 'overweight' ? 'overweight' : 'obese'))); 
                          ?>">
                            <?php echo htmlspecialchars($student->nutritional_status ?? 'N/A'); ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge <?php echo ($student->sbfp_beneficiary ?? 'No') == 'Yes' ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo htmlspecialchars($student->sbfp_beneficiary ?? 'No'); ?>
                          </span>
                        </td>
                        <td><?php echo !empty($student->date_of_weighing) ? date('M j, Y', strtotime($student->date_of_weighing)) : 'N/A'; ?></td>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="<?= base_url('assets/js/division_view_statics.js'); ?>"></script>
  </body>
</html>