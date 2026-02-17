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
    <link rel="stylesheet" href="<?= base_url('assets/css/user_dashboard.css'); ?>">
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
                  <h1 class="h2 font-weight-bold mb-2">Nutritional Status Dashboard</h1>
                  <p class="mb-0 opacity-8"><?php if (!empty($selected_school)) { echo 'Showing nutritional data for ' . htmlspecialchars($selected_school); } else { echo 'Comprehensive overview of student nutritional assessments and health metrics'; } ?></p>
                </div>
                <div class="d-flex align-items-center">
                  <div class="assessment-switcher">
                      <button class="btn btn-primary btn-sm <?php echo $is_baseline ? 'active' : ''; ?>" 
                              id="switchToBaseline">
                          <i class="fas fa-flag me-1"></i> Baseline
                      </button>
                      <button class="btn btn-info btn-sm <?php echo $is_midline ? 'active' : ''; ?>" 
                              id="switchToMidline">
                          <i class="fas fa-flag me-1"></i> Midline
                      </button>
                      <button class="btn btn-success btn-sm <?php echo $is_endline ? 'active' : ''; ?>" 
                              id="switchToEndline">
                          <i class="fas fa-flag-checkered me-1"></i> Endline
                      </button>
                  </div>
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
                          else echo ucfirst($school_level); 
                      ?>
                  </span>
                  <?php endif; ?>
              </h6>
              <div class="no-print">
                  <!-- School Level Filter Buttons -->
                  <div class="btn-group me-2" role="group">
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
                      <th colspan="2" class="text-center th-red">Severely Wasted</th>
                      <th colspan="2" class="text-center th-orange">Wasted</th>
                      <th colspan="2" class="text-center th-green">Normal BMI</th>
                      <th colspan="2" class="text-center th-orange">Overweight</th>
                      <th colspan="2" class="text-center th-red">Obese</th>

                      <th colspan="2" class="text-center th-red">Severely Stunted</th>
                      <th colspan="2" class="text-center th-orange">Stunted</th>
                      <th colspan="2" class="text-center th-green">Normal HFA</th>
                      <th colspan="2" class="text-center th-green">Tall</th>
                      <th colspan="2" class="text-center ">Pupils Height</th>
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
                      <?php $enrol = gdata($nutritionalData, $gkey, 'enrolment'); ?>
                      <tr>
                        <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= gdata($nutritionalData, $gkey, 'pupils_weighed') ?></td>

                        <!-- BMI columns (severely_wasted, wasted, normal_bmi, overweight, obese) each with count and % -->
                        <?php
                          $bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $gkey, $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>

                        <!-- HFA columns (severely_stunted, stunted, normal_hfa, tall, pupils_height) -->
                        <?php
                          $hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $gkey, $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                    <?php endforeach; ?>

                    <!-- Grand total row - sum only the _total rows for elementary -->
                    <tr class="table-primary">
                      <td class="fw-bold">Grand Total (Elementary)</td>
                      <?php
                        $totalEnrol = 0; $totalWeighed = 0;
                        $grandCounts = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                        foreach ($elementaryGrades as $gkey => $glabel) {
                          if (substr($gkey, -6) === '_total') {
                            $totalEnrol += gdata($nutritionalData, $gkey, 'enrolment');
                            $totalWeighed += gdata($nutritionalData, $gkey, 'pupils_weighed');
                            foreach ($grandCounts as $k => &$v) {
                              $v += gdata($nutritionalData, $gkey, $k);
                            }
                            unset($v);
                          }
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
                      <?php $enrol = gdata($nutritionalData, $gkey, 'enrolment'); ?>
                      <tr>
                        <td class="fw-bold"><?= htmlspecialchars($glabel) ?></td>
                        <td class="text-center"><?= $enrol ?></td>
                        <td class="text-center"><?= gdata($nutritionalData, $gkey, 'pupils_weighed') ?></td>

                        <?php
                          foreach ($bmiFields as $bf) {
                            $val = gdata($nutritionalData, $gkey, $bf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                          foreach ($hfaFields as $hf) {
                            $val = gdata($nutritionalData, $gkey, $hf);
                            echo '<td class="text-center">' . $val . '</td>';
                            echo '<td class="text-center">' . pct($val, $enrol) . '</td>';
                          }
                        ?>
                      </tr>
                    <?php endforeach; ?>

                    <!-- Grand total for Secondary -->
                    <tr class="table-primary">
                      <td class="fw-bold">Grand Total (Secondary)</td>
                      <?php
                        $totalEnrol2 = 0; $totalWeighed2 = 0;
                        $grandCounts2 = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
                        foreach ($secondaryGrades as $gkey => $glabel) {
                          if (substr($gkey, -6) === '_total') {
                            $totalEnrol2 += gdata($nutritionalData, $gkey, 'enrolment');
                            $totalWeighed2 += gdata($nutritionalData, $gkey, 'pupils_weighed');
                            foreach ($grandCounts2 as $k => &$v) {
                              $v += gdata($nutritionalData, $gkey, $k);
                            }
                            unset($v);
                          }
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
  </div>
</div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    window.UserDashboardConfig = {
      urls: {
        set_assessment_type: '<?= site_url("userdashboard/set_assessment_type"); ?>',
        set_school_level: '<?= site_url("userdashboard/set_school_level"); ?>',
        base: '<?= site_url("userdashboard"); ?>'
      },
      school_level: '<?= isset($school_level) ? $school_level : ""; ?>',
      assessment_type_display: '<?= ucfirst($assessment_type); ?>'
    };
    </script>
    <script src="<?= base_url('assets/js/user_dashboard.js'); ?>"></script>
  </body>
</html>