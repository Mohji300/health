<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter SHD Data - <?= htmlspecialchars($report->school_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .entry-table th, .entry-table td { vertical-align: middle; text-align: center; font-size: 0.75rem; padding: 0.3rem; }
        .entry-table input { width: 65px; text-align: center; padding: 0.2rem; font-size: 0.75rem; }
        .section-header { background-color: #d9e2e8; font-weight: bold; }
        .sub-section { background-color: #f0f3f5; }
        .sub-section2 { background-color: #fef9e6; }
        .table-responsive { max-height: 70vh; overflow-y: auto; }
        .sign-line { border-top: 1px solid #000; width: 200px; margin-top: 30px; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= htmlspecialchars($report->school_name) ?> (<?= htmlspecialchars($report->school_year) ?>)</h3>
        <div>
            <button class="btn btn-info me-2" id="uploadExcelBtn"><i class="fas fa-file-excel"></i> Upload Excel</button>
            <button class="btn btn-success me-2" id="saveDataBtn"><i class="fas fa-save"></i> Save All Data</button>
            <a href="<?= site_url('shd_reports_controller') ?>" class="btn btn-secondary">Back to List</a>
            <input type="file" id="excelFileInput" accept=".xlsx, .xls" style="display:none;">
        </div>
    </div>

    <form id="reportForm">
        <div class="table-responsive">
            <table class="table table-bordered entry-table" id="dataEntryTable">
                <thead>
                <tr>
                    <th style="min-width: 220px;">Indicators</th>
                    <th>Kinder</th>
                    <th>Elem<br>Gr 1</th><th>Elem<br>Gr 2</th><th>Elem<br>Gr 3</th>
                    <th>Elem<br>Gr 4</th><th>Elem<br>Gr 5</th><th>Elem<br>Gr 6</th>
                    <th>Elem<br>SPED</th>
                    <th>Elem Total</th>
                    <th>Sec<br>Gr 7</th><th>Sec<br>Gr 8</th><th>Sec<br>Gr 9</th>
                    <th>Sec<br>Gr 10</th><th>Sec<br>Gr 11</th><th>Sec<br>Gr 12</th>
                    <th>Sec Total</th>
                    <th>Grand Total</th>
                </tr>
                </thead>
                <tbody>
                <?php
                // Full list of rows
                $rows = [
                    ['label' => '<strong>I. GENERAL INFORMATION</strong>', 'type' => 'section'],
                    ['label' => 'A. School Enrolment', 'type' => 'sub'],
                    ['label' => 'a. Male', 'field' => 'enrol_male'],
                    ['label' => 'b. Female', 'field' => 'enrol_female'],
                    ['label' => '<strong>II. HEALTH SERVICES</strong>', 'type' => 'section'],
                    ['label' => 'A. Health Appraisal', 'type' => 'sub'],
                    ['label' => '1. No. of Assessed:', 'type' => 'sub2'],
                    ['label' => '1st Visit:', 'type' => 'sub2'],
                    ['label' => 'a. Learners', 'field' => 'assessed_1st_learners'],
                    ['label' => 'MALE', 'field' => 'assessed_1st_male'],
                    ['label' => 'FEMALE', 'field' => 'assessed_1st_female'],
                    ['label' => 'c. NTP', 'field' => 'assessed_1st_ntp'],
                    ['label' => 'MALE', 'field' => 'assessed_1st_ntp_male'],
                    ['label' => 'FEMALE', 'field' => 'assessed_1st_ntp_female'],
                    ['label' => 'Re-Visit:', 'type' => 'sub2'],
                    ['label' => 'a. Learners', 'field' => 'assessed_rev_learners'],
                    ['label' => 'MALE', 'field' => 'assessed_rev_male'],
                    ['label' => 'FEMALE', 'field' => 'assessed_rev_female'],
                    ['label' => 'c. NTP', 'field' => 'assessed_rev_ntp'],
                    ['label' => 'MALE', 'field' => 'assessed_rev_ntp_male'],
                    ['label' => 'FEMALE', 'field' => 'assessed_rev_ntp_female'],
                    ['label' => '2. No. with Health Problems', 'type' => 'sub2'],
                    ['label' => 'a. Learners', 'field' => 'health_prob_learners'],
                    ['label' => 'MALE', 'field' => 'health_prob_male'],
                    ['label' => 'FEMALE', 'field' => 'health_prob_female'],
                    ['label' => 'c. NTP', 'field' => 'health_prob_ntp'],
                    ['label' => 'MALE', 'field' => 'health_prob_ntp_male'],
                    ['label' => 'FEMALE', 'field' => 'health_prob_ntp_female'],
                    ['label' => '3. No. of Vision Screening', 'type' => 'sub2'],
                    ['label' => 'a. Learners', 'field' => 'vision_learners'],
                    ['label' => 'MALE', 'field' => 'vision_male'],
                    ['label' => 'FEMALE', 'field' => 'vision_female'],
                    ['label' => 'c. NTP', 'field' => 'vision_ntp'],
                    ['label' => 'MALE', 'field' => 'vision_ntp_male'],
                    ['label' => 'FEMALE', 'field' => 'vision_ntp_female'],
                    ['label' => 'B. Treatment / Nursing Intervention Done', 'type' => 'sub'],
                    ['label' => 'a. Learners', 'field' => 'treatment_learners'],
                    ['label' => 'MALE', 'field' => 'treatment_male'],
                    ['label' => 'FEMALE', 'field' => 'treatment_female'],
                    ['label' => 'c. NTP', 'field' => 'treatment_ntp'],
                    ['label' => 'MALE', 'field' => 'treatment_ntp_male'],
                    ['label' => 'FEMALE', 'field' => 'treatment_ntp_female'],
                    ['label' => 'C. No. of Pupils Dewormed', 'type' => 'sub'],
                    ['label' => 'a. 1st Round', 'field' => 'deworm_1st'],
                    ['label' => 'MALE', 'field' => 'deworm_1st_male'],
                    ['label' => 'FEMALE', 'field' => 'deworm_1st_female'],
                    ['label' => 'b. 2nd Round', 'field' => 'deworm_2nd'],
                    ['label' => 'MALE', 'field' => 'deworm_2nd_male'],
                    ['label' => 'FEMALE', 'field' => 'deworm_2nd_female'],
                    ['label' => 'D. No. of Pupils Given Iron Supplement', 'type' => 'sub'],
                    ['label' => 'a. Learners', 'field' => 'iron_learners'],
                    ['label' => 'E. No. of Pupils Immunized (Specify vaccine)', 'type' => 'sub'],
                    ['label' => 'a. Learners', 'field' => 'immunized_learners'],
                    ['label' => 'F. No. of consultation attended', 'type' => 'sub'],
                    ['label' => 'a. Learners', 'field' => 'consultation_learners'],
                    ['label' => 'G. Referral (No. Referred to)', 'type' => 'sub'],
                    ['label' => 'a. Physician', 'field' => 'referral_physician'],
                    ['label' => 'b. Dentist', 'field' => 'referral_dentist'],
                    ['label' => 'c. Guidance Counselor', 'field' => 'referral_guidance'],
                    ['label' => 'd. Other facilities', 'field' => 'referral_other'],
                    ['label' => 'e. RHU/District/Provincial Hospital', 'field' => 'referral_hospital'],
                    ['label' => '<strong>III. HEALTH EDUCATION</strong>', 'type' => 'section'],
                    ['label' => 'A. No. of Classes given health lectures', 'field' => 'health_lectures'],
                    ['label' => 'B. No. of orientation training conducted to: a. Learners', 'field' => 'orientation_learners'],
                    ['label' => 'C. No. of conferences/meeting with:', 'type' => 'sub2'],
                    ['label' => 'a. Teachers/Administrators', 'field' => 'meeting_teachers'],
                    ['label' => 'b. Health officials', 'field' => 'meeting_health_officials'],
                    ['label' => 'c. Learners', 'field' => 'meeting_learners'],
                    ['label' => 'd. Parents', 'field' => 'meeting_parents'],
                    ['label' => 'e. LGU/Barangay', 'field' => 'meeting_lgu'],
                    ['label' => 'f. NGOs/Stakeholders', 'field' => 'meeting_ngo'],
                    ['label' => 'D. Involvement as Resource Person/Consultant/Adviser/Judge', 'type' => 'sub2'],
                    ['label' => 'a. Health Activities/programs/contests', 'field' => 'resource_health_activities'],
                    ['label' => 'b. Class Discussion', 'field' => 'resource_class_discussion'],
                    ['label' => 'c. Health Clubs/Organization', 'field' => 'resource_health_clubs'],
                    ['label' => '<strong>IV. SCHOOL COMMUNITY ACTIVITIES FOR HEALTH AND NUTRITION</strong>', 'type' => 'section'],
                    ['label' => 'A. PTA/Homeroom Organization Meetings', 'field' => 'community_pta'],
                    ['label' => 'B. Parent Education Seminar/Workshop/Training', 'field' => 'community_parent_seminar'],
                    ['label' => 'C. Home Visits Conducted', 'field' => 'community_home_visits'],
                    ['label' => 'D. Hospital Visits made', 'field' => 'community_hospital_visits'],
                    ['label' => '<strong>V. COMMON SIGNS & SYMPTOMS</strong>', 'type' => 'section'],
                    ['label' => 'A. Nutritional Status', 'type' => 'sub'],
                    ['label' => 'a. Normal Weight', 'field' => 'nutrition_normal_weight'],
                    ['label' => 'b. Wasted / Underweight', 'field' => 'nutrition_wasted'],
                    ['label' => 'c. Underweight / Severely Wasted', 'field' => 'nutrition_severe_wasted'],
                    ['label' => 'd. Overweight', 'field' => 'nutrition_overweight'],
                    ['label' => 'e. Obese', 'field' => 'nutrition_obese'],
                    ['label' => 'f. Normal Height', 'field' => 'nutrition_normal_height'],
                    ['label' => 'g. Stunted', 'field' => 'nutrition_stunted'],
                    ['label' => 'h. Severely stunted', 'field' => 'nutrition_severe_stunted'],
                    ['label' => 'i. Tall', 'field' => 'nutrition_tall'],
                    ['label' => 'B. Vision / Auditory', 'type' => 'sub'],
                    ['label' => 'VISUAL - a. Passed', 'field' => 'vision_passed'],
                    ['label' => 'b. Failed', 'field' => 'vision_failed'],
                    ['label' => 'AUDITORY - a. Passed', 'field' => 'auditory_passed'],
                    ['label' => 'b. Failed', 'field' => 'auditory_failed'],
                    ['label' => 'C. Skin and Scalp', 'type' => 'sub'],
                    ['label' => 'a. Presence of Lice (Pediculosis)', 'field' => 'skin_lice'],
                    ['label' => 'b. Redness of Skin', 'field' => 'skin_redness'],
                    ['label' => 'c. White Spots', 'field' => 'skin_white_spots'],
                    ['label' => 'd. Flaky Skin', 'field' => 'skin_flaky'],
                    ['label' => 'e. Impetigo/Boil', 'field' => 'skin_impetigo'],
                    ['label' => 'f. Hematoma', 'field' => 'skin_hematoma'],
                    ['label' => 'g. Bruises/Injuries', 'field' => 'skin_bruises'],
                    ['label' => 'h. Itchiness', 'field' => 'skin_itchiness'],
                    ['label' => 'i. Skin lesions', 'field' => 'skin_lesions'],
                    ['label' => 'j. Acne / Pimple', 'field' => 'skin_acne'],
                    ['label' => 'k. Capillary refill > 3 sec', 'field' => 'skin_capillary_refill'],
                    ['label' => 'l. Others, specify', 'field' => 'skin_others'],
                    ['label' => 'D. Eye and Ears', 'type' => 'sub'],
                    ['label' => 'a. Inflamed fluid', 'field' => 'eye_inflamed_fluid'],
                    ['label' => 'b. Eye Redness', 'field' => 'eye_redness'],
                    ['label' => 'c. Ocular Misalignment', 'field' => 'eye_misalignment'],
                    ['label' => 'd. Pale Conjunctiva', 'field' => 'eye_pale_conjunctiva'],
                    ['label' => 'e. Matted Eyelashes', 'field' => 'eye_matted_lashes'],
                    ['label' => 'f. Eye Discharge', 'field' => 'eye_discharge'],
                    ['label' => 'g. Ear Discharge', 'field' => 'ear_discharge'],
                    ['label' => 'h. Impacted Cerumen', 'field' => 'ear_impacted_cerumen'],
                    ['label' => 'i. Mucus Discharge', 'field' => 'ear_mucus'],
                    ['label' => 'j. Nosebleeding (Epistaxis)', 'field' => 'nosebleed'],
                    ['label' => 'k. Other, specify', 'field' => 'eye_ear_other'],
                    ['label' => 'E. Mouth / Neck / Throat', 'type' => 'sub'],
                    ['label' => 'a. Presence of Lesions', 'field' => 'mouth_lesions'],
                    ['label' => 'b. Inflamed Pharynx', 'field' => 'mouth_inflamed_pharynx'],
                    ['label' => 'c. Enlarged tonsils', 'field' => 'mouth_enlarged_tonsils'],
                    ['label' => 'd. Enlarged lymph nodes', 'field' => 'mouth_lymph_nodes'],
                    ['label' => 'F. Heart and Lungs', 'type' => 'sub'],
                    ['label' => 'a. Rales', 'field' => 'heart_rales'],
                    ['label' => 'b. Wheeze', 'field' => 'heart_wheeze'],
                    ['label' => 'c. Murmur', 'field' => 'heart_murmur'],
                    ['label' => 'd. Irregular heart rate', 'field' => 'heart_irregular'],
                    ['label' => 'e. Colds', 'field' => 'heart_colds'],
                    ['label' => 'f. Cough', 'field' => 'heart_cough'],
                    ['label' => 'g. Others, specify', 'field' => 'heart_other'],
                    ['label' => 'G. Deformities', 'type' => 'sub'],
                    ['label' => 'a. Acquired (Specify)', 'field' => 'deformity_acquired'],
                    ['label' => 'b. Congenital (Specify)', 'field' => 'deformity_congenital'],
                    ['label' => 'H. Abdomen', 'type' => 'sub'],
                    ['label' => 'a. Distended', 'field' => 'abdomen_distended'],
                    ['label' => 'b. Abdominal Pain', 'field' => 'abdomen_pain'],
                    ['label' => 'c. Tenderness', 'field' => 'abdomen_tenderness'],
                    ['label' => 'd. Dysmenorrhea', 'field' => 'abdomen_dysmenorrhea'],
                    ['label' => 'e. Others, specify', 'field' => 'abdomen_other'],
                ];

                // Render function: 7 elem inputs, elem total, 6 sec inputs, sec total, grand total
                function renderRowCells($fieldKey, $report_data) {
                    // Elementary: 7 inputs
                    for ($i = 0; $i < 7; $i++) {
                        $val = isset($report_data[$fieldKey]['elem'][$i]) ? $report_data[$fieldKey]['elem'][$i] : '';
                        echo '<td><input type="number" class="form-control form-control-sm" data-field="'.$fieldKey.'" data-level="elem" data-index="'.$i.'" value="'.$val.'"></td>';
                    }
                    // Elementary total
                    echo '<td><input type="number" class="form-control form-control-sm elem-total" data-field="'.$fieldKey.'" readonly></td>';
                    // Secondary: 6 inputs
                    for ($i = 0; $i < 7; $i++) {
                        $val = isset($report_data[$fieldKey]['sec'][$i]) ? $report_data[$fieldKey]['sec'][$i] : '';
                        echo '<td><input type="number" class="form-control form-control-sm" data-field="'.$fieldKey.'" data-level="sec" data-index="'.$i.'" value="'.$val.'"></td>';
                    }
                    // Secondary total and grand total
                    echo '<td><input type="number" class="form-control form-control-sm sec-total" data-field="'.$fieldKey.'" readonly></td>';
                    echo '<td><input type="number" class="form-control form-control-sm grand-total" data-field="'.$fieldKey.'" readonly></td>';
                }

                $report_data = isset($report_data) ? $report_data : [];
                foreach ($rows as $row):
                    if (isset($row['type'])) {
                        $colspan = 18; // indicators + 8 elem + 1 e-total + 6 sec + 1 s-total + 1 grand = 18 columns
                        if ($row['type'] == 'section')
                            echo '<tr class="section-header"><td colspan="'.$colspan.'">'.$row['label'].'</td></tr>';
                        elseif ($row['type'] == 'sub')
                            echo '<tr class="sub-section"><td colspan="'.$colspan.'"><strong>'.$row['label'].'</strong></td></tr>';
                        elseif ($row['type'] == 'sub2')
                            echo '<tr class="sub-section2"><td colspan="'.$colspan.'"><em>'.$row['label'].'</em></td></tr>';
                        continue;
                    }
                    ?>
                    <tr>
                        <td style="text-align: left;"><?= $row['label'] ?></td>
                        <?php renderRowCells($row['field'], $report_data); ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Remarks and signature -->
        <div class="mt-4 p-3 bg-white border rounded">
            <div class="mb-3">
                <label for="remarks" class="form-label"><strong>I. Other signs&Symptoms Noted</strong></label> <br>
                <label>1.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>2.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>4.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>5.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>6.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>7.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>8.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>9.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea><br>
                <label>10.</label><textarea class="form-control" id="remarks" rows="1"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea>
            </div>

        <!-- Remarks and signature -->
        <div class="mt-4 p-3 bg-white border rounded">
            <div class="mb-3">
                <label for="remarks" class="form-label"><strong>VI. Remarks</strong></label>
                <textarea class="form-control" id="remarks" rows="3"><?= htmlspecialchars($report_data['remarks'] ?? '') ?></textarea>
            </div>
            <div class="row mt-4">
                <div class="col-md-4">
                    <p>Prepared by:</p><div class="sign-line"></div><p>Nurse II</p>
                </div>
                <div class="col-md-4">
                    <p>Noted by:</p><div class="sign-line"></div><p>Principal I</p><p>Date: ___________</p>
                </div>
                <div class="col-md-4">
                    <p>Approved by:</p><div class="sign-line"></div><p>Nurse II</p>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){
    function recalcTotals(fieldKey) {
        let elemTotal = 0, secTotal = 0;
        $(`input[data-field="${fieldKey}"][data-level="elem"]`).each(function(){
            let v = parseFloat($(this).val());
            if(!isNaN(v)) elemTotal += v;
        });
        $(`input[data-field="${fieldKey}"][data-level="sec"]`).each(function(){
            let v = parseFloat($(this).val());
            if(!isNaN(v)) secTotal += v;
        });
        $(`input.elem-total[data-field="${fieldKey}"]`).val(elemTotal);
        $(`input.sec-total[data-field="${fieldKey}"]`).val(secTotal);
        $(`input.grand-total[data-field="${fieldKey}"]`).val(elemTotal + secTotal);
    }

    $(document).on('input', 'input[data-field][data-level]', function(){
        recalcTotals($(this).data('field'));
    });

    $('#saveDataBtn').click(function(){
        let allData = {};
        $('input[data-field][data-level]').each(function(){
            let field = $(this).data('field'), level = $(this).data('level'), idx = $(this).data('index');
            let val = $(this).val() === '' ? null : parseFloat($(this).val());
            if(!allData[field]) allData[field] = { elem: [], sec: [] };
            if(level === 'elem') allData[field].elem[idx] = val;
            if(level === 'sec') allData[field].sec[idx] = val;
        });
        allData['remarks'] = $('#remarks').val();

        $.ajax({
            url: '<?= site_url("shd_reports_controller/save_report_data/".$report->id) ?>',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(allData),
            dataType: 'json',
            success: function(res){
                if(res.status === 'success') alert('Data saved successfully!');
                else alert('Error saving data');
            },
            error: function(){ alert('Server error'); }
        });
    });

    // Excel upload
    $('#uploadExcelBtn').click(function(){ $('#excelFileInput').click(); });
    $('#excelFileInput').change(function(){
        let file = this.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append('excel_file', file);
        $.ajax({
            url: '<?= site_url("shd_reports_controller/upload_excel/".$report->id) ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function(){
                $('#uploadExcelBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
            },
            success: function(res){
                if(res.status === 'success'){
                    alert('Data imported! Reloading...');
                    location.reload();
                } else alert('Error: ' + res.message);
            },
            error: function(){ alert('Upload failed'); },
            complete: function(){
                $('#uploadExcelBtn').prop('disabled', false).html('<i class="fas fa-file-excel"></i> Upload Excel');
                $('#excelFileInput').val('');
            }
        });
    });

    // Initial totals
    $('input[data-field]').each(function(){ recalcTotals($(this).data('field')); });
});
</script>
</body>
</html>