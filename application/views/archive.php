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
    <style>
      body { background-color: #f8f9fa; }
      .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
      .table-responsive { border-radius: 0.5rem; overflow: hidden; }
      table th { background-color: #0d6efd; color: white; font-weight: 600; }
      .table-sm thead th { padding: 0.5rem; font-size: 0.85rem; }
      .table-sm tbody td { padding: 0.5rem; font-size: 0.85rem; }
      .bg-archive { background-color: #f8f9fa; border-left: 4px solid #6c757d; }
      .btn-archive {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
      }
      .btn-archive:hover {
        background-color: #5a6268;
        border-color: #545b62;
      }
      .status-severely-wasted { background-color: #f8d7da !important; }
      .status-wasted { background-color: #fff3cd !important; }
      .status-overweight { background-color: #d1ecf1 !important; }
      .status-obese { background-color: #f5c6cb !important; }
      
      .archive-badge {
        font-size: 0.8rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
      }
      .badge-archived {
        background: linear-gradient(45deg, #6c757d, #495057);
        color: white;
      }
      
      /* Header styling */
      .bg-gradient-archive { 
        background: linear-gradient(45deg, #6c757d, #495057); 
      }
      
      /* Search bar styling */
      #searchBar {
        border-right: none;
      }
      
      #clearSearch {
        border-left: none;
      }
      
      .input-group-text {
        background-color: #f8f9fa;
      }
      
      .search-result-badge {
        position: absolute;
        right: 10px;
        top: -8px;
        z-index: 100;
        font-size: 0.7rem;
      }
      
      .input-group {
        position: relative;
      }
      
      mark {
        padding: 0 2px;
        border-radius: 2px;
        font-weight: bold;
      }
      
      @media print {
        .no-print { display: none !important; }
        body { background-color: white; }
      }
    </style>
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
    <script>
      let archiveConfirmModal = null;
      let archiveProgressModal = null;
      let alertModal = null;
      let schoolDetailsModal = null;
      let selectedSchoolYear = '';
      let currentAssessmentType = 'all';
      let userRole = '<?= $user_role ?>';
      let isAdmin = userRole === 'admin';

      document.addEventListener('DOMContentLoaded', () => {
          // Initialize modals
          alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
          schoolDetailsModal = new bootstrap.Modal(document.getElementById('schoolDetailsModal'));
          
          // Initialize archive modals only for admin users
          if (isAdmin) {
              archiveConfirmModal = new bootstrap.Modal(document.getElementById('archiveConfirmModal'));
              archiveProgressModal = new bootstrap.Modal(document.getElementById('archiveProgressModal'));
              
              // Archive event listeners only for admin
              document.getElementById('archiveBtn').addEventListener('click', showArchiveConfirmation);
              document.getElementById('confirmArchiveBtn').addEventListener('click', startArchiveProcess);
              document.getElementById('confirmArchive').addEventListener('change', toggleArchiveButton);
          }
          
          // Common event listeners
          updateRecordCount();
          
          // Search bar event listeners
          const searchBar = document.getElementById('searchBar');
          const clearSearchBtn = document.getElementById('clearSearch');

          if (searchBar) {
              searchBar.addEventListener('input', performSearch);
          }
          if (clearSearchBtn) {
              clearSearchBtn.addEventListener('click', clearSearch);
          }

          // Year filter event
          const yearFilter = document.getElementById('yearFilter');
          if (yearFilter) {
              yearFilter.addEventListener('change', filterByYear);
          }

          // Assessment filter event
          const assessmentFilter = document.getElementById('assessmentFilter');
          if (assessmentFilter) {
              assessmentFilter.addEventListener('change', filterByAssessment);
          }

          // Attach event listeners to view school details buttons
          document.addEventListener('click', function(event) {
              // View school details button
              if (event.target.closest('.view-school-details')) {
                  const button = event.target.closest('.view-school-details');
                  const year = button.getAttribute('data-year');
                  const school = button.getAttribute('data-school');
                  viewSchoolDetails(year, school, 'all');
              }
              
              // Assessment filter buttons in modal
              if (event.target.closest('.assessment-filter')) {
                  const button = event.target.closest('.assessment-filter');
                  const type = button.getAttribute('data-type');
                  filterSchoolDetailsByAssessment(type);
              }
          });
          
          // Initialize assessment filter buttons
          updateAssessmentFilterButtons('all');
      });

      function updateRecordCount() {
          let totalRecords = 0;
          const schoolRows = document.querySelectorAll('.school-row');
          
          schoolRows.forEach(row => {
              if (row.style.display !== 'none') {
                  const baseline = parseInt(row.getAttribute('data-baseline')) || 0;
                  const midline = parseInt(row.getAttribute('data-midline')) || 0;
                  const endline = parseInt(row.getAttribute('data-endline')) || 0;
                  totalRecords += (baseline + midline + endline);
              }
          });
          
          const recordCountElement = document.getElementById('recordCount');
          if (recordCountElement) {
              recordCountElement.textContent = totalRecords + ' Record' + (totalRecords !== 1 ? 's' : '');
          }
      }

      function filterByYear() {
          const selectedYear = document.getElementById('yearFilter').value;
          const yearGroups = document.querySelectorAll('.year-group');
          
          yearGroups.forEach(group => {
              const year = group.getAttribute('data-year');
              
              if (!selectedYear || year === selectedYear) {
                  group.style.display = 'block';
                  const schoolRows = group.querySelectorAll('.school-row');
                  schoolRows.forEach(row => row.style.display = 'table-row');
              } else {
                  group.style.display = 'none';
              }
          });
          
          filterByAssessment();
          updateRecordCount();
      }

      function filterByAssessment() {
          const selectedAssessment = document.getElementById('assessmentFilter').value;
          const visibleRows = document.querySelectorAll('.year-group:not([style*="display: none"]) .school-row');
          
          visibleRows.forEach(row => {
              const baseline = parseInt(row.getAttribute('data-baseline')) || 0;
              const midline = parseInt(row.getAttribute('data-midline')) || 0;
              const endline = parseInt(row.getAttribute('data-endline')) || 0;
              
              let shouldShow = false;
              
              if (!selectedAssessment) {
                  shouldShow = true;
              } else if (selectedAssessment === 'baseline' && baseline > 0) {
                  shouldShow = true;
              } else if (selectedAssessment === 'midline' && midline > 0) {
                  shouldShow = true;
              } else if (selectedAssessment === 'endline' && endline > 0) {
                  shouldShow = true;
              }
              
              row.style.display = shouldShow ? 'table-row' : 'none';
          });
          
          updateRecordCount();
      }

      function performSearch() {
          const searchTerm = document.getElementById('searchBar').value.toLowerCase().trim();
          
          if (!searchTerm) {
              document.querySelectorAll('.school-row').forEach(row => {
                  row.style.display = 'table-row';
              });
              return;
          }
          
          const schoolRows = document.querySelectorAll('.school-row');
          let matchCount = 0;
          
          schoolRows.forEach(row => {
              const schoolName = row.querySelector('td:first-child strong').textContent.toLowerCase();
              const schoolIdElement = row.querySelector('td:nth-child(2) .badge');
              const schoolId = schoolIdElement ? schoolIdElement.textContent.toLowerCase() : '';
              
              const matchesSchoolName = schoolName.includes(searchTerm);
              const matchesSchoolId = schoolId.includes(searchTerm);
              
              if (matchesSchoolName || matchesSchoolId) {
                  row.style.display = 'table-row';
                  highlightText(row, searchTerm);
                  matchCount++;
              } else {
                  row.style.display = 'none';
                  removeHighlights(row);
              }
          });
          
          document.querySelectorAll('.year-group').forEach(group => {
              const yearId = group.querySelector('.collapse').id;
              const visibleRows = group.querySelectorAll(`#${yearId} .school-row[style*="table-row"]`);
              
              if (visibleRows.length > 0) {
                  group.style.display = 'block';
                  const collapse = new bootstrap.Collapse(document.getElementById(yearId), {
                      toggle: false
                  });
                  collapse.show();
              } else {
                  group.style.display = 'none';
              }
          });
          
          updateSearchResultCount(matchCount);
          updateRecordCount();
      }

      function highlightText(row, searchTerm) {
          removeHighlights(row);
          
          const schoolNameCell = row.querySelector('td:first-child');
          const schoolNameText = schoolNameCell.textContent;
          const highlightedName = highlightTerm(schoolNameText, searchTerm);
          schoolNameCell.innerHTML = `<strong>${highlightedName}</strong>`;
          
          const schoolIdElement = row.querySelector('td:nth-child(2) .badge');
          if (schoolIdElement) {
              const schoolIdText = schoolIdElement.textContent;
              const highlightedId = highlightTerm(schoolIdText, searchTerm);
              schoolIdElement.innerHTML = highlightedId;
          }
      }

      function highlightTerm(text, term) {
          if (!term) return text;
          const regex = new RegExp(`(${term})`, 'gi');
          return text.replace(regex, '<mark class="bg-warning text-dark">$1</mark>');
      }

      function removeHighlights(row) {
          const schoolNameCell = row.querySelector('td:first-child');
          if (schoolNameCell) {
              const originalText = schoolNameCell.textContent.replace(/\n/g, '').trim();
              schoolNameCell.innerHTML = `<strong>${originalText}</strong>`;
          }
          
          const schoolIdElement = row.querySelector('td:nth-child(2) .badge');
          if (schoolIdElement) {
              const originalId = schoolIdElement.textContent.replace(/\n/g, '').trim();
              schoolIdElement.innerHTML = originalId;
          }
          
          row.querySelectorAll('mark').forEach(mark => {
              mark.replaceWith(mark.textContent);
          });
      }

      function clearSearch() {
          const searchBar = document.getElementById('searchBar');
          searchBar.value = '';
          
          document.querySelectorAll('.school-row').forEach(row => {
              row.style.display = 'table-row';
              removeHighlights(row);
          });
          
          document.querySelectorAll('.year-group').forEach(group => {
              group.style.display = 'block';
          });
          
          expandAll();
          updateSearchResultCount(null);
          updateRecordCount();
      }

      function updateSearchResultCount(matchCount) {
          const searchBar = document.getElementById('searchBar');
          const searchTerm = searchBar.value.trim();
          
          if (!searchTerm) {
              const existingBadge = document.querySelector('.search-result-badge');
              if (existingBadge) {
                  existingBadge.remove();
              }
              return;
          }
          
          let resultBadge = document.querySelector('.search-result-badge');
          
          if (!resultBadge) {
              resultBadge = document.createElement('span');
              resultBadge.className = 'search-result-badge badge bg-info ms-2';
              searchBar.parentNode.appendChild(resultBadge);
          }
          
          if (matchCount === 0) {
              resultBadge.textContent = 'No results found';
              resultBadge.className = 'search-result-badge badge bg-danger ms-2';
          } else {
              resultBadge.textContent = `${matchCount} result${matchCount !== 1 ? 's' : ''} found`;
              resultBadge.className = 'search-result-badge badge bg-success ms-2';
          }
      }

      function expandAll() {
          const collapses = document.querySelectorAll('.collapse');
          collapses.forEach(collapse => {
              const bsCollapse = new bootstrap.Collapse(collapse, {
                  toggle: false
              });
              bsCollapse.show();
          });
      }

      function viewSchoolDetails(year, school, assessmentType = 'all') {
          currentAssessmentType = assessmentType;
          const title = document.getElementById('schoolModalTitle');
          const schoolName = document.getElementById('schoolName');
          const schoolYear = document.getElementById('schoolYear');
          const schoolId = document.getElementById('schoolId');
          const detailsTable = document.getElementById('schoolDetailsTable');
          
          detailsTable.innerHTML = `
              <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2">Loading school details...</p>
              </div>
          `;
          
          title.textContent = `Archived Records - ${school}`;
          schoolName.textContent = `School: ${school}`;
          schoolYear.textContent = `School Year: ${year}`;
          schoolId.textContent = `Loading school ID...`;
          
          updateAssessmentFilterButtons(assessmentType);
          
          const url = `<?= site_url("archive/get_school_details") ?>?year=${encodeURIComponent(year)}&school=${encodeURIComponent(school)}&type=${encodeURIComponent(assessmentType)}`;
          
          console.log('Fetching URL:', url);
          
          fetch(url, {
              headers: {
                  'X-Requested-With': 'XMLHttpRequest'
              }
          })
          .then(response => {
              console.log('Response status:', response.status);
              const contentType = response.headers.get('content-type');
              if (!contentType || !contentType.includes('application/json')) {
                  console.error('Non-JSON response:', contentType);
                  return response.text().then(text => {
                      console.error('Response text:', text);
                      throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                  });
              }
              return response.json();
          })
          .then(data => {
              console.log('Response data:', data);
              if (data.success) {
                  if (data.records && data.records.length > 0 && data.records[0].school_id) {
                      schoolId.textContent = `School ID: ${data.records[0].school_id}`;
                  } else {
                      schoolId.textContent = `School ID: Not Available`;
                  }
                  
                  renderSchoolDetailsTable(data.records, detailsTable, assessmentType);
              } else {
                  detailsTable.innerHTML = `
                      <div class="alert alert-danger">
                          <i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to load school details.'}
                      </div>
                  `;
                  schoolId.textContent = `School ID: Error loading data`;
              }
          })
          .catch(error => {
              console.error('Error loading school details:', error);
              detailsTable.innerHTML = `
                  <div class="alert alert-danger">
                      <i class="fas fa-exclamation-circle"></i> Error loading school details: ${error.message}
                  </div>
              `;
              schoolId.textContent = `School ID: Connection error`;
          });
          
          schoolDetailsModal.show();
      }

      function updateAssessmentFilterButtons(selectedType) {
          document.querySelectorAll('.assessment-filter').forEach(btn => {
              btn.classList.remove('active');
              btn.classList.remove('btn-primary', 'btn-info', 'btn-success');
              btn.classList.add('btn-outline-secondary', 'btn-outline-primary', 'btn-outline-info', 'btn-outline-success');
          });
          
          const selectedButton = document.querySelector(`.assessment-filter[data-type="${selectedType}"]`);
          if (selectedButton) {
              selectedButton.classList.remove('btn-outline-secondary', 'btn-outline-primary', 'btn-outline-info', 'btn-outline-success');
              switch(selectedType) {
                  case 'all':
                      selectedButton.classList.add('btn-secondary', 'active');
                      break;
                  case 'baseline':
                      selectedButton.classList.add('btn-primary', 'active');
                      break;
                  case 'midline':
                      selectedButton.classList.add('btn-info', 'active');
                      break;
                  case 'endline':
                      selectedButton.classList.add('btn-success', 'active');
                      break;
              }
          }
      }

      function filterSchoolDetailsByAssessment(type) {
          currentAssessmentType = type;
          updateAssessmentFilterButtons(type);
          
          const tableBody = document.querySelector('#schoolDetailsTable tbody');
          if (!tableBody) return;
          
          const rows = tableBody.querySelectorAll('tr');
          let visibleCount = 0;
          
          rows.forEach(row => {
              const assessmentType = row.getAttribute('data-assessment-type');
              
              if (type === 'all' || assessmentType === type) {
                  row.style.display = '';
                  visibleCount++;
              } else {
                  row.style.display = 'none';
              }
          });
          
          const footer = document.querySelector('#schoolDetailsTable tfoot');
          if (footer) {
              footer.querySelector('td').innerHTML = `<strong>Showing ${visibleCount} of ${rows.length} records</strong>`;
          }
      }

      function renderSchoolDetailsTable(records, container, assessmentType = 'all') {
          if (!records || records.length === 0) {
              container.innerHTML = '<div class="alert alert-info">No records found for this school.</div>';
              return;
          }
          
          const baselineCount = records.filter(r => r.assessment_type === 'baseline').length;
          const midlineCount = records.filter(r => r.assessment_type === 'midline').length;
          const endlineCount = records.filter(r => r.assessment_type === 'endline').length;
          
          let html = `
              <div class="alert alert-light mb-3">
                  <div class="row">
                      <div class="col-md-4">
                          <span class="badge bg-primary">${baselineCount} Baseline</span>
                      </div>
                      <div class="col-md-4">
                          <span class="badge bg-info">${midlineCount} Midline</span>
                      </div>
                      <div class="col-md-4">
                          <span class="badge bg-success">${endlineCount} Endline</span>
                      </div>
                  </div>
              </div>
              <table class="table table-sm table-striped">
                  <thead>
                      <tr>
                          <th>Student Name</th>
                          <th>Grade</th>
                          <th>Section</th>
                          <th>Assessment Type</th>
                          <th>Weight (kg)</th>
                          <th>Height (m)</th>
                          <th>BMI</th>
                          <th>Nutritional Status</th>
                          <th>Date Weighed</th>
                      </tr>
                  </thead>
                  <tbody>
          `;
          
          let displayedCount = 0;
          
          records.forEach(record => {
              if (assessmentType !== 'all' && record.assessment_type !== assessmentType) {
                  return;
              }
              
              displayedCount++;
              
              html += `
                  <tr class="${getStatusClass(record.nutritional_status)}" data-assessment-type="${record.assessment_type}">
                      <td>${escapeHtml(record.name)}</td>
                      <td>${escapeHtml(record.grade_level)}</td>
                      <td>${escapeHtml(record.section)}</td>
                      <td><span class="badge ${getAssessmentBadgeClass(record.assessment_type)}">${record.assessment_type}</span></td>
                      <td>${record.weight}</td>
                      <td>${record.height}</td>
                      <td>${record.bmi}</td>
                      <td><span class="badge ${getStatusBadgeClass(record.nutritional_status)}">${record.nutritional_status}</span></td>
                      <td>${record.date_of_weighing}</td>
                  </tr>
              `;
          });
          
          html += `
                  </tbody>
                  <tfoot>
                      <tr class="table-info">
                          <td colspan="9" class="text-center">
                              <strong>Showing ${displayedCount} of ${records.length} records</strong>
                          </td>
                      </tr>
                  </tfoot>
              </table>
          `;
          
          container.innerHTML = html;
          updateAssessmentFilterButtons(assessmentType);
      }

      function printSchoolDetails() {
          const printContent = document.getElementById('schoolDetailsTable').innerHTML;
          const originalContent = document.body.innerHTML;
          const schoolTitle = document.getElementById('schoolModalTitle').textContent;
          const schoolName = document.getElementById('schoolName').textContent;
          const schoolYear = document.getElementById('schoolYear').textContent;
          const schoolId = document.getElementById('schoolId').textContent;
          
          document.body.innerHTML = `
              <!DOCTYPE html>
              <html>
              <head>
                  <title>School Archive Details</title>
                  <style>
                      body { font-family: Arial, sans-serif; margin: 20px; }
                      table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                      th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                      th { background-color: #f2f2f2; font-weight: bold; }
                      .badge { padding: 3px 8px; border-radius: 4px; font-size: 12px; }
                      .bg-danger { background-color: #dc3545 !important; color: white; }
                      .bg-warning { background-color: #ffc107 !important; color: black; }
                      .bg-success { background-color: #28a745 !important; color: white; }
                      .bg-info { background-color: #17a2b8 !important; color: white; }
                      .bg-primary { background-color: #007bff !important; color: white; }
                      .bg-secondary { background-color: #6c757d !important; color: white; }
                      .status-severely-wasted { background-color: #f8d7da !important; }
                      .status-wasted { background-color: #fff3cd !important; }
                      .status-overweight { background-color: #d1ecf1 !important; }
                      .status-obese { background-color: #f5c6cb !important; }
                      .table-info { background-color: #d1ecf1; }
                      @media print {
                          body { margin: 0; padding: 10px; }
                          table { font-size: 12px; }
                      }
                  </style>
              </head>
              <body>
                  <h4>${schoolTitle}</h4>
                  <p><strong>${schoolName}</strong></p>
                  <p><strong>${schoolYear}</strong></p>
                  <p><strong>${schoolId}</strong></p>
                  <hr>
                  ${printContent}
                  <p class="text-muted" style="margin-top: 20px; font-size: 12px;">
                      Printed on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
                      <br>Assessment Type Filter: ${currentAssessmentType === 'all' ? 'All Types' : currentAssessmentType}
                      <br>Printed by: <?= $this->session->userdata('username') ?> (Role: <?= ucfirst($user_role) ?>)
                  </p>
              </body>
              </html>
          `;
          
          window.print();
          document.body.innerHTML = originalContent;
          location.reload();
      }

      function exportSchoolCSV() {
          const table = document.querySelector('#schoolDetailsTable table');
          if (!table) {
              showAlert('Error', 'No data to export.');
              return;
          }
          
          let csv = [];
          const rows = table.querySelectorAll('tr');
          
          rows.forEach(row => {
              const rowData = [];
              const cells = row.querySelectorAll('td, th');
              
              cells.forEach(cell => {
                  let text = cell.textContent.trim();
                  text = text.replace(/Baseline|Midline|Endline/g, '');
                  text = text.replace(/\s+/g, ' ').trim();
                  
                  if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                      text = '"' + text.replace(/"/g, '""') + '"';
                  }
                  
                  rowData.push(text);
              });
              
              csv.push(rowData.join(','));
          });
          
          const csvContent = csv.join('\n');
          const blob = new Blob([csvContent], { type: 'text/csv' });
          const url = window.URL.createObjectURL(blob);
          
          const a = document.createElement('a');
          a.href = url;
          a.download = `archived_records_${new Date().toISOString().split('T')[0]}.csv`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(url);
          
          showAlert('Success', 'CSV file downloaded successfully!');
      }

      // Archive Functions
      function showArchiveConfirmation() {
          if (!isAdmin) {
              showAlert('Permission Denied', 'Only administrators can archive records.');
              return;
          }
          
          const schoolYearSelect = document.getElementById('schoolYearSelect');
          selectedSchoolYear = schoolYearSelect.value;
          
          if (!selectedSchoolYear) {
              showAlert('Error', 'Please select a school year to archive.');
              return;
          }
          
          document.getElementById('confirmArchive').checked = false;
          document.getElementById('confirmArchiveBtn').disabled = true;
          document.getElementById('confirmSchoolYear').textContent = selectedSchoolYear;
          archiveConfirmModal.show();
      }

      function toggleArchiveButton() {
          const checkbox = document.getElementById('confirmArchive');
          const button = document.getElementById('confirmArchiveBtn');
          button.disabled = !checkbox.checked;
      }

      function startArchiveProcess() {
          archiveConfirmModal.hide();
          archiveProgressModal.show();
          
          const progressBar = document.getElementById('archiveProgressBar');
          const statusText = document.getElementById('archiveStatus');
          progressBar.style.width = '0%';
          progressBar.textContent = '0%';
          statusText.textContent = 'Starting archive process...';
          
          simulateProgress();
          
          fetch('<?= site_url("archive/process_archive") ?>', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                  'X-Requested-With': 'XMLHttpRequest'
              },
              body: 'school_year=' + encodeURIComponent(selectedSchoolYear)
          })
          .then(response => {
              const contentType = response.headers.get('content-type');
              if (!contentType || !contentType.includes('application/json')) {
                  throw new Error('Server returned non-JSON response');
              }
              return response.json();
          })
          .then(data => {
              progressBar.style.width = '100%';
              progressBar.textContent = '100%';
              statusText.textContent = 'Archive completed successfully!';
              
              setTimeout(() => {
                  archiveProgressModal.hide();
                  
                  if (data.success) {
                      showAlert('Success', data.message || 'Records archived successfully!');
                      setTimeout(() => {
                          window.location.reload();
                      }, 2000);
                  } else {
                      showAlert('Error', data.message || 'Error archiving records.');
                  }
              }, 1500);
          })
          .catch(error => {
              console.error('Archive error:', error);
              archiveProgressModal.hide();
              
              if (error.message.includes('non-JSON response')) {
                  showAlert('Server Error', 'The server returned an unexpected response.');
              } else {
                  showAlert('Network Error', 'Failed to connect to server: ' + error.message);
              }
          });
      }

      function simulateProgress() {
          const progressBar = document.getElementById('archiveProgressBar');
          const statusText = document.getElementById('archiveStatus');
          let progress = 0;
          
          const interval = setInterval(() => {
              if (progress >= 90) {
                  clearInterval(interval);
                  return;
              }
              
              progress += 10;
              progressBar.style.width = progress + '%';
              progressBar.textContent = progress + '%';
              
              if (progress <= 30) {
                  statusText.textContent = 'Preparing records for archive...';
              } else if (progress <= 60) {
                  statusText.textContent = 'Transferring records to archive...';
              } else {
                  statusText.textContent = 'Finalizing archive process...';
              }
          }, 500);
      }

      function showAlert(title, message) {
          document.getElementById('alertTitle').textContent = title;
          document.getElementById('alertBody').textContent = message;
          alertModal.show();
      }

      // Helper functions
      function getStatusBadgeClass(status) {
          switch(status) {
              case 'Severely Wasted': return 'bg-danger';
              case 'Wasted': return 'bg-warning text-dark';
              case 'Overweight': return 'bg-info';
              case 'Obese': return 'bg-danger';
              default: return 'bg-success';
          }
      }

      function getAssessmentBadgeClass(type) {
          switch(type) {
              case 'baseline': return 'bg-primary';
              case 'midline': return 'bg-info';
              case 'endline': return 'bg-success';
              default: return 'bg-secondary';
          }
      }

      function getStatusClass(status) {
          switch(status) {
              case 'Severely Wasted': return 'status-severely-wasted';
              case 'Wasted': return 'status-wasted';
              case 'Overweight': return 'status-overweight';
              case 'Obese': return 'status-obese';
              default: return '';
          }
      }

      function escapeHtml(text) {
          const div = document.createElement('div');
          div.textContent = text;
          return div.innerHTML;
      }
    </script>
  </body>
</html>