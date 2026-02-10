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
                  <p class="mb-0 opacity-8">View and manage archived nutritional assessment records</p>
                </div>
                <div>
                  <span class="badge bg-light text-dark fs-6">
                    <i class="fas fa-box-archive me-2"></i>Archive System
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Controls -->
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

          <!-- Archived Records Table -->
          <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-table"></i> Archived Records</h5>
              <span class="badge bg-light text-dark" id="recordCount">0 Records</span>
            </div>
            <div class="card-body">
              <?php if (!empty($archived_records)): ?>
                <div class="alert alert-success mb-3">
                  <i class="fas fa-check-circle"></i>
                  Showing <strong><?= count($archived_records) ?></strong> archived records
                </div>
                
                <div class="table-responsive">
                  <table class="table table-sm table-striped" id="archiveTable">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>School</th>
                        <th>Grade</th>
                        <th>School Year</th>
                        <th>Assessment Type</th>
                        <th>Weight (kg)</th>
                        <th>Height (m)</th>
                        <th>BMI</th>
                        <th>Nutritional Status</th>
                        <th>Archived Date</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($archived_records as $record): ?>
                        <tr class="<?= getStatusClass($record->nutritional_status) ?>">
                          <td><?= htmlspecialchars($record->name) ?></td>
                          <td><?= htmlspecialchars($record->school_name) ?></td>
                          <td><?= htmlspecialchars($record->grade_level) ?></td>
                          <td><?= htmlspecialchars($record->year) ?></td>
                          <td>
                            <span class="badge <?= getAssessmentBadgeClass($record->assessment_type) ?>">
                              <?= ucfirst($record->assessment_type) ?>
                            </span>
                          </td>
                          <td><?= $record->weight ?></td>
                          <td><?= $record->height ?></td>
                          <td><?= $record->bmi ?></td>
                          <td>
                            <span class="badge <?= getStatusBadgeClass($record->nutritional_status) ?>">
                              <?= $record->nutritional_status ?>
                            </span>
                          </td>
                          <td><?= date('Y-m-d', strtotime($record->archived_at)) ?></td>
                          <td>
                            <button type="button" class="btn btn-sm btn-info" onclick="viewRecord(<?= $record->id ?>)">
                              <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" onclick="restoreRecord(<?= $record->id ?>)">
                              <i class="fas fa-undo"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                
                <!-- Pagination -->
                <?php if (!empty($pagination)): ?>
                  <nav class="mt-3">
                    <?= $pagination ?>
                  </nav>
                <?php endif; ?>
                
              <?php else: ?>
                <div class="text-center py-5">
                  <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No Archived Records Found</h5>
                  <p class="text-muted">No records have been archived yet. Use the archive section above to archive records.</p>
                </div>
              <?php endif; ?>
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

    <!-- Record Details Modal -->
    <div class="modal fade" id="recordDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Archived Record Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="recordDetailsContent">
            <!-- Details will be loaded here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
      let recordDetailsModal = null;
      let selectedSchoolYear = '';

      document.addEventListener('DOMContentLoaded', () => {
        // Initialize modals
        archiveConfirmModal = new bootstrap.Modal(document.getElementById('archiveConfirmModal'));
        archiveProgressModal = new bootstrap.Modal(document.getElementById('archiveProgressModal'));
        alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
        recordDetailsModal = new bootstrap.Modal(document.getElementById('recordDetailsModal'));
        
        // Update record count
        updateRecordCount();
        
        // Event listeners
        document.getElementById('archiveBtn').addEventListener('click', showArchiveConfirmation);
        document.getElementById('confirmArchiveBtn').addEventListener('click', startArchiveProcess);
        document.getElementById('confirmArchive').addEventListener('change', toggleArchiveButton);
      });

      function updateRecordCount() {
        const rows = document.querySelectorAll('#archiveTable tbody tr');
        const count = rows.length;
        document.getElementById('recordCount').textContent = count + ' Record' + (count !== 1 ? 's' : '');
      }

      function showArchiveConfirmation() {
        const schoolYearSelect = document.getElementById('schoolYearSelect');
        selectedSchoolYear = schoolYearSelect.value;
        
        if (!selectedSchoolYear) {
          showAlert('Error', 'Please select a school year to archive.');
          return;
        }
        
        // Reset checkbox
        document.getElementById('confirmArchive').checked = false;
        document.getElementById('confirmArchiveBtn').disabled = true;
        
        // Update confirmation text
        document.getElementById('confirmSchoolYear').textContent = selectedSchoolYear;
        
        // Show confirmation modal
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
        
        // Reset progress
        const progressBar = document.getElementById('archiveProgressBar');
        const statusText = document.getElementById('archiveStatus');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        statusText.textContent = 'Starting archive process...';
        
        // Simulate progress updates
        simulateProgress();
        
        // Make AJAX call to archive records with error handling
        fetch('<?= site_url("archive/process_archive") ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: 'school_year=' + encodeURIComponent(selectedSchoolYear)
        })
        .then(response => {
          // First check if response is JSON
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
          }
          return response.json();
        })
        .then(data => {
          // Complete progress
          progressBar.style.width = '100%';
          progressBar.textContent = '100%';
          statusText.textContent = 'Archive completed successfully!';
          
          setTimeout(() => {
            archiveProgressModal.hide();
            
            if (data.success) {
              showAlert('Success', data.message || 'Records archived successfully!');
              // Reload page to show updated records
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
            showAlert('Server Error', 'The server returned an unexpected response. Please check your console for details.');
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
          
          // Update status text
          if (progress <= 30) {
            statusText.textContent = 'Preparing records for archive...';
          } else if (progress <= 60) {
            statusText.textContent = 'Transferring records to archive...';
          } else {
            statusText.textContent = 'Finalizing archive process...';
          }
        }, 500);
      }

      function viewRecord(recordId) {
        fetch('<?= site_url("archive/get_record_details/") ?>' + recordId, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => {
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            const content = document.getElementById('recordDetailsContent');
            content.innerHTML = `
              <div class="row">
                <div class="col-md-6">
                  <h6>Student Information</h6>
                  <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${data.record.name}</td></tr>
                    <tr><td><strong>Birthday:</strong></td><td>${data.record.birthday}</td></tr>
                    <tr><td><strong>Sex:</strong></td><td>${data.record.sex}</td></tr>
                    <tr><td><strong>Age:</strong></td><td>${data.record.age || 'N/A'}</td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6>School Information</h6>
                  <table class="table table-sm">
                    <tr><td><strong>School:</strong></td><td>${data.record.school_name}</td></tr>
                    <tr><td><strong>School Level:</strong></td><td>${data.record.school_level || 'N/A'}</td></tr>
                    <tr><td><strong>Grade Level:</strong></td><td>${data.record.grade_level}</td></tr>
                    <tr><td><strong>Section:</strong></td><td>${data.record.section}</td></tr>
                    <tr><td><strong>School Year:</strong></td><td>${data.record.year}</td></tr>
                  </table>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-6">
                  <h6>Assessment Data</h6>
                  <table class="table table-sm">
                    <tr><td><strong>Weight:</strong></td><td>${data.record.weight} kg</td></tr>
                    <tr><td><strong>Height:</strong></td><td>${data.record.height} m</td></tr>
                    <tr><td><strong>Height Squared:</strong></td><td>${data.record.height_squared}</td></tr>
                    <tr><td><strong>BMI:</strong></td><td>${data.record.bmi}</td></tr>
                    <tr><td><strong>Nutritional Status:</strong></td><td><span class="badge ${getStatusBadgeClass(data.record.nutritional_status)}">${data.record.nutritional_status}</span></td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6>Additional Information</h6>
                  <table class="table table-sm">
                    <tr><td><strong>Legislative District:</strong></td><td>${data.record.legislative_district || 'N/A'}</td></tr>
                    <tr><td><strong>School District:</strong></td><td>${data.record.school_district || 'N/A'}</td></tr>
                    <tr><td><strong>Date of Weighing:</strong></td><td>${data.record.date_of_weighing}</td></tr>
                    <tr><td><strong>Assessment Type:</strong></td><td><span class="badge ${getAssessmentBadgeClass(data.record.assessment_type)}">${data.record.assessment_type}</span></td></tr>
                    <tr><td><strong>SBFP Beneficiary:</strong></td><td>${data.record.sbfp_beneficiary}</td></tr>
                    <tr><td><strong>Height for Age:</strong></td><td>${data.record.height_for_age || 'N/A'}</td></tr>
                    <tr><td><strong>Archived Date:</strong></td><td>${data.record.archived_at}</td></tr>
                  </table>
                </div>
              </div>
            `;
            recordDetailsModal.show();
          } else {
            showAlert('Error', data.message || 'Failed to load record details.');
          }
        })
        .catch(error => {
          console.error('View record error:', error);
          showAlert('Error', 'Failed to load record details. Please check your console for details.');
        });
      }

      function restoreRecord(recordId) {
        if (confirm('Are you sure you want to restore this record to active assessments?')) {
          fetch('<?= site_url("archive/restore_record/") ?>' + recordId, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              throw new Error('Server returned non-JSON response');
            }
            return response.json();
          })
          .then(data => {
            if (data.success) {
              showAlert('Success', data.message || 'Record restored successfully!');
              // Reload the table row
              setTimeout(() => {
                window.location.reload();
              }, 1500);
            } else {
              showAlert('Error', data.message || 'Failed to restore record.');
            }
          })
          .catch(error => {
            console.error('Restore error:', error);
            showAlert('Error', 'Failed to restore record. Please check your console for details.');
          });
        }
      }

      function showAlert(title, message) {
        document.getElementById('alertTitle').textContent = title;
        document.getElementById('alertBody').textContent = message;
        alertModal.show();
      }

      // Helper functions for badge classes (JavaScript version)
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
    </script>
  </body>
</html>