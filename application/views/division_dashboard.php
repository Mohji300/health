<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Division Dashboard - SBFP</title>
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/division_dashboard.css'); ?>">
    <style>
        .sex-row-male { background-color: #e3f2fd; }
        .sex-row-female { background-color: #fce4ec; }
        .sex-row-total { background-color: #f5f5f5; font-weight: bold; }
        .grade-separator td { border-top: 2px solid #dee2e6 !important; }
    </style>
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
                                <h1 class="h2 font-weight-bold mb-2">Division Dashboard</h1>
                                <p class="mb-0">Welcome, <?php echo htmlspecialchars($user_name); ?> &middot; 
                                    <span class="badge <?php 
                                        if ($assessment_type == 'baseline') echo 'badge-baseline';
                                        elseif ($assessment_type == 'midline') echo 'badge-midline';
                                        else echo 'badge-endline';
                                    ?> assessment-badge">
                                        <?php echo ucfirst($assessment_type); ?> Assessment
                                    </span>
                                </p>
                            </div>
                            <div class="d-flex align-items-center">
                                <!-- assessment dropdown moved to controls below -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overall Summary Card -->
                <div class="card bg-primary text-white mb-4 cursor-pointer" id="overallSummaryCard">
                    <div class="card-body">
                        <h2 class="card-title h4">Division Report Status</h2>
                        <div class="row mt-3">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="display-4 fw-bold"><?php echo $overall_stats['total_schools']; ?></div>
                                <div class="text-white-50">Total Schools</div>
                            </div>
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="display-4 fw-bold"><?php echo $overall_stats['total_submitted']; ?></div>
                                <div class="text-white-50">Reports Submitted</div>
                            </div>
                            <div class="col-md-4 text-center">
                                <?php
                                    $total_schools = isset($overall_stats['total_schools']) ? (int)$overall_stats['total_schools'] : 0;
                                    $total_submitted = isset($overall_stats['total_submitted']) ? (int)$overall_stats['total_submitted'] : 0;
                                    if ($total_schools > 0) {
                                        $completion_rate_raw = ($total_submitted / $total_schools) * 100;
                                        $completion_display = number_format($completion_rate_raw, 2) . '%';
                                    } else {
                                        $completion_display = '0%';
                                    }
                                ?>
                                <div class="display-4 fw-bold"><?php echo $completion_display; ?></div>
                                <div class="text-white-50">Completion Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Schools/Districts Box -->
                <div class="card mb-4 d-none" id="schoolsBox">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="h5 mb-0" id="boxTitle">All Districts</h3>
                            <button type="button" class="btn-close" id="closeSchoolsBox"></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Districts View -->
                        <div id="districtsView">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Click on any district to view all schools in that district
                            </div>
                            <div class="row" id="districtsContainer">
                                <!-- Districts loaded from PHP data -->
                                <?php if (!empty($district_schools_summary)): ?>
                                    <?php foreach ($district_schools_summary as $district_name => $district_data): ?>
                                        <?php 
                                        $total_schools = $district_data['total_schools'];
                                        $submitted_schools = $district_data['submitted_schools'];
                                        $completion_rate = $district_data['completion_rate'];
                                        $status = $district_data['status'];
                                        $statusClass = $status === 'Completed' ? 'bg-success text-white' :
                                                       ($status === 'In Progress' ? 'bg-warning text-dark' : 'bg-secondary text-white');
                                        $progressClass = $completion_rate >= 75 ? 'bg-success' :
                                                         ($completion_rate >= 40 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100 cursor-pointer district-card" data-district="<?php echo htmlspecialchars($district_name); ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($district_name); ?></h6>
                                                        <span class="badge rounded-pill <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">Schools:</small>
                                                            <small class="fw-bold"><?php echo $submitted_schools; ?>/<?php echo $total_schools; ?></small>
                                                        </div>
                                                        <div class="progress">
                                                            <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo $completion_rate; ?>%"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-1">
                                                            <small class="text-muted">Completion:</small>
                                                            <small class="fw-bold"><?php echo $completion_rate; ?>%</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="text-center py-4 text-muted">
                                            No districts found.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Schools View -->
                        <div id="schoolsView" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 id="districtSchoolsTitle"></h5>
                                <button type="button" class="btn btn-secondary btn-sm" id="backToDistricts">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Districts
                                </button>
                            </div>
                            
                            <!-- District Summary -->
                            <div class="alert alert-primary mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span id="submissionText">Submission Progress:</span>
                                    <span class="fw-bold" id="submissionCount">0/0 schools submitted</span>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" id="submissionProgressBar" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <!-- Schools Search -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="schoolSearch" placeholder="Search schools...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        Clear
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Schools List -->
                            <div class="border rounded" style="max-height: 500px; overflow-y: auto;">
                                <div class="list-group list-group-flush" id="schoolsList">
                                    <!-- Schools will be loaded here via JavaScript -->
                                </div>
                                <div class="text-center py-4 text-muted d-none" id="noSchoolsMessage">
                                    No schools found for this district.
                                </div>
                                <div class="text-center py-4 text-muted d-none" id="noSearchResults">
                                    No schools match your search.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Comprehensive Nutritional Status Table -->
                <div class="card mt-1">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            Division Nutritional Assessment Report
                            <span class="badge <?php 
                                if ($assessment_type == 'baseline') echo 'badge-baseline';
                                elseif ($assessment_type == 'midline') echo 'badge-midline';
                                else echo 'badge-endline';
                            ?> assessment-badge">
                                <?php echo ucfirst($assessment_type); ?>
                            </span>
                            <?php if ($school_level !== 'all'): ?>
                            <span class="badge bg-secondary ms-2 school-level-badge">
                                <i class="fas fa-filter me-1"></i>
                                <?php 
                                    if ($school_level === 'elementary') echo 'Elementary';
                                    elseif ($school_level === 'secondary') echo 'Secondary';
                                    elseif ($school_level === 'integrated') echo 'Integrated';
                                    elseif ($school_level === 'integrated_elementary') echo 'Integrated Elementary';
                                    elseif ($school_level === 'integrated_secondary') echo 'Integrated Secondary';
                                    elseif ($school_level === 'shs_only') echo 'Stand Alone SHS';
                                    else echo ucfirst($school_level);
                                ?>
                            </span>
                            <?php endif; ?>
                        </h4>
                        <div class="no-print d-flex align-items-center">
                            <!-- School Level Filter Dropdown -->
                            <div class="dropdown me-2">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="schoolLevelDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-filter me-1"></i>
                                    School Level: 
                                    <?php 
                                        $level = isset($school_level) ? $school_level : 'all';
                                        if ($level == 'elementary') echo 'Elementary ';
                                        elseif ($level == 'secondary') echo 'Secondary ';
                                        elseif ($level == 'integrated') echo 'Integrated ';
                                        elseif ($level == 'integrated_elementary') echo 'Integrated Elementary';
                                        elseif ($level == 'integrated_secondary') echo 'Integrated Secondary';
                                        elseif ($level == 'shs_only') echo 'Stand Alone SHS';
                                        else echo 'Elementary';
                                    ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="schoolLevelDropdown">
                                    <li><h6 class="dropdown-header">Filter by School Level</h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?php echo ($level == 'elementary') ? 'active' : ''; ?>" href="#" data-level="elementary">
                                         Elementary 
                                    </a></li>
                                    <li><a class="dropdown-item <?php echo ($level == 'secondary') ? 'active' : ''; ?>" href="#" data-level="secondary">
                                         Secondary 
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?php echo ($level == 'integrated_elementary') ? 'active' : ''; ?>" href="#" data-level="integrated_elementary">
                                         Integrated Elementary
                                    </a></li>
                                    <li><a class="dropdown-item <?php echo ($level == 'integrated_secondary') ? 'active' : ''; ?>" href="#" data-level="integrated_secondary">
                                         Integrated Secondary
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?php echo ($level == 'shs_only') ? 'active' : ''; ?>" href="#" data-level="shs_only">
                                         Stand Alone SHS
                                    </a></li>
                                </ul>
                            </div>
                            <div class="btn-group me-2">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="assessmentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-chart-line me-1"></i>
                                    <?php echo ucfirst($assessment_type); ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="assessmentDropdown">
                                    <li><h6 class="dropdown-header">Select Assessment</h6></li>
                                    <li><a class="dropdown-item <?= ($assessment_type == 'baseline') ? 'active' : '' ?>" href="#" data-type="baseline">Baseline <span class="badge bg-info ms-2"><?= isset($baseline_count) ? $baseline_count : 0 ?></span></a></li>
                                    <li><a class="dropdown-item <?= ($assessment_type == 'midline') ? 'active' : '' ?>" href="#" data-type="midline">Midline <span class="badge bg-info ms-2"><?= isset($midline_count) ? $midline_count : 0 ?></span></a></li>
                                    <li><a class="dropdown-item <?= ($assessment_type == 'endline') ? 'active' : '' ?>" href="#" data-type="endline">Endline <span class="badge bg-info ms-2"><?= isset($endline_count) ? $endline_count : 0 ?></span></a></li>
                                </ul>
                            </div>
                            <form id="districtFilterForm" method="get" action="<?= site_url('division_dashboard_controller'); ?>" class="d-flex align-items-center me-2">
                                <input type="hidden" name="assessment_type" value="<?= htmlspecialchars($assessment_type); ?>">
                                <input type="hidden" name="school_level" value="<?= htmlspecialchars($school_level); ?>">
                                <label for="districtFilter" class="me-2 mb-0 small text-muted">District</label>
                                <select id="districtFilter" name="legislative_district_id" class="form-select form-select-sm" style="min-width: 180px;">
                                    <option value="">All Districts</option>
                                    <?php foreach ($legislative_districts as $district): ?>
                                        <option value="<?= (int)($district->id ?? 0); ?>" <?= ((string)($selected_legislative_district_id ?? '') === (string)($district->id ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($district->name ?? 'Unknown District'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <button id="btnPrint" class="btn btn-success">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <!-- No Data Alert -->
                        <?php if (!isset($has_data) || !$has_data): ?>
                        <div class="alert alert-warning m-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">No <?php echo ucfirst($assessment_type); ?> Data Available</h5>
                                    <p class="mb-0">
                                        No <?php echo strtolower($assessment_type); ?> assessment data has been submitted yet. 
                                        The table below shows all zeros.
                                        <?php if ($assessment_type == 'midline'): ?>
                                            Schools need to create midline assessments first to see data here.
                                        <?php elseif ($assessment_type == 'endline'): ?>
                                            Schools need to create endline assessments first to see data here.
                                        <?php else: ?>
                                            Schools need to create baseline assessments first to see data here.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div id="tableContainer" class="table-responsive p-3">
                            
                            <!-- Elementary Table with Sex Breakdown -->
                            <table id="elementaryTable" class="table table-bordered table-sm mb-0 <?php echo ($school_level === 'secondary' || $school_level === 'integrated_secondary' || $school_level === 'shs_only') ? 'd-none' : ''; ?>">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="3">Grade Level</th>
                                        <th rowspan="3">Sex</th>
                                        <th rowspan="3" class="text-center">Enrolment</th>
                                        <th rowspan="3" class="text-center">Pupils Weighed</th>
                                        <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
                                        <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th colspan="2" class="text-center th-red text-white">Severely Wasted</th>
                                        <th colspan="2" class="text-center th-orange text-white">Wasted</th>
                                        <th colspan="2" class="text-center th-green text-white">Normal BMI</th>
                                        <th colspan="2" class="text-center th-orange text-white">Overweight</th>
                                        <th colspan="2" class="text-center th-red text-white">Obese</th>
                                        <th colspan="2" class="text-center th-red text-white">Severely Stunted</th>
                                        <th colspan="2" class="text-center th-orange text-white">Stunted</th>
                                        <th colspan="2" class="text-center th-green text-white">Normal HFA</th>
                                        <th colspan="2" class="text-center th-green text-white">Tall</th>
                                        <th colspan="2" class="text-center">Pupils Height</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <!-- For each category: count and % -->
                                        <?php for ($i=0;$i<10;$i++): ?>
                                            <th class="text-center">Count</th>
                                            <th class="text-center">%</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Helper functions defined inline
                                    function gdata_division($data, $key, $field) {
                                        if (!isset($data[$key])) return 0;
                                        return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
                                    }
                                    
                                    function pct_division($num, $den) {
                                        if (!$den || $den == 0) return '0%';
                                        return round(($num / $den) * 100) . '%';
                                    }

                                    function sum_group_data($data, $groups, $sex_key, $field) {
                                        $total = 0;
                                        if (!is_array($groups)) {
                                            return $total;
                                        }

                                        foreach ($groups as $keys) {
                                            if (!is_array($keys) || !isset($keys[$sex_key])) {
                                                continue;
                                            }
                                            $total += gdata_division($data, $keys[$sex_key], $field);
                                        }

                                        return $total;
                                    }
                                    
                                    // Define grade arrays with sex breakdown structure
                                    $elementaryGrades = [
                                        'Kinder' => ['m' => 'Kinder_m', 'f' => 'Kinder_f', 'total' => 'Kinder_total'],
                                        'Grade 1' => ['m' => 'Grade 1_m', 'f' => 'Grade 1_f', 'total' => 'Grade 1_total'],
                                        'Grade 2' => ['m' => 'Grade 2_m', 'f' => 'Grade 2_f', 'total' => 'Grade 2_total'],
                                        'Grade 3' => ['m' => 'Grade 3_m', 'f' => 'Grade 3_f', 'total' => 'Grade 3_total'],
                                        'Grade 4' => ['m' => 'Grade 4_m', 'f' => 'Grade 4_f', 'total' => 'Grade 4_total'],
                                        'Grade 5' => ['m' => 'Grade 5_m', 'f' => 'Grade 5_f', 'total' => 'Grade 5_total'],
                                        'Grade 6' => ['m' => 'Grade 6_m', 'f' => 'Grade 6_f', 'total' => 'Grade 6_total'],
                                        'SPED' => ['m' => 'SPED_m', 'f' => 'SPED_f', 'total' => 'SPED_total']
                                    ];
                                    
                                    $secondaryGrades = [
                                        'Grade 7' => ['m' => 'Grade 7_m', 'f' => 'Grade 7_f', 'total' => 'Grade 7_total'],
                                        'Grade 8' => ['m' => 'Grade 8_m', 'f' => 'Grade 8_f', 'total' => 'Grade 8_total'],
                                        'Grade 9' => ['m' => 'Grade 9_m', 'f' => 'Grade 9_f', 'total' => 'Grade 9_total'],
                                        'Grade 10' => ['m' => 'Grade 10_m', 'f' => 'Grade 10_f', 'total' => 'Grade 10_total'],
                                        'Grade 11' => ['m' => 'Grade 11_m', 'f' => 'Grade 11_f', 'total' => 'Grade 11_total'],
                                        'Grade 12' => ['m' => 'Grade 12_m', 'f' => 'Grade 12_f', 'total' => 'Grade 12_total']
                                    ];
                                    
                                    $shsGrades = [
                                        'Grade 11' => ['m' => 'Grade 11_m', 'f' => 'Grade 11_f', 'total' => 'Grade 11_total'],
                                        'Grade 12' => ['m' => 'Grade 12_m', 'f' => 'Grade 12_f', 'total' => 'Grade 12_total']
                                    ];
                                    
                                    // Define BMI and HFA fields
                                    $bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
                                    $hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
                                    
                                    // Check if nutritional data exists
                                    $has_nutritional_data = isset($nutritional_data) && !empty($nutritional_data);
                                    ?>
                                    
                                    <?php 
                                    $grade_count = 0;
                                    foreach ($elementaryGrades as $grade_name => $sex_keys): 
                                        $grade_count++;
                                        $rowspan = 3;
                                    ?>
                                        <!-- Male Row -->
                                        <tr class="sex-row-male">
                                            <?php if ($grade_count == 1 || true): ?>
                                                <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                                            <?php endif; ?>
                                            <td class="text-center">M</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['m'], $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['m'], $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                        
                                        <!-- Female Row -->
                                        <tr class="sex-row-female">
                                            <td class="text-center">F</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['f'], $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['f'], $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                        
                                        <!-- Total Row -->
                                        <tr class="sex-row-total">
                                            <td class="text-center fw-bold">Total</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center fw-bold"><?= $enrol ?></td>
                                            <td class="text-center fw-bold"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['total'], $bf);
                                                    echo '<td class="text-center fw-bold">' . $val . '</td>';
                                                    echo '<td class="text-center fw-bold">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['total'], $hf);
                                                    echo '<td class="text-center fw-bold">' . $val . '</td>';
                                                    echo '<td class="text-center fw-bold">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center fw-bold">0</td><td class="text-center fw-bold">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                        
                                        <!-- Separator between grades -->
                                        <?php if ($grade_count < count($elementaryGrades)): ?>
                                            <tr class="grade-separator">
                                                <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                                            </tr>
                                        <?php endif; ?>
                                        
                                    <?php endforeach; ?>

                                    <!-- Grand total block - Elementary (grouped M / F / Total) -->
                                    <?php
                                    $totalEnrol_m = sum_group_data($nutritional_data, $elementaryGrades, 'm', 'enrolment');
                                    $totalWeighed_m = sum_group_data($nutritional_data, $elementaryGrades, 'm', 'pupils_weighed');
                                    $totalEnrol_f = sum_group_data($nutritional_data, $elementaryGrades, 'f', 'enrolment');
                                    $totalWeighed_f = sum_group_data($nutritional_data, $elementaryGrades, 'f', 'pupils_weighed');
                                    $totalEnrol_t = sum_group_data($nutritional_data, $elementaryGrades, 'total', 'enrolment');
                                    $totalWeighed_t = sum_group_data($nutritional_data, $elementaryGrades, 'total', 'pupils_weighed');
                                    $grandCounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                    $grandCounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                    $grandCounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                    foreach ($grandCounts_m as $field => $value) {
                                        $grandCounts_m[$field] = sum_group_data($nutritional_data, $elementaryGrades, 'm', $field);
                                    }
                                    foreach ($grandCounts_f as $field => $value) {
                                        $grandCounts_f[$field] = sum_group_data($nutritional_data, $elementaryGrades, 'f', $field);
                                    }
                                    foreach ($grandCounts_t as $field => $value) {
                                        $grandCounts_t[$field] = sum_group_data($nutritional_data, $elementaryGrades, 'total', $field);
                                    }
                                    ?>

                                    <tr class="table-primary">
                                        <td class="fw-bold text-center align-middle" rowspan="3">Grand Total (Elementary)</td>
                                        <td class="text-center">M</td>
                                        <td class="text-center fw-bold"><?= $totalEnrol_m ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed_m ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts_m[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol_m) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts_m[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol_m) ?></td><?php endforeach; ?>
                                    </tr>

                                    <tr class="table-primary">
                                        <td class="text-center">F</td>
                                        <td class="text-center fw-bold"><?= $totalEnrol_f ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed_f ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts_f[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol_f) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts_f[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol_f) ?></td><?php endforeach; ?>
                                    </tr>

                                    <tr class="table-primary">
                                        <td class="text-center fw-bold">Total</td>
                                        <td class="text-center fw-bold"><?= $totalEnrol_t ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed_t ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts_t[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol_t) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts_t[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol_t) ?></td><?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- SHS-only Table (Grade 11-12) -->
                            <table id="shsTable" class="table table-bordered table-sm mb-0 <?php echo ($school_level === 'shs_only') ? '' : 'd-none'; ?>">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="3">Grade Level</th>
                                        <th rowspan="3">Sex</th>
                                        <th rowspan="3" class="text-center">Enrolment</th>
                                        <th rowspan="3" class="text-center">Students Weighed</th>
                                        <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
                                        <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th colspan="2" class="text-center th-red text-white">Severely Wasted</th>
                                        <th colspan="2" class="text-center th-orange text-white">Wasted</th>
                                        <th colspan="2" class="text-center th-green text-white">Normal BMI</th>
                                        <th colspan="2" class="text-center th-orange text-white">Overweight</th>
                                        <th colspan="2" class="text-center th-red text-white">Obese</th>
                                        <th colspan="2" class="text-center th-red text-white">Severely Stunted</th>
                                        <th colspan="2" class="text-center th-orange text-white">Stunted</th>
                                        <th colspan="2" class="text-center th-green text-white">Normal HFA</th>
                                        <th colspan="2" class="text-center th-green text-white">Tall</th>
                                        <th colspan="2" class="text-center">Students Height</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <?php for ($i=0;$i<10;$i++): ?>
                                            <th class="text-center">Count</th>
                                            <th class="text-center">%</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grade_count = 0;
                                    foreach ($shsGrades as $grade_name => $sex_keys): 
                                        $grade_count++;
                                        $rowspan = 3;
                                    ?>
                                        <tr class="sex-row-male">
                                            <?php if ($grade_count == 1 || true): ?>
                                                <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                                            <?php endif; ?>
                                            <td class="text-center">M</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['m'], $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['m'], $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) { echo '<td class="text-center">0</td><td class="text-center">0%</td>'; }
                                            }
                                            ?>
                                        </tr>
                                        <tr class="sex-row-female">
                                            <td class="text-center">F</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['f'], $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['f'], $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) { echo '<td class="text-center">0</td><td class="text-center">0%</td>'; }
                                            }
                                            ?>
                                        </tr>
                                        <tr class="sex-row-total">
                                            <td class="text-center fw-bold">Total</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center fw-bold"><?= $enrol ?></td>
                                            <td class="text-center fw-bold"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['total'], $bf);
                                                    echo '<td class="text-center fw-bold">' . $val . '</td>';
                                                    echo '<td class="text-center fw-bold">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['total'], $hf);
                                                    echo '<td class="text-center fw-bold">' . $val . '</td>';
                                                    echo '<td class="text-center fw-bold">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) { echo '<td class="text-center fw-bold">0</td><td class="text-center fw-bold">0%</td>'; }
                                            }
                                            ?>
                                        </tr>
                                        <?php if ($grade_count < count($shsGrades)): ?>
                                            <tr class="grade-separator">
                                                <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php
                                        $shsEnrol_m = sum_group_data($nutritional_data, $shsGrades, 'm', 'enrolment');
                                        $shsWeighed_m = sum_group_data($nutritional_data, $shsGrades, 'm', 'pupils_weighed');
                                        $shsEnrol_f = sum_group_data($nutritional_data, $shsGrades, 'f', 'enrolment');
                                        $shsWeighed_f = sum_group_data($nutritional_data, $shsGrades, 'f', 'pupils_weighed');
                                        $shsEnrol_t = sum_group_data($nutritional_data, $shsGrades, 'total', 'enrolment');
                                        $shsWeighed_t = sum_group_data($nutritional_data, $shsGrades, 'total', 'pupils_weighed');
                                        $shsCounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        $shsCounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        $shsCounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        foreach ($shsCounts_m as $field => $value) {
                                            $shsCounts_m[$field] = sum_group_data($nutritional_data, $shsGrades, 'm', $field);
                                        }
                                        foreach ($shsCounts_f as $field => $value) {
                                            $shsCounts_f[$field] = sum_group_data($nutritional_data, $shsGrades, 'f', $field);
                                        }
                                        foreach ($shsCounts_t as $field => $value) {
                                            $shsCounts_t[$field] = sum_group_data($nutritional_data, $shsGrades, 'total', $field);
                                        }
                                        ?>

                                        <tr class="table-primary">
                                            <td class="fw-bold text-center align-middle" rowspan="3">Grand Total (SHS)</td>
                                            <td class="text-center">M</td>
                                            <td class="text-center fw-bold"><?= $shsEnrol_m ?></td>
                                            <td class="text-center fw-bold"><?= $shsWeighed_m ?></td>
                                            <?php foreach ($bmiFields as $bf): $val = $shsCounts_m[$bf]; ?>
                                                <td class="text-center fw-bold"><?= $val ?></td>
                                                <td class="text-center fw-bold"><?= pct_division($val, $shsEnrol_m) ?></td>
                                            <?php endforeach; ?>
                                            <?php foreach ($hfaFields as $hf): $val = $shsCounts_m[$hf]; ?>
                                                <td class="text-center fw-bold"><?= $val ?></td>
                                                <td class="text-center fw-bold"><?= pct_division($val, $shsEnrol_m) ?></td>
                                            <?php endforeach; ?>
                                        </tr>

                                        <tr class="table-primary">
                                            <td class="text-center">F</td>
                                            <td class="text-center fw-bold"><?= $shsEnrol_f ?></td>
                                            <td class="text-center fw-bold"><?= $shsWeighed_f ?></td>
                                            <?php foreach ($bmiFields as $bf): $val = $shsCounts_f[$bf]; ?>
                                                <td class="text-center fw-bold"><?= $val ?></td>
                                                <td class="text-center fw-bold"><?= pct_division($val, $shsEnrol_f) ?></td>
                                            <?php endforeach; ?>
                                            <?php foreach ($hfaFields as $hf): $val = $shsCounts_f[$hf]; ?>
                                                <td class="text-center fw-bold"><?= $val ?></td>
                                                <td class="text-center fw-bold"><?= pct_division($val, $shsEnrol_f) ?></td>
                                            <?php endforeach; ?>
                                        </tr>

                                        <tr class="table-primary">
                                            <td class="text-center fw-bold">Total</td>
                                            <td class="text-center fw-bold"><?= $shsEnrol_t ?></td>
                                            <td class="text-center fw-bold"><?= $shsWeighed_t ?></td>
                                            <?php foreach ($bmiFields as $bf): $val = $shsCounts_t[$bf]; ?>
                                                <td class="text-center fw-bold"><?= $val ?></td>
                                                <td class="text-center fw-bold"><?= pct_division($val, $shsEnrol_t) ?></td>
                                            <?php endforeach; ?>
                                            <?php foreach ($hfaFields as $hf): $val = $shsCounts_t[$hf]; ?>
                                                <td class="text-center fw-bold"><?= $val ?></td>
                                                <td class="text-center fw-bold"><?= pct_division($val, $shsEnrol_t) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                </tbody>
                            </table>

                            <!-- Secondary Table with Sex Breakdown -->
                            <table id="secondaryTable" class="table table-bordered table-sm mb-0 <?php echo ($school_level === 'elementary' || $school_level === 'integrated_elementary') ? 'd-none' : ''; ?>">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="3">Grade Level</th>
                                        <th rowspan="3">Sex</th>
                                        <th rowspan="3" class="text-center">Enrolment</th>
                                        <th rowspan="3" class="text-center">Students Weighed</th>
                                        <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
                                        <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th colspan="2" class="text-center th-red text-white">Severely Wasted</th>
                                        <th colspan="2" class="text-center th-orange text-white">Wasted</th>
                                        <th colspan="2" class="text-center th-green text-white">Normal BMI</th>
                                        <th colspan="2" class="text-center th-orange text-white">Overweight</th>
                                        <th colspan="2" class="text-center th-red text-white">Obese</th>
                                        <th colspan="2" class="text-center th-red text-white">Severely Stunted</th>
                                        <th colspan="2" class="text-center th-orange text-white">Stunted</th>
                                        <th colspan="2" class="text-center th-green text-white">Normal HFA</th>
                                        <th colspan="2" class="text-center th-green text-white">Tall</th>
                                        <th colspan="2" class="text-center">Students Height</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <?php for ($i=0;$i<10;$i++): ?>
                                            <th class="text-center">Count</th>
                                            <th class="text-center">%</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grade_count = 0;
                                    foreach ($secondaryGrades as $grade_name => $sex_keys): 
                                        $grade_count++;
                                        $rowspan = 3;
                                    ?>
                                        <!-- Male Row -->
                                        <tr class="sex-row-male">
                                            <?php if ($grade_count == 1 || true): ?>
                                                <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                                            <?php endif; ?>
                                            <td class="text-center">M</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['m'], $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['m'], $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                        
                                        <!-- Female Row -->
                                        <tr class="sex-row-female">
                                            <td class="text-center">F</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['f'], $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['f'], $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                        
                                        <!-- Total Row -->
                                        <tr class="sex-row-total">
                                            <td class="text-center fw-bold">Total</td>
                                            <?php 
                                                $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'enrolment') : 0;
                                                $weighed = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'pupils_weighed') : 0;
                                            ?>
                                            <td class="text-center fw-bold"><?= $enrol ?></td>
                                            <td class="text-center fw-bold"><?= $weighed ?></td>
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['total'], $bf);
                                                    echo '<td class="text-center fw-bold">' . $val . '</td>';
                                                    echo '<td class="text-center fw-bold">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $sex_keys['total'], $hf);
                                                    echo '<td class="text-center fw-bold">' . $val . '</td>';
                                                    echo '<td class="text-center fw-bold">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center fw-bold">0</td><td class="text-center fw-bold">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                        
                                        <!-- Separator between grades -->
                                        <?php if ($grade_count < count($secondaryGrades)): ?>
                                            <tr class="grade-separator">
                                                <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                                            </tr>
                                        <?php endif; ?>
                                        
                                    <?php endforeach; ?>

                                    <!-- Grand total block - Secondary (grouped M / F / Total) -->
                                    <?php
                                    $sEnrol_m = sum_group_data($nutritional_data, $secondaryGrades, 'm', 'enrolment');
                                    $sWeighed_m = sum_group_data($nutritional_data, $secondaryGrades, 'm', 'pupils_weighed');
                                    $sEnrol_f = sum_group_data($nutritional_data, $secondaryGrades, 'f', 'enrolment');
                                    $sWeighed_f = sum_group_data($nutritional_data, $secondaryGrades, 'f', 'pupils_weighed');
                                    $sEnrol_t = sum_group_data($nutritional_data, $secondaryGrades, 'total', 'enrolment');
                                    $sWeighed_t = sum_group_data($nutritional_data, $secondaryGrades, 'total', 'pupils_weighed');
                                    $scounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                    $scounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                    $scounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                    foreach ($scounts_m as $field => $value) {
                                        $scounts_m[$field] = sum_group_data($nutritional_data, $secondaryGrades, 'm', $field);
                                    }
                                    foreach ($scounts_f as $field => $value) {
                                        $scounts_f[$field] = sum_group_data($nutritional_data, $secondaryGrades, 'f', $field);
                                    }
                                    foreach ($scounts_t as $field => $value) {
                                        $scounts_t[$field] = sum_group_data($nutritional_data, $secondaryGrades, 'total', $field);
                                    }
                                    ?>

                                    <tr class="table-primary">
                                        <td class="fw-bold text-center align-middle" rowspan="3">Grand Total (Secondary)</td>
                                        <td class="text-center">M</td>
                                        <td class="text-center fw-bold"><?= $sEnrol_m ?></td>
                                        <td class="text-center fw-bold"><?= $sWeighed_m ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $scounts_m[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $sEnrol_m) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $scounts_m[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $sEnrol_m) ?></td><?php endforeach; ?>
                                    </tr>

                                    <tr class="table-primary">
                                        <td class="text-center">F</td>
                                        <td class="text-center fw-bold"><?= $sEnrol_f ?></td>
                                        <td class="text-center fw-bold"><?= $sWeighed_f ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $scounts_f[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $sEnrol_f) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $scounts_f[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $sEnrol_f) ?></td><?php endforeach; ?>
                                    </tr>

                                    <tr class="table-primary">
                                        <td class="text-center fw-bold">Total</td>
                                        <td class="text-center fw-bold"><?= $sEnrol_t ?></td>
                                        <td class="text-center fw-bold"><?= $sWeighed_t ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $scounts_t[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $sEnrol_t) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $scounts_t[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $sEnrol_t) ?></td><?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- School Details Modal -->
    <div class="modal fade" id="schoolModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">School Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="schoolModalBody">
                    <!-- Content will be loaded here via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    window.DivisionDashboardConfig = {
        urls: {
            set_assessment_type: '<?= site_url("division_dashboard_controller/set_assessment_type"); ?>',
            set_school_level: '<?= site_url("division_dashboard_controller/set_school_level"); ?>',
            base: '<?= site_url("division_dashboard_controller"); ?>',
            get_school_details: '<?= base_url("division_dashboard_controller/get_school_details/"); ?>',
            get_district_schools: '<?= base_url("division_dashboard_controller/get_district_schools") ?>'
        },
        school_level: '<?= isset($school_level) ? $school_level : "all"; ?>',
        assessment_type: '<?= isset($assessment_type) ? $assessment_type : ""; ?>',
        selected_legislative_district_id: '<?= isset($selected_legislative_district_id) ? (int)$selected_legislative_district_id : ""; ?>',
        assessment_type_display: '<?= ucfirst(isset($assessment_type) ? $assessment_type : ""); ?>',
        school_level_display: '<?php 
            $level = isset($school_level) ? $school_level : 'all';
            if ($level == 'all') echo 'All Schools';
            elseif ($level == 'elementary') echo 'Elementary Schools';
            elseif ($level == 'secondary') echo 'Secondary Schools';
            elseif ($level == 'integrated') echo 'Integrated Schools';
            elseif ($level == 'integrated_elementary') echo 'Integrated Schools (Elementary)';
            elseif ($level == 'integrated_secondary') echo 'Integrated Schools (Secondary)';
            else echo ucfirst($level);
        ?>',
        school_level: '<?= isset($school_level) ? $school_level : "all"; ?>',
        // identifiers for client-side print header
        school_id: '<?= addslashes($this->session->userdata('school_id') ?? '') ?>',
        school_name: '<?= addslashes($this->session->userdata('school_name') ?? '') ?>',
        user_name: '<?= addslashes($this->session->userdata('name') ?? ($auth_user['name'] ?? '')) ?>'
    };
    </script>
    <script src="<?= base_url(ASSETS_PATH . '/js/division_dashboard.js'); ?>"></script>
</body>
</html>