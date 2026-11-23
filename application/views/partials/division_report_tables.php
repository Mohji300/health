<?php
/**
 * Partial: Division report table.
 *
 * Rendered by Division_dashboard_controller::get_dashboard_data().
 */

$has_nutritional_data = !empty($nutritional_data);

if (!function_exists('gdata_division')) {
    function gdata_division($data, $key, $field) {
        return isset($data[$key][$field]) ? (int) $data[$key][$field] : 0;
    }
}

if (!function_exists('pct_division')) {
    function pct_division($num, $den) {
        return $den ? round(($num / $den) * 100) . '%' : '0%';
    }
}

if (!function_exists('sum_group_data')) {
    function sum_group_data($data, $groups, $sex_key, $field) {
        $total = 0;
        foreach ($groups as $keys) {
            if (isset($keys[$sex_key])) {
                $total += gdata_division($data, $keys[$sex_key], $field);
            }
        }
        return $total;
    }
}

$grades = [
    'Kinder'   => ['m' => 'Kinder_m',   'f' => 'Kinder_f',   'total' => 'Kinder_total'],
    'Grade 1'  => ['m' => 'Grade 1_m',  'f' => 'Grade 1_f',  'total' => 'Grade 1_total'],
    'Grade 2'  => ['m' => 'Grade 2_m',  'f' => 'Grade 2_f',  'total' => 'Grade 2_total'],
    'Grade 3'  => ['m' => 'Grade 3_m',  'f' => 'Grade 3_f',  'total' => 'Grade 3_total'],
    'Grade 4'  => ['m' => 'Grade 4_m',  'f' => 'Grade 4_f',  'total' => 'Grade 4_total'],
    'Grade 5'  => ['m' => 'Grade 5_m',  'f' => 'Grade 5_f',  'total' => 'Grade 5_total'],
    'Grade 6'  => ['m' => 'Grade 6_m',  'f' => 'Grade 6_f',  'total' => 'Grade 6_total'],
    'SPED'     => ['m' => 'SPED_m',     'f' => 'SPED_f',     'total' => 'SPED_total'],
    'Grade 7'  => ['m' => 'Grade 7_m',  'f' => 'Grade 7_f',  'total' => 'Grade 7_total'],
    'Grade 8'  => ['m' => 'Grade 8_m',  'f' => 'Grade 8_f',  'total' => 'Grade 8_total'],
    'Grade 9'  => ['m' => 'Grade 9_m',  'f' => 'Grade 9_f',  'total' => 'Grade 9_total'],
    'Grade 10' => ['m' => 'Grade 10_m', 'f' => 'Grade 10_f', 'total' => 'Grade 10_total'],
    'Grade 11' => ['m' => 'Grade 11_m', 'f' => 'Grade 11_f', 'total' => 'Grade 11_total'],
    'Grade 12' => ['m' => 'Grade 12_m', 'f' => 'Grade 12_f', 'total' => 'Grade 12_total']
];

$bmiFields = ['severely_wasted', 'wasted', 'normal_bmi', 'overweight', 'obese'];
$hfaFields = ['severely_stunted', 'stunted', 'normal_hfa', 'tall', 'pupils_height'];
$reportFields = array_merge($bmiFields, $hfaFields);
$sexRows = [
    ['key' => 'm', 'label' => 'M', 'class' => 'sex-row-male'],
    ['key' => 'f', 'label' => 'F', 'class' => 'sex-row-female'],
    ['key' => 'total', 'label' => 'Total', 'class' => 'sex-row-total']
];
?>

<?php if (!$has_nutritional_data): ?>
    <div class="alert alert-warning m-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">No <?= ucfirst($assessment_type); ?> Data Available</h5>
                <p class="mb-0">No <?= strtolower($assessment_type); ?> assessment data has been submitted yet. The table below shows zeros.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<table id="allGradesTable" class="table table-bordered table-sm mb-0">
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
            <?php for ($i = 0; $i < 10; $i++): ?>
                <th class="text-center">Count</th>
                <th class="text-center">%</th>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($grades as $gradeName => $gradeKeys): ?>
            <?php foreach ($sexRows as $sexRow): ?>
                <?php
                $gradeKey = $gradeKeys[$sexRow['key']];
                $enrolment = gdata_division($nutritional_data, $gradeKey, 'enrolment');
                $rowClass = $sexRow['key'] === 'total' ? 'fw-bold' : '';
                ?>
                <tr class="<?= $sexRow['class'] ?>">
                    <?php if ($sexRow['key'] === 'm'): ?>
                        <td class="fw-bold text-center align-middle" rowspan="3"><?= htmlspecialchars($gradeName, ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endif; ?>
                    <td class="text-center <?= $rowClass ?>"><?= $sexRow['label'] ?></td>
                    <td class="text-center <?= $rowClass ?>"><?= $enrolment ?></td>
                    <td class="text-center <?= $rowClass ?>"><?= gdata_division($nutritional_data, $gradeKey, 'pupils_weighed') ?></td>
                    <?php foreach ($reportFields as $field): ?>
                        <?php $value = gdata_division($nutritional_data, $gradeKey, $field); ?>
                        <td class="text-center <?= $rowClass ?>"><?= $value ?></td>
                        <td class="text-center <?= $rowClass ?>"><?= pct_division($value, $enrolment) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?php foreach ($sexRows as $sexRow): ?>
            <?php
            $enrolment = sum_group_data($nutritional_data, $grades, $sexRow['key'], 'enrolment');
            $weighed = sum_group_data($nutritional_data, $grades, $sexRow['key'], 'pupils_weighed');
            $rowClass = $sexRow['key'] === 'total' ? 'fw-bold' : '';
            ?>
            <tr class="table-primary">
                <?php if ($sexRow['key'] === 'm'): ?>
                    <td class="fw-bold text-center align-middle" rowspan="3">Grand Total</td>
                <?php endif; ?>
                <td class="text-center <?= $rowClass ?>"><?= $sexRow['label'] ?></td>
                <td class="text-center fw-bold"><?= $enrolment ?></td>
                <td class="text-center fw-bold"><?= $weighed ?></td>
                <?php foreach ($reportFields as $field): ?>
                    <?php $value = sum_group_data($nutritional_data, $grades, $sexRow['key'], $field); ?>
                    <td class="text-center fw-bold"><?= $value ?></td>
                    <td class="text-center fw-bold"><?= pct_division($value, $enrolment) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
