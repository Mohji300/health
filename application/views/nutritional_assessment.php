<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Assessment - <?php echo isset($assessment_type) ? ucfirst($assessment_type) : 'Baseline'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/nutritional_assessment.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>
      
      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">

          <!-- Header Card - Similar to User Dashboard -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h1 class="h2 font-weight-bold mb-2">Nutritional Assessment</h1>
                  <p class="mb-0 opacity-8">Add and manage student nutritional assessment records</p>
                </div>
              </div>
            </div>
          </div>


          <!-- Assessment Details Info Box -->
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

          <!-- Excel Upload Section (simplified) -->
          <div class="bg-purple-light p-4 rounded mb-4 no-print">
            <h2 class="h4 text-dark mb-3"><i class="fas fa-file-excel"></i> Extract Data from Nutritional Status Report</h2>
            <form id="uploadForm" enctype="multipart/form-data" class="d-flex flex-column align-items-center">
              <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls,.csv" class="d-none">
              <button type="button" id="chooseFileBtn" class="btn btn-purple mb-2">
                <i class="fas fa-file-excel me-2"></i>Choose Nutritional Report File
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
                          <input type="date" id="date" name="date" class="form-control" required>
                      </div>
                      <div class="mb-3">
                          <label for="name" class="form-label fw-bold">Name:</label>
                          <input type="text" id="name" name="name" class="form-control" placeholder="Last Name, First Name" required>
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

          <!-- Student Table Section -->
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
                    <tr><td colspan="13" class="text-center text-muted">No student records yet. Add some students above.</td></tr>
                  </tbody>
                </table>
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

        </div> <!-- /.container-fluid -->
      </div> <!-- /#page-content-wrapper -->
    </div> <!-- /#wrapper -->

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 bg-transparent shadow-none">
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
    window.NutritionalAssessmentConfig = {
      urls: {
        process_excel: '<?= site_url("nutritional_upload/process_excel"); ?>',
        bulk_store: '<?= site_url("nutritionalassessment/bulk_store"); ?>'
      },
      redirect_after: '<?= site_url("sbfpdashboard"); ?>'
    };
  </script>
  <script src="<?= base_url('assets/js/nutritional_assessment.js'); ?>"></script>
  </body>
</html>