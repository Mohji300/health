<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enrollment Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo base_url(ASSETS_PATH . '/css/enrollment.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">
<div class="d-flex" id="wrapper">
    <?php $this->load->view('templates/sidebar'); ?>
    <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">
            <!-- Main content area -->
            <div class="main-content" id="mainContent">
    <!-- Enrollment Tracker Content -->
    <div class="tracker-wrapper">
        <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
                <h1 class="h2 font-weight-bold mb-2">Enrollment Tracker</h1>
                <p class="mb-0 opacity-8">View and manage enrollment records and nutrition status</p>
            </div>
        </div>
        <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-secondary fs-6" id="selectedSchoolYearDisplay">
                School year: <?php echo !empty($school_year) ? htmlspecialchars($school_year) : '–'; ?>
            </span>
            <span class="badge bg-info text-dark fs-6" id="schoolTypeBadge">
                <i class="bi bi-building"></i>
                <?php echo !empty($school_type) ? htmlspecialchars($school_type) : 'Type not set'; ?>
            </span>
            <button class="btn btn-sm btn-outline-primary" id="refreshNutritionBtn" title="Reload uploaded student totals">
                <i class="bi bi-arrow-clockwise"></i> Refresh Uploaded Totals
            </button>
        </div>

        <?php
            $uploaded_summary = isset($uploaded_student_summary) && is_array($uploaded_student_summary)
                ? $uploaded_student_summary
                : array();
            $uploaded_grades = isset($uploaded_summary['grades']) && is_array($uploaded_summary['grades'])
                ? $uploaded_summary['grades']
                : array();
            $uploaded_grade_labels = array(
                'kindergarten' => 'Kindergarten',
                'sped' => 'SPED',
                'grade_1' => 'Grade 1',
                'grade_2' => 'Grade 2',
                'grade_3' => 'Grade 3',
                'grade_4' => 'Grade 4',
                'grade_5' => 'Grade 5',
                'grade_6' => 'Grade 6',
                'grade_7' => 'Grade 7',
                'grade_8' => 'Grade 8',
                'grade_9' => 'Grade 9',
                'grade_10' => 'Grade 10',
                'grade_11' => 'Grade 11',
                'grade_12' => 'Grade 12'
            );
            $school_type_key = strtolower(trim(isset($school_type) ? (string) $school_type : ''));
            $school_type_key = preg_replace('/[^a-z0-9]+/', ' ', $school_type_key);
            if (strpos($school_type_key, 'stand alone shs') !== false || $school_type_key === 'shs only') {
                $visible_uploaded_grade_ids = array('grade_11', 'grade_12');
            } elseif (strpos($school_type_key, 'integrated') !== false) {
                $visible_uploaded_grade_ids = array_keys($uploaded_grade_labels);
            } elseif (strpos($school_type_key, 'elementary') !== false) {
                $visible_uploaded_grade_ids = array(
                    'sped', 'grade_1', 'grade_2', 'grade_3', 'grade_4', 'grade_5', 'grade_6'
                );
            } elseif (in_array($school_type_key, array('secondary', 'junior high school', 'senior high school', 'senior high'), true)) {
                $visible_uploaded_grade_ids = array(
                    'grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'
                );
            } else {
                $visible_uploaded_grade_ids = array_keys($uploaded_grade_labels);
            }
            $uploaded_grade_labels = array_intersect_key(
                $uploaded_grade_labels,
                array_flip($visible_uploaded_grade_ids)
            );
        ?>
        <section class="summary-card" aria-labelledby="uploadedStudentTitle">
            <h2 class="h5 fw-semibold mb-1" id="uploadedStudentTitle">
                Uploaded Students <span class="badge bg-secondary"><?php echo html_escape(ucfirst(isset($assessment_type) ? $assessment_type : 'baseline')); ?></span>
            </h2>
            <p class="text-muted small mb-3">Counts use the same active assessment records as the User Dashboard. School-year selection applies to the enrollment comparison.</p>
            <div id="uploadedStudentSummary">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total Uploaded</div>
                        <div class="fs-3 fw-bold"><?php echo number_format(isset($uploaded_summary['total']) ? (int) $uploaded_summary['total'] : 0); ?></div>
                    </div></div>
                    <div class="col-md-4"><div class="border rounded p-3 h-100">
                        <div class="text-muted small">Male</div>
                        <div class="fs-3 fw-bold"><?php echo number_format(isset($uploaded_summary['male']) ? (int) $uploaded_summary['male'] : 0); ?></div>
                    </div></div>
                    <div class="col-md-4"><div class="border rounded p-3 h-100">
                        <div class="text-muted small">Female</div>
                        <div class="fs-3 fw-bold"><?php echo number_format(isset($uploaded_summary['female']) ? (int) $uploaded_summary['female'] : 0); ?></div>
                    </div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr><th scope="col">Grade Level</th><th scope="col">Male</th><th scope="col">Female</th><th scope="col">Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploaded_grade_labels as $grade_id => $grade_label):
                                $grade_counts = isset($uploaded_grades[$grade_id]) && is_array($uploaded_grades[$grade_id])
                                    ? $uploaded_grades[$grade_id]
                                    : array();
                            ?>
                                <tr>
                                    <th scope="row"><?php echo html_escape($grade_label); ?></th>
                                    <td><?php echo number_format(isset($grade_counts['male']) ? (int) $grade_counts['male'] : 0); ?></td>
                                    <td><?php echo number_format(isset($grade_counts['female']) ? (int) $grade_counts['female'] : 0); ?></td>
                                    <td class="fw-semibold"><?php echo number_format(isset($grade_counts['total']) ? (int) $grade_counts['total'] : 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div id="overallSummary" class="summary-card"></div>
        <div id="grandTotalWrapper" class="mb-3"></div>
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
        <div class="modal-body"><input type="text" class="form-control" id="schoolYearInput" placeholder="2025-2026" value="<?php echo !empty($school_year) ? html_escape($school_year) : '2025-2026'; ?>"></div>
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
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.BASE_URL          = '<?php echo base_url(); ?>';
    window.SCHOOL_YEAR       = <?php echo json_encode($school_year); ?>;
    window.SCHOOL_TYPE       = <?php echo json_encode($school_type); ?>;
    window.ASSESSMENT_TYPE   = <?php echo json_encode(isset($assessment_type) ? $assessment_type : 'baseline'); ?>;
    window.NUTRITIONAL_COUNTS = <?php echo json_encode(isset($nutritional_counts) ? $nutritional_counts : new stdClass()); ?>;
    window.UPLOADED_STUDENT_SUMMARY = <?php echo json_encode(isset($uploaded_student_summary) ? $uploaded_student_summary : array()); ?>;
    window.UPLOADED_GRADE_IDS = <?php echo json_encode(isset($visible_uploaded_grade_ids) ? $visible_uploaded_grade_ids : array()); ?>;
    window.ENROLLMENT_DATA   = <?php echo json_encode($enrollment); ?>;
</script>
<script src="<?php echo base_url(ASSETS_PATH . '/js/enrollment.js'); ?>"></script>
</body>
</html>