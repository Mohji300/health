<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Nutritional Assessment Form View with Excel Upload (Bootstrap 5)
 * Place this file in application/views/nutritional_assessment.php of your CodeIgniter 3 app.
 */
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Assessment - <?php echo isset($assessment_type) ? ucfirst($assessment_type) : 'Baseline'; ?></title>
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
      .status-severely-wasted { background-color: #f8d7da !important; }
      .status-wasted { background-color: #fff3cd !important; }
      .status-overweight { background-color: #d1ecf1 !important; }
      .status-obese { background-color: #f5c6cb !important; }
      .no-print { /* no-print class handled by CSS */ }
      .bg-purple-light {
        background-color: #f8f9fa;
        border-left: 4px solid #6f42c1;
      }
      .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
      }
      .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
      }
      
      /* Assessment type styling */
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
        min-width: 90px;
      }
      .assessment-switcher .btn.active {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      }
      .assessment-switcher .btn:not(.active) {
        background: transparent;
        border-color: transparent;
      }
      .assessment-switcher .btn:not(.active):hover {
        background: rgba(0,0,0,0.05);
      }
      
      .assessment-badge {
        font-size: 0.8rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
        margin-left: 10px;
      }
      .badge-baseline {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
      }
      .badge-endline {
        background: linear-gradient(45deg, #1cc88a, #13855c);
        color: white;
      }
      
      /* Header styling */
      .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
      .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
      .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
      .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
      .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
      .text-gray-800 { color: #5a5c69 !important; }
      .text-gray-300 { color: #dddfeb !important; }
      
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
                          <span class="badge <?php echo (isset($assessment_type) && $assessment_type == 'endline') ? 'badge-endline' : 'badge-baseline'; ?>">
                              <?php echo isset($assessment_type) ? ucfirst($assessment_type) : 'Baseline'; ?>
                          </span>
                      </p>
                  </div>
              </div>
          </div>
          <?php endif; ?>

          <!-- Excel Upload Section -->
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

          <!-- Upload Results Section -->
          <div id="uploadResultsSection" class="d-none no-print">
            <div class="card mb-4">
              <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Extraction Results</h5>
              </div>
              <div class="card-body">
                <div id="uploadResultsMessage" class="alert alert-success"></div>
                <div id="uploadStudentsTableContainer"></div>
                <div class="text-end mt-3">
                  <button type="button" id="addExtractedStudentsBtn" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Add All to Student List
                  </button>
                </div>
              </div>
            </div>
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
    <div class="modal fade" id="loadingModal" tabindex="-1" backdrop="static" keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p>Submitting records...</p>
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

    <!-- Upload Loading Spinner -->
    <div id="uploadLoadingSpinner" class="text-center d-none no-print">
      <div class="spinner-border text-purple" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Extracting data from Excel file...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Keep all your existing JavaScript code exactly as it is
      const STORAGE_PREFIX = 'nutritional_assessment_';
      let students = [];
      let alertModal = null;
      let loadingModal = null;
      let extractedStudents = [];

      // Initialize
      document.addEventListener('DOMContentLoaded', () => {
        alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
        loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), { keyboard: false, backdrop: 'static' });
        
        // Set today's date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;

        // Load from localStorage
        loadStudents();
        updateUI();

        // Event listeners
        document.getElementById('assessmentForm').addEventListener('submit', (e) => {
          e.preventDefault();
          addStudent();
        });

        document.getElementById('clearFormBtn').addEventListener('click', clearForm);
        document.getElementById('clearAllBtn').addEventListener('click', clearAllStudents);
        document.getElementById('submitBtn').addEventListener('click', submitReport);
        
        // Excel upload event listeners
        document.getElementById('chooseFileBtn').addEventListener('click', function() {
          document.getElementById('excelFile').click();
        });

        document.getElementById('excelFile').addEventListener('change', function(event) {
          if (event.target.files.length > 0) {
            extractFromExcel();
          }
        });

        document.getElementById('addExtractedStudentsBtn').addEventListener('click', addExtractedStudents);
        
        // Assessment switcher
        document.getElementById('switchToBaseline').addEventListener('click', function() {
          switchAssessmentType('baseline');
        });
        
        document.getElementById('switchToEndline').addEventListener('click', function() {
          switchAssessmentType('endline');
        });
      });

      function switchAssessmentType(type) {
        // Get current URL
        const currentUrl = window.location.href;
        const url = new URL(currentUrl);
        
        // Update assessment_type parameter
        url.searchParams.set('assessment_type', type);
        
        // Reload page with new assessment type
        window.location.href = url.toString();
      }

      // Excel Upload Functions
      function extractFromExcel() {
        const file = document.getElementById('excelFile').files[0];
        if (!file) return;

        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(fileExtension)) {
          showAlert('Invalid File', 'Please select an Excel file (.xlsx, .xls) or CSV file');
          return;
        }

        if (file.size > 5 * 1024 * 1024) { // 5MB
          showAlert('File Too Large', 'Please select a file smaller than 5MB');
          return;
        }

        document.getElementById('uploadLoadingSpinner').classList.remove('d-none');
        document.getElementById('chooseFileBtn').disabled = true;

        const formData = new FormData();
        formData.append('excel_file', file);

        fetch('<?php echo site_url("nutritional_upload/process_excel"); ?>', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          document.getElementById('uploadLoadingSpinner').classList.add('d-none');
          document.getElementById('chooseFileBtn').disabled = false;
          document.getElementById('excelFile').value = '';

          if (data.success) {
            extractedStudents = data.students || [];
            showUploadResults(data.message, extractedStudents);
          } else {
            showAlert('Processing Error', data.message);
          }
        })
        .catch(error => {
          document.getElementById('uploadLoadingSpinner').classList.add('d-none');
          document.getElementById('chooseFileBtn').disabled = false;
          document.getElementById('excelFile').value = '';
          showAlert('Error', 'An error occurred while processing the file: ' + error.message);
        });
      }

      function showUploadResults(message, students) {
        const resultsMessage = document.getElementById('uploadResultsMessage');
        const resultsSection = document.getElementById('uploadResultsSection');
        const tableContainer = document.getElementById('uploadStudentsTableContainer');

        resultsMessage.textContent = message;
        resultsSection.classList.remove('d-none');

        if (students && students.length > 0) {
          let tableHtml = `
            <div class="table-responsive mt-3">
              <table class="table table-sm table-striped">
                <thead class="table-dark">
                  <tr>
                    <th>Name</th>
                    <th>Birthday</th>
                    <th>Weight</th>
                    <th>Height</th>
                    <th>Sex</th>
                    <th>BMI</th>
                    <th>Nutritional Status</th>
                    <th>Height-for-Age</th>
                  </tr>
                </thead>
                <tbody>
          `;

          students.forEach(student => {
            tableHtml += `
              <tr>
                <td>${escapeHtml(student.name)}</td>
                <td>${escapeHtml(student.birthday)}</td>
                <td>${student.weight || ''}</td>
                <td>${student.height || ''}</td>
                <td>${escapeHtml(student.sex)}</td>
                <td>${student.bmi || ''}</td>
                <td>${escapeHtml(student.nutritional_status)}</td>
                <td>${escapeHtml(student.height_for_age)}</td>
              </tr>
            `;
          });

          tableHtml += `
                </tbody>
              </table>
            </div>
          `;

          tableContainer.innerHTML = tableHtml;
        }
      }

      function addExtractedStudents() {
        if (extractedStudents.length === 0) {
          showAlert('No Students', 'No extracted students to add.');
          return;
        }

        let addedCount = 0;
        
        extractedStudents.forEach((extractedStudent, index) => {
          console.log("Extracted student data:", extractedStudent);
          
          // Calculate age from birthday
          const birthday = extractedStudent.birthday;
          let ageYears = 0, ageMonths = 0;
          let ageDisplay = '0|0';
          
          if (birthday) {
            const bdate = new Date(birthday);
            const today = new Date();
            ageYears = today.getFullYear() - bdate.getFullYear();
            ageMonths = today.getMonth() - bdate.getMonth();
            if (ageMonths < 0) {
              ageYears--;
              ageMonths += 12;
            }
            ageDisplay = ageYears + '|' + ageMonths;
          }
          
          // Map snake_case to camelCase for the UI
          const student = {
            name: extractedStudent.name,
            birthday: extractedStudent.birthday,
            weight: extractedStudent.weight,
            height: extractedStudent.height,
            sex: extractedStudent.sex,
            grade: document.getElementById('grade').value,
            section: document.getElementById('section').value,
            school_year: document.getElementById('school_year').value,
            date: document.getElementById('date').value,
            legislative_district: document.getElementById('legislative_district').value,
            school_district: document.getElementById('school_district').value,
            school_id: document.getElementById('school_id').value,
            school_name: document.getElementById('school_name').value,
            heightSquared: extractedStudent.height_squared || (extractedStudent.height ? (extractedStudent.height * extractedStudent.height).toFixed(4) : null),
            age: ageDisplay,
            ageYears: ageYears,
            ageMonths: ageMonths,
            ageDisplay: ageDisplay,
            bmi: extractedStudent.bmi,
            nutritionalStatus: extractedStudent.nutritional_status || 'Not Specified',
            heightForAge: extractedStudent.height_for_age || 'Not Specified',
            sbfpBeneficiary: extractedStudent.sbfp_beneficiary || ((extractedStudent.nutritional_status === 'Severely Wasted' || extractedStudent.nutritional_status === 'Wasted') ? 'Yes' : 'No')
          };
          
          console.log("Mapped student data with age:", student);
          
          students.push(student);
          addedCount++;
        });

        saveStudents();
        updateUI();
        document.getElementById('uploadResultsSection').classList.add('d-none');
        showAlert('Success', `Added ${addedCount} student(s) to the list.`);
        extractedStudents = [];
      }

      // Existing Functions (keep all your existing functions the same)
function getStorageKey() {
    const ld = document.getElementById('legislative_district').value || 'na';
    const sd = document.getElementById('school_district').value || 'na';
    const gr = document.getElementById('grade').value || 'na';
    const sc = document.getElementById('section').value || 'na';
    const sy = document.getElementById('school_year').value || 'na';
    const si = document.getElementById('school_id').value || 'na';
    const sn = document.getElementById('school_name').value || 'na';
    return STORAGE_PREFIX + [ld, sd, gr, sc, sy, sn].join('_');
}

      function loadStudents() {
        const key = getStorageKey();
        const stored = localStorage.getItem(key);
        if (stored) {
          try {
            students = JSON.parse(stored);
            // Update the current session count in the stats card (guarded)
            const curEl = document.getElementById('currentSessionCount');
            if (curEl) {
              curEl.textContent = students.length;
            } else {
              console.warn('Element #currentSessionCount not found when loading students');
            }
          } catch (e) {
            console.error('Error loading students:', e);
            students = [];
          }
        }
      }

      function saveStudents() {
        const key = getStorageKey();
        localStorage.setItem(key, JSON.stringify(students));
        // Update the current session count
        const curEl = document.getElementById('currentSessionCount');
        if (curEl) curEl.textContent = students.length;
      }

      function clearStoredStudents() {
        const key = getStorageKey();
        localStorage.removeItem(key);
        const curEl = document.getElementById('currentSessionCount');
        if (curEl) curEl.textContent = '0';
      }

function calculateStudentData(name, birthday, weight, height, sex) {
    let ageYears = 0, ageMonths = 0;
    if (birthday) {
        const bdate = new Date(birthday);
        const today = new Date();
        ageYears = today.getFullYear() - bdate.getFullYear();
        ageMonths = today.getMonth() - bdate.getMonth();
        if (ageMonths < 0) {
            ageYears--;
            ageMonths += 12;
        }
    }

    const heightSq = (height * height).toFixed(4);
    const bmi = (weight / (height * height)).toFixed(2);

    let nutritionalStatus = 'Normal';
    if (bmi < 16) nutritionalStatus = 'Severely Wasted';
    else if (bmi < 18.5) nutritionalStatus = 'Wasted';
    else if (bmi < 25) nutritionalStatus = 'Normal';
    else if (bmi < 30) nutritionalStatus = 'Overweight';
    else nutritionalStatus = 'Obese';

    const sbfpBeneficiary = (nutritionalStatus === 'Severely Wasted' || nutritionalStatus === 'Wasted') ? 'Yes' : 'No';

    return {
        name: name.trim(),
        birthday: birthday,
        weight: parseFloat(weight),
        height: parseFloat(height),
        sex: sex,
        grade: document.getElementById('grade').value,
        section: document.getElementById('section').value,
        school_year: document.getElementById('school_year').value, // Add this line
        date: document.getElementById('date').value,
        legislative_district: document.getElementById('legislative_district').value,
        school_district: document.getElementById('school_district').value,
        school_id: document.getElementById('school_id').value,
        school_name: document.getElementById('school_name').value,
        heightSquared: parseFloat(heightSq),
        age: ageYears + '|' + ageMonths,
        ageYears: ageYears,
        ageMonths: ageMonths,
        ageDisplay: ageYears + '|' + ageMonths,
        bmi: parseFloat(bmi),
        nutritionalStatus: nutritionalStatus,
        heightForAge: 'Normal',
        sbfpBeneficiary: sbfpBeneficiary
    };
}

      function addStudent() {
        const form = document.getElementById('assessmentForm');
        if (!form.checkValidity()) {
          form.classList.add('was-validated');
          return;
        }

        const name = document.getElementById('name').value;
        const birthday = document.getElementById('birthday').value;
        const weight = document.getElementById('weight').value;
        const height = document.getElementById('height').value;
        const sex = document.getElementById('sex').value;

        const student = calculateStudentData(name, birthday, weight, height, sex);
        students.push(student);
        saveStudents();
        clearForm();
        updateUI();
        showAlert('Success', 'Student added to the list!');
      }

      function removeStudent(idx) {
        if (confirm('Remove this student?')) {
          students.splice(idx, 1);
          saveStudents();
          updateUI();
        }
      }

      function clearForm() {
        document.getElementById('name').value = '';
        document.getElementById('birthday').value = '';
        document.getElementById('weight').value = '';
        document.getElementById('height').value = '';
        document.getElementById('sex').value = '';
        document.getElementById('assessmentForm').classList.remove('was-validated');
      }

      function clearAllStudents() {
        if (!confirm('Clear all student records?')) return;
        students = [];
        saveStudents();
        updateUI();
        showAlert('Success', 'All records cleared.');
      }

      function updateUI() {
        const tbody = document.getElementById('studentTableBody');
        const count = document.getElementById('studentCount');
        const clearBtn = document.getElementById('clearAllBtn');
        const submitBtn = document.getElementById('submitBtn');

        if (count) {
          count.textContent = students.length + ' Student' + (students.length !== 1 ? 's' : '');
        } else {
          console.warn('Element #studentCount not found');
        }

        if (clearBtn) clearBtn.disabled = students.length === 0;
        if (submitBtn) submitBtn.disabled = students.length === 0;

        const curEl = document.getElementById('currentSessionCount');
        if (curEl) curEl.textContent = students.length;

        if (!tbody) {
          console.warn('Element #studentTableBody not found; skipping table render');
          return;
        }

        if (students.length === 0) {
          tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted">No student records yet. Add some students above.</td></tr>';
          return;
        }

tbody.innerHTML = students.map((s, idx) => `
    <tr class="${getRowClass(s.nutritionalStatus)}">
        <td>${s.name}</td>
        <td>${s.birthday}</td>
        <td>${s.grade}</td>
        <td>${s.school_year || s.year || 'N/A'}</td> <!-- Added School Year -->
        <td>${s.weight}</td>
        <td>${s.height}</td>
        <td>${s.sex}</td>
        <td>${s.heightSquared}</td>
        <td>${s.ageDisplay}</td>
        <td>${s.bmi}</td>
        <td>${s.nutritionalStatus}</td>
        <td>${s.heightForAge}</td>
        <td>${s.sbfpBeneficiary}</td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeStudent(${idx})"><i class="fas fa-trash"></i></button></td>
    </tr>
`).join('');
      }

      function getRowClass(status) {
        switch (status) {
          case 'Severely Wasted': return 'status-severely-wasted';
          case 'Wasted': return 'status-wasted';
          case 'Overweight': return 'status-overweight';
          case 'Obese': return 'status-obese';
          default: return '';
        }
      }

      async function submitReport() {
        if (students.length === 0) {
          showAlert('No Records', 'Add students before submitting.');
          return;
        }

        if (!confirm('Submit ' + students.length + ' student record(s)?')) return;

        loadingModal.show();

        try {
          // Get assessment_type from URL or default to baseline
          const urlParams = new URLSearchParams(window.location.search);
          const assessmentType = urlParams.get('assessment_type') || 'baseline';
          
          console.log("Submitting with assessment_type:", assessmentType);
          
          const response = await fetch('<?= site_url('nutritionalassessment/bulk_store') ?>', {
            method: 'POST',
            headers: { 
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'students=' + encodeURIComponent(JSON.stringify(students)) + 
                  '&assessment_type=' + encodeURIComponent(assessmentType)
          });

          const result = await response.json();
          console.log("Server response:", result);

          if (result.success) {
            students = [];
            saveStudents();
            clearStoredStudents();
            updateUI();
            loadingModal.hide();
            showAlert('Success', result.message || 'Records submitted successfully!');
            setTimeout(() => window.location.href = '<?= site_url('sbfpdashboard') ?>', 1500);
          } else {
            loadingModal.hide();
            showAlert('Error', result.message || 'Error submitting records. Check console for details.');
            console.error("Submission errors:", result.errors);
          }
        } catch (e) {
          loadingModal.hide();
          console.error("Network error:", e);
          showAlert('Network Error', 'Error communicating with server: ' + e.message);
        }
      }

      function showAlert(title, message) {
        const tEl = document.getElementById('alertTitle');
        const bEl = document.getElementById('alertBody');
        if (tEl) tEl.textContent = title;
        if (bEl) bEl.textContent = message;
        if (typeof alertModal !== 'undefined' && alertModal) {
          alertModal.show();
        } else {
          // Fallback: use built-in alert
          console.warn('alertModal not initialized; falling back to window.alert');
          window.alert(title + '\n\n' + message);
        }
      }

      function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe
          .toString()
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }
    </script>
  </body>
</html>