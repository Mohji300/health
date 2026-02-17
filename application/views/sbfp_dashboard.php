<?php defined('BASEPATH') OR exit('No direct script access allowed');

$auth = isset($auth_user) ? (object)$auth_user : (object)[];
$sections = isset($sections) ? $sections : [];
$submitted = isset($submittedAssessments) ? $submittedAssessments : [];
$flash = isset($flash) ? $flash : [];
$school_data = isset($school_data) ? $school_data : [];
$related_schools = isset($related_schools) ? $related_schools : [];

$assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$is_midline = ($assessment_type == 'midline');
$is_endline = ($assessment_type == 'endline');

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
$midline_count = 0;
$endline_count = 0;
foreach ($submitted as $s) {
    $type = $s->assessment_type ?? 'baseline';
    if ($type == 'baseline') {
        $baseline_count++;
    } elseif ($type == 'midline') {
        $midline_count++;
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
    <link rel="stylesheet" href="<?= base_url('assets/css/sbfp_dashboard.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
        <?php 
        if (isset($this)) { $this->load->view('templates/sidebar'); } ?>

        <div id="page-content-wrapper" class="w-100">
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
                            <?php if ($is_baseline) {
                                echo 'Baseline';
                            } elseif ($is_midline) {
                                echo 'Midline';
                            } else {
                                echo 'Endline';
                            }
                            ?> Assessment
                        </span>
                      </h6>
                    <div class="assessment-switcher">
                        <button class="btn btn-primary btn-sm <?php echo $is_baseline ? 'active' : ''; ?>" 
                                id="switchToBaseline">
                            <i class="fas fa-flag me-1"></i> Baseline
                        </button>
                        <button class="btn btn-info btn-sm <?php echo $is_midline ? 'active' : ''; ?>" 
                                id="switchToMidline">
                            <i class="fas fa-flag me-1"></i> Midline
                        </button>
                        <button class="btn btn-success btn-sm <?php echo $is_endline ? 'active' : ''; ?>" 
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
                                        <?php
                                        $badge_class = 'bg-primary';
                                        $icon = 'fa-flag';
                                        if ($is_baseline) {
                                            $badge_class = 'bg-primary';
                                            $icon = 'fa-flag';
                                        } elseif ($is_midline) {
                                            $badge_class = 'bg-info';
                                            $icon = 'fa-flag';
                                        } else {
                                            $badge_class = 'bg-success';
                                            $icon = 'fa-flag-checkered';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> mb-1">
                                            <i class="fas <?php echo $icon; ?> me-1"></i>
                                            <?php echo ucfirst($assessment_type); ?> Submitted
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
                                      class="btn btn-<?php 
                                            if ($is_baseline) {
                                                echo 'primary';
                                            } elseif ($is_midline) {
                                                echo 'info';
                                            } else {
                                                echo 'success';
                                            }
                                      ?> btn-sm">
                                        <i class="fas fa-<?php echo $is_endline ? 'flag-checkered' : 'flag'; ?> me-1"></i>
                                        Create <?php echo ucfirst($assessment_type); ?>
                                    </a>
                                    
                                    <!-- Remove Section Button -->
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-section-btn" 
                                            data-section-id="<?php echo (int)$item->id; ?>"
                                            data-grade="<?php echo htmlspecialchars($item->grade); ?>"
                                            data-section="<?php echo htmlspecialchars($item->section); ?>"
                                            data-school_year="<?php echo htmlspecialchars($item->school_year ?? ''); ?>">
                                        <i class="fas fa-trash me-1"></i> Remove
                                    </button>
                                <?php else: ?>
                                    
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
                        <?php $current_count = 0; 
                        if ($is_baseline) $current_count = $baseline_count;
                        elseif ($is_midline) $current_count = $midline_count;
                        else $current_count = $endline_count;
                        echo $current_count . ' ' . ucfirst($assessment_type);
                        ?>
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
                                <a href="<?php echo site_url('nutritionalassessment?legislative_district=' . urlencode($auth->legislative_district ?? '') . '&school_district=' . urlencode($auth->school_district ?? '') . '&grade=' . urlencode($s->grade) . '&section=' . urlencode($s->section) . '&school_year=' . urlencode($s->school_year ?? '') . '&assessment_type=' . urlencode($assessment_type)); ?>" >
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
                        <div class="col-4">
                            <div class="card border-left-primary">
                                <div class="card-body text-center py-2">
                                    <div class="h4 mb-0 fw-bold text-primary" id="baselineCount">
                                        <?php echo $baseline_count; ?>
                                    </div>
                                    <div class="text-xs text-muted">Baseline</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-left-info">
                                <div class="card-body text-center py-2">
                                    <div class="h4 mb-0 fw-bold text-info" id="midlineCount">
                                        <?php echo $midline_count; ?>
                                    </div>
                                    <div class="text-xs text-muted">Midline</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
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
        </div> 
    </div> 

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

<!-- Remove Section Modal -->
<div class="modal fade" id="removeSectionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove this section?</p>
                <p><strong>Grade:</strong> <span id="removeGrade"></span></p>
                <p><strong>Section:</strong> <span id="removeSection"></span></p>
                <p><strong>School Year:</strong> <span id="removeSchoolYear"></span></p>
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Important:</strong> 
                    <ul class="mb-0 mt-1">
                        <li>This section will be permanently removed</li>
                        <li>If there are any assessments associated with this section, they will also be deleted</li>
                        <li>This action cannot be undone</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="removeSectionForm" method="post" action="<?php echo site_url('sbfpdashboard/remove_section'); ?>" style="display: inline;">
                    <input type="hidden" name="section_id" id="removeSectionId" value="">
                    <button type="submit" class="btn btn-danger">Remove Section</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
window.SbfpDashboardConfig = {
  urls: {
    set_assessment_type: '<?= site_url("sbfpdashboard/set_assessment_type"); ?>',
    delete_assessment: '<?= site_url("sbfpdashboard/delete_assessment"); ?>',
    toggle_lock: '<?= site_url("sbfpdashboard/toggle_lock"); ?>'
  },
  assessment_type: '<?= isset($assessment_type) ? $assessment_type : ""; ?>'
};
</script>
<script src="<?= base_url('assets/js/sbfp_dashboard.js'); ?>"></script>
</body>
</html>