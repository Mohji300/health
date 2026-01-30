<?php defined('BASEPATH') OR exit('No direct script access allowed'); 

// Get current assessment type from session or default to baseline
$assessment_type = isset($assessment_type) ? $assessment_type : 'baseline';
$is_baseline = ($assessment_type == 'baseline');
$next_type = $is_baseline ? 'endline' : 'baseline';

// Get counts
$baseline_count = isset($baseline_count) ? $baseline_count : 0;
$endline_count = isset($endline_count) ? $endline_count : 0;

// Helper functions and variable definitions must be at the TOP
function gdata($data, $key, $field) {
    if (!isset($data[$key])) return 0;
    return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
}

function pct($num, $den) {
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
    <style>
      #wrapper { display: flex; width: 100%; }
      #sidebar-wrapper { min-width: 220px; max-width: 260px; background: #f8f9fa; border-right: 1px solid #e3e6ea; }
      #page-content-wrapper { flex: 1 1 auto; padding: 20px; }
      @media (max-width: 767px) { #sidebar-wrapper { display: none; } }

      .card { border: none; border-radius: 0.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
      .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
      .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
      .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
      .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
      
      /* Enhanced table styling to match reports */
      .table th { border-top: 1px solid #e3e6f0; font-weight: 600; background-color: #f8f9fc; }
      .table-bordered th, .table-bordered td { border: 1px solid #000000; }
      
      /* Statistics card borders */
      .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
      .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
      .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
      .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
      .text-gray-800 { color: #5a5c69 !important; }
      .text-gray-300 { color: #dddfeb !important; }
      
      /* Assessment switcher styling */
      .assessment-switcher {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 50px;
        padding: 2px;
        display: inline-flex;
        align-items: center;
        margin-left: 20px;
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

          <!-- Header Card -->
          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h1 class="h2 font-weight-bold mb-2">Nutritional Status Dashboard<?php echo !empty($selected_school) ? ' — ' . htmlspecialchars($selected_school) : ''; ?></h1>
                  <p class="mb-0 opacity-8"><?php if (!empty($selected_school)) { echo 'Showing nutritional data for ' . htmlspecialchars($selected_school); } else { echo 'Comprehensive overview of student nutritional assessments and health metrics'; } ?></p>
                </div>
                <div class="d-flex align-items-center">
                  <div class="assessment-switcher">
                    <button class="btn btn-primary btn-sm <?php echo $is_baseline ? 'active' : ''; ?>" 
                            id="switchToBaseline">
                      <i class="fas fa-flag me-1"></i> Baseline
                    </button>
                    <button class="btn btn-success btn-sm <?php echo !$is_baseline ? 'active' : ''; ?>" 
                            id="switchToEndline">
                      <i class="fas fa-flag-checkered me-1"></i> Endline
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Add this in your view, after the Assessment Info Card -->
<?php if (!$has_data): ?>
<div class="alert alert-warning mb-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div>
            <h5 class="alert-heading mb-1">No <?php echo ucfirst($assessment_type); ?> Data Available</h5>
            <p class="mb-0">
                No <?php echo strtolower($assessment_type); ?> assessment data has been submitted yet. 
                The table below shows all zeros. 
                <?php if ($assessment_type == 'endline'): ?>
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
                  <?php echo $is_baseline ? 'Baseline' : 'Endline'; ?> Assessment Report
                  <span class="badge <?php echo $is_baseline ? 'badge-baseline' : 'badge-endline'; ?> assessment-badge">
                    <?php echo ucfirst($assessment_type); ?>
                  </span>
                </h5>
                <p class="mb-0">
                  Showing nutritional data for 
                  <strong><?php echo $is_baseline ? 'baseline' : 'endline'; ?></strong> assessments.
                  <?php if ($is_baseline): ?>
                    Baseline assessments are initial measurements taken at the beginning of the program.
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
                <span class="badge <?php echo $is_baseline ? 'badge-baseline' : 'badge-endline'; ?> ms-2">
                  <?php echo ucfirst($assessment_type); ?>
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

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Assessment Types Help</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <h6><span class="badge badge-baseline me-2">Baseline</span> Assessment</h6>
            <p>Initial measurements taken at the beginning of the School-Based Feeding Program (SBFP). 
            Used as a reference point to measure progress.</p>
            
            <h6><span class="badge badge-endline me-2">Endline</span> Assessment</h6>
            <p>Final measurements taken at the end of the SBFP. Used to evaluate the program's 
            effectiveness by comparing with baseline data.</p>
            
            <div class="alert alert-info mt-3">
              <i class="fas fa-lightbulb me-2"></i>
              <strong>Tip:</strong> Switch between baseline and endline views to compare nutritional 
              status changes over time.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(document).ready(function() {
        // Switch between Baseline and Endline views
        $('#switchToBaseline').click(function() {
          switchAssessmentType('baseline');
        });
        
        $('#switchToEndline').click(function() {
          switchAssessmentType('endline');
        });
        
        function switchAssessmentType(type) {
          // Show loading state
          var activeBtn = type === 'baseline' ? $('#switchToBaseline') : $('#switchToEndline');
          var inactiveBtn = type === 'baseline' ? $('#switchToEndline') : $('#switchToBaseline');
          
          var originalHtml = activeBtn.html();
          activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
          activeBtn.prop('disabled', true);
          inactiveBtn.prop('disabled', true);
          
          $.ajax({
            url: '<?php echo site_url("userdashboard/set_assessment_type"); ?>',
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
              console.log('Switch response:', response);
              if (response.success) {
                // Redirect back to the dashboard; preserve selected school when present
                window.location.href = '<?php echo site_url("userdashboard") . (!empty($selected_school) ? "?school_name=" . urlencode($selected_school) : ""); ?>';
              } else {
                alert('Error: ' + response.message);
                activeBtn.html(originalHtml);
                activeBtn.prop('disabled', false);
                inactiveBtn.prop('disabled', false);
              }
            },
            error: function(xhr, status, error) {
              console.error('Switch error:', error);
              alert('Error switching assessment type. Please try again.');
              activeBtn.html(originalHtml);
              activeBtn.prop('disabled', false);
              inactiveBtn.prop('disabled', false);
            }
          });
        }
        
        // Table switching
        const btnElem = document.getElementById('btnElementary');
        const btnSec = document.getElementById('btnSecondary');
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        const btnPrint = document.getElementById('btnPrint');

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

        btnPrint.addEventListener('click', () => {
          // Open a new window and print only the visible table
          const win = window.open('', '_blank');
          const isElemVisible = !elemTable.classList.contains('d-none');
          const tableHtml = (isElemVisible ? elemTable : secTable).outerHTML;
          
          // Get current assessment type
          const assessmentType = '<?php echo ucfirst($assessment_type); ?>';
          const reportDate = new Date().toLocaleDateString();

          const printCss = '<style>' +
            '@page{size:A4 landscape;margin:8mm;} body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:4px;color:#000;font-size:8px;line-height:1.2;} ' +
            'table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8px;margin:0;} th,td{border:0.5px solid #dee2e6;padding:2px;word-wrap:break-word;line-height:1.1;} ' +
            '.no-print{display:none!important;} h3{font-size:10px;margin:0 0 2px 0;font-weight:bold;} p{font-size:7px;margin:0 0 4px 0;} </style>';

          win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title>' +
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
            printCss +
            '</head><body>');
          win.document.write('<h3>Nutritional Status Report - ' + assessmentType + ' Assessment</h3>');
          win.document.write('<p>Report Date: ' + reportDate + '</p>');
          win.document.write(tableHtml);
          win.document.write('<script>window.onload=function(){ setTimeout(function(){ window.print(); window.onafterprint=function(){ window.close(); } },200); }<\/script>');
          win.document.write('</body></html>');
          win.document.close();
        });
      });
    </script>
  </body>
</html>