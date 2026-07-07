<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Assessment - <?php echo isset($assessment_type) ? ucfirst($assessment_type) : 'Baseline'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/nutritional_assessment.css'); ?>">
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
                  <h1 class="h2 font-weight-bold mb-2"><?php echo (isset($is_beneficiary_mode) && $is_beneficiary_mode) ? 'Manage Beneficiaries' : 'Upload Nutritional Assessment'; ?></h1>
                  <p class="mb-0 opacity-8">
                    <?php if (isset($is_beneficiary_mode) && $is_beneficiary_mode): ?>
                      Mark or unmark students as SBFP beneficiaries
                    <?php else: ?>
                      Add and manage student nutritional assessment records
                    <?php endif; ?>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Assessment Details Info Box (always visible) -->
          <?php if (!empty($legislative_district) && !empty($school_district) && !empty($grade) && !empty($section)): ?>
          <div class="alert alert-info mb-4 no-print">
              <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Assessment Details</h5>
              <div class="row">
                  <div class="col-md-6">
                      <p class="mb-1"><strong>Legislative District:</strong> <?= htmlspecialchars($legislative_district) ?></p>
                      <p class="mb-0"><strong>School District:</strong> <?= htmlspecialchars($school_district) ?></p>
                      <p class="mb-1"><strong>School ID:</strong> <?= htmlspecialchars($school_id)?></p>
                      <p class="mb-0"><strong>School Name:</strong> <?= htmlspecialchars($school_name ?? 'Unknown') ?></p>
                  </div>
                  <div class="col-md-6">
                      <p class="mb-1"><strong>Grade Level:</strong> <?= htmlspecialchars($grade) ?></p>
                      <p class="mb-0"><strong>Section:</strong> <?= htmlspecialchars($section) ?></p>
                      <p class="mb-0"><strong>School Year:</strong> <?= htmlspecialchars($school_year ?? 'Not set') ?></p>
                      <p class="mb-0"><strong>Assessment Type:</strong> 
                          <span class="badge <?php 
                              echo (isset($assessment_type) && $assessment_type == 'endline') ? 'badge-endline' : 
                                  ((isset($assessment_type) && $assessment_type == 'midline') ? 'badge-midline' : 'badge-baseline'); 
                                ?>">
                              <?php echo isset($assessment_type) ? ucfirst($assessment_type) : 'Baseline'; ?>
                          </span>
                      </p>
                  </div>
              </div>
          </div>
          <?php endif; ?>

          <?php if (isset($is_beneficiary_mode) && $is_beneficiary_mode): ?>
            <div class="card mb-4">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Manage Beneficiaries</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> 
                  Here you can mark or unmark students as SBFP beneficiaries for the 
                  <strong><?php echo ucfirst($assessment_type); ?></strong> assessment of 
                  <strong><?php echo htmlspecialchars($grade); ?> - <?php echo htmlspecialchars($section); ?></strong>.
                </div>

                <div class="mb-3">
                  <button type="button" id="toggleAllYesBtn" class="btn btn-success btn-sm">
                    <i class="fas fa-check-circle"></i> Mark All as Beneficiary
                  </button>
                  <button type="button" id="toggleAllNoBtn" class="btn btn-warning btn-sm">
                    <i class="fas fa-times-circle"></i> Mark All as Non-Beneficiary
                  </button>
                </div>

                <div class="table-responsive">
                  <table class="table table-bordered table-sm compact-table" id="beneficiaryTable" style="width:100%">
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
                        <th class="col-beneficiary">SBFP Beneficiary</th>
                        <th class="col-action">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($students)): ?>
                        <?php 
                        $counter = 1;
                        foreach ($students as $student): 
                          // Format dates
                          $dob = date('m/d/Y', strtotime($student->birthday));
                          $weighing_date = date('m/d/Y', strtotime($student->date_of_weighing));
                          // Nutritional status badge class
                          $ns_class = '';
                          switch($student->nutritional_status) {
                            case 'Severely Wasted': $ns_class = 'badge-severely-wasted'; break;
                            case 'Wasted': $ns_class = 'badge-wasted'; break;
                            case 'Normal': $ns_class = 'badge-normal'; break;
                            case 'Overweight': $ns_class = 'badge-overweight'; break;
                            case 'Obese': $ns_class = 'badge-obese'; break;
                            default: $ns_class = 'badge-secondary';
                          }
                          // HFA badge class
                          $hfa_lc = strtolower($student->height_for_age);
                          $hfa_class = 'badge-secondary';
                          $hfa_display = $student->height_for_age;
                          if ($hfa_lc === 'severely stunted') {
                            $hfa_class = 'badge-severely-wasted';
                            $hfa_display = 'Severely Stunted';
                          } elseif ($hfa_lc === 'stunted') {
                            $hfa_class = 'badge-wasted';
                            $hfa_display = 'Stunted';
                          } elseif ($hfa_lc === 'normal') {
                            $hfa_class = 'badge-normal';
                            $hfa_display = 'Normal';
                          } elseif ($hfa_lc === 'tall' || $hfa_lc === 'above normal') {
                            $hfa_class = 'badge-info';
                            $hfa_display = 'Tall';
                          }
                        ?>
                          <tr data-id="<?= $student->id ?>">
                            <td class="text-center"><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($student->name) ?></td>
                            <td class="text-center"><?= $student->sex ?></td>
                            <td><?= htmlspecialchars($student->grade_level) ?>/<?= htmlspecialchars($student->section) ?></td>
                            <td class="text-center"><?= $dob ?></td>
                            <td class="text-center"><?= $weighing_date ?></td>
                            <td class="text-center"><?= htmlspecialchars($student->age) ?></td>
                            <td class="text-center"><?= number_format($student->weight, 1) ?></td>
                            <td class="text-center"><?= $student->height ?></td>
                            <td class="text-center"><?= $student->bmi ?></td>
                            <td class="text-center"><span class="badge <?= $ns_class ?> text-black"><?= $student->nutritional_status ?></span></td>
                            <td class="text-center"><span class="badge <?= $hfa_class ?> text-black"><?= htmlspecialchars($hfa_display) ?></span></td>
                            <td class="text-center beneficiary-status-cell">
                              <?php if ($student->sbfp_beneficiary == 'Yes'): ?>
                                <span class="badge bg-success">Yes</span>
                              <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                              <?php endif; ?>
                            </td>
                            <td class="text-center">
                              <button class="btn btn-sm btn-primary toggle-beneficiary" 
                                      data-id="<?= $student->id ?>" 
                                      data-current="<?= $student->sbfp_beneficiary ?>">
                                <i class="fas fa-exchange-alt"></i> Toggle
                              </button>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr><td colspan="14" class="text-center text-muted">No students found for this section.<?php if ($assessment_type == 'baseline'): ?> You need to upload baseline assessment first.<?php endif; ?></td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <a href="<?= site_url('sbfp/dashboard') ?>" class="btn btn-secondary mt-3">
                  <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
              </div>
            </div>

          <?php else: ?>
            <!-- Excel Upload Section -->
            <div class="bg-purple-light p-4 rounded mb-4 no-print">
              <h2 class="h4 text-dark mb-3"><i class="fas fa-file-excel"></i> Extract Data from Nutritional Status Report</h2>
              <form id="uploadForm" enctype="multipart/form-data" class="d-flex flex-column align-items-center">
                <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls,.csv" class="d-none">
                <button type="button" id="chooseFileBtn" class="btn btn-purple mb-2">
                  <i class="fas fa-file-excel me-2"></i>Upload Nutritional Report File
                </button>
                <small class="text-muted">Supported formats: .xlsx, .xls, .csv (Max 5MB)</small>
              </form>
            </div>

            <!-- Instructions -->
            <div class="alert alert-primary mb-4 no-print">
              <h5 class="alert-heading"><i class="fas fa-lightbulb"></i> Instructions</h5>
              <p>
                Fill out all fields below and click <strong>"Add Student to List"</strong> to add to the list below. 
                Student data will be saved locally in your browser until you click <strong>"Submit Report"</strong>.
                The SBFP Beneficiary column will appear automatically if the nutritional status is "Severely Wasted" or "Wasted".
                When you're ready to save all records to the database, click <strong>"Submit Report"</strong>.
              </p>
            </div>

            <!-- Form Section -->
            <div class="card mb-4 no-print">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-pen-to-square"></i> Add Student</h5>
              </div>
              <div class="card-body">
                <form id="assessmentForm" class="needs-validation" novalidate>
                  <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date" class="form-label fw-bold">Date of Weighing:</label>
                            <input type="date" id="date" name="date" class="form-control" required value="<?= htmlspecialchars($this->input->get('date') ?? '') ?>">
                        </div>
                        <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="last_name" class="form-label fw-bold">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" class="form-control text-uppercase" placeholder="Last Name" required style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-5">
                            <label for="first_name" class="form-label fw-bold">First Name:</label>
                            <input type="text" id="first_name" name="first_name" class="form-control text-uppercase" placeholder="First Name" required style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-2">
                            <label for="middle_initial" class="form-label fw-bold">M.I.:</label>
                            <input type="text" id="middle_initial" name="middle_initial" class="form-control text-uppercase" placeholder="M.I." maxlength="2" style="text-transform: uppercase;">
                        </div>
                        </div>
                        <div class="mb-3">
                            <label for="birthday" class="form-label fw-bold">Birthday:</label>
                            <input type="date" id="birthday" name="birthday" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="school_year" class="form-label fw-bold">School Year:</label>
                            <input type="text" id="school_year" name="school_year" 
                                  class="form-control bg-light" 
                                  value="<?= htmlspecialchars($school_year ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="legislative_district" class="form-label fw-bold">Legislative District:</label>
                            <input type="text" id="legislative_district" name="legislative_district" 
                                  class="form-control bg-light" 
                                  value="<?= htmlspecialchars($legislative_district ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="school_name" class="form-label fw-bold">School Name:</label>
                            <input type="text" id="school_name" name="school_name" 
                                  class="form-control bg-light" 
                                  value="<?= htmlspecialchars($school_name ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="school_id" class="form-label fw-bold">School ID: </label>
                            <input type="text" id="school_id" name="school_id" 
                                  class="form-control bg-light" 
                                  value="<?= htmlspecialchars($school_id ?? '') ?>" readonly>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="weight" class="form-label fw-bold">Weight (kg):</label>
                        <input type="number" id="weight" name="weight" class="form-control" step="0.1" min="0" required>
                      </div>
                      <div class="mb-3">
                        <label for="height" class="form-label fw-bold">Height (meters):</label>
                        <input type="number" id="height" name="height" class="form-control" step="0.01" min="0" required>
                      </div>
                      <div class="mb-3">
                        <label for="sex" class="form-label fw-bold">Sex:</label>
                        <select id="sex" name="sex" class="form-select" required>
                          <option value="">Select Sex</option>
                          <option value="M">Male</option>
                          <option value="F">Female</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="school_district" class="form-label fw-bold">School District:</label>
                        <input type="text" id="school_district" name="school_district" 
                               class="form-control bg-light" 
                               value="<?= htmlspecialchars($school_district ?? '') ?>" readonly>
                      </div>
                      <div class="mb-3">
                        <label for="grade" class="form-label fw-bold">Grade Level:</label>
                        <input type="text" id="grade" name="grade" 
                               class="form-control bg-light" 
                               value="<?= htmlspecialchars($grade ?? '') ?>" readonly>
                      </div>
                      <div class="mb-3">
                        <label for="section" class="form-label fw-bold">Section:</label>
                        <input type="text" id="section" name="section" 
                               class="form-control bg-light" 
                               value="<?= htmlspecialchars($section ?? '') ?>" readonly>
                      </div>
                      <input type="hidden" id="section_id_hidden" value="<?= htmlspecialchars($section_id ?? '') ?>">
                    </div>
                  </div>

                  <div class="row mt-3">
                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-success me-2">
                        <i class="fas fa-plus"></i> Add Student to List
                      </button>
                      <button type="button" id="clearFormBtn" class="btn btn-secondary">
                        <i class="fas fa-eraser"></i> Clear Form
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- Student Table Section (Session) -->
            <div class="card no-print">
              <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table"></i> Student Records (Current Session)</h5>
                <span class="badge bg-light text-dark" id="studentCount">0 Students</span>
              </div>
              <div class="card-body">
                <div class="alert alert-warning mb-3">
                  <i class="fas fa-exclamation-triangle"></i>
                  <strong>Note:</strong> Student records are saved locally in your browser. 
                  They will persist if you refresh the page or close the browser, but will be cleared after submission.
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-striped" id="studentTable">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Birthday</th>
                        <th>Grade</th>
                        <th>School Year</th>
                        <th>Weight (kg)</th>
                        <th>Height (m)</th>
                        <th>Sex</th>
                        <th>Height² (m²)</th>
                        <th>Age (y|m)</th>
                        <th>BMI</th>
                        <th>Nutritional Status</th>
                        <th>Height-For-Age</th>
                        <th>SBFP Beneficiary</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody id="studentTableBody">
                      <tr><td colspan="14" class="text-center text-muted">No student records yet. Add some students above.</td></tr>
                    </tbody>
                  </table>
                </div>

                <!-- Server-side Errors Panel (populated after submission) -->
                <div id="serverErrorsPanel" class="alert alert-danger d-none mt-3" role="alert">
                  <h5 class="mb-2">Submission Errors</h5>
                  <p id="serverErrorsMessage">The following records could not be saved:</p>
                  <ul id="serverErrorsList" class="mb-0"></ul>
                </div>

                <div class="row mt-3">
                  <div class="col-12 text-end">
                    <button type="button" id="clearAllBtn" class="btn btn-danger me-2" disabled>
                      <i class="fas fa-trash"></i> Clear All
                    </button>
                    <button type="button" id="submitBtn" class="btn btn-primary" disabled>
                      <i class="fas fa-paper-plane"></i> Submit Report
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div> 
      </div> 
    </div> 

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 bg-white shadow-none">
          <div class="modal-body text-center p-4">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-3 text-dark fs-5 fw-semibold">Submitting records...</div>
            <div class="text-muted mt-2">Please wait while we save your data.</div>
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

    <!-- Upload Loading Modal -->
    <div class="modal fade" id="uploadLoadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
          <div class="modal-body text-center p-5">
            <div class="spinner-border text-purple mb-3" style="width: 3rem; height: 3rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="text-dark mb-2">Extracting Data</h5>
            <p class="text-muted mb-0">Processing Excel file. This may take a moment...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Clear All Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmTitle">Confirmation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="confirmBody">Are you sure?</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmYesBtn">Yes, Clear All</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Report Confirmation Modal -->
    <div class="modal fade" id="submitConfirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="submitConfirmTitle">Submit Report Confirmation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="submitConfirmBody">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> 
              <strong>Important:</strong> Once submitted, all student records will be permanently saved to the database and cleared from this page.
            </div>
            <p id="submitConfirmText"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitConfirmYesBtn">
              <i class="fas fa-paper-plane"></i> Yes, Submit Report
            </button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      window.nutritionalassessmentConfig = {
        urls: {
          process_excel: '<?= site_url("nutritional_upload/process_excel"); ?>',
          bulk_store: '<?= site_url("nutritionalassessment/bulk_store"); ?>',
          classify: '<?= site_url("nutritionalassessment/classify"); ?>'
        },
        redirect_after: '<?= site_url("sbfp/dashboard"); ?>'
      };
      var existingWeighingDate = '<?php echo isset($existing_weighing_date) ? $existing_weighing_date : ''; ?>';
    </script>
    
    <script src="<?= base_url(ASSETS_PATH . '/js/nutritional_assessment.js'); ?>"></script>

    <script>
      (function() {
        var pendingAction = null; // 'single' or 'all'
        var pendingId = null;
        var pendingNewValue = null;
        var pendingButton = null;

        var confirmBtn = document.getElementById('confirmYesBtn');
        var confirmModalEl = document.getElementById('confirmModal');

        function init() {
          // Single toggle: show modal
          document.querySelectorAll('.toggle-beneficiary').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
              var id = this.dataset.id;
              var current = this.dataset.current;
              var newValue = (current === 'Yes') ? 'No' : 'Yes';
              var actionText = (newValue === 'Yes') ? 'mark' : 'remove';
              var statusText = (newValue === 'Yes') ? 'beneficiary' : 'non-beneficiary';

              pendingAction = 'single';
              pendingId = id;
              pendingNewValue = newValue;
              pendingButton = this;

              // Change to GREEN for beneficiary update
              confirmBtn.textContent = 'Yes, Update';
              confirmBtn.className = 'btn btn-success';

              document.getElementById('confirmTitle').textContent = 'Confirm Beneficiary Update';
              document.getElementById('confirmBody').innerHTML = 
                'Are you sure you want to <strong>' + actionText + '</strong> this student as a <strong>' + statusText + '</strong>?';

              if (confirmModal) {
                  confirmModal.show();
              } else {
                  // fallback (should not happen)
                  var modal = new bootstrap.Modal(confirmModalEl);
                  modal.show();
              }
            });
          });

          // Mark All buttons
          document.getElementById('toggleAllYesBtn').addEventListener('click', function() {
            showAllConfirmation('Yes', 'beneficiary');
          });
          document.getElementById('toggleAllNoBtn').addEventListener('click', function() {
            showAllConfirmation('No', 'non-beneficiary');
          });

          function showAllConfirmation(value, statusText) {
            pendingAction = 'all';
            pendingNewValue = value;

            // Change to GREEN for bulk beneficiary update
            confirmBtn.textContent = 'Yes, Update All';
            confirmBtn.className = 'btn btn-success';

            document.getElementById('confirmTitle').textContent = 'Confirm Bulk Update';
            document.getElementById('confirmBody').innerHTML = 
              'Are you sure you want to mark <strong>ALL</strong> students as <strong>' + statusText + '</strong>? This action cannot be undone.';

          if (confirmModal) {
              confirmModal.show();
          } else {
              // fallback (should not happen)
              var modal = new bootstrap.Modal(confirmModalEl);
              modal.show();
          }
          }

          // Handle modal confirm button click
          confirmBtn.addEventListener('click', function() {
            if (pendingAction === 'single') {
              executeSingleToggle(pendingId, pendingNewValue, pendingButton);
            } else if (pendingAction === 'all') {
              executeAllToggle(pendingNewValue);
            }
            // Reset pending
            pendingAction = null;
            pendingId = null;
            pendingNewValue = null;
            pendingButton = null;
            // Reset to RED (default for delete/clear)
            confirmBtn.textContent = 'Yes, Clear All';
            confirmBtn.className = 'btn btn-danger';
          });

          // Reset to RED when modal is hidden (e.g., user clicks Cancel)
          confirmModalEl.addEventListener('hidden.bs.modal', function() {
            if (pendingAction === null) {
              confirmBtn.textContent = 'Yes, Clear All';
              confirmBtn.className = 'btn btn-danger';
            }
          });
        }

        function executeSingleToggle(id, newValue, btn) {
          fetch('<?= site_url("nutritionalassessment/toggle_beneficiary") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id) + '&beneficiary=' + encodeURIComponent(newValue)
          })
          .then(response => response.json())
          .then(resp => {
            if (resp.success) {
              var row = btn.closest('tr');
              var statusCell = row.querySelector('.beneficiary-status-cell');
              if (newValue === 'Yes') {
                statusCell.innerHTML = '<span class="badge bg-success">Yes</span>';
                btn.dataset.current = 'Yes';
              } else {
                statusCell.innerHTML = '<span class="badge bg-secondary">No</span>';
                btn.dataset.current = 'No';
              }
              showNotification('Beneficiary status updated.', 'success');
              if (confirmModal) confirmModal.hide();   // <-- ADD THIS
            } else {
              showNotification('Error: ' + (resp.message || 'Could not update'), 'danger');
              if (confirmModal) confirmModal.hide();   // <-- AND THIS
            }
          })
          .catch(function() {
            showNotification('Server error. Please try again.', 'danger');
            if (confirmModal) confirmModal.hide();     // <-- AND THIS
          });
        }

        function executeAllToggle(value) {
          var data = {
            legislative_district: '<?= htmlspecialchars($legislative_district) ?>',
            school_district: '<?= htmlspecialchars($school_district) ?>',
            school_id: '<?= htmlspecialchars($school_id) ?>',
            grade: '<?= htmlspecialchars($grade) ?>',
            section: '<?= htmlspecialchars($section) ?>',
            school_year: '<?= htmlspecialchars($school_year) ?>',
            assessment_type: '<?= htmlspecialchars($assessment_type) ?>',
            beneficiary: value
          };

          var formData = new URLSearchParams();
          for (var key in data) {
            formData.append(key, data[key]);
          }

          fetch('<?= site_url("nutritionalassessment/toggle_all_beneficiaries") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
          })
          .then(response => response.json())
          .then(resp => {
            if (resp.success) {
              showNotification('Updated ' + (resp.updated || 0) + ' student(s).', 'success');
              if (confirmModal) confirmModal.hide();   // <-- ADD THIS
              location.reload();
            } else {
              showNotification('Error updating all.', 'danger');
              if (confirmModal) confirmModal.hide();   // <-- ADD THIS
            }
          })
          .catch(function() {
            showNotification('Server error.', 'danger');
            if (confirmModal) confirmModal.hide();     // <-- ADD THIS
          });
        }

        function showNotification(msg, type) {
          var alertDiv = document.createElement('div');
          alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed top-0 end-0 m-3';
          alertDiv.style.zIndex = 9999;
          alertDiv.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
          document.body.appendChild(alertDiv);
          setTimeout(function() {
            alertDiv.classList.remove('show');
            setTimeout(function() { alertDiv.remove(); }, 300);
          }, 3000);
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', init);
        } else {
          init();
        }
      })();
    </script>
    
  </body>
</html>