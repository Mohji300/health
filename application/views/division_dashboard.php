<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Division Dashboard - SBFP</title>
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Layout to work with templates/sidebar (fixed #mainSidebar + #sidebarSpacer) */
        #wrapper { display: flex; width: 100%; }
        #page-content-wrapper { flex: 1 1 auto; padding: 20px; }
        @media (max-width: 767.98px) { #mainSidebar { transform: translateX(-100%); } }

        .cursor-pointer { cursor: pointer; }
        .progress { height: 8px; }
        .school-item:hover { background-color: #f8f9fa; }
        .modal-backdrop { z-index: 1040; }
        .modal { z-index: 1050; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-inprogress { background-color: #fef3c7; color: #92400e; }
        .status-nodata { background-color: #f3f4f6; color: #374151; }
        .submission-pending { background-color: #f3f4f6; color: #6b7280; }
        .submission-submitted { background-color: #d1fae5; color: #065f46; }
        .district-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        
        /* Enhanced table styling to match reports */
        .table th { border-top: 1px solid #e3e6f0; font-weight: 600; background-color: #f8f9fc; }
        .table-bordered th, .table-bordered td { border: 1px solid #e3e6f0; }
        
        /* Assessment switcher styling */
        .assessment-switcher {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 2px;
            display: inline-flex;
            align-items: center;
            margin-left: 15px;
        }
        .assessment-switcher .btn {
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
            transition: all 0.3s;
            min-width: 85px;
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
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            margin-left: 8px;
        }
        .badge-baseline {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }
        .badge-endline {
            background: linear-gradient(45deg, #1cc88a, #13855c);
            color: white;
        }
        
        /* Table switcher buttons */
        .table-switcher .btn.active {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .table-switcher .btn:not(.active) {
            background: transparent;
            border-color: #dee2e6;
        }
        
        
        /* Welcome message with toggle */
        .welcome-toggle-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;

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
        }
    </style>
</head>
<body class="bg-light">
    <div id="wrapper">
        <?php $this->load->view('templates/sidebar'); ?>
        <div id="page-content-wrapper">
            <div class="container-fluid py-4">
    
                <!-- Welcome Message with Toggle Switch -->
                <div class="alert alert-info mb-4">
                    <div class="welcome-toggle-container">
                        <div>
                            <h4 class="alert-heading mb-1">Welcome, <?php echo htmlspecialchars($user_district); ?></h4>
                            <p class="mb-0">Viewing all school districts and their reports | 
                                <span class="badge <?php echo ($assessment_type == 'baseline') ? 'badge-baseline' : 'badge-endline'; ?>">
                                    <?php echo ucfirst($assessment_type); ?> Assessment
                                </span>
                            </p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="assessment-switcher">
                                <a href="<?php echo site_url('division_dashboard_controller?assessment_type=baseline'); ?>" 
                                   class="btn btn-primary btn-sm <?php echo ($assessment_type == 'baseline') ? 'active' : ''; ?>">
                                    <i class="fas fa-flag me-1"></i> Baseline

                                </a>
                                <a href="<?php echo site_url('division_dashboard_controller?assessment_type=endline'); ?>" 
                                   class="btn btn-success btn-sm <?php echo ($assessment_type == 'endline') ? 'active' : ''; ?>">
                                    <i class="fas fa-flag-checkered me-1"></i> Endline

                                </a>
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
                <div class="display-4 fw-bold"><?php echo $overall_stats['overall_completion']; ?>%</div>  <!-- HERE -->
                <div class="text-white-50">Completion Rate</div>
            </div>
        </div>
    </div>
</div>
                
                <!-- Schools/Districts Box (Initially Hidden) -->
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
                <div class="card mt-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            Division Nutritional Assessment Report
                            <span class="badge <?php echo ($assessment_type == 'baseline') ? 'badge-baseline' : 'badge-endline'; ?> assessment-badge">
                                <?php echo ucfirst($assessment_type); ?>
                            </span>
                        </h4>
                        <div class="no-print">
                            <div class="btn-group table-switcher me-2" role="group">
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
                                        <?php if ($assessment_type == 'endline'): ?>
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
                                    <?php 
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
                                    
                                    // Helper functions
                                    function gdata($data, $key, $field) {
                                        if (!isset($data[$key])) return 0;
                                        return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
                                    }
                                    
                                    function pct($num, $den) {
                                        if (!$den || $den == 0) return '0%';
                                        return round(($num / $den) * 100) . '%';
                                    }
                                    
                                    // Define BMI and HFA fields
                                    $bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
                                    $hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
                                    
                                    // Check if nutritional data exists
                                    $has_nutritional_data = isset($nutritional_data) && !empty($nutritional_data);
                                    ?>
                                    
                                    <?php foreach ($elementaryGrades as $gkey => $glabel): ?>
                                        <?php $enrol = $has_nutritional_data ? gdata($nutritional_data, $gkey, 'enrolment') : 0; ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $has_nutritional_data ? gdata($nutritional_data, $gkey, 'pupils_weighed') : 0 ?></td>

                                            <!-- BMI columns (severely_wasted, wasted, normal_bmi, overweight, obese) each with count and % -->
                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata($nutritional_data, $gkey, $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
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
                                                    $val = gdata($nutritional_data, $gkey, $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
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
                                                    $totalEnrol += gdata($nutritional_data, $gkey, 'enrolment');
                                                    $totalWeighed += gdata($nutritional_data, $gkey, 'pupils_weighed');
                                                    foreach ($grandCounts as $k => &$v) {
                                                        $v += gdata($nutritional_data, $gkey, $k);
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
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol) ?></td><?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Secondary Table (hidden by default) -->
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
                                        <?php $enrol = $has_nutritional_data ? gdata($nutritional_data, $gkey, 'enrolment') : 0; ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                                            <td class="text-center"><?= $enrol ?></td>
                                            <td class="text-center"><?= $has_nutritional_data ? gdata($nutritional_data, $gkey, 'pupils_weighed') : 0 ?></td>

                                            <?php
                                            if ($has_nutritional_data) {
                                                foreach ($bmiFields as $bf) {
                                                    $val = gdata($nutritional_data, $gkey, $bf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                                                }
                                                foreach ($hfaFields as $hf) {
                                                    $val = gdata($nutritional_data, $gkey, $hf);
                                                    echo '<td class="text-center">' . $val . '</td>';
                                                    echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
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
                                                    $totalEnrol2 += gdata($nutritional_data, $gkey, 'enrolment');
                                                    $totalWeighed2 += gdata($nutritional_data, $gkey, 'pupils_weighed');
                                                    foreach ($grandCounts2 as $k => &$v) {
                                                        $v += gdata($nutritional_data, $gkey, $k);
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
                                        <?php foreach ($bmiFields as $bf): $val = $grandCounts2[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol2) ?></td><?php endforeach; ?>
                                        <?php foreach ($hfaFields as $hf): $val = $grandCounts2[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol2) ?></td><?php endforeach; ?>
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
    
    <!-- Bootstrap 5.3.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (for easier AJAX) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let currentView = 'districts';
        let selectedDistrict = null;
        let allSchools = [];
        
        // Store all schools data from PHP
        const allSchoolsData = <?php echo json_encode($all_schools_by_district); ?>;
        
        // Assessment type switching - changed to links instead of AJAX
        $('#switchToBaseline, #switchToEndline').on('click', function(e) {
            e.preventDefault();
            const type = $(this).hasClass('btn-primary') ? 'baseline' : 'endline';
            window.location.href = '<?php echo site_url("division_dashboard_controller"); ?>?assessment_type=' + type;
        });
        
        // Table switching functionality
        const btnElem = document.getElementById('btnElementary');
        const btnSec = document.getElementById('btnSecondary');
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        const btnPrint = document.getElementById('btnPrint');

        if (btnElem && btnSec) {
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
                
                // Get current assessment type
                const assessmentType = '<?php echo ucfirst($assessment_type); ?>';
                const reportDate = new Date().toLocaleDateString();

                win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title>' +
                    '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
                    '<style>body{padding:20px;} table{width:100%; border-collapse:collapse;} th, td{border:1px solid #dee2e6;}</style>' +
                    '</head><body>');
                win.document.write('<h3>Nutritional Status Report - Division Level</h3>');
                win.document.write('<p>Assessment Type: ' + assessmentType + ' | Report Date: ' + reportDate + '</p>');
                win.document.write(tableHtml);
                win.document.write('<script>window.onload=function(){ window.print(); window.onafterprint=function(){ window.close(); } }<\/script>');
                win.document.write('</body></html>');
                win.document.close();
            });
        }
        
        // Toggle schools box
        $('#overallSummaryCard').click(function() {
            $('#schoolsBox').toggleClass('d-none');
            const icon = $(this).find('.fa-chevron-up');
            const text = $(this).find('.small');
            
            if ($('#schoolsBox').hasClass('d-none')) {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                text.html('<i class="fas fa-chevron-up me-1"></i> Click to view all districts');
            } else {
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                text.html('<i class="fas fa-chevron-down me-1"></i> Hide districts');
                // No need to load districts - they're already loaded in PHP
            }
        });
        
        $('#closeSchoolsBox').click(function() {
            $('#schoolsBox').addClass('d-none');
            $('#overallSummaryCard').find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $('#overallSummaryCard').find('.small').html('<i class="fas fa-chevron-up me-1"></i> Click to view all districts');
        });
        
        // Add click event to district cards (loaded from PHP)
        $(document).on('click', '.district-card', function() {
            const districtName = $(this).data('district');
            selectDistrict(districtName);
        });
        
        function selectDistrict(districtName) {
            selectedDistrict = districtName;
            currentView = 'schools';
            
            $('#boxTitle').text('Schools in ' + districtName);
            $('#districtsView').addClass('d-none');
            $('#schoolsView').removeClass('d-none');
            $('#districtSchoolsTitle').text('Schools in ' + districtName);
            
            // Get schools from PHP data (no AJAX needed)
            if (allSchoolsData && allSchoolsData[districtName]) {
                allSchools = allSchoolsData[districtName];
                displaySchools(allSchools);
                updateSubmissionStats(allSchools);
            } else {
                allSchools = [];
                displaySchools([]);
                updateSubmissionStats([]);
            }
        }
        
        function displaySchools(schools) {
            const list = $('#schoolsList');
            list.empty();
            
            if (schools.length === 0) {
                $('#noSchoolsMessage').removeClass('d-none');
                $('#noSearchResults').addClass('d-none');
                return;
            }
            
            $('#noSchoolsMessage').addClass('d-none');
            $('#noSearchResults').addClass('d-none');
            
            schools.forEach(function(school) {
                const statusClass = school.has_submitted ? 'submission-submitted' : 'submission-pending';
                const statusText = school.has_submitted ? 'Submitted' : 'Pending';
                const icon = school.has_submitted ? 'check' : 'circle';
                const iconColor = school.has_submitted ? 'text-success' : 'text-muted';
                
                const item = `
                    <a href="#" class="list-group-item list-group-item-action school-item" data-school="${school.name}">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="rounded-circle ${statusClass} p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="fas fa-${icon} fa-xs ${iconColor}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-medium">${school.name}</div>
                                ${school.code ? `<small class="text-muted">School ID: ${school.code}</small>` : ''}
                            </div>
                            <div>
                                <span class="badge ${statusClass}">${statusText}</span>
                            </div>
                        </div>
                    </a>
                `;
                
                list.append(item);
            });
            
            // Add click event to school items
            $('.school-item').click(function(e) {
                e.preventDefault();
                const schoolName = $(this).data('school');
                showSchoolDetails(schoolName);
            });
        }
        
        function updateSubmissionStats(schools) {
            const submitted = schools.filter(s => s.has_submitted).length;
            const total = schools.length;
            const percentage = total > 0 ? Math.round((submitted / total) * 100) : 0;
            
            $('#submissionText').text('Submission Progress:');
            $('#submissionCount').text(submitted + '/' + total + ' schools submitted');
            $('#submissionProgressBar').css('width', percentage + '%');
        }
        
        // Search functionality
        $('#schoolSearch').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            filterSchools(searchTerm);
        });
        
        $('#clearSearch').click(function() {
            $('#schoolSearch').val('');
            filterSchools('');
        });
        
        function filterSchools(searchTerm) {
            if (!searchTerm) {
                displaySchools(allSchools);
                $('#noSearchResults').addClass('d-none');
                return;
            }
            
            const filteredSchools = allSchools.filter(school => 
                school.name.toLowerCase().includes(searchTerm) ||
                (school.code && school.code.toLowerCase().includes(searchTerm))
            );
            
            if (filteredSchools.length === 0) {
                $('#schoolsList').empty();
                $('#noSchoolsMessage').addClass('d-none');
                $('#noSearchResults').removeClass('d-none');
            } else {
                displaySchools(filteredSchools);
                $('#noSearchResults').addClass('d-none');
            }
        }
        
        // Back to districts view
        $('#backToDistricts').click(function() {
            currentView = 'districts';
            selectedDistrict = null;
            allSchools = [];
            
            $('#boxTitle').text('All Districts');
            $('#schoolsView').addClass('d-none');
            $('#districtsView').removeClass('d-none');
            $('#schoolSearch').val(''); // Clear search
        });
        
        // Show school details (still uses AJAX for school details)
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
                url: '<?php echo base_url("index.php/division_dashboard_controller/get_school_details/"); ?>' + encodeURIComponent(schoolName),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
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
                error: function() {
                    $('#schoolModalBody').html(`
                        <div class="alert alert-danger">
                            Error loading school details. Please try again.
                        </div>
                    `);
                }
            });
        }
    });
    </script>
</body>
</html>