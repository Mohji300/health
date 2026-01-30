<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Dashboard - SBFP</title>
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Layout for fixed sidebar */
        #wrapper { display: flex; width: 100%; }
        #page-content-wrapper { flex: 1 1 auto; padding: 20px; }
        @media (max-width: 767.98px) { #mainSidebar { transform: translateX(-100%); } }

        .cursor-pointer { cursor: pointer; }
        .progress { height: 8px; }
        .school-item:hover { background-color: #f8f9fa; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-inprogress { background-color: #fef3c7; color: #92400e; }
        .status-nodata { background-color: #f3f4f6; color: #374151; }
        .submission-pending { background-color: #f3f4f6; color: #6b7280; }
        .submission-submitted { background-color: #d1fae5; color: #065f46; }
        
        /* Enhanced table styling from user dashboard */
        .table th { border-top: 1px solid #e3e6f0; font-weight: 600; background-color: #f8f9fc; }
        .table-bordered th, .table-bordered td { border: 1px solid #e3e6f0; }
        .table-fixed { table-layout: fixed; }
        .small-cell { font-size: 0.85rem; }
        
        /* Assessment switcher styling */
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

        /* Fix switcher visibility */
        .assessment-switcher .btn {
            font-weight: 600;
        }

        /* Baseline button colors */
        .assessment-switcher .btn.btn-primary {
            color: #0d6efd; /* Bootstrap primary blue */
        }

        /* Endline button colors */
        .assessment-switcher .btn.btn-success {
            color: #198754; /* Bootstrap success green */
        }

        /* Active state keeps white text */
        .assessment-switcher .btn.active.btn-primary,
        .assessment-switcher .btn.active.btn-success {
            color: #ffffff !important;
        }
        
        /* Assessment type badge */
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

                /* small helper used to visually verify print CSS in print preview */
                .print-debug { display: none; }

                /* Print layout for A4 (bond) - single page landscape */
            @page { size: A4 landscape; margin: 8mm; }
            @media print {
                html, body { background: #fff; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: Arial, Helvetica, sans-serif; font-size: 8px; margin: 0; padding: 0; line-height: 1.2; }
                .no-print, .btn, .assessment-switcher, .modal, #sidebar-wrapper, .btn-group { display: none !important; }
                #wrapper { display: block !important; }
                #page-content-wrapper { padding: 4px !important; }
                .card { box-shadow: none !important; border: none !important; padding: 0 !important; }
                /* Aggressive compression for single-page fit */
                table { width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; font-size: 8px !important; margin: 0 !important; }
                th, td { padding: 2px !important; border: 0.5px solid #dee2e6 !important; word-wrap: break-word; white-space: normal; line-height: 1.1; }
                .small-cell td, .small-cell th { padding: 1.5px !important; }
                .table thead { background: #f8f9fc !important; -webkit-print-color-adjust: exact; }
                .table-primary { background-color: #f1f5f9 !important; }
                /* Minimize header/footer */
                h3 { font-size: 10px; margin: 0 0 2px 0; font-weight: bold; }
                p { font-size: 7px; margin: 0 0 4px 0; }

                /* show debug banner only in print preview/print */
                .print-debug { display: block !important; position: fixed !important; top: 4mm !important; left: 8mm !important; background: #ffeb3b !important; color: #000 !important; padding: 4px 8px !important; z-index: 99999 !important; font-weight: 700 !important; border-radius: 3px !important; }

                }
    </style>
</head>
<body class="bg-light">
        <div id="wrapper">
            <?php $this->load->view('templates/sidebar'); ?>
            <div id="page-content-wrapper">
                <div class="container-fluid py-4">
                    <div class="print-debug">PRINT CSS ACTIVE — visible in print preview</div>
                
                <!-- Welcome Message -->
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="alert-heading mb-1">Welcome, <?php echo htmlspecialchars($user_district); ?></h4>
                            <p class="mb-0">Viewing reports for <?php echo htmlspecialchars($parsed_user_district); ?> district</p>
                        </div>
                        <div class="assessment-switcher">
                            <button class="btn btn-primary btn-sm <?php echo ($assessment_type ?? 'baseline') == 'baseline' ? 'active' : ''; ?>" 
                                    id="switchToBaseline">
                                <i class="fas fa-flag me-1"></i> Baseline
                            </button>
                            <button class="btn btn-success btn-sm <?php echo ($assessment_type ?? 'baseline') == 'endline' ? 'active' : ''; ?>" 
                                    id="switchToEndline">
                                <i class="fas fa-flag-checkered me-1"></i> Endline
                            </button>
                        </div>
                    </div>
                </div>
                
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
                
                <!-- Schools Box (Initially Hidden) -->
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
                
                <!-- Detailed Nutritional Status Table (from user dashboard) -->
                <?php 
                // Define grade arrays and helper functions
                function gdata($data, $key, $field) {
                    if (!isset($data[$key])) return 0;
                    return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
                }
                
                function pct($num, $den) {
                    if (!$den || $den == 0) return '0%';
                    return round(($num / $den) * 100) . '%';
                }
                
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
                
                <?php if (!empty($nutritional_data)): ?>
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Consolidated Nutritional Assessment Report - <?php echo htmlspecialchars($parsed_user_district); ?> District
                            <span class="badge <?php echo ($assessment_type ?? 'baseline') == 'baseline' ? 'badge-baseline' : 'badge-endline'; ?> ms-2">
                                <?php echo ucfirst($assessment_type ?? 'baseline'); ?>
                            </span>
                        </h6>
                        <div class="no-print">
                            <div class="btn-group me-2" role="group">
                                <button id="btnElementary" type="button" class="btn btn-outline-primary active">
                                    <i class="fas fa-child me-1"></i> Elementary
                                </button>
                                <button id="btnSecondary" type="button" class="btn btn-outline-primary">
                                    <i class="fas fa-graduation-cap me-1"></i> Secondary
                                </button>
                            </div>
                            <button id="btnPrint" class="btn btn-success">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="tableContainer" class="table-responsive p-3">
                            
<!-- Elementary Table -->
<table id="elementaryTable" class="table table-bordered table-sm table-fixed small-cell mb-0">
    <thead class="table-light">
        <tr>
            <th rowspan="3">Grade Levels</th>
            <th rowspan="3" class="text-center">Enrolment</th>
            <th rowspan="3" class="text-center">Pupils Weighed</th>
            <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
            <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
        </tr>
        <tr class="table-secondary">
            <th colspan="2" class="text-center">Severely Wasted</th>
            <th colspan="2" class="text-center">Wasted</th>
            <th colspan="2" class="text-center">Normal BMI</th>
            <th colspan="2" class="text-center">Overweight</th>
            <th colspan="2" class="text-center">Obese</th>

            <th colspan="2" class="text-center">Severely Stunted</th>
            <th colspan="2" class="text-center">Stunted</th>
            <th colspan="2" class="text-center">Normal HFA</th>
            <th colspan="2" class="text-center">Tall</th>
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
        <?php foreach ($elementaryGrades as $gkey => $glabel): ?>
            <?php 
            // For district view, get data if it exists
            $gradeData = isset($nutritional_data[$gkey]) ? $nutritional_data[$gkey] : [];
            $enrol = isset($gradeData['enrolment']) ? $gradeData['enrolment'] : 0;
            $weighed = isset($gradeData['pupils_weighed']) ? $gradeData['pupils_weighed'] : $enrol;
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                <td class="text-center"><?= $enrol ?></td>
                <td class="text-center"><?= $weighed ?></td>

                <!-- BMI columns -->
                <?php
                // BMI fields from model
                $bmiFields = ['severely_wasted', 'wasted', 'normal_bmi', 'overweight', 'obese'];
                foreach ($bmiFields as $field):
                    $val = isset($gradeData[$field]) ? $gradeData[$field] : 0;
                ?>
                    <td class="text-center"><?= $val ?></td>
                    <td class="text-center"><?= pct($val, $enrol) ?></td>
                <?php endforeach; ?>

                <!-- HFA columns -->
                <?php
                // HFA fields from model
                $hfaFields = ['severely_stunted', 'stunted', 'normal_hfa', 'tall', 'pupils_height'];
                foreach ($hfaFields as $field):
                    $val = isset($gradeData[$field]) ? $gradeData[$field] : 0;
                ?>
                    <td class="text-center"><?= $val ?></td>
                    <td class="text-center"><?= pct($val, $enrol) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>

        <!-- Grand total row -->
        <tr class="table-primary">
            <td class="fw-bold">Grand Total (Elementary)</td>
            <?php
            $totalEnrol = 0;
            $totalWeighed = 0;
            $bmiTotals = array_fill_keys($bmiFields, 0);
            $hfaTotals = array_fill_keys($hfaFields, 0);
            
            // Calculate totals only for _total rows
            foreach ($elementaryGrades as $gkey => $glabel):
                if (strpos($gkey, '_total') !== false && isset($nutritional_data[$gkey])):
                    $gradeData = $nutritional_data[$gkey];
                    $enrol = isset($gradeData['enrolment']) ? $gradeData['enrolment'] : 0;
                    $totalEnrol += $enrol;
                    
                    $weighed = isset($gradeData['pupils_weighed']) ? $gradeData['pupils_weighed'] : $enrol;
                    $totalWeighed += $weighed;
                    
                    // Add BMI totals
                    foreach ($bmiFields as $field):
                        $bmiTotals[$field] += isset($gradeData[$field]) ? $gradeData[$field] : 0;
                    endforeach;
                    
                    // Add HFA totals
                    foreach ($hfaFields as $field):
                        $hfaTotals[$field] += isset($gradeData[$field]) ? $gradeData[$field] : 0;
                    endforeach;
                endif;
            endforeach;
            ?>
            <td class="text-center fw-bold"><?= $totalEnrol ?></td>
            <td class="text-center fw-bold"><?= $totalWeighed ?></td>
            
            <!-- BMI totals -->
            <?php foreach ($bmiTotals as $val): ?>
                <td class="text-center fw-bold"><?= $val ?></td>
                <td class="text-center fw-bold"><?= pct($val, $totalEnrol) ?></td>
            <?php endforeach; ?>
            
            <!-- HFA totals -->
            <?php foreach ($hfaTotals as $val): ?>
                <td class="text-center fw-bold"><?= $val ?></td>
                <td class="text-center fw-bold"><?= pct($val, $totalEnrol) ?></td>
            <?php endforeach; ?>
        </tr>
    </tbody>
</table>

<!-- Secondary Table (hidden by default) -->
<table id="secondaryTable" class="table table-bordered table-sm table-fixed small-cell mb-0 d-none">
    <thead class="table-light">
        <tr>
            <th rowspan="3">Grade Levels</th>
            <th rowspan="3" class="text-center">Enrolment</th>
            <th rowspan="3" class="text-center">Pupils Weighed</th>
            <th colspan="10" class="text-center">BODY MASS INDEX (BMI)</th>
            <th colspan="10" class="text-center">HEIGHT-FOR-AGE (HFA)</th>
        </tr>
        <tr class="table-secondary">
            <th colspan="2" class="text-center">Severely Wasted</th>
            <th colspan="2" class="text-center">Wasted</th>
            <th colspan="2" class="text-center">Normal BMI</th>
            <th colspan="2" class="text-center">Overweight</th>
            <th colspan="2" class="text-center">Obese</th>

            <th colspan="2" class="text-center">Severely Stunted</th>
            <th colspan="2" class="text-center">Stunted</th>
            <th colspan="2" class="text-center">Normal HFA</th>
            <th colspan="2" class="text-center">Tall</th>
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
            <?php 
            $gradeData = isset($nutritional_data[$gkey]) ? $nutritional_data[$gkey] : [];
            $enrol = isset($gradeData['enrolment']) ? $gradeData['enrolment'] : 0;
            $weighed = isset($gradeData['pupils_weighed']) ? $gradeData['pupils_weighed'] : $enrol;
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                <td class="text-center"><?= $enrol ?></td>
                <td class="text-center"><?= $weighed ?></td>

                <!-- BMI columns -->
                <?php
                $bmiFields = ['severely_wasted', 'wasted', 'normal_bmi', 'overweight', 'obese'];
                foreach ($bmiFields as $field):
                    $val = isset($gradeData[$field]) ? $gradeData[$field] : 0;
                ?>
                    <td class="text-center"><?= $val ?></td>
                    <td class="text-center"><?= pct($val, $enrol) ?></td>
                <?php endforeach; ?>

                <!-- HFA columns -->
                <?php
                $hfaFields = ['severely_stunted', 'stunted', 'normal_hfa', 'tall', 'pupils_height'];
                foreach ($hfaFields as $field):
                    $val = isset($gradeData[$field]) ? $gradeData[$field] : 0;
                ?>
                    <td class="text-center"><?= $val ?></td>
                    <td class="text-center"><?= pct($val, $enrol) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>

        <!-- Grand total for Secondary -->
        <tr class="table-primary">
            <td class="fw-bold">Grand Total (Secondary)</td>
            <?php
            $totalEnrol2 = 0;
            $totalWeighed2 = 0;
            $bmiTotals2 = array_fill_keys($bmiFields, 0);
            $hfaTotals2 = array_fill_keys($hfaFields, 0);
            
            foreach ($secondaryGrades as $gkey => $glabel):
                if (strpos($gkey, '_total') !== false && isset($nutritional_data[$gkey])):
                    $gradeData = $nutritional_data[$gkey];
                    $enrol = isset($gradeData['enrolment']) ? $gradeData['enrolment'] : 0;
                    $totalEnrol2 += $enrol;
                    
                    $weighed = isset($gradeData['pupils_weighed']) ? $gradeData['pupils_weighed'] : $enrol;
                    $totalWeighed2 += $weighed;
                    
                    foreach ($bmiFields as $field):
                        $bmiTotals2[$field] += isset($gradeData[$field]) ? $gradeData[$field] : 0;
                    endforeach;
                    
                    foreach ($hfaFields as $field):
                        $hfaTotals2[$field] += isset($gradeData[$field]) ? $gradeData[$field] : 0;
                    endforeach;
                endif;
            endforeach;
            ?>
            <td class="text-center fw-bold"><?= $totalEnrol2 ?></td>
            <td class="text-center fw-bold"><?= $totalWeighed2 ?></td>
            
            <!-- BMI totals -->
            <?php foreach ($bmiTotals2 as $val): ?>
                <td class="text-center fw-bold"><?= $val ?></td>
                <td class="text-center fw-bold"><?= pct($val, $totalEnrol2) ?></td>
            <?php endforeach; ?>
            
            <!-- HFA totals -->
            <?php foreach ($hfaTotals2 as $val): ?>
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
    
    <!-- Bootstrap 5.3.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script>
    $(document).ready(function() {
        console.log('Document ready - JavaScript loaded');
        
        // Toggle schools box
        $('#overallSummaryCard').click(function(e) {
            console.log('Summary card clicked');
            e.stopPropagation(); // Prevent event bubbling
            
            $('#schoolsBox').toggleClass('d-none');
            const icon = $(this).find('.fa-chevron-up');
            const text = $(this).find('.small');
            
            if ($('#schoolsBox').hasClass('d-none')) {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                text.html('<i class="fas fa-chevron-up me-1"></i> Click to view schools');
            } else {
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                text.html('<i class="fas fa-chevron-down me-1"></i> Hide schools');
            }
        });
        
        $('#closeSchoolsBox').click(function(e) {
            console.log('Close button clicked');
            e.preventDefault();
            e.stopPropagation();
            
            $('#schoolsBox').addClass('d-none');
            $('#overallSummaryCard').find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $('#overallSummaryCard').find('.small').html('<i class="fas fa-chevron-up me-1"></i> Click to view schools');
        });
        
        // Show school details
        $(document).on('click', '.school-item', function(e) {
            e.preventDefault();
            const schoolName = $(this).data('school');
            console.log('School item clicked:', schoolName);
            showSchoolDetails(schoolName);
        });
        
        function showSchoolDetails(schoolName) {
            $('#schoolModalBody').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading school details...</p>
                </div>
            `);
            
            const modal = new bootstrap.Modal(document.getElementById('schoolModal'));
            modal.show();
            
            $.ajax({
                url: '<?php echo base_url("district_dashboard/get_school_details/"); ?>' + encodeURIComponent(schoolName),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success && response.data) {
                        const details = response.data;
                        $('#schoolModalBody').html(`
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">School Name</label>
                                    <div class="form-control bg-light">${details.school_name || 'N/A'}</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">School Address</label>
                                    <div class="form-control bg-light">${details.school_address || 'N/A'}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Legislative District</label>
                                    <div class="form-control bg-light">${details.legislative_district || 'N/A'}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">School District</label>
                                    <div class="form-control bg-light">${details.school_district || 'N/A'}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">School Level</label>
                                    <div class="form-control bg-light">${details.school_level || 'N/A'}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">School Head Name</label>
                                    <div class="form-control bg-light">${details.school_head_name || 'N/A'}</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <div class="form-control bg-light">${details.email_address || 'N/A'}</div>
                                </div>
                            </div>
                        `);
                    } else {
                        $('#schoolModalBody').html(`
                            <div class="alert alert-danger">
                                Unable to load school details. Please try again.
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', error);
                    $('#schoolModalBody').html(`
                        <div class="alert alert-danger">
                            Error loading school details. Please try again.
                        </div>
                    `);
                }
            });
        }
        
        // Assessment type switching
        $('#switchToBaseline').click(function(e) {
            e.preventDefault();
            switchAssessmentType('baseline');
        });
        
        $('#switchToEndline').click(function(e) {
            e.preventDefault();
            switchAssessmentType('endline');
        });
        
        function switchAssessmentType(type) {
            var activeBtn = type === 'baseline' ? $('#switchToBaseline') : $('#switchToEndline');
            var inactiveBtn = type === 'baseline' ? $('#switchToEndline') : $('#switchToBaseline');
            
            var originalHtml = activeBtn.html();
            activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
            activeBtn.prop('disabled', true);
            inactiveBtn.prop('disabled', true);
            
            // Redirect with assessment type parameter
            window.location.href = '<?php echo site_url("district_dashboard"); ?>?assessment_type=' + type;
        }
        
        // Table switching
        const btnElem = document.getElementById('btnElementary');
        const btnSec = document.getElementById('btnSecondary');
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        const btnPrint = document.getElementById('btnPrint');

        if (btnElem && btnSec && elemTable && secTable) {
            btnElem.addEventListener('click', () => {
                btnElem.classList.add('active');
                btnSec.classList.remove('active');
                elemTable.classList.remove('d-none');
                secTable.classList.add('d-none');
            });

            btnSec.addEventListener('click', () => {
                btnSec.classList.add('active');
                btnElem.classList.remove('active');
                secTable.classList.remove('d-none');
                elemTable.classList.add('d-none');
            });
        }

        if (btnPrint) {
            btnPrint.addEventListener('click', () => {
                // Open a new window and print only the visible table
                const win = window.open('', '_blank');
                const isElemVisible = !elemTable.classList.contains('d-none');
                const tableHtml = (isElemVisible ? elemTable : secTable).outerHTML;
                
                const assessmentType = '<?php echo ucfirst($assessment_type ?? "baseline"); ?>';
                const reportDate = new Date().toLocaleDateString();
                const districtName = '<?php echo htmlspecialchars($parsed_user_district); ?>';

                win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>District Nutritional Report - ' + districtName + '</title>' +
                    '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
                    '<style>body{padding:20px;} table{width:100%; border-collapse:collapse;} th, td{border:1px solid #dee2e6; font-size:12px;}' +
                    '.print-header { margin-bottom: 20px; text-align: center; }' +
                    '.print-header h3 { color: #2c3e50; }' +
                    '</style>' +
                    '</head><body>');
                win.document.write('<div class="print-header">');
                win.document.write('<h3>Nutritional Status Report - ' + districtName + ' District</h3>');
                win.document.write('<p><strong>Assessment Type:</strong> ' + assessmentType + ' | <strong>Report Date:</strong> ' + reportDate + '</p>');
                win.document.write('</div>');
                win.document.write(tableHtml);
                win.document.write('<script>window.onload=function(){ window.print(); window.onafterprint=function(){ window.close(); } }<\/script>');
                win.document.write('</body></html>');
                win.document.close();
            });
        }
        
        // Search box filtering for schools list
        $('#schoolSearch').on('keyup', function() {
            const term = $(this).val().toLowerCase().trim();
            let visible = 0;
            $('.school-item').each(function() {
                const name = ($(this).data('school') || '').toString().toLowerCase();
                const code = ($(this).data('code') || '').toString().toLowerCase();
                if (!term || name.indexOf(term) !== -1 || code.indexOf(term) !== -1) {
                    $(this).show();
                    visible++;
                } else {
                    $(this).hide();
                }
            });

            if (visible === 0) {
                $('#noSearchResults').removeClass('d-none');
            } else {
                $('#noSearchResults').addClass('d-none');
            }
        });

        $('#clearSearch').on('click', function() {
            $('#schoolSearch').val('');
            $('#noSearchResults').addClass('d-none');
            $('.school-item').show();
        });
        
        // Add a test click handler to check if jQuery is working
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'Not loaded');
        
        // Test if the summary card is being clicked
        $('#overallSummaryCard').on('mousedown mouseup click', function(e) {
            console.log('Event triggered:', e.type);
        });
    });
    </script>
</body>
</html>