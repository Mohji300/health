<?php defined('BASEPATH') OR exit('No direct script access allowed'); 

// Define PHP helper functions at the top of the file
function getStatusClass($status) {
    switch($status) {
        case 'Severely Wasted': return 'status-severely-wasted';
        case 'Wasted': return 'status-wasted';
        case 'Overweight': return 'status-overweight';
        case 'Obese': return 'status-obese';
        default: return '';
    }
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'Severely Wasted': return 'bg-danger';
        case 'Wasted': return 'bg-warning text-dark';
        case 'Overweight': return 'bg-info';
        case 'Obese': return 'bg-danger';
        default: return 'bg-success';
    }
}

function getAssessmentBadgeClass($type) {
    switch($type) {
        case 'baseline': return 'bg-primary';
        case 'midline': return 'bg-info';
        case 'endline': return 'bg-success';
        default: return 'bg-secondary';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Assessment Archive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/archive.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>
      
      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">

          <!-- Header Card -->
          <div class="card bg-gradient-archive text-white mb-4">
              <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h1 class="h2 font-weight-bold mb-2">Nutritional Assessment Archive</h1>
                          <p class="mb-0 opacity-8">View archived nutritional assessment records grouped by school and year</p>
                      </div>
                      <div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Archive Controls - Only show for admin users -->
          <?php if ($user_role === 'admin'): ?>
          <div class="card mb-4 no-print">
              <div class="card-header bg-dark text-white">
                  <h5 class="mb-0"><i class="fas fa-database"></i> Archive Management</h5>
              </div>
              <div class="card-body">
                  <div class="row">
                      <div class="col-md-8">
                          <div class="alert alert-info mb-3">
                              <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Archive Information</h6>
                              <p class="mb-2">This feature allows you to archive nutritional assessment records for any school year.</p>
                              <p class="mb-0">Archived records will be moved to a separate database table for long-term storage and removed from active assessments.</p>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="bg-archive p-4 rounded">
                              <h5 class="text-dark mb-3 text-center">Archive Records</h5>
                              <div class="mb-3">
                                  <label class="form-label fw-bold">School Year:</label>
                                  <select id="schoolYearSelect" class="form-select">
                                      <option value="">Select School Year</option>
                                      <?php if (!empty($school_years)): ?>
                                          <?php foreach ($school_years as $year): ?>
                                              <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                                          <?php endforeach; ?>
                                      <?php else: ?>
                                          <option value="">No school years found</option>
                                      <?php endif; ?>
                                  </select>
                              </div>
                              <div class="text-center">
                                  <button type="button" id="archiveBtn" class="btn btn-archive btn-lg w-100">
                                      <i class="fas fa-box-archive me-2"></i>Start Archiving Process
                                  </button>
                                  <small class="text-muted mt-2 d-block">This action cannot be undone</small>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
          <?php endif; ?>

          <!-- Archived Records Summary -->
          <div class="card">
              <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                  <h5 class="mb-0"><i class="fas fa-table"></i> Archived Records Summary</h5>
                  <div>
                      <span class="badge bg-light text-dark" id="recordCount">0 Records</span>
                  </div>
              </div>
              <div class="card-body">
                  <!-- Filters -->
                  <div class="row mb-4">
                      <div class="col-md-4">
                          <div class="input-group">
                              <span class="input-group-text"><i class="fas fa-filter"></i></span>
                              <select id="yearFilter" class="form-select">
                                  <option value="">All School Years</option>
                                  <?php if (!empty($school_years)): ?>
                                      <?php foreach ($school_years as $year): ?>
                                          <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                                      <?php endforeach; ?>
                                  <?php endif; ?>
                              </select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="input-group">
                              <span class="input-group-text"><i class="fas fa-clipboard-check"></i></span>
                              <select id="assessmentFilter" class="form-select">
                                  <option value="">All Assessment Types</option>
                                  <option value="baseline">Baseline</option>
                                  <option value="midline">Midline</option>
                                  <option value="endline">Endline</option>
                              </select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="input-group">
                              <span class="input-group-text"><i class="fas fa-search"></i></span>
                              <input type="text" id="searchBar" class="form-control" placeholder="Search schools or school IDs...">
                              <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                  <i class="fas fa-times"></i>
                              </button>
                          </div>
                      </div>
                  </div>
                  
                  <?php if (!empty($archived_summary)): ?>
                      <?php 
                      // Group summary by year
                      $grouped_by_year = [];
                      foreach ($archived_summary as $record) {
                          $grouped_by_year[$record->year][] = $record;
                      }
                      ?>
                      
                      <?php foreach ($grouped_by_year as $year => $schools): ?>
                          <div class="card mb-3 year-group" data-year="<?= $year ?>">
                              <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                  <h6 class="mb-0">
                                      <i class="fas fa-calendar-alt me-2"></i>
                                      School Year: <?= htmlspecialchars($year) ?>
                                      <span class="badge bg-light text-dark ms-2"><?= count($schools) ?> Schools</span>
                                  </h6>
                                  <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" 
                                          data-bs-target="#year<?= preg_replace('/[^a-zA-Z0-9]/', '', $year) ?>">
                                      <i class="fas fa-chevron-down"></i>
                                  </button>
                              </div>
                              
                              <div class="collapse show" id="year<?= preg_replace('/[^a-zA-Z0-9]/', '', $year) ?>">
                                  <div class="card-body p-0">
                                      <div class="table-responsive">
                                          <table class="table table-hover mb-0">
                                              <thead class="table-light">
                                                  <tr>
                                                      <th>School Name</th>
                                                      <th>School ID</th>
                                                      <th>Assessment Types</th>
                                                      <th>Archive Date Range</th>
                                                      <th>Actions</th>
                                                  </tr>
                                              </thead>
                                              <tbody>
                                                  <?php foreach ($schools as $school): ?>
                                                      <tr class="school-row" 
                                                          data-school="<?= htmlspecialchars($school->school_name) ?>" 
                                                          data-year="<?= $year ?>"
                                                          data-baseline="<?= $school->baseline ?>"
                                                          data-midline="<?= $school->midline ?>"
                                                          data-endline="<?= $school->endline ?>">
                                                          <td>
                                                              <strong><?= htmlspecialchars($school->school_name) ?></strong>
                                                          </td>
                                                          <td>
                                                              <?php if (!empty($school->school_id)): ?>
                                                                  <span class="badge bg-dark rounded-pill">
                                                                      ID: <?= htmlspecialchars($school->school_id) ?>
                                                                  </span>
                                                              <?php else: ?>
                                                                  <span class="badge bg-secondary rounded-pill">
                                                                      No ID
                                                                  </span>
                                                              <?php endif; ?>
                                                          </td>
                                                          <td>
                                                              <div class="d-flex flex-wrap gap-1">
                                                                  <?php if ($school->baseline > 0): ?>
                                                                      <span class="badge bg-primary" title="Baseline">
                                                                          <?= $school->baseline ?> Baseline
                                                                      </span>
                                                                  <?php endif; ?>
                                                                  <?php if ($school->midline > 0): ?>
                                                                      <span class="badge bg-info" title="Midline">
                                                                          <?= $school->midline ?> Midline
                                                                      </span>
                                                                  <?php endif; ?>
                                                                  <?php if ($school->endline > 0): ?>
                                                                      <span class="badge bg-success" title="Endline">
                                                                          <?= $school->endline ?> Endline
                                                                      </span>
                                                                  <?php endif; ?>
                                                                  <?php if ($school->baseline == 0 && $school->midline == 0 && $school->endline == 0): ?>
                                                                      <span class="badge bg-secondary">
                                                                          No Assessments
                                                                      </span>
                                                                  <?php endif; ?>
                                                              </div>
                                                          </td>
                                                          <td>
                                                              <small class="text-muted">
                                                                  <?= date('M d, Y', strtotime($school->first_archived)) ?> 
                                                                  to 
                                                                  <?= date('M d, Y', strtotime($school->last_archived)) ?>
                                                              </small>
                                                          </td>
                                                          <td>
                                                              <div class="btn-group btn-group-sm" role="group">
                                                                  <button class="btn btn-outline-info view-school-details" 
                                                                          data-year="<?= $year ?>"
                                                                          data-school="<?= htmlspecialchars($school->school_name) ?>">
                                                                      <i class="fas fa-eye"></i> View Details
                                                                  </button>
                                                              </div>
                                                          </td>
                                                      </tr>
                                                  <?php endforeach; ?>
                                              </tbody>
                                          </table>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <div class="text-center py-5">
                          <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                          <h5 class="text-muted">No Archived Records Found</h5>
                          <p class="text-muted">No records have been archived yet.</p>
                      </div>
                  <?php endif; ?>
              </div>
          </div>

          <!-- School Details Modal -->
          <div class="modal fade" id="schoolDetailsModal" tabindex="-1">
              <div class="modal-dialog modal-xl modal-dialog-centered">
                  <div class="modal-content">
                      <div class="modal-header bg-primary text-white">
                          <h5 class="modal-title" id="schoolModalTitle">School Details</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                          <div class="row mb-4">
                              <div class="col-md-8">
                                  <h6 id="schoolName"></h6>
                                  <p class="text-muted" id="schoolYear"></p>
                                  <p class="text-muted" id="schoolId"></p>
                              </div>
                              <div class="col-md-4 text-end">
                                  <div class="btn-group" role="group">
                                      <button class="btn btn-sm btn-outline-primary" onclick="printSchoolDetails()">
                                          <i class="fas fa-print"></i> Print
                                      </button>
                                      <button class="btn btn-sm btn-outline-success" onclick="exportSchoolCSV()">
                                          <i class="fas fa-download"></i> Export CSV
                                      </button>
                                  </div>
                              </div>
                          </div>
                          
                          <!-- Assessment Type Filter for School Details -->
                          <div class="row mb-3">
                              <div class="col-md-12">
                                  <div class="btn-group btn-group-sm" role="group">
                                      <button type="button" class="btn btn-outline-secondary assessment-filter active" data-type="all">
                                          All Assessments
                                      </button>
                                      <button type="button" class="btn btn-outline-primary assessment-filter" data-type="baseline">
                                          Baseline
                                      </button>
                                      <button type="button" class="btn btn-outline-info assessment-filter" data-type="midline">
                                          Midline
                                      </button>
                                      <button type="button" class="btn btn-outline-success assessment-filter" data-type="endline">
                                          Endline
                                      </button>
                                  </div>
                              </div>
                          </div>
                          
                          <div class="table-responsive" id="schoolDetailsTable">
                              <!-- School details table will be loaded here -->
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                  </div>
              </div>
          </div>

        </div> <!-- /.container-fluid -->
      </div> <!-- /#page-content-wrapper -->
    </div> <!-- /#wrapper -->

    <!-- Archive Confirmation Modal -->
    <div class="modal fade" id="archiveConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title text-dark"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Archive</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <h6 class="alert-heading">Important Notice!</h6>
              <p>You are about to archive all nutritional assessment records for school year: <strong id="confirmSchoolYear"></strong></p>
              <p class="mb-0"><strong>This action will:</strong></p>
              <ul class="mb-0">
                <li>Move all records to the archive table</li>
                <li>Remove records from active assessments</li>
                <li>Cannot be undone automatically</li>
              </ul>
            </div>
            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" id="confirmArchive">
              <label class="form-check-label" for="confirmArchive">
                I understand this action cannot be undone
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-warning" id="confirmArchiveBtn" disabled>
              <i class="fas fa-box-archive me-2"></i>Proceed with Archive
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Archive Progress Modal -->
    <div class="modal fade" id="archiveProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">Archiving in Progress</h5>
          </div>
          <div class="modal-body">
            <div class="text-center">
              <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <h5 class="text-dark mb-2">Processing Archive</h5>
              <p class="text-muted mb-3">Please wait while we archive the records. This may take a few moments.</p>
              <div class="progress mb-3">
                <div id="archiveProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 0%">0%</div>
              </div>
              <p id="archiveStatus" class="text-muted small">Initializing archive process...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="alertTitle">Alert</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="alertBody">Message</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/archive.js'); ?>"></script>
    <script>
      // Pass PHP variables to JavaScript
      const userRole = '<?= $user_role ?>';
      const isAdmin = userRole === 'admin';
      const siteUrl = '<?= site_url("archive/get_school_details") ?>';
      const archiveUrl = '<?= site_url("archive/process_archive") ?>';
      const username = '<?= $this->session->userdata("username") ?>';
    </script>
  </body>
</html>