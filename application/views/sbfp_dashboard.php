<?php defined('BASEPATH') OR exit('No direct script access allowed');

$auth = isset($auth_user) ? (object)$auth_user : (object)[];
$sections = isset($sections) ? $sections : [];
$submitted = isset($submittedAssessments) ? $submittedAssessments : [];
$flash = isset($flash) ? $flash : [];
$school_data = isset($school_data) ? $school_data : [];
$related_schools = isset($related_schools) ? $related_schools : [];

// Get current assessment type from session or default to baseline
$assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$next_type = $is_baseline ? 'endline' : 'baseline';

// Helper to check submitted assessments for current type
$submitted_lookup = [];
foreach ($submitted as $s) {
    if (($s->assessment_type ?? 'baseline') == $assessment_type) {
        $key = $s->grade . '||' . $s->section . '||' . ($s->school_year ?? '');
        $submitted_lookup[$key] = [
            'total_students' => $s->total_students ?? 0,
            'last_updated' => $s->last_updated ?? null
        ];
    }
}

// Count assessments for each type
$baseline_count = 0;
$endline_count = 0;
foreach ($submitted as $s) {
    if (($s->assessment_type ?? 'baseline') == 'baseline') {
        $baseline_count++;
    } else {
        $endline_count++;
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SBFP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      
      .submitted-badge { background-color: #d1fae5; color: #065f46; }
      .school-card { transition: transform 0.2s; }
      .school-card:hover { transform: translateY(-2px); }
      
      /* Badge gradients to match other dashboards */
      .legislative-badge { background: linear-gradient(45deg, #4e73df, #224abe); }
      .district-badge { background: linear-gradient(45deg, #1cc88a, #13855c); }
      .school-badge { background: linear-gradient(45deg, #36b9cc, #258391); }
      .level-badge { background: linear-gradient(45deg, #f6c23e, #dda20a); }
      
      .text-gray-800 { color: #5a5c69 !important; }
      .text-gray-300 { color: #dddfeb !important; }
      
      /* Table styling */
      .table th { border-top: 1px solid #e3e6f0; font-weight: 600; background-color: #f8f9fc; }
      .table-bordered th, .table-bordered td { border: 1px solid #e3e6f0; }
      
      .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
      
      /* Assessment type switcher */
      .assessment-switcher {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 50px;
        padding: 2px;
        display: inline-flex;
        align-items: center;
      }
      .assessment-switcher .btn {
        border-radius: 50px;
        padding: 5px 15px;
        font-size: 0.9rem;
        transition: all 0.3s;
      }
      .assessment-switcher .btn.active {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      }
      .assessment-switcher .btn:not(.active) {
        background: transparent;
        border-color: transparent;
      }
      
      /* Delete button animation */
      .btn-delete:hover {
        transform: scale(1.05);
        transition: transform 0.2s;
      }
      
      /* Modal styling */
      .modal-backdrop { opacity: 0.5; }
    </style>
  </head>
  <body class="bg-light">
    <div id="wrapper">
        <?php // Load the global sidebar (templates/sidebar.php)
        if (isset($this)) { $this->load->view('templates/sidebar'); } ?>

        <div id="page-content-wrapper">
          <div class="container-fluid py-4">

            <!-- Header Card -->
            <div class="card bg-gradient-primary text-white mb-4">
              <div class="card-body">
                <h1 class="h2 font-weight-bold mb-2">School-Based Feeding Program (SBFP) Dashboard</h1>
                <p class="mb-0 opacity-8">Manage nutritional assessments, sections, and student health monitoring</p>
              </div>
            </div>

            <!-- Flash Messages -->
            <?php if (!empty($flash)): ?>
              <?php foreach ($flash as $type => $message): ?>
                <div class="alert alert-<?php echo $type == 'error' ? 'danger' : $type; ?> alert-dismissible fade show" role="alert">
                  <?php echo htmlspecialchars($message); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <!-- School Information Section -->
            <div class="row mb-4">
              <div class="col-md-12">
                <div class="card shadow">
                  <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                      <i class="fas fa-school me-1"></i> School Information
                    </h6>
                    <span class="badge bg-primary rounded-pill">
                      <i class="fas fa-id-card me-1"></i> ID: <?php echo htmlspecialchars($auth->school_id ?? 'N/A'); ?>
                    </span>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-3 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                          <div class="card-body">
                            <div class="row no-gutters align-items-center">
                              <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                  Legislative District
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                  <?php echo htmlspecialchars($auth->legislative_district ?? 'Not set'); ?>
                                </div>
                              </div>
                              <div class="col-auto">
                                <i class="fas fa-landmark fa-2x text-gray-300"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-3 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                          <div class="card-body">
                            <div class="row no-gutters align-items-center">
                              <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                  School District
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                  <?php echo htmlspecialchars($auth->school_district ?? 'Not set'); ?>
                                </div>
                              </div>
                              <div class="col-auto">
                                <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-3 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                          <div class="card-body">
                            <div class="row no-gutters align-items-center">
                              <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                  School Level
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                  <?php echo htmlspecialchars($auth->school_level ?? 'Not set'); ?>
                                </div>
                              </div>
                              <div class="col-auto">
                                <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-3 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                          <div class="card-body">
                            <div class="row no-gutters align-items-center">
                              <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                  Active Sections
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                  <?php echo count($sections); ?>
                                </div>
                              </div>
                              <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row mt-3">
                      <div class="col-md-8">
                        <h6 class="font-weight-bold text-dark mb-2">
                          <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($auth->school_name ?? 'Not set'); ?>
                        </h6>
                        <p class="text-muted mb-1">
                          <i class="fas fa-map me-1"></i> <?php echo htmlspecialchars($auth->school_address ?? 'Not set'); ?>
                        </p>
                        <p class="text-muted mb-0">
                          <i class="fas fa-user-tie me-1"></i> School Head: <?php echo htmlspecialchars($auth->school_head_name ?? 'Not set'); ?>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="row g-4">
              <div class="col-lg-8 mb-4">
                <div class="card shadow">
                  <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <h6 class="m-0 font-weight-bold text-primary me-3">
                        <i class="fas fa-clipboard-list me-1"></i> 
                        Nutritional Status 
                        <span id="assessmentTypeTitle">
                          <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?> Assessment
                        </span>
                      </h6>
                      <div class="assessment-switcher">
                        <button class="btn btn-primary btn-sm <?php echo $is_baseline ? 'active' : ''; ?>" 
                                id="switchToBaseline">
                          <i class="fas fa-flag me-1"></i> Baseline
                        </button>
                        <button class="btn btn-success btn-sm <?php echo !$is_baseline ? 'active' : ''; ?>" 
                                id="switchToEndline">
                          <i class="fas fa-flag-checkered me-1"></i> Endline
                        </button>
                      </div>
                    </div>
                    <span class="badge bg-primary rounded-pill" id="activeSectionsCount">
                      <?php echo count($sections); ?> Active Sections
                    </span>
                  </div>
                  <div class="card-body">
<!-- Create Section Form -->
<div class="card border mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="fas fa-plus-circle me-1"></i> Create New Section
        </h6>
    </div>
    <div class="card-body">
        <form action="<?php echo site_url('sbfpdashboard/create_section'); ?>" method="post" class="row g-3" id="createSectionForm">
            <div class="col-md-3">
                <label class="form-label fw-bold text-dark">Grade Level</label>
                <select name="grade" class="form-select" required>
                    <option value="">Select Grade</option>
                    <?php $grades = ['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'];
                    foreach ($grades as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold text-dark">Section Name</label>
                <input type="text" name="section" class="form-control" placeholder="e.g., Section A, Diamond, Emerald" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold text-dark">School Year</label>
                <input type="text" name="school_year" class="form-control" placeholder="e.g., 2024-2025" required>
                <small class="text-muted">Format: YYYY-YYYY (e.g., 2024-2025)</small>
            </div>

            <input type="hidden" name="legislative_district" value="<?php echo htmlspecialchars($auth->legislative_district ?? ''); ?>">
            <input type="hidden" name="school_district" value="<?php echo htmlspecialchars($auth->school_district ?? ''); ?>">

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-plus-circle me-1"></i> Create Section
                </button>
            </div>
        </form>
    </div>
</div>

                    <?php if (!empty($sections)): ?>
                      <h6 class="mt-4 mb-3 fw-bold text-dark">
                        <i class="fas fa-list me-1"></i> Saved Grade Levels & Sections
                      </h6>
                      <div class="table-responsive">
                        <table class="table table-bordered table-hover">
<thead class="table-light">
    <tr>
        <th>Grade Level</th>
        <th>Section</th>
        <th>School Year</th>
        <th class="text-center">Status</th>
        <th class="text-center">Actions</th>
    </tr>
</thead>
                          <tbody>
                          <?php foreach ($sections as $item):
                            $key = $item->grade . '||' . $item->section . '||' . ($item->school_year ?? '');
                            $isSubmitted = isset($submitted_lookup[$key]);
                            $assessmentData = $isSubmitted ? $submitted_lookup[$key] : null;
                          ?>
<tr>
    <td class="fw-bold"><?php echo htmlspecialchars($item->grade); ?></td>
    <td><?php echo htmlspecialchars($item->section); ?></td>
    <td>
        <span class="badge bg-secondary">
            <?php echo htmlspecialchars($item->school_year ?? 'N/A'); ?>
        </span>
    </td>
    <td class="text-center">
                                <?php if ($isSubmitted): ?>
                                  <div class="d-flex flex-column align-items-center">
                                    <span class="badge <?php echo $is_baseline ? 'bg-primary' : 'bg-success'; ?> mb-1">
                                      <i class="fas fa-<?php echo $is_baseline ? 'flag' : 'flag-checkered'; ?> me-1"></i>
                                      <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?> Submitted
                                    </span>
                                    <small class="text-muted">
                                      <?php echo $assessmentData['total_students'] ?? 0; ?> students
                                      <?php if ($assessmentData['last_updated']): ?>
                                        <br><?php echo date('M d, Y', strtotime($assessmentData['last_updated'])); ?>
                                      <?php endif; ?>
                                    </small>
                                  </div>
                                <?php else: ?>
                                  <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i> Pending Assessment
                                  </span>
                                <?php endif; ?>
                              </td>
                              <td>
              <div class="d-flex justify-content-center gap-2">
    <?php if (!$isSubmitted): ?>
        <!-- Create Assessment Button -->
        <a href="<?php echo site_url('nutritionalassessment?legislative_district=' . urlencode($auth->legislative_district ?? '') . '&school_district=' . urlencode($auth->school_district ?? '') . '&grade=' . urlencode($item->grade) . '&section=' . urlencode($item->section) . '&school_year=' . urlencode($item->school_year ?? '') . '&school_id=' . urlencode($auth->school_id ?? '') . '&school_name=' . urlencode($auth->school_name ?? '') . '&assessment_type=' . $assessment_type); ?>" 
           class="btn btn-<?php echo $is_baseline ? 'primary' : 'success'; ?> btn-sm">
            <i class="fas fa-<?php echo $is_baseline ? 'flag' : 'flag-checkered'; ?> me-1"></i>
            Create <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?>
        </a>
        
        <!-- Remove Section Button -->
        <form action="<?php echo site_url('sbfpdashboard/remove_section'); ?>" method="post" onsubmit="return confirm('Remove this section? This action cannot be undone.');" class="d-inline">
            <input type="hidden" name="section_id" value="<?php echo (int)$item->id; ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash me-1"></i> Remove
            </button>
        </form>
    <?php else: ?>
        <!-- Edit Assessment Button -->
        <a href="<?php echo site_url('nutritionalassessment?legislative_district=' . urlencode($auth->legislative_district ?? '') . '&school_district=' . urlencode($auth->school_district ?? '') . '&grade=' . urlencode($item->grade) . '&section=' . urlencode($item->section) . '&school_year=' . urlencode($item->school_year ?? '') . '&school_id=' . urlencode($auth->school_id ?? '') . '&school_name=' . urlencode($auth->school_name ?? '') . '&assessment_type=' . $assessment_type); ?>" 
           class="btn btn-outline-<?php echo $is_baseline ? 'primary' : 'success'; ?> btn-sm">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        
        <!-- Lock Button -->
        <button class="btn btn-warning btn-sm toggle-lock"
                data-grade="<?php echo htmlspecialchars($item->grade); ?>"
                data-section="<?php echo htmlspecialchars($item->section); ?>"
                data-school_year="<?php echo htmlspecialchars($item->school_year ?? ''); ?>"
                data-type="<?php echo $assessment_type; ?>"
                title="Lock/Unlock Assessment">
            <i class="fas fa-lock"></i>
        </button>
        
        <!-- Delete Assessment Button -->
        <button class="btn btn-danger btn-sm delete-assessment btn-delete"
                data-grade="<?php echo htmlspecialchars($item->grade); ?>"
                data-section="<?php echo htmlspecialchars($item->section); ?>"
                data-school_year="<?php echo htmlspecialchars($item->school_year ?? ''); ?>"
                data-type="<?php echo $assessment_type; ?>"
                title="Delete Assessment">
            <i class="fas fa-trash"></i>
        </button>
    <?php endif; ?>
</div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-500 mb-2">No sections created yet</h5>
                        <p class="text-gray-500 mb-4">Create your first section above to start nutritional assessments</p>
                      </div>
                    <?php endif; ?>

                  </div>
                </div>
              </div>

              <div class="col-lg-4 mb-4">
                <div class="card shadow">
                  <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">
                      <i class="fas fa-chart-line me-1"></i> Assessment Overview
                    </h6>
                    <span class="badge bg-info rounded-pill" id="totalAssessments">
                      <?php echo $is_baseline ? $baseline_count : $endline_count; ?> <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?>
                    </span>
                  </div>
                  <div class="card-body">
                    <?php 
                    // Filter assessments for current type
                    $filtered_assessments = array_filter($submitted, function($s) use ($assessment_type) {
                        return ($s->assessment_type ?? 'baseline') == $assessment_type;
                    });
                    ?>
                    
                    <?php if (!empty($filtered_assessments)): ?>
                      <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-file-export me-1"></i> 
                        <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?> Assessments
                      </h6>
                      <div class="list-group list-group-flush">
                        <?php foreach ($filtered_assessments as $s): ?>
                          <div class="list-group-item border-0 px-0 py-3">
<div class="d-flex justify-content-between align-items-start">
    <div>
        <strong class="d-block"><?php echo htmlspecialchars($s->grade . ' - ' . $s->section); ?></strong>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted">
                <i class="fas fa-calendar me-1"></i> 
                <?php echo htmlspecialchars(date('M j, Y', strtotime($s->last_updated))); ?>
            </small>
            <span class="badge bg-dark">
                <i class="fas fa-graduation-cap me-1"></i>
                <?php echo htmlspecialchars($s->school_year ?? 'N/A'); ?>
            </span>
        </div>
    </div>
    <span class="badge <?php echo $is_baseline ? 'bg-primary' : 'bg-success'; ?>">
        <i class="fas fa-<?php echo $is_baseline ? 'flag' : 'flag-checkered'; ?>"></i>
    </span>
</div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                              <small class="text-muted">
                                <i class="fas fa-users me-1"></i> <?php echo $s->total_students ?? 0; ?> students
                              </small>
                              <div class="btn-group btn-group-sm">
                                <a href="<?php echo site_url('nutritionalassessment?legislative_district=' . urlencode($auth->legislative_district ?? '') . '&school_district=' . urlencode($auth->school_district ?? '') . '&grade=' . urlencode($s->grade) . '&section=' . urlencode($s->section) . '&school_year=' . urlencode($s->school_year ?? '') . '&assessment_type=' . urlencode($assessment_type)); ?>" 
                                   class="btn btn-outline-info btn-sm">
                                  <i class="fas fa-eye"></i>
                                </a>
<button class="btn btn-outline-danger btn-sm delete-assessment-list" 
        data-grade="<?php echo htmlspecialchars($s->grade); ?>"
        data-section="<?php echo htmlspecialchars($s->section); ?>"
        data-school_year="<?php echo htmlspecialchars($s->school_year ?? ''); ?>"
        data-type="<?php echo htmlspecialchars($assessment_type); ?>">
                                  <i class="fas fa-trash"></i>
                                </button>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <div class="mt-3 text-center">
                        <small class="text-muted">
                          Showing <?php echo count($filtered_assessments); ?> <?php echo strtolower($assessment_type); ?> assessment(s)
                        </small>
                      </div>
                    <?php else: ?>
                      <div class="text-center py-4">
                        <i class="fas fa-file-import fa-3x text-gray-300 mb-3"></i>
                        <h6 class="text-gray-500 mb-2">No <?php echo strtolower($assessment_type); ?> assessment data submitted yet</h6>
                        <p class="text-gray-500 small">Create sections and submit <?php echo strtolower($assessment_type); ?> assessment data to see summaries here</p>
                      </div>
                    <?php endif; ?>
                    
                    <!-- Quick Stats -->
                    <div class="row mt-4">
                      <div class="col-6">
                        <div class="card border-left-primary">
                          <div class="card-body text-center py-2">
                            <div class="h4 mb-0 fw-bold text-primary" id="baselineCount">
                              <?php echo $baseline_count; ?>
                            </div>
                            <div class="text-xs text-muted">Baseline</div>
                          </div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="card border-left-success">
                          <div class="card-body text-center py-2">
                            <div class="h4 mb-0 fw-bold text-success" id="endlineCount">
                              <?php echo $endline_count; ?>
                            </div>
                            <div class="text-xs text-muted">Endline</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div> <!-- /#page-content-wrapper -->
    </div> <!-- /#wrapper -->

<!-- Delete Assessment Modal -->
<div class="modal fade" id="deleteAssessmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Assessment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this 
                    <span class="badge <?php echo $is_baseline ? 'bg-primary' : 'bg-success'; ?>">
                        <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?>
                    </span> 
                    assessment?
                </p>
                <p><strong>Grade:</strong> <span id="deleteGrade"></span></p>
                <p><strong>Section:</strong> <span id="deleteSection"></span></p>
                <p><strong>School Year:</strong> <span id="deleteSchoolYear"></span></p>
                <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Assessment</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Debug info
    console.log('Dashboard loaded');
    console.log('Current assessment type:', '<?php echo $assessment_type; ?>');
    
    // Switch between Baseline and Endline views
    $('#switchToBaseline').click(function() {
        console.log('Switching to baseline...');
        switchAssessmentType('baseline');
    });
    
    $('#switchToEndline').click(function() {
        console.log('Switching to endline...');
        switchAssessmentType('endline');
    });
    
    function switchAssessmentType(type) {
        $.ajax({
            url: '<?php echo site_url("sbfpdashboard/set_assessment_type"); ?>',
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                console.log('Switch response:', response);
                if (response.success) {
                    // Reload the page to show the switched view
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Switch AJAX error:', error);
                console.error('Response:', xhr.responseText);
                alert('AJAX error. Check console for details.');
            }
        });
    }
    
    // Handle assessment deletion button clicks
$('.delete-assessment, .delete-assessment-list').click(function() {
    var grade = $(this).data('grade');
    var section = $(this).data('section');
    var school_year = $(this).data('school_year');
    var type = $(this).data('type');
    
    console.log('Delete clicked:', grade, section, school_year, type);
    
    $('#deleteGrade').text(grade);
    $('#deleteSection').text(section);
    $('#deleteSchoolYear').text(school_year);
    
    // Store data for deletion
    $('#deleteAssessmentModal').data('grade', grade);
    $('#deleteAssessmentModal').data('section', section);
    $('#deleteAssessmentModal').data('school_year', school_year);
    $('#deleteAssessmentModal').data('type', type);
    
    // Show the modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteAssessmentModal'));
    deleteModal.show();
});
    
    // Handle delete confirmation
$('#confirmDeleteBtn').click(function() {
    var modal = $('#deleteAssessmentModal');
    var grade = modal.data('grade');
    var section = modal.data('section');
    var school_year = modal.data('school_year');
    var type = modal.data('type');
    
    if (!grade || !section || !type) {
        alert('Error: Missing assessment data');
        return;
    }
    
    if (confirm('Are you absolutely sure? This will permanently delete the assessment data.')) {
        // Show loading
        var button = $(this);
        var originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
        button.prop('disabled', true);
        
        $.ajax({
            url: '<?php echo site_url("sbfpdashboard/delete_assessment"); ?>',
            method: 'POST',
            data: {
                grade: grade,
                section: section,
                school_year: school_year,
                assessment_type: type
            },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    // Close modal and reload page
                    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteAssessmentModal'));
                    deleteModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete AJAX error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error communicating with server. Check console for details.');
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    }
});
    
    // Toggle Lock functionality
// Toggle Lock functionality
$('.toggle-lock').click(function() {
    var grade = $(this).data('grade');
    var section = $(this).data('section');
    var school_year = $(this).data('school_year');
    var type = $(this).data('type');
    var button = $(this);
    
    console.log('Toggle lock clicked:', grade, section, school_year, type);
    
    // Add loading state
    var originalHtml = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i>');
    button.prop('disabled', true);
    
    $.ajax({
        url: '<?php echo site_url("sbfpdashboard/toggle_lock"); ?>',
        method: 'POST',
        data: {
            grade: grade,
            section: section,
            school_year: school_year,
            assessment_type: type
        },
        dataType: 'json',
        success: function(response) {
            console.log('Lock response:', response);
            if (response.success) {
                // Toggle lock icon
                if (button.find('i').hasClass('fa-lock')) {
                    button.html('<i class="fas fa-unlock"></i>');
                    button.removeClass('btn-warning').addClass('btn-success');
                } else {
                    button.html('<i class="fas fa-lock"></i>');
                    button.removeClass('btn-success').addClass('btn-warning');
                }
                alert('Assessment ' + response.message.toLowerCase());
            } else {
                alert('Error: ' + response.message);
                button.html(originalHtml);
            }
        },
        error: function(xhr, status, error) {
            console.error('Lock AJAX error:', error);
            alert('Error communicating with server');
            button.html(originalHtml);
        },
        complete: function() {
            button.prop('disabled', false);
        }
    });
});
});
</script>
  </body>
</html>