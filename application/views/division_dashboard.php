<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Division Dashboard - SBFP</title>
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/division_dashboard.css'); ?>">
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
                                <div class="assessment-switcher">
                                    <button class="btn btn-primary btn-sm <?php echo ($assessment_type == 'baseline') ? 'active' : ''; ?>" 
                                            id="switchToBaseline">
                                        <i class="fas fa-flag me-1"></i> Baseline
                                    </button>
                                    <button class="btn btn-info btn-sm <?php echo ($assessment_type == 'midline') ? 'active' : ''; ?>" 
                                            id="switchToMidline">
                                        <i class="fas fa-flag me-1"></i> Midline
                                    </button>
                                    <button class="btn btn-success btn-sm <?php echo ($assessment_type == 'endline') ? 'active' : ''; ?>" 
                                            id="switchToEndline">
                                        <i class="fas fa-flag-checkered me-1"></i> Endline
                                    </button>
                                </div>
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
                <div class="card mt-2">
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
                                        if ($school_level === 'elementary') echo 'Elementary Only';
                                        elseif ($school_level === 'secondary') echo 'Secondary Only';
                                        elseif ($school_level === 'integrated') echo 'Integrated (All)';
                                        elseif ($school_level === 'integrated_elementary') echo 'Integrated (Elementary)';
                                        elseif ($school_level === 'integrated_secondary') echo 'Integrated (Secondary)';
                                        else echo ucfirst($school_level);
                                    ?>
                                </span>
                                <?php endif; ?>
                        </h4>
                        <div class="no-print d-flex align-items-center">
                            <div class="btn-group me-2" role="group">
                                <button id="btnElementaryFilter" type="button" 
                                        class="btn btn-sm btn-outline-primary <?php echo (in_array($school_level, ['all', 'elementary', 'integrated', 'integrated_elementary'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-child me-1"></i> Elementary
                                    <span class="badge bg-info ms-1">K-6</span>
                                </button>
                                <button id="btnSecondaryFilter" type="button" 
                                        class="btn btn-sm btn-outline-primary <?php echo (in_array($school_level, ['secondary', 'integrated_secondary'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-graduation-cap me-1"></i> Secondary
                                    <span class="badge bg-info ms-1">7-12</span>
                                </button>
                                <button id="btnIntegratedFilter" type="button" 
                                        class="btn btn-sm btn-outline-primary <?php echo (in_array($school_level, ['integrated', 'integrated_elementary', 'integrated_secondary'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-university me-1"></i> Integrated
                                    <span class="badge bg-info ms-1">K-12</span>
                                </button>
                            </div>
                            <button id="btnPrint" class="btn btn-success">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                    </div>
                    
                    <!-- Replace the Integrated Sub-menu with this: -->
                    <div id="integratedSubMenu" class="no-print mb-3 <?php echo (in_array($school_level, ['integrated', 'integrated_elementary', 'integrated_secondary'])) ? '' : 'd-none'; ?>">
                        <div class="btn-group" role="group">
                            <button id="btnIntegratedElementary" type="button" 
                                    class="btn btn-sm <?php echo (in_array($school_level, ['integrated', 'integrated_elementary'])) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-child me-1"></i> Integrated Elementary
                                <span class="badge bg-info ms-1">K-6</span>
                            </button>
                            <button id="btnIntegratedSecondary" type="button" 
                                    class="btn btn-sm <?php echo ($school_level === 'integrated_secondary') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-graduation-cap me-1"></i> Integrated Secondary
                                <span class="badge bg-info ms-1">7-12</span>
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
                            
                            <!-- Elementary Table -->
                            <table id="elementaryTable" class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="3">Grade Levels</th>
                                        <th rowspan="3" class="text-center">Enrolment</th>
                                        <th rowspan="3" class="text-center">Pupils Weighed</th>
                                        <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
                                        <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th colspan="2" class="text-center th-red">Severely Wasted</th>
                                        <th colspan="2" class="text-center th-orange">Wasted</th>
                                        <th colspan="2" class="text-center th-green">Normal BMI</th>
                                        <th colspan="2" class="text-center th-orange">Overweight</th>
                                        <th colspan="2" class="text-center th-red">Obese</th>
                                        <th colspan="2" class="text-center th-red">Severely Stunted</th>
                                        <th colspan="2" class="text-center th-orange">Stunted</th>
                                        <th colspan="2" class="text-center th-green">Normal HFA</th>
                                        <th colspan="2" class="text-center th-green">Tall</th>
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
                                    
                                    // Define grade arrays
                                    $elementaryGrades = [
                                        'Kinder_m' => 'Kinder (M)', 'Kinder_f' => 'Kinder (F)', 'Kinder_total' => 'Kinder (Total)',
                                        'Grade 1_m' => 'Grade 1 (M)', 'Grade 1_f' => 'Grade 1 (F)', 'Grade 1_total' => 'Grade 1 (Total)',
                                        'Grade 2_m' => 'Grade 2 (M)', 'Grade 2_f' => 'Grade 2 (F)', 'Grade 2_total' => 'Grade 2 (Total)',
                                        'Grade 3_m' => 'Grade 3 (M)', 'Grade 3_f' => 'Grade 3 (F)', 'Grade 3_total' => 'Grade 3 (Total)',
                                        'Grade 4_m' => 'Grade 4 (M)', 'Grade 4_f' => 'Grade 4 (F)', 'Grade 4_total' => 'Grade 4 (Total)',
                                        'Grade 5_m' => 'Grade 5 (M)', 'Grade 5_f' => 'Grade 5 (F)', 'Grade 5_total' => 'Grade 5 (Total)',
                                        'Grade 6_m' => 'Grade 6 (M)', 'Grade 6_f' => 'Grade 6 (F)', 'Grade 6_total' => 'Grade 6 (Total)'
                                    ];
                                    
                                    $secondaryGrades = [
                                        'Grade 7_m' => 'Grade 7 (M)', 'Grade 7_f' => 'Grade 7 (F)', 'Grade 7_total' => 'Grade 7 (Total)',
                                        'Grade 8_m' => 'Grade 8 (M)', 'Grade 8_f' => 'Grade 8 (F)', 'Grade 8_total' => 'Grade 8 (Total)',
                                        'Grade 9_m' => 'Grade 9 (M)', 'Grade 9_f' => 'Grade 9 (F)', 'Grade 9_total' => 'Grade 9 (Total)',
                                        'Grade 10_m' => 'Grade 10 (M)', 'Grade 10_f' => 'Grade 10 (F)', 'Grade 10_total' => 'Grade 10 (Total)',
                                        'Grade 11_m' => 'Grade 11 (M)', 'Grade 11_f' => 'Grade 11 (F)', 'Grade 11_total' => 'Grade 11 (Total)',
                                        'Grade 12_m' => 'Grade 12 (M)', 'Grade 12_f' => 'Grade 12 (F)', 'Grade 12_total' => 'Grade 12 (Total)'
                                    ];
                                    
                                    // Define BMI and HFA fields
                                    $bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
                                    $hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
                                    
                                    // Check if nutritional data exists
                                    $has_nutritional_data = isset($nutritional_data) && !empty($nutritional_data);
                                    ?>
                                    
                                    <?php foreach ($elementaryGrades as $gkey => $glabel): ?>
                                        <?php $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $gkey, 'enrolment') : 0; ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $has_nutritional_data ? gdata_division($nutritional_data, $gkey, 'pupils_weighed') : 0 ?></td>

                                            <!-- BMI columns (severely_wasted, wasted, normal_bmi, overweight, obese) each with count and % -->
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $gkey, $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                // Display zeros if no data
                                                for ($i=0; $i<5; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>

                                            <!-- HFA columns (severely_stunted, stunted, normal_hfa, tall, pupils_height) -->
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $gkey, $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                // Display zeros if no data
                                                for ($i=0; $i<5; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Grand total row - sum only the _total rows for elementary -->
                                    <tr class="table-primary">
                                        <td class="fw-bold">Grand Total (Elementary)</td>
                                        <?php
                                        if ($has_nutritional_data) {
                                            $totalEnrol = 0; $totalWeighed = 0;
                                            $grandCounts = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                            foreach ($elementaryGrades as $gkey => $glabel) {
                                                if (substr($gkey, -6) === '_total') {
                                                    $totalEnrol += gdata_division($nutritional_data, $gkey, 'enrolment');
                                                    $totalWeighed += gdata_division($nutritional_data, $gkey, 'pupils_weighed');
                                                    foreach ($grandCounts as $k => &$v) {
                                                        $v += gdata_division($nutritional_data, $gkey, $k);
                                                    }
                                                    unset($v);
                                                }
                                            }
                                        } else {
                                            $totalEnrol = 0;
                                            $totalWeighed = 0;
                                            $grandCounts = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        }
                                        ?>
                                        <td class="text-center fw-bold"><?= $totalEnrol ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol) ?></td><?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Secondary Table -->
                            <table id="secondaryTable" class="table table-bordered table-sm mb-0 d-none">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="3">Grade Levels</th>
                                        <th rowspan="3" class="text-center">Enrolment</th>
                                        <th rowspan="3" class="text-center">Pupils Weighed</th>
                                        <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
                                        <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th colspan="2" class="text-center th-red">Severely Wasted</th>
                                        <th colspan="2" class="text-center th-orange">Wasted</th>
                                        <th colspan="2" class="text-center th-green">Normal BMI</th>
                                        <th colspan="2" class="text-center th-orange">Overweight</th>
                                        <th colspan="2" class="text-center th-red">Obese</th>
                                        <th colspan="2" class="text-center th-red">Severely Stunted</th>
                                        <th colspan="2" class="text-center th-orange">Stunted</th>
                                        <th colspan="2" class="text-center th-green">Normal HFA</th>
                                        <th colspan="2" class="text-center th-green">Tall</th>
                                        <th colspan="2" class="text-center">Pupils Height</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <?php for ($i=0;$i<10;$i++): ?>
                                            <th class="text-center">Count</th>
                                            <th class="text-center">%</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($secondaryGrades as $gkey => $glabel): ?>
                                        <?php $enrol = $has_nutritional_data ? gdata_division($nutritional_data, $gkey, 'enrolment') : 0; ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $has_nutritional_data ? gdata_division($nutritional_data, $gkey, 'pupils_weighed') : 0 ?></td>

                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata_division($nutritional_data, $gkey, $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata_division($nutritional_data, $gkey, $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct_division($val, $enrol) . '</td>';
                                                }
                                            } else {
                                                // Display zeros if no data
                                                for ($i=0; $i<10; $i++) {
                                                    echo '<td class="text-center">0</td><td class="text-center">0%</td>';
                                                }
                                            }
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Grand total for Secondary -->
                                    <tr class="table-primary">
                                        <td class="fw-bold">Grand Total (Secondary)</td>
                                        <?php
                                        if ($has_nutritional_data) {
                                            $totalEnrol2 = 0; $totalWeighed2 = 0;
                                            $grandCounts2 = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                            foreach ($secondaryGrades as $gkey => $glabel) {
                                                if (substr($gkey, -6) === '_total') {
                                                    $totalEnrol2 += gdata_division($nutritional_data, $gkey, 'enrolment');
                                                    $totalWeighed2 += gdata_division($nutritional_data, $gkey, 'pupils_weighed');
                                                    foreach ($grandCounts2 as $k => &$v) {
                                                        $v += gdata_division($nutritional_data, $gkey, $k);
                                                    }
                                                    unset($v);
                                                }
                                            }
                                        } else {
                                            $totalEnrol2 = 0;
                                            $totalWeighed2 = 0;
                                            $grandCounts2 = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        }
                                        ?>
                                        <td class="text-center fw-bold"><?= $totalEnrol2 ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed2 ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts2[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol2) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts2[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct_division($val, $totalEnrol2) ?></td><?php endforeach; ?>
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
            get_school_details: '<?= base_url("division_dashboard_controller/get_school_details/"); ?>'
        },
        school_level: '<?= isset($school_level) ? $school_level : "all"; ?>',
        assessment_type: '<?= isset($assessment_type) ? $assessment_type : ""; ?>',
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
        all_schools_by_district: <?php echo json_encode($all_schools_by_district); ?>
    };
    </script>
    <script src="<?= base_url('assets/js/division_dashboard.js'); ?>"></script>
</body>
</html>