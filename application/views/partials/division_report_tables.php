<?php
/**
 * Partial: Division report tables
 *
 * Rendered by:
 *   Division_dashboard_controller::get_dashboard_data()
 *
 * Expected vars:
 *   $nutritional_data (array)
 *   $school_level     (string)
 *   $assessment_type  (string)
 *   $has_data         (bool)
 *
 * IMPORTANT:
 *   - Do NOT wrap in <div id="tableContainer"> or <div id="tableContent">.
 *     The caller owns those wrappers.
 *   - Helper functions are guarded with function_exists() so this partial
 *     can be safely included more than once in a single request.
 */

$has_nutritional_data = !empty($nutritional_data);
?>

<?php if (!function_exists('gdata_division')): ?>
    <?php
    function gdata_division($data, $key, $field) {
        if (!isset($data[$key])) return 0;
        return isset($data[$key][$field]) ? (int)$data[$key][$field] : 0;
    }
    ?>
<?php endif; ?>

<?php if (!function_exists('pct_division')): ?>
    <?php
    function pct_division($num, $den) {
        if (!$den || $den == 0) return '0%';
        return round(($num / $den) * 100) . '%';
    }
    ?>
<?php endif; ?>

<?php if (!function_exists('sum_group_data')): ?>
    <?php
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
    ?>
<?php endif; ?>

<?php if (empty($has_data)): ?>
    <div class="alert alert-warning m-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">No <?= ucfirst($assessment_type); ?> Data Available</h5>
                <p class="mb-0">
                    No <?= strtolower($assessment_type); ?> assessment data has been submitted yet.
                    The tables below show all zeros.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$elementaryGrades = [
    'Kinder'  => ['m' => 'Kinder_m',  'f' => 'Kinder_f',  'total' => 'Kinder_total'],
    'Grade 1' => ['m' => 'Grade 1_m', 'f' => 'Grade 1_f', 'total' => 'Grade 1_total'],
    'Grade 2' => ['m' => 'Grade 2_m', 'f' => 'Grade 2_f', 'total' => 'Grade 2_total'],
    'Grade 3' => ['m' => 'Grade 3_m', 'f' => 'Grade 3_f', 'total' => 'Grade 3_total'],
    'Grade 4' => ['m' => 'Grade 4_m', 'f' => 'Grade 4_f', 'total' => 'Grade 4_total'],
    'Grade 5' => ['m' => 'Grade 5_m', 'f' => 'Grade 5_f', 'total' => 'Grade 5_total'],
    'Grade 6' => ['m' => 'Grade 6_m', 'f' => 'Grade 6_f', 'total' => 'Grade 6_total'],
    'SPED'    => ['m' => 'SPED_m',    'f' => 'SPED_f',    'total' => 'SPED_total'],
];

$secondaryGrades = [
    'Grade 7'  => ['m' => 'Grade 7_m',  'f' => 'Grade 7_f',  'total' => 'Grade 7_total'],
    'Grade 8'  => ['m' => 'Grade 8_m',  'f' => 'Grade 8_f',  'total' => 'Grade 8_total'],
    'Grade 9'  => ['m' => 'Grade 9_m',  'f' => 'Grade 9_f',  'total' => 'Grade 9_total'],
    'Grade 10' => ['m' => 'Grade 10_m', 'f' => 'Grade 10_f', 'total' => 'Grade 10_total'],
    'Grade 11' => ['m' => 'Grade 11_m', 'f' => 'Grade 11_f', 'total' => 'Grade 11_total'],
    'Grade 12' => ['m' => 'Grade 12_m', 'f' => 'Grade 12_f', 'total' => 'Grade 12_total'],
];

$shsGrades = [
    'Grade 11' => ['m' => 'Grade 11_m', 'f' => 'Grade 11_f', 'total' => 'Grade 11_total'],
    'Grade 12' => ['m' => 'Grade 12_m', 'f' => 'Grade 12_f', 'total' => 'Grade 12_total'],
];

$bmiFields = ['severely_wasted','wasted','normal_bmi','overweight','obese'];
$hfaFields = ['severely_stunted','stunted','normal_hfa','tall','pupils_height'];
?>

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
            $rowspan = 3;
        ?>
            <tr class="sex-row-male">
                <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                <td class="text-center">M</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'enrolment')      : 0;
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

            <tr class="sex-row-female">
                <td class="text-center">F</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'enrolment')      : 0;
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

            <tr class="sex-row-total">
                <td class="text-center fw-bold">Total</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'enrolment')      : 0;
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

            <?php if ($grade_count < count($elementaryGrades)): ?>
                <tr class="grade-separator">
                    <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                </tr>
            <?php endif; ?>

        <?php endforeach; ?>

        <?php
        $totalEnrol_m   = sum_group_data($nutritional_data, $elementaryGrades, 'm', 'enrolment');
        $totalWeighed_m = sum_group_data($nutritional_data, $elementaryGrades, 'm', 'pupils_weighed');
        $totalEnrol_f   = sum_group_data($nutritional_data, $elementaryGrades, 'f', 'enrolment');
        $totalWeighed_f = sum_group_data($nutritional_data, $elementaryGrades, 'f', 'pupils_weighed');
        $totalEnrol_t   = sum_group_data($nutritional_data, $elementaryGrades, 'total', 'enrolment');
        $totalWeighed_t = sum_group_data($nutritional_data, $elementaryGrades, 'total', 'pupils_weighed');

        $grandCounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
        $grandCounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
        $grandCounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
        foreach ($grandCounts_m as $field => $v) {
            $grandCounts_m[$field] = sum_group_data($nutritional_data, $elementaryGrades, 'm', $field);
        }
        foreach ($grandCounts_f as $field => $v) {
            $grandCounts_f[$field] = sum_group_data($nutritional_data, $elementaryGrades, 'f', $field);
        }
        foreach ($grandCounts_t as $field => $v) {
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
                <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                <td class="text-center">M</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'enrolment')      : 0;
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
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'enrolment')      : 0;
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
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'enrolment')      : 0;
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
            $shsEnrol_m   = sum_group_data($nutritional_data, $shsGrades, 'm', 'enrolment');
            $shsWeighed_m = sum_group_data($nutritional_data, $shsGrades, 'm', 'pupils_weighed');
            $shsEnrol_f   = sum_group_data($nutritional_data, $shsGrades, 'f', 'enrolment');
            $shsWeighed_f = sum_group_data($nutritional_data, $shsGrades, 'f', 'pupils_weighed');
            $shsEnrol_t   = sum_group_data($nutritional_data, $shsGrades, 'total', 'enrolment');
            $shsWeighed_t = sum_group_data($nutritional_data, $shsGrades, 'total', 'pupils_weighed');

            $shsCounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
            $shsCounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
            $shsCounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
            foreach ($shsCounts_m as $field => $v) {
                $shsCounts_m[$field] = sum_group_data($nutritional_data, $shsGrades, 'm', $field);
            }
            foreach ($shsCounts_f as $field => $v) {
                $shsCounts_f[$field] = sum_group_data($nutritional_data, $shsGrades, 'f', $field);
            }
            foreach ($shsCounts_t as $field => $v) {
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
            <tr class="sex-row-male">
                <td class="fw-bold text-center align-middle" rowspan="<?= $rowspan ?>"><?= htmlspecialchars($grade_name) ?></td>
                <td class="text-center">M</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['m'], 'enrolment')      : 0;
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

            <tr class="sex-row-female">
                <td class="text-center">F</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['f'], 'enrolment')      : 0;
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

            <tr class="sex-row-total">
                <td class="text-center fw-bold">Total</td>
                <?php
                    $enrol   = $has_nutritional_data ? gdata_division($nutritional_data, $sex_keys['total'], 'enrolment')      : 0;
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

            <?php if ($grade_count < count($secondaryGrades)): ?>
                <tr class="grade-separator">
                    <td colspan="100" style="padding: 0;"><div style="border-top: 2px solid #dee2e6;"></div></td>
                </tr>
            <?php endif; ?>

        <?php endforeach; ?>

        <?php
        $sEnrol_m   = sum_group_data($nutritional_data, $secondaryGrades, 'm', 'enrolment');
        $sWeighed_m = sum_group_data($nutritional_data, $secondaryGrades, 'm', 'pupils_weighed');
        $sEnrol_f   = sum_group_data($nutritional_data, $secondaryGrades, 'f', 'enrolment');
        $sWeighed_f = sum_group_data($nutritional_data, $secondaryGrades, 'f', 'pupils_weighed');
        $sEnrol_t   = sum_group_data($nutritional_data, $secondaryGrades, 'total', 'enrolment');
        $sWeighed_t = sum_group_data($nutritional_data, $secondaryGrades, 'total', 'pupils_weighed');

        $scounts_m = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
        $scounts_f = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
        $scounts_t = array_fill_keys(array_merge($bmiFields, $hfaFields), 0);
        foreach ($scounts_m as $field => $v) {
            $scounts_m[$field] = sum_group_data($nutritional_data, $secondaryGrades, 'm', $field);
        }
        foreach ($scounts_f as $field => $v) {
            $scounts_f[$field] = sum_group_data($nutritional_data, $secondaryGrades, 'f', $field);
        }
        foreach ($scounts_t as $field => $v) {
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