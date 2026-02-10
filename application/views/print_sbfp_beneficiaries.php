<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SBFP Form 1A - <?php echo ucfirst($assessment_type); ?> Print</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0; font-size: 14px; }
        .header h3 { margin: 0; font-size: 12px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .form-title { font-weight: bold; font-size: 11px; text-align: center; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; }
        th, td { border: 1px solid #000; padding: 3px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-left { text-align: left; }
        .signature { margin-top: 20px; font-size: 9px; }
        .signature table { border: none; }
        .signature td { border: none; padding: 15px 20px; }
        .footer-note { font-size: 8px; margin-top: 10px; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Department of Education</h2>
        <h3>Region V-Bicol</h3>
        <p>Master List Beneficiaries for School-Based Feeding Program (SBFP) (SY 2025-2026) - <?php echo strtoupper($assessment_type); ?></p>
        <p>Division: MASBATE PROVINCE</p>
        <?php if (!empty($selected_school)): ?>
        <p>School: <?php echo htmlspecialchars($selected_school); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="form-title">MASTER LIST OF BENEFICIARIES</div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Name</th>
                <th rowspan="2">Sex</th>
                <th rowspan="2">Grade/<br>Section</th>
                <th rowspan="2">Date of Birth<br>(MM/DD/YYYY)</th>
                <th rowspan="2">Date of Weighing/<br>Measuring (MM/DD/YYYY)</th>
                <th rowspan="2">Age in<br>Years/Months</th>
                <th rowspan="2">Weight<br>(Kg)</th>
                <th rowspan="2">Height<br>(cm)</th>
                <th rowspan="2">BMI for<br>6 y.o. and above</th>
                <th colspan="2">Nutritional Status (NS)</th>
                <th rowspan="2">Parent's consent<br>for milk?<br>(yes or no)</th>
                <th rowspan="2">Participation<br>in 4Ps<br>(yes or no)</th>
                <th rowspan="2">Beneficiary of SBFP<br>in Previous Years<br>(yes or no)</th>
            </tr>
            <tr>
                <th>BMI-A</th>
                <th>HFA</th>
            </tr>
        </thead>
        <tbody>
            <?php $counter = 1; foreach ($beneficiaries as $student): ?>
            <?php 
                $dob = date('m/d/Y', strtotime($student['birthday']));
                $weighing_date = date('m/d/Y', strtotime($student['date_of_weighing']));
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
                <td><?= number_format($student['height'], 1) ?></td>
                <td><?= number_format($student['bmi'], 1) ?></td>
                <td><?= $student['nutritional_status'] ?></td>
                <td><?= $student['height_for_age'] ?></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($beneficiaries)): ?>
            <tr>
                <td colspan="16" style="text-align: center; padding: 20px;">
                    No <?php echo $assessment_type; ?> data available for printing.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="signature">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <p>Prepared by:</p>
                    <p>__________________________</p>
                    <p>Classroom Adviser/School Nurse</p>
                    <p>Printed Name & Signature</p>
                </td>
                <td style="width: 50%;">
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
            window.print();
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>