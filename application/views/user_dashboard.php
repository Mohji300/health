<?php defined('BASEPATH') OR exit('No direct script access allowed'); 

$assessment_type = isset($assessment_type) ? $assessment_type : 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$is_midline = ($assessment_type == 'midline');
$is_endline = ($assessment_type == 'endline');

$baseline_count = isset($baseline_count) ? $baseline_count : 0;
$midline_count = isset($midline_count) ? $midline_count : 0;
$endline_count = isset($endline_count) ? $endline_count : 0;

function gdata($data, $key, $field) {
    if (!isset($data[$key])) return 0;
    return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
}

function pct($num, $den) {
    if (!$den || $den == 0) return '0%';
    return round(($num / $den) * 100) . '%';
}

// Define field arrays globally
$bmiFields = ['severely_wasted', 'wasted', 'normal_bmi', 'overweight', 'obese'];
$hfaFields = ['severely_stunted', 'stunted', 'normal_hfa', 'tall', 'pupils_height'];

// Define grade levels with their sex breakdown keys
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

// Get values from controller
$school_level = isset($school_level) ? $school_level : 'all';
$display_mode = isset($display_mode) ? $display_mode : 'normal';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutritional Status Dashboard - <?php echo ucfirst($assessment_type); ?><?php echo !empty($selected_school) ? ' - ' . htmlspecialchars($selected_school) : ''; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/user_dashboard.css'); ?>">
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
                  <h1 class="h2 font-weight-bold mb-2">CONSOLIDATED DATA OF NUTRITIONAL ASSESSMENTS IN SCHOOLS</h1>
                  <p class="mb-0 opacity-8"><?php if (!empty($selected_school)) { echo 'Showing nutritional data for ' . htmlspecialchars($selected_school); } else { echo 'Comprehensive overview of student nutritional assessments and health metrics'; } ?></p>
                </div>
              </div>
            </div>
          </div>

          <?php if (!$has_data): ?>
          <div class="alert alert-warning mb-4">
              <div class="d-flex align-items-center">
                  <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                  <div>
                      <h5 class="alert-heading mb-1">No <?php echo ucfirst($assessment_type); ?> Data Available</h5>
                      <p class="mb-0">
                          No <?php echo strtolower($assessment_type); ?> assessment data has been submitted yet. 
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

          <!-- Assessment Info Card -->
          <div class="alert alert-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="alert-heading mb-1">
                  <i class="fas fa-clipboard-list me-2"></i>
                  <?php 
                    if ($is_baseline) {
                      echo 'Baseline';
                    } elseif ($is_midline) {
                      echo 'Midline';
                    } else {
                      echo 'Endline';
                    }
                  ?> Assessment Report
                  <span class="badge <?php 
                      if ($is_baseline) {
                          echo 'badge-baseline';
                      } elseif ($is_midline) {
                          echo 'badge-midline';
                      } else {
                          echo 'badge-endline';
                      }
                  ?> assessment-badge">
                      <?php echo ucfirst($assessment_type); ?>
                  </span>
                </h5>
                <p class="mb-0">
                  Showing nutritional data for 
                  <strong><?php echo ucfirst($assessment_type); ?></strong> assessments.
                  <?php if ($is_baseline): ?>
                    Baseline assessments are initial measurements taken at the beginning of the program.
                  <?php elseif ($is_midline): ?>
                    Midline assessments are intermediate measurements taken during the program to track progress.
                  <?php else: ?>
                    Endline assessments are final measurements taken at the end of the program.
                  <?php endif; ?>
                </p>
              </div>
              <div class="text-end">
                <div class="btn-group">
                  <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="fas fa-question-circle me-1"></i> Help
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Main Content Card -->
          <div class="card shadow">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">
                  Consolidated Nutritional Assessment Report
                  <span class="badge <?php 
                      if ($is_baseline) {
                          echo 'badge-baseline';
                      } elseif ($is_midline) {
                          echo 'badge-midline';
                      } else {
                          echo 'badge-endline';
                      }
                  ?> ms-2">
                      <?php echo ucfirst($assessment_type); ?>
                  </span>
                  <?php if ($school_level !== 'all'): ?>
                  <span class="badge bg-secondary ms-2">
                      <i class="fas fa-filter me-1"></i>
                      <?php 
                          if ($school_level === 'integrated_elementary') echo 'Integrated (Elementary)';
                          elseif ($school_level === 'integrated_secondary') echo 'Integrated (Secondary)';
                          elseif ($school_level === 'Stand Alone SHS') echo 'Stand Alone SHS';
                          else echo ucfirst($school_level); 
                      ?>
                  </span>
                  <?php endif; ?>
              </h6>
              <!-- assessment dropdown moved to the right-side controls -->
              <div class="no-print d-flex align-items-center">
                  <!-- School Level Filter Buttons - Only show if in normal mode -->
                  <?php if ($display_mode === 'normal'): ?>
                  <div class="btn-group me-2" role="group">
                      <button id="btnElementary" type="button" 
                              class="btn btn-outline-primary <?php echo ($school_level === 'all' || $school_level === 'elementary' || $school_level === 'integrated_elementary') ? 'active' : ''; ?>">
                          <i class="fas fa-child me-1"></i> Elementary
                          <span class="badge bg-info ms-1">K-sped</span>
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
                  <?php endif; ?>
                  
                  <!-- Integrated SubMenu - Only show for integrated mode -->
                  <?php if ($display_mode === 'integrated'): ?>
                  <div id="integratedSubMenu" class="btn-group me-2" role="group">
                      <button id="btnIntegratedElementary" type="button" 
                              class="btn btn-sm <?php echo (in_array($school_level, ['integrated', 'integrated_elementary'])) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                          <i class="fas fa-child me-1"></i> Elementary
                          <span class="badge bg-info ms-1">K-6</span>
                      </button>
                      <button id="btnIntegratedSecondary" type="button" 
                              class="btn btn-sm <?php echo ($school_level === 'integrated_secondary') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                          <i class="fas fa-graduation-cap me-1"></i> Secondary
                          <span class="badge bg-info ms-1">7-12</span>
                      </button>
                  </div>
                  <?php endif; ?>
                  
                    <div class="btn-group me-2">
                      <button class="btn btn-outline-primary dropdown-toggle" type="button" id="assessmentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-line me-1"></i>
                        <?php echo ucfirst($assessment_type); ?>
                      </button>
                      <ul class="dropdown-menu" aria-labelledby="assessmentDropdown">
                        <li><h6 class="dropdown-header">Select Assessment</h6></li>
                        <li><a class="dropdown-item <?= ($assessment_type == 'baseline') ? 'active' : '' ?>" href="#" data-type="baseline">Baseline</a></li>
                        <li><a class="dropdown-item <?= ($assessment_type == 'midline') ? 'active' : '' ?>" href="#" data-type="midline">Midline</a></li>
                        <li><a class="dropdown-item <?= ($assessment_type == 'endline') ? 'active' : '' ?>" href="#" data-type="endline">Endline</a></li>
                      </ul>
                    </div>

                    <button id="btnPrint" class="btn btn-success">
                      <i class="fas fa-print me-1"></i> Print Report
                    </button>
              </div>
          </div>

            <div class="card-body p-0">
              <div id="tableContainer" class="table-responsive p-3">
                <!-- Elementary Table with Sex Breakdown -->
                <table id="elementaryTable" class="table table-bordered table-sm table-fixed small-cell mb-0">
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
                      <?php for ($i=0;$i<10;$i++): ?>
                        <th class="text-center">Count</th>
                        <th class="text-center">%</th>
                      <?php endfor; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $grade_count = 0;
                    foreach ($elementaryGrades as $grade_name => $sex_keys): 
                      $grade_count++;
                      $rowspan = 3; // M, F, Total
                    ?>
                      <!-- Male Row -->
                      <tr class="sex-row-male">
                        <?php if ($grade_count == 1 || true): ?>
                          <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                        <?php endif; ?>
                        <td class="text-center">M</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['m'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['m'], 'pupils_weighed');
                        ?>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['m'], $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['m'], $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <!-- Female Row -->
                      <tr class="sex-row-female">
                        <td class="text-center">F</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['f'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['f'], 'pupils_weighed');
                        ?>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['f'], $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['f'], $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <!-- Total Row -->
                      <tr class="sex-row-total">
                        <td class="text-center fw-bold">Total</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['total'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['total'], 'pupils_weighed');
                        ?>
                        <td class="text-center fw-bold"><?= $enrol ?></td>
                        <td class="text-center fw-bold"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['total'], $bf);
                            echo '<td class="text-center fw-bold">' . $val . '</td>';
                            echo '<td class="text-center fw-bold">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['total'], $hf);
                            echo '<td class="text-center fw-bold">' . $val . '</td>';
                            echo '<td class="text-center fw-bold">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <!-- Add separator between grades except after the last one -->
                      <?php if ($grade_count < count($elementaryGrades)): ?>
                        <tr class="grade-separator">
                          <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                        </tr>
                      <?php endif; ?>
                      
                    <?php endforeach; ?>

                    <!-- Grand total rows -->
                    <?php
                      $totalEnrol_m = $totalWeighed_m = 0;
                      $totalEnrol_f = $totalWeighed_f = 0;
                      $totalEnrol_t = $totalWeighed_t = 0;
                      $grandCounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      $grandCounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      $grandCounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      foreach ($elementaryGrades as $grade_name => $sex_keys) {
                        $totalEnrol_m += gdata($nutritionalData, $sex_keys['m'], 'enrolment');
                        $totalWeighed_m += gdata($nutritionalData, $sex_keys['m'], 'pupils_weighed');

                        $totalEnrol_f += gdata($nutritionalData, $sex_keys['f'], 'enrolment');
                        $totalWeighed_f += gdata($nutritionalData, $sex_keys['f'], 'pupils_weighed');

                        $totalEnrol_t += gdata($nutritionalData, $sex_keys['total'], 'enrolment');
                        $totalWeighed_t += gdata($nutritionalData, $sex_keys['total'], 'pupils_weighed');

                        foreach ($grandCounts_m as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['m'], $k); } unset($v);
                        foreach ($grandCounts_f as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['f'], $k); } unset($v);
                        foreach ($grandCounts_t as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['total'], $k); } unset($v);
                      }
                    ?>

                    <tr class="table-primary">
                      <td class="fw-bold text-center align-middle" rowspan="3">Grand Total (ELEMENTARY)</td>
                      <td class="text-center">M</td>
                      <td class="text-center fw-bold"><?= $totalEnrol_m ?></td>
                      <td class="text-center fw-bold"><?= $totalWeighed_m ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $grandCounts_m[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol_m) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $grandCounts_m[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol_m) ?></td><?php endforeach; ?>
                    </tr>

                    <tr class="table-primary">
                      <td class="text-center">F</td>
                      <td class="text-center fw-bold"><?= $totalEnrol_f ?></td>
                      <td class="text-center fw-bold"><?= $totalWeighed_f ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $grandCounts_f[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol_f) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $grandCounts_f[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol_f) ?></td><?php endforeach; ?>
                    </tr>

                    <tr class="table-primary">
                      <td class="text-center fw-bold">Total</td>
                      <td class="text-center fw-bold"><?= $totalEnrol_t ?></td>
                      <td class="text-center fw-bold"><?= $totalWeighed_t ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $grandCounts_t[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol_t) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $grandCounts_t[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $totalEnrol_t) ?></td><?php endforeach; ?>
                    </tr>
                  </tbody>
                </table>

                <!-- Secondary Table with Sex Breakdown -->
                <table id="secondaryTable" class="table table-bordered table-sm table-fixed small-cell mb-0 mt-4">
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
                      <tr class="sex-row-male">
                        <?php if ($grade_count == 1 || true): ?>
                          <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                        <?php endif; ?>
                        <td class="text-center">M</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['m'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['m'], 'pupils_weighed');
                        ?>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['m'], $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['m'], $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <tr class="sex-row-female">
                        <td class="text-center">F</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['f'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['f'], 'pupils_weighed');
                        ?>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['f'], $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['f'], $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <tr class="sex-row-total">
                        <td class="text-center fw-bold">Total</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['total'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['total'], 'pupils_weighed');
                        ?>
                        <td class="text-center fw-bold"><?= $enrol ?></td>
                        <td class="text-center fw-bold"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['total'], $bf);
                            echo '<td class="text-center fw-bold">' . $val . '</td>';
                            echo '<td class="text-center fw-bold">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['total'], $hf);
                            echo '<td class="text-center fw-bold">' . $val . '</td>';
                            echo '<td class="text-center fw-bold">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <?php if ($grade_count < count($secondaryGrades)): ?>
                        <tr class="grade-separator">
                          <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                        </tr>
                      <?php endif; ?>
                      
                    <?php endforeach; ?>
                    
                    <?php
                      $tEnrol_m = $tWeighed_m = 0;
                      $tEnrol_f = $tWeighed_f = 0;
                      $tEnrol_t = $tWeighed_t = 0;
                      $gcounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      $gcounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      $gcounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      foreach ($secondaryGrades as $grade_name => $sex_keys) {
                        $tEnrol_m += gdata($nutritionalData, $sex_keys['m'], 'enrolment');
                        $tWeighed_m += gdata($nutritionalData, $sex_keys['m'], 'pupils_weighed');
                        $tEnrol_f += gdata($nutritionalData, $sex_keys['f'], 'enrolment');
                        $tWeighed_f += gdata($nutritionalData, $sex_keys['f'], 'pupils_weighed');
                        $tEnrol_t += gdata($nutritionalData, $sex_keys['total'], 'enrolment');
                        $tWeighed_t += gdata($nutritionalData, $sex_keys['total'], 'pupils_weighed');
                        foreach ($gcounts_m as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['m'], $k); } unset($v);
                        foreach ($gcounts_f as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['f'], $k); } unset($v);
                        foreach ($gcounts_t as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['total'], $k); } unset($v);
                      }
                    ?>

                    <tr class="table-primary">
                      <td class="fw-bold text-center align-middle" rowspan="3">Grand Total (SECONDARY)</td>
                      <td class="text-center">M</td>
                      <td class="text-center fw-bold"><?= $tEnrol_m ?></td>
                      <td class="text-center fw-bold"><?= $tWeighed_m ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $gcounts_m[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $tEnrol_m) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $gcounts_m[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $tEnrol_m) ?></td><?php endforeach; ?>
                    </tr>

                    <tr class="table-primary">
                      <td class="text-center">F</td>
                      <td class="text-center fw-bold"><?= $tEnrol_f ?></td>
                      <td class="text-center fw-bold"><?= $tWeighed_f ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $gcounts_f[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $tEnrol_f) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $gcounts_f[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $tEnrol_f) ?></td><?php endforeach; ?>
                    </tr>

                    <tr class="table-primary">
                      <td class="text-center fw-bold">Total</td>
                      <td class="text-center fw-bold"><?= $tEnrol_t ?></td>
                      <td class="text-center fw-bold"><?= $tWeighed_t ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $gcounts_t[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $tEnrol_t) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $gcounts_t[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $tEnrol_t) ?></td><?php endforeach; ?>
                    </tr>
                  </tbody>
                </table>

              </div>
            </div>
                            <!-- Stand Alone SHS Table with Sex Breakdown -->
                <table id="shsTable" class="table table-bordered table-sm table-fixed small-cell mb-0 mt-4">
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
                          $enrol = gdata($nutritionalData, $sex_keys['m'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['m'], 'pupils_weighed');
                        ?>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['m'], $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['m'], $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <tr class="sex-row-female">
                        <td class="text-center">F</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['f'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['f'], 'pupils_weighed');
                        ?>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['f'], $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['f'], $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                      
                      <tr class="sex-row-total">
                        <td class="text-center fw-bold">Total</td>
                        <?php 
                          $enrol = gdata($nutritionalData, $sex_keys['total'], 'enrolment');
                          $weighed = gdata($nutritionalData, $sex_keys['total'], 'pupils_weighed');
                        ?>
                        <td class="text-center fw-bold"><?= $enrol ?></td>
                        <td class="text-center fw-bold"><?= $weighed ?></td>
                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $sex_keys['total'], $bf);
                            echo '<td class="text-center fw-bold">' . $val . '</td>';
                            echo '<td class="text-center fw-bold">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $sex_keys['total'], $hf);
                            echo '<td class="text-center fw-bold">' . $val . '</td>';
                            echo '<td class="text-center fw-bold">' . pct($val, $enrol) . '</td>';
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
                      $sEnrol_m = $sWeighed_m = 0;
                      $sEnrol_f = $sWeighed_f = 0;
                      $sEnrol_t = $sWeighed_t = 0;
                      $scounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      $scounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      $scounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                      foreach ($shsGrades as $grade_name => $sex_keys) {
                        $sEnrol_m += gdata($nutritionalData, $sex_keys['m'], 'enrolment');
                        $sWeighed_m += gdata($nutritionalData, $sex_keys['m'], 'pupils_weighed');
                        $sEnrol_f += gdata($nutritionalData, $sex_keys['f'], 'enrolment');
                        $sWeighed_f += gdata($nutritionalData, $sex_keys['f'], 'pupils_weighed');
                        $sEnrol_t += gdata($nutritionalData, $sex_keys['total'], 'enrolment');
                        $sWeighed_t += gdata($nutritionalData, $sex_keys['total'], 'pupils_weighed');
                        foreach ($scounts_m as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['m'], $k); } unset($v);
                        foreach ($scounts_f as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['f'], $k); } unset($v);
                        foreach ($scounts_t as $k => &$v) { $v += gdata($nutritionalData, $sex_keys['total'], $k); } unset($v);
                      }
                    ?>

                    <tr class="table-primary">
                      <td class="fw-bold text-center align-middle" rowspan="3">Grand Total (STAND ALONE SHS)</td>
                      <td class="text-center">M</td>
                      <td class="text-center fw-bold"><?= $sEnrol_m ?></td>
                      <td class="text-center fw-bold"><?= $sWeighed_m ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $scounts_m[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $sEnrol_m) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $scounts_m[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $sEnrol_m) ?></td><?php endforeach; ?>
                    </tr>

                    <tr class="table-primary">
                      <td class="text-center">F</td>
                      <td class="text-center fw-bold"><?= $sEnrol_f ?></td>
                      <td class="text-center fw-bold"><?= $sWeighed_f ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $scounts_f[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $sEnrol_f) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $scounts_f[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $sEnrol_f) ?></td><?php endforeach; ?>
                    </tr>

                    <tr class="table-primary">
                      <td class="text-center fw-bold">Total</td>
                      <td class="text-center fw-bold"><?= $sEnrol_t ?></td>
                      <td class="text-center fw-bold"><?= $sWeighed_t ?></td>
                      <?php foreach ($bmiFields as $bf): $val = $scounts_t[$bf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $sEnrol_t) ?></td><?php endforeach; ?>
                      <?php foreach ($hfaFields as $hf): $val = $scounts_t[$hf]; ?><td class="text-center fw-bold"><?= $val ?></td><td class="text-center fw-bold"><?= pct($val, $sEnrol_t) ?></td><?php endforeach; ?>
                    </tr>
                  </tbody>
                </table>
          </div>
        </div>
      </div>
    </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    window.user_dashboard_controllerConfig = window.user_dashboard_controllerConfig || {};
    window.user_dashboard_controllerConfig.urls = {
      set_school_level: '<?= site_url("users/set_school_level"); ?>',
      set_assessment_type: '<?= site_url("users/set_assessment_type"); ?>',
      base: '<?= site_url("users"); ?>'
    };
    window.user_dashboard_controllerConfig.school_level = '<?= isset($school_level) ? addslashes($school_level) : ""; ?>';
    window.user_dashboard_controllerConfig.user_actual_school_level = '<?= isset($user_actual_school_level) ? addslashes($user_actual_school_level) : ""; ?>';
    window.user_dashboard_controllerConfig.assessment_type_display = '<?= ucfirst($assessment_type); ?>';
    window.user_dashboard_controllerConfig.display_mode = '<?= isset($display_mode) ? $display_mode : "normal"; ?>';
    // Provide school and user identifiers for client scripts (used by print header)
    window.user_dashboard_controllerConfig.school_id = '<?= addslashes($this->session->userdata('school_id') ?? '') ?>';
    window.user_dashboard_controllerConfig.school_name = '<?= addslashes(isset($selected_school) ? $selected_school : ($this->session->userdata('school_name') ?? '')) ?>';
    window.user_dashboard_controllerConfig.user_name = '<?= addslashes($this->session->userdata('name') ?? ($auth_user['name'] ?? '')) ?>';
    console.log('Config loaded:', window.user_dashboard_controllerConfig);
    </script>
    <script src="<?= base_url(ASSETS_PATH . '/js/user_dashboard.js'); ?>"></script>
  </body>
</html>