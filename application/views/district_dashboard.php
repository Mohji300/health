<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function gdata($data, $key, $field) {
    if (!isset($data[$key])) return 0;
    return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
}

function pct($num, $den) {
    if (!$den || $den == 0) return '0%';
    return round(($num / $den) * 100) . '%';
}

// Get current assessment type from session or default to baseline
$assessment_type = isset($assessment_type) ? $assessment_type : 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$is_midline = ($assessment_type == 'midline');
$is_endline = ($assessment_type == 'endline');

// Get school level filter
$school_level = isset($school_level) ? $school_level : 'all';

// Get counts
$baseline_count = isset($baseline_count) ? $baseline_count : 0;
$midline_count = isset($midline_count) ? $midline_count : 0;
$endline_count = isset($endline_count) ? $endline_count : 0;

// Define grade arrays here (or they can stay where they are in your view)
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

$bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
$hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Dashboard - SBFP</title>
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/district_dashboard.css'); ?>">
</head>
<body class="bg-light">
    <div class="d-flex" id="wrapper">
        <?php $this->load->view('templates/sidebar'); ?>
        <div id="page-content-wrapper" class="w-100">
            <div class="container-fluid py-4">
                <div class="print-debug">PRINT CSS ACTIVE — visible in print preview</div>
                
                <!-- Header Card -->
                <div class="card bg-gradient-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h2 font-weight-bold mb-2">District Dashboard</h1>
                                <p class="mb-0">Viewing reports for <?php echo htmlspecialchars($parsed_user_district); ?> district &middot;
                                    <span class="badge <?php 
                                        if (($assessment_type ?? 'baseline') == 'baseline') echo 'badge-baseline';
                                        elseif (($assessment_type ?? 'baseline') == 'midline') echo 'badge-midline';
                                        else echo 'badge-endline';
                                    ?> assessment-badge">
                                        <?php echo ucfirst($assessment_type ?? 'baseline'); ?> Assessment
                                    </span>
                                </p>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="assessment-switcher">
                                    <button class="btn btn-primary btn-sm <?php echo ($assessment_type ?? 'baseline') == 'baseline' ? 'active' : ''; ?>" 
                                            id="switchToBaseline">
                                        <i class="fas fa-flag me-1"></i> Baseline
                                    </button>
                                    <button class="btn btn-info btn-sm <?php echo ($assessment_type ?? 'baseline') == 'midline' ? 'active' : ''; ?>" 
                                            id="switchToMidline">
                                        <i class="fas fa-flag me-1"></i> Midline
                                    </button>
                                    <button class="btn btn-success btn-sm <?php echo ($assessment_type ?? 'baseline') == 'endline' ? 'active' : ''; ?>" 
                                            id="switchToEndline">
                                        <i class="fas fa-flag-checkered me-1"></i> Endline
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- No Data Alert -->
                <?php if (!$has_data): ?>
                <div class="alert alert-warning mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">No <?php echo ucfirst($assessment_type); ?> Data Available</h5>
                            <p class="mb-0">
                                No <?php echo strtolower($assessment_type); ?> assessment data has been submitted yet for this district. 
                                The table below shows all zeros.
                                <?php if ($assessment_type == 'midline'): ?>
                                    You need to create midline assessments first to see data here.
                                <?php elseif ($assessment_type == 'endline'): ?>
                                    You need to create endline assessments first to see data here.
                                <?php else: ?>
                                    You need to create baseline assessments first to see data here.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php elseif ($processed_count == 0): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">Data Processing Notice</h5>
                            <p class="mb-0">
                                <?php echo $processed_count; ?> <?php echo ucfirst($assessment_type); ?> assessment records processed. 
                                Some records may have been skipped due to missing or invalid data.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Overall Summary Card -->
                <div class="card bg-primary text-white mb-4 cursor-pointer no-print" id="overallSummaryCard">
                    <div class="card-body">
                        <h2 class="card-title h4"><?php echo htmlspecialchars($parsed_user_district); ?> District Report Status</h2>
                        <div class="row mt-3">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="display-4 fw-bold">
                                    <?php echo isset($district_stats[$parsed_user_district]['total_schools']) ? 
                                        $district_stats[$parsed_user_district]['total_schools'] : 0; ?>
                                </div>
                                <div class="text-white-50">Total Schools</div>
                            </div>
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="display-4 fw-bold">
                                    <?php echo isset($district_stats[$parsed_user_district]['submitted_reports']) ? 
                                        $district_stats[$parsed_user_district]['submitted_reports'] : 0; ?>
                                </div>
                                <div class="text-white-50">Reports Submitted</div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="display-4 fw-bold">
                                    <?php echo isset($district_stats[$parsed_user_district]['completion_rate']) ? 
                                        $district_stats[$parsed_user_district]['completion_rate'] : 0; ?>%
                                </div>
                                <div class="text-white-50">Completion Rate</div>
                            </div>
                        </div>
                        <div class="mt-3 text-white-50 small">
                            <i class="fas fa-chevron-up me-1"></i> Click to view schools
                        </div>
                    </div>
                </div>
                
                <!-- Schools Box -->
                <div class="card mb-4 d-none no-print" id="schoolsBox">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="h5 mb-0">Schools in <?php echo htmlspecialchars($parsed_user_district); ?></h3>
                            <button type="button" class="btn-close" id="closeSchoolsBox"></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Submission Progress -->
                        <?php
                        $submitted_count = 0;
                        $total_schools = count($user_schools);
                        foreach ($user_schools as $school) {
                            if ($school['has_submitted']) $submitted_count++;
                        }
                        $submission_percentage = $total_schools > 0 ? round(($submitted_count / $total_schools) * 100) : 0;
                        ?>
                        <div class="alert alert-primary mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Submission Progress:</span>
                                <span class="fw-bold"><?php echo $submitted_count; ?>/<?php echo $total_schools; ?> schools submitted</span>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-success" style="width: <?php echo $submission_percentage; ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Schools Search + List -->
                        <div class="mb-3 d-flex gap-2 align-items-center">
                            <input id="schoolSearch" type="text" class="form-control form-control-sm" placeholder="Search schools by name or id...">
                            <button id="clearSearch" class="btn btn-outline-secondary btn-sm">Clear</button>
                        </div>

                        <div class="border rounded" style="max-height: 400px; overflow-y: auto;">
                            <div class="list-group list-group-flush" id="schoolsList">
                                <?php if (empty($user_schools)): ?>
                                <div class="text-center py-4 text-muted" id="noSchoolsMessage">
                                    No schools found for your district.
                                </div>
                                <?php else: ?>
                                    <?php foreach ($user_schools as $school): ?>
                                    <a href="#" class="list-group-item list-group-item-action school-item" 
                                       data-school="<?php echo htmlspecialchars($school['name']); ?>" data-code="<?php echo htmlspecialchars($school['code'] ?? ''); ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="rounded-circle 
                                                    <?php echo $school['has_submitted'] ? 'submission-submitted' : 'submission-pending'; ?> 
                                                    p-2 d-flex align-items-center justify-content-center" 
                                                    style="width: 36px; height: 36px;">
                                                    <i class="fas fa-<?php echo $school['has_submitted'] ? 'check' : 'circle'; ?> fa-xs"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium"><?php echo htmlspecialchars($school['name']); ?></div>
                                                <div class="small text-muted">SCHOOL ID: <?php echo htmlspecialchars($school['code'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div>
                                                <span class="badge <?php echo $school['has_submitted'] ? 'submission-submitted' : 'submission-pending'; ?>">
                                                    <?php echo $school['has_submitted'] ? 'Submitted' : 'Pending'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-center py-3 text-muted d-none" id="noSearchResults">No matching schools found.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Nutritional Assessment Card -->
                <?php if (!empty($nutritional_data)): ?>
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="m-0 font-weight-bold text-primary">
                            District Nutritional Assessment Report - <br> <?php echo htmlspecialchars($parsed_user_district); ?> District
                            <span class="badge <?php 
                                if ($assessment_type == 'baseline') echo 'badge-baseline';
                                elseif ($assessment_type == 'midline') echo 'badge-midline';
                                else echo 'badge-endline';
                            ?> ms-2">
                                <?php echo ucfirst($assessment_type ?? 'baseline'); ?>
                            </span>
                            <!-- Show active filter badge -->
                            <?php if ($school_level !== 'all'): ?>
                            <span class="badge bg-secondary ms-2 filter-badge">
                                <i class="fas fa-filter me-1"></i>
                                <?php 
                                    if ($school_level === 'integrated_elementary') echo 'Integrated (Elementary)';
                                    elseif ($school_level === 'integrated_secondary') echo 'Integrated (Secondary)';
                                    else echo ucfirst($school_level); 
                                ?>
                            </span>
                            <?php endif; ?>
                        </h6>
                        <div class="no-print d-flex flex-wrap align-items-center">
                            <!-- School Level Filter Buttons -->
                            <div class="btn-group me-2 mb-2 mb-sm-0" role="group">
                                <button id="btnElementary" type="button" 
                                        class="btn btn-outline-primary <?php echo ($school_level === 'all' || $school_level === 'elementary' || $school_level === 'integrated_elementary') ? 'active' : ''; ?>">
                                    <i class="fas fa-child me-1"></i> Elementary
                                    <span class="badge bg-info ms-1">K-6</span>
                                </button>
                                <button id="btnSecondary" type="button" 
                                        class="btn btn-outline-primary <?php echo ($school_level === 'secondary' || $school_level === 'integrated_secondary') ? 'active' : ''; ?>">
                                    <i class="fas fa-graduation-cap me-1"></i> Secondary
                                    <span class="badge bg-info ms-1">7-12</span>
                                </button>
                                <button id="btnIntegrated" type="button" 
                                        class="btn btn-outline-primary <?php echo (in_array($school_level, ['integrated', 'integrated_elementary', 'integrated_secondary'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-university me-1"></i> Integrated
                                    <span class="badge bg-info ms-1">K-12</span>
                                </button>
                            </div>
                            <button id="btnPrint" class="btn btn-success">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                    </div>
                    
                    <!-- Integrated Sub-Menu -->
                    <div id="integratedSubMenu" class="no-print <?php echo (in_array($school_level, ['integrated', 'integrated_elementary', 'integrated_secondary'])) ? '' : 'd-none'; ?>">
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
                        <div id="tableContainer" class="table-responsive p-3">
                            
                            <!-- Elementary Table -->
                            <table id="elementaryTable" class="table table-bordered table-sm table-fixed small-cell mb-0 <?php echo ($school_level === 'secondary' || $school_level === 'integrated_secondary') ? 'd-none' : ''; ?>">
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
                                    <?php 
                                    $bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
                                    $hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
                                    ?>
                                    <?php foreach ($elementaryGrades as $gkey => $glabel): ?>
                                        <?php $enrol = gdata($nutritional_data, $gkey, 'enrolment'); ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= gdata($nutritional_data, $gkey, 'pupils_weighed') ?></td>

                                            <!-- BMI columns -->
                                            <?php foreach ($bmiFields as $bf): ?>
                                                <?php $val = gdata($nutritional_data, $gkey, $bf); ?>
                                                <td class="text-center"><?= $val ?></td>
                                                <td class="text-center"><?= pct($val, $enrol) ?></td>
                                            <?php endforeach; ?>

                                            <!-- HFA columns -->
                                            <?php foreach ($hfaFields as $hf): ?>
                                                <?php $val = gdata($nutritional_data, $gkey, $hf); ?>
                                                <td class="text-center"><?= $val ?></td>
                                                <td class="text-center"><?= pct($val, $enrol) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Grand total row - Elementary -->
                                    <tr class="table-primary">
                                        <td class="fw-bold">Grand Total (Elementary)</td>
                                        <?php
                                        $totalEnrol = 0; $totalWeighed = 0;
                                        $grandCounts = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        foreach ($elementaryGrades as $gkey => $glabel) {
                                            if (substr($gkey, -6) === '_total') {
                                                $totalEnrol += gdata($nutritional_data, $gkey, 'enrolment');
                                                $totalWeighed += gdata($nutritional_data, $gkey, 'pupils_weighed');
                                                foreach ($grandCounts as $k => &$v) {
                                                    $v += gdata($nutritional_data, $gkey, $k);
                                                }
                                                unset($v);
                                            }
                                        }
                                        ?>
                                        <td class="text-center fw-bold"><?= $totalEnrol ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts[$bf]; ?>
                                            <td class="text-center fw-bold"><?= $val ?></td>
                                            <td class="text-center fw-bold"><?= pct($val, $totalEnrol) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts[$hf]; ?>
                                            <td class="text-center fw-bold"><?= $val ?></td>
                                            <td class="text-center fw-bold"><?= pct($val, $totalEnrol) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Secondary Table -->
                            <table id="secondaryTable" class="table table-bordered table-sm table-fixed small-cell mb-0 <?php echo ($school_level === 'secondary' || $school_level === 'integrated_secondary') ? '' : 'd-none'; ?>">
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
                                        <?php $enrol = gdata($nutritional_data, $gkey, 'enrolment'); ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= gdata($nutritional_data, $gkey, 'pupils_weighed') ?></td>

                                            <!-- BMI columns -->
                                            <?php foreach ($bmiFields as $bf): ?>
                                                <?php $val = gdata($nutritional_data, $gkey, $bf); ?>
                                                <td class="text-center"><?= $val ?></td>
                                                <td class="text-center"><?= pct($val, $enrol) ?></td>
                                            <?php endforeach; ?>

                                            <!-- HFA columns -->
                                            <?php foreach ($hfaFields as $hf): ?>
                                                <?php $val = gdata($nutritional_data, $gkey, $hf); ?>
                                                <td class="text-center"><?= $val ?></td>
                                                <td class="text-center"><?= pct($val, $enrol) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Grand total row - Secondary -->
                                    <tr class="table-primary">
                                        <td class="fw-bold">Grand Total (Secondary)</td>
                                        <?php
                                        $totalEnrol2 = 0; $totalWeighed2 = 0;
                                        $grandCounts2 = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                                        foreach ($secondaryGrades as $gkey => $glabel) {
                                            if (substr($gkey, -6) === '_total') {
                                                $totalEnrol2 += gdata($nutritional_data, $gkey, 'enrolment');
                                                $totalWeighed2 += gdata($nutritional_data, $gkey, 'pupils_weighed');
                                                foreach ($grandCounts2 as $k => &$v) {
                                                    $v += gdata($nutritional_data, $gkey, $k);
                                                }
                                                unset($v);
                                            }
                                        }
                                        ?>
                                        <td class="text-center fw-bold"><?= $totalEnrol2 ?></td>
                                        <td class="text-center fw-bold"><?= $totalWeighed2 ?></td>
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts2[$bf]; ?>
                                            <td class="text-center fw-bold"><?= $val ?></td>
                                            <td class="text-center fw-bold"><?= pct($val, $totalEnrol2) ?></td>
                                        <?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts2[$hf]; ?>
                                            <td class="text-center fw-bold"><?= $val ?></td>
                                            <td class="text-center fw-bold"><?= pct($val, $totalEnrol2) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
    window.DistrictDashboardConfig = {
        urls: {
            set_school_level: '<?= site_url("District_dashboard_controller/set_school_level"); ?>',
            base: '<?= site_url("District_dashboard_controller"); ?>',
            get_school_details: '<?= base_url("District_dashboard_controller/get_school_details/"); ?>'
        },
        assessment_type: '<?= $assessment_type ?? "baseline"; ?>',
        assessment_type_display: '<?= ucfirst($assessment_type ?? "baseline"); ?>',
        school_level: '<?= $school_level ?? "all"; ?>',
        district_name: '<?= htmlspecialchars($parsed_user_district); ?>'
    };
    </script>
    <script src="<?= base_url('assets/js/district_dashboard.js'); ?>"></script>
</body>
</html>