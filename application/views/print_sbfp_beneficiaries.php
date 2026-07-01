<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SBFP Form 1A - <?= ucfirst($assessment_type) ?> Print</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h3 { margin: 2px 0; }
        .header p { margin: 2px 0; }
        .form-title { text-align: center; font-weight: bold; font-size: 16px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
        .signature { margin-top: 40px; }
        .signature table { border: none; }
        .signature td { border: none; }
        .footer-note { text-align: center; font-size: 10px; margin-top: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h2>Department of Education</h2>
        <h3>Region V-Bicol</h3>
        <p>Master List Beneficiaries for School-Based Feeding Program (SBFP) (SY <?= $school_year ?>) - <?= strtoupper($assessment_type) ?></p>
        <p>Division: MASBATE PROVINCE</p>
        <?php if (!empty($selected_school)): ?>
            <p>School: <?= htmlspecialchars($selected_school) ?></p>
        <?php endif; ?>
        <?php if (!empty($section_id)): ?>
            <p>Section: <?= htmlspecialchars($section_id) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-title">MASTER LIST OF BENEFICIARIES</div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Sex</th>
                <th>Grade/Section</th>
                <th>Date of Birth</th>
                <th>Date of Weighing</th>
                <th>Age</th>
                <th>Weight (Kg)</th>
                <th>Height (cm)</th>
                <th>BMI</th>
                <th>Nutritional Status</th>
                <th>Height for Age</th>
                <th>Classification</th>
                <th>Pregnant</th>
                <th>With 0-1 Child</th>
                <th>Dewormed</th>
                <th>Parent's Consent</th>
                <th>Participation in 4Ps</th>
                <th>Previous SBFP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($beneficiaries)): ?>
                <?php $counter = 1; foreach ($beneficiaries as $student): ?>
                <?php
                    $dob = date('m/d/Y', strtotime($student['birthday']));
                    $weighing_date = date('m/d/Y', strtotime($student['date_of_weighing']));
                    $height = isset($student['height']) ? $student['height'] : number_format($student['height'] * 100, 1);
                    $bmi = isset($student['bmi']) ? $student['bmi'] : number_format($student['bmi'], 2);

                    $classification = '';
                    foreach (['classification_of_beneficiary_(Primary or Secondary)','classification_of_beneficiary','beneficiary_classification','classification_primary_secondary'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $classification = $student[$c]; break; }
                    }
                    $pregnant = '';
                    foreach (['pregnant','is_pregnant','pregnancy_status','pregnancy'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $pregnant = $student[$c]; break; }
                    }
                    $child01 = '';
                    foreach (['with_0_1_year_old_child','with_0_1_children','has_child_0_1','child_0_1'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $child01 = $student[$c]; break; }
                    }
                    $dewormed = '';
                    foreach (['dewormed','is_dewormed','deworming'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $dewormed = $student[$c]; break; }
                    }
                    $parentConsent = '';
                    foreach (['parent_consent','parents_consent','parent_consent_for_milk','parent_consent_milk'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $parentConsent = $student[$c]; break; }
                    }
                    $ps4 = '';
                    foreach (['participation_4ps','participation_in_4ps','is_4ps','4ps_participation'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $ps4 = $student[$c]; break; }
                    }
                    $prevSbfp = '';
                    foreach (['previous_sbfp','sbfp_previous','previous_beneficiary_sbfp','previous_sbfp_beneficiary'] as $c) {
                        if (isset($student[$c]) && $student[$c] !== '') { $prevSbfp = $student[$c]; break; }
                    }
                ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td class="text-left"><?= htmlspecialchars($student['name']) ?></td>
                    <td><?= $student['sex'] ?></td>
                    <td><?= htmlspecialchars($student['grade_level']) ?>/<?= htmlspecialchars($student['section']) ?></td>
                    <td><?= $dob ?></td>
                    <td><?= $weighing_date ?></td>
                    <td><?= htmlspecialchars($student['age']) ?></td>
                    <td><?= number_format($student['weight'], 1) ?></td>
                    <td><?= $height ?></td>
                    <td><?= $bmi ?></td>
                    <td><?= $student['nutritional_status'] ?></td>
                    <td><?= $student['height_for_age'] ?></td>
                    <td><?= htmlspecialchars($classification ?: '') ?></td>
                    <td><?= htmlspecialchars($pregnant ?: '') ?></td>
                    <td><?= htmlspecialchars($child01 ?: '') ?></td>
                    <td><?= htmlspecialchars($dewormed ?: '') ?></td>
                    <td><?= htmlspecialchars($parentConsent ?: '') ?></td>
                    <td><?= htmlspecialchars($ps4 ?: '') ?></td>
                    <td><?= htmlspecialchars($prevSbfp ?: '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="19" style="text-align:center; padding:20px;">No data available for printing.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature">
        <table style="width:100%; border:none;">
            <tr>
                <td style="width:50%; border:none;">
                    <p>Prepared by:</p>
                    <p>__________________________</p>
                    <p>Classroom Adviser/School Nurse</p>
                    <p>Printed Name & Signature</p>
                </td>
                <td style="width:50%; border:none;">
                    <p>Noted by:</p>
                    <p>__________________________</p>
                    <p>School Head</p>
                    <p>Printed Name & Signature</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        Note: This form follows the official SBFP Form 1A format as per DepEd guidelines.
    </div>

    <script>
        window.onload = function() {
            // Automatically open the print dialog
            window.print();
            // Close the tab after printing or cancelling
            window.onafterprint = function() {
                window.close();
            };
        };
    </script>
</body>
</html>