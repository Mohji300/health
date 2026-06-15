<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Management System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo base_url(ASSETS_PATH . '/css/enrollment.css'); ?>">
</head>

<body class="bg-light">
<div class="d-flex" id="wrapper">
    <?php $this->load->view('templates/sidebar'); ?>
    <div id="page-content-wrapper" class="w-100">
<!-- Main content area -->
<div class="main-content" id="mainContent">
    <?php
    // Header (reports header template)
    $title = 'Enrollment Tracker';
    $current_filters = [];
    $reports = [];
    $this->load->view('templates/default_header', ['title' => $title]);
    ?>

    <!-- Enrollment Tracker Content -->
    <div class="tracker-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div>
                <h2 class="fw-bold"><i class="bi bi-person-arms-up"></i> Enrollment Tracker</h2>
                <span class="badge bg-secondary fs-6" id="selectedSchoolYearDisplay">School year: –</span>
            </div>
            <button class="btn btn-outline-secondary" id="resetTotalBtn">
                <i class="bi bi-arrow-repeat"></i> Change Total Enrollment
            </button>
        </div>

        <div id="overallSummary" class="summary-card"></div>
        <div id="circlesGrid" class="row g-4"></div>

        <!-- SBFP Filter -->
        <div class="beneficiary-container mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="fw-semibold"><i class="bi bi-people-fill"></i> SBFP Beneficiaries by Nutrition Status</h4>
                <div class="d-flex align-items-center">
                    <select id="beneficiaryGradeFilter" class="form-select w-auto">
                        <option value="all">All Grades</option>
                        <option value="kindergarten">Kindergarten</option><option value="sped">SPED</option>
                        <option value="grade_1">Grade 1</option><option value="grade_2">Grade 2</option>
                        <option value="grade_3">Grade 3</option><option value="grade_4">Grade 4</option>
                        <option value="grade_5">Grade 5</option><option value="grade_6">Grade 6</option>
                        <option value="grade_7">Grade 7</option><option value="grade_8">Grade 8</option>
                        <option value="grade_9">Grade 9</option><option value="grade_10">Grade 10</option>
                        <option value="grade_11">Grade 11</option><option value="grade_12">Grade 12</option>
                    </select>
                    <select id="beneficiaryCategorySelect" class="form-select w-auto ms-2 d-none">
                        <option value="all">All</option>
                        <option value="severely_wasted">Severely Wasted</option>
                        <option value="wasted">Wasted</option>
                        <option value="severely_stunted">Severely Stunted</option>
                        <option value="stunted">Stunted</option>
                    </select>
                    <div class="btn-group ms-2" role="group" aria-label="beneficiary quick filters">
                        <button class="btn btn-outline-secondary btn-sm" id="beneficiaryToggleBtn" title="Show/Hide beneficiaries">Show</button>
                        <button class="btn btn-outline-secondary btn-sm" id="btnAllGrades">All</button>
                        <button class="btn btn-outline-secondary btn-sm" id="btnKindergarten">Kinder</button>
                        <button class="btn btn-outline-secondary btn-sm" id="btnWasted">Wasted</button>
                        <button class="btn btn-outline-secondary btn-sm" id="btnStunted">Stunted</button>
                    </div>
                    <button class="btn btn-outline-primary btn-sm ms-2" id="classificationBtn">Classification of SBFP beneficiaries</button>
                </div>
            </div>
            <div id="beneficiaryStats" class="beneficiary-stats"></div>
        </div>

        <!-- MODAL: Classification -->
        <div class="modal fade" id="classificationModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
                <div class="modal-header modal-header-custom"><h5 class="modal-title">Classification of SBFP beneficiaries</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div id="classificationGrades" class="d-grid gap-2"></div>
                        </div>
                        <div class="col-md-7">
                            <h6 id="classificationSelectedGrade">Select a grade</h6>
                            <div id="classificationCategories" class="mb-3"></div>
                            <div id="classificationPreview" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary d-none" id="classificationSaveBtn">Save</button>
                </div>
            </div></div>
        </div>

        <!-- Comparison Chart -->
        <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h4 class="fw-semibold"><i class="bi bi-bar-chart-steps"></i> Nutritional Status Comparison</h4>
                <div class="filter-btn-group" id="filterButtons">
                    <button class="btn btn-outline-primary" data-period="baseline">Baseline</button>
                    <button class="btn btn-outline-primary" data-period="midline">Midline</button>
                    <button class="btn btn-outline-primary" data-period="endline">Endline</button>
                </div>
            </div>
            <canvas id="nutritionChart" width="400" height="200" style="max-height: 400px; width: 100%;"></canvas>
        </div>
        <footer class="text-center mt-4 text-muted small">
            <i class="bi bi-info-circle"></i> Add/Remove changes total enrollment. The "0" is a placeholder for future logic.
        </footer>
    </div>
</div>

<!-- All modals (School Year, Total Enrollment, Add, Remove, Notes) -->
<!-- MODAL: School Year -->
<div class="modal fade" id="schoolYearModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title"><i class="bi bi-calendar"></i> Select School Year</h5></div>
        <div class="modal-body"><input type="text" class="form-control" id="schoolYearInput" placeholder="2025-2026" value="2025-2026"></div>
        <div class="modal-footer"><button class="btn btn-primary btn-primary-custom" id="confirmSchoolYearBtn">Continue</button></div>
    </div></div>
</div>

<!-- MODAL: Total Enrollment -->
<div class="modal fade" id="totalModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Set Total Enrollment per Grade</h5></div>
        <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
            <div class="row g-3">
                <div class="col-md-4"><label>Kindergarten</label><input type="number" class="form-control total-input" id="total_kindergarten" min="0" value="0"></div>
                <div class="col-md-4"><label>SPED</label><input type="number" class="form-control total-input" id="total_sped" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 1</label><input type="number" class="form-control total-input" id="total_grade_1" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 2</label><input type="number" class="form-control total-input" id="total_grade_2" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 3</label><input type="number" class="form-control total-input" id="total_grade_3" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 4</label><input type="number" class="form-control total-input" id="total_grade_4" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 5</label><input type="number" class="form-control total-input" id="total_grade_5" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 6</label><input type="number" class="form-control total-input" id="total_grade_6" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 7</label><input type="number" class="form-control total-input" id="total_grade_7" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 8</label><input type="number" class="form-control total-input" id="total_grade_8" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 9</label><input type="number" class="form-control total-input" id="total_grade_9" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 10</label><input type="number" class="form-control total-input" id="total_grade_10" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 11</label><input type="number" class="form-control total-input" id="total_grade_11" min="0" value="0"></div>
                <div class="col-md-4"><label>Grade 12</label><input type="number" class="form-control total-input" id="total_grade_12" min="0" value="0"></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary btn-primary-custom" id="confirmTotalBtn">Set Enrollment</button></div>
    </div></div>
</div>

<!-- MODAL: Add Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Add Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="addGradeId">
            <div class="mb-3"><label>Student Name</label><input type="text" class="form-control" id="studentName" required></div>
            <div class="mb-3"><label>Reason for adding</label><textarea class="form-control" id="addReason" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="confirmAddBtn">Add Student</button></div>
    </div></div>
</div>

<!-- MODAL: Remove Student -->
<div class="modal fade" id="removeStudentModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Remove Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="removeGradeId">
            <p>Select a student to remove:</p>
            <div id="removeStudentList" class="student-list"></div>
            <div class="mt-3"><label>Reason for removal (optional)</label><textarea id="removeReason" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" id="confirmRemoveBtn">Remove Selected</button></div>
    </div></div>
</div>

<!-- MODAL: Notes -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header modal-header-custom"><h5 class="modal-title">Student Notes</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div id="notesContent"></div></div>
    </div></div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.BASE_URL = '<?php echo base_url(); ?>';
</script>
<script src="<?php echo base_url(ASSETS_PATH . '/js/enrollment.js'); ?>"></script>
</body>
</html>