<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shd_reports_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('shd_reports_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');

        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
    }

    public function index()
    {
        $data['reports'] = $this->shd_reports_model->list_all_reports();
        $data['total_reports'] = count($data['reports']);
        $this->load->view('shd_reports', $data);
    }

    public function create_school_report()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $month = trim($this->input->post('month', TRUE));
        $school_year = trim($this->input->post('school_year', TRUE));
        $districtmunicipality = trim($this->input->post('districtmunicipality', TRUE));
        $school_id = trim($this->input->post('school_id', TRUE));
        $school_name = trim($this->input->post('school_name', TRUE));
        

        if (empty($school_name) || empty($school_year)) {
            echo json_encode(['status' => 'error', 'message' => 'School name and year are required.']);
            return;
        }

        $id = $this->shd_reports_model->create_school_report([
            'month' => $month,
            'school_year' => $school_year,
            'districtmunicipality' => $districtmunicipality,
            'school_id' => $school_id,
            'school_name' => $school_name,
        ]);

        echo json_encode(['status' => 'success', 'report_id' => $id]);
    }

    public function report_entry($id)
    {
        $report = $this->shd_reports_model->get_report($id);
        if (!$report) show_404();

        $data['report'] = $report;
        $data['report_data'] = ($report->report_data !== null) ? json_decode($report->report_data, true) : [];
        $this->load->view('shd_report_entry', $data);
    }

    public function save_report_data($id)
    {
        $raw_input = file_get_contents('php://input');
        if (empty($raw_input) || $raw_input === 'null') {
            echo json_encode(['status' => 'error', 'message' => 'No data received']);
            return;
        }

        $data = json_decode($raw_input, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
            return;
        }

        $success = $this->shd_reports_model->update_report_data($id, json_encode($data));
        echo json_encode(['status' => $success ? 'success' : 'error']);
    }

    public function upload_excel($report_id)
    {
        $this->output->set_content_type('application/json');

        // 1. Check PhpSpreadsheet
        $autoload_path = FCPATH . 'vendor/autoload.php';
        if (!file_exists($autoload_path)) {
            $this->output->set_output(json_encode([
                'status' => 'error',
                'message' => 'PhpSpreadsheet library not found. Run "composer require phpoffice/phpspreadsheet" in project root.'
            ]));
            return;
        }
        require_once $autoload_path;

        // 2. Get report
        $report = $this->shd_reports_model->get_report($report_id);
        if (!$report) {
            $this->output->set_output(json_encode(['status' => 'error', 'message' => 'Report not found']));
            return;
        }

        // 3. Validate uploaded file
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = isset($_FILES['excel_file']) ? 'Upload error code: ' . $_FILES['excel_file']['error'] : 'No file uploaded';
            $this->output->set_output(json_encode(['status' => 'error', 'message' => $error_msg]));
            return;
        }

        $file = $_FILES['excel_file']['tmp_name'];
        try {
            // 4. Load spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // 5. Parse data
            $parsed_data = $this->parse_shd_excel($rows);
            $existing = ($report->report_data !== null) ? json_decode($report->report_data, true) : [];
            $merged = array_merge($existing, $parsed_data);

            // 6. Save
            $success = $this->shd_reports_model->update_report_data($report_id, json_encode($merged));
            if (!$success) {
                throw new Exception('Database update failed');
            }

            $this->output->set_output(json_encode(['status' => 'success', 'message' => 'Data imported successfully']));
        } catch (Exception $e) {
            log_message('error', 'Excel import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->output->set_output(json_encode([
                'status' => 'error',
                'message' => 'Failed to parse Excel: ' . $e->getMessage()
            ]));
        }
    }

    private function parse_shd_excel($rows)
    {
        $data = [];

        // ---- Numeric fields mapping (unchanged) ----
        $row_mapping = [
            20 => 'enrol_male',
            21 => 'enrol_female',
            25 => 'assessed_1st_learners',
            26 => 'assessed_1st_male',
            27 => 'assessed_1st_female',
            28 => 'assessed_1st_ntp',
            29 => 'assessed_1st_ntp_male',
            30 => 'assessed_1st_ntp_female',
            32 => 'assessed_rev_learners',
            33 => 'assessed_rev_male',
            34 => 'assessed_rev_female',
            35 => 'assessed_rev_ntp',
            36 => 'assessed_rev_ntp_male',
            37 => 'assessed_rev_ntp_female',
            39 => 'health_prob_learners',
            40 => 'health_prob_male',
            41 => 'health_prob_female',
            42 => 'health_prob_ntp',
            43 => 'health_prob_ntp_male',
            44 => 'health_prob_ntp_female',
            46 => 'vision_learners',
            47 => 'vision_male',
            48 => 'vision_female',
            49 => 'vision_ntp',
            50 => 'vision_ntp_male',
            51 => 'vision_ntp_female',
            53 => 'treatment_learners',
            54 => 'treatment_male',
            55 => 'treatment_female',
            56 => 'treatment_ntp',
            57 => 'treatment_ntp_male',
            58 => 'treatment_ntp_female',
            60 => 'deworm_1st',
            61 => 'deworm_1st_male',
            62 => 'deworm_1st_female',
            63 => 'deworm_2nd',
            64 => 'deworm_2nd_male',
            65 => 'deworm_2nd_female',
            67 => 'iron_learners',
            69 => 'immunized_learners',
            71 => 'consultation_learners',
            73 => 'referral_physician',
            74 => 'referral_dentist',
            75 => 'referral_guidance',
            76 => 'referral_other',
            77 => 'referral_hospital',
            81 => 'health_lectures',
            84 => 'orientation_learners',
            86 => 'meeting_teachers',
            87 => 'meeting_health_officials',
            88 => 'meeting_learners',
            89 => 'meeting_parents',
            90 => 'meeting_lgu',
            91 => 'meeting_ngo',
            93 => 'resource_health_activities',
            94 => 'resource_class_discussion',
            95 => 'resource_health_clubs',
            97 => 'community_pta',
            98 => 'community_parent_seminar',
            99 => 'community_home_visits',
            100 => 'community_hospital_visits',
            103 => 'nutrition_normal_weight',
            104 => 'nutrition_wasted',
            105 => 'nutrition_severe_wasted',
            106 => 'nutrition_overweight',
            107 => 'nutrition_obese',
            108 => 'nutrition_normal_height',
            109 => 'nutrition_stunted',
            110 => 'nutrition_severe_stunted',
            111 => 'nutrition_tall',
            113 => 'vision_passed',
            114 => 'vision_failed',
            115 => 'auditory_passed',
            116 => 'auditory_failed',
            118 => 'skin_lice',
            119 => 'skin_redness',
            120 => 'skin_white_spots',
            121 => 'skin_flaky',
            122 => 'skin_impetigo',
            123 => 'skin_hematoma',
            124 => 'skin_bruises',
            125 => 'skin_itchiness',
            126 => 'skin_lesions',
            127 => 'skin_acne',
            128 => 'skin_capillary_refill',
            129 => 'skin_others',
            131 => 'eye_inflamed_fluid',
            132 => 'eye_redness',
            133 => 'eye_misalignment',
            134 => 'eye_pale_conjunctiva',
            135 => 'eye_matted_lashes',
            136 => 'eye_discharge',
            137 => 'ear_discharge',
            138 => 'ear_impacted_cerumen',
            139 => 'ear_mucus',
            140 => 'nosebleed',
            141 => 'eye_ear_other',
            143 => 'mouth_lesions',
            144 => 'mouth_inflamed_pharynx',
            145 => 'mouth_enlarged_tonsils',
            146 => 'mouth_lymph_nodes',
            148 => 'heart_rales',
            149 => 'heart_wheeze',
            150 => 'heart_murmur',
            151 => 'heart_irregular',
            152 => 'heart_colds',
            153 => 'heart_cough',
            154 => 'heart_other',
            156 => 'deformity_acquired',
            157 => 'deformity_congenital',
            159 => 'abdomen_distended',
            160 => 'abdomen_pain',
            161 => 'abdomen_tenderness',
            162 => 'abdomen_dysmenorrhea',
            163 => 'abdomen_other',
        ];

        $elem_cols = [4,5,6,7,8,9,10,11];
        $sec_cols  = [13,14,15,16,17,18];

        // Parse numeric fields
        foreach ($row_mapping as $row_idx => $field_key) {
            if (!isset($rows[$row_idx])) continue;
            $row = $rows[$row_idx];

            $elem_vals = [];
            foreach ($elem_cols as $col) {
                $val = isset($row[$col]) ? trim($row[$col]) : '';
                $elem_vals[] = (is_numeric($val) && $val !== '') ? floatval($val) : null;
            }

            $sec_vals = [];
            foreach ($sec_cols as $col) {
                $val = isset($row[$col]) ? trim($row[$col]) : '';
                $sec_vals[] = (is_numeric($val) && $val !== '') ? floatval($val) : null;
            }

            $data[$field_key] = [
                'elem' => $elem_vals,
                'sec'  => $sec_vals
            ];
        }

        // ---- Extract "I. Other Signs & Symptoms Noted" (1-15) ----
        $startRow = -1;
        for ($i = 0; $i < count($rows); $i++) {
            if (!isset($rows[$i])) continue;
            foreach ($rows[$i] as $cell) {
                if (is_string($cell) && stripos(trim($cell), 'I. Other Signs') !== false) {
                    $startRow = $i;
                    break 2;
                }
            }
        }

        // DEBUG: uncomment to see rows after header
        // if ($startRow !== -1) {
        //     file_put_contents('debug_rows_after_header.txt', print_r(array_slice($rows, $startRow + 1, 30), true));
        // }

        if ($startRow !== -1) {
            for ($i = $startRow + 1; $i < count($rows); $i++) {
                if (!isset($rows[$i])) continue;
                $row = $rows[$i];

                // Check columns 0..5 for a standalone number (1-15)
                for ($col = 0; $col <= 5; $col++) {
                    if (!isset($row[$col])) continue;
                    $cell = trim($row[$col]);
                    // Match whole cell being a number with optional dot or parenthesis
                    if (preg_match('/^\s*(\d+)\s*[\.\)]?\s*$/', $cell, $matches)) {
                        $num = intval($matches[1]);
                        if ($num >= 1 && $num <= 15) {
                            // Find the first non-empty cell to the right as symptom
                            $symptom = '';
                            for ($c = $col + 1; $c < count($row); $c++) {
                                if (isset($row[$c]) && trim($row[$c]) !== '') {
                                    $symptom = trim($row[$c]);
                                    break;
                                }
                            }
                            $data['other_sign_' . $num] = $symptom;
                            // Break the column loop, continue to next row
                            break;
                        }
                    }
                }
            }
        }

        // ---- Extract "VI. Remarks" ----
        $foundRemark = false;
        for ($i = 0; $i < count($rows) && !$foundRemark; $i++) {
            if (!isset($rows[$i])) continue;
            foreach ($rows[$i] as $cell) {
                if (is_string($cell) && stripos(trim($cell), 'VI. Remarks') !== false) {
                    $remark = '';
                    // Look in the same row, next columns
                    for ($c = 0; $c < 5; $c++) {
                        if (isset($rows[$i][$c]) && is_string($rows[$i][$c]) && trim($rows[$i][$c]) !== '') {
                            // Avoid picking the header itself
                            if (stripos(trim($rows[$i][$c]), 'VI. Remarks') === false) {
                                $remark = trim($rows[$i][$c]);
                                break;
                            }
                        }
                    }
                    // If not found, check the next row's first non-empty string
                    if (empty($remark) && isset($rows[$i+1])) {
                        foreach ($rows[$i+1] as $cell2) {
                            if (is_string($cell2) && trim($cell2) !== '') {
                                $remark = trim($cell2);
                                break;
                            }
                        }
                    }
                    $data['remarks'] = $remark;
                    $foundRemark = true;
                    break;
                }
            }
        }

            // ---- Extract signature fields (Robust) ----
    $signatureConfig = [
        'prepared_by' => [
            'labels' => ['Prepared by:', 'Prepared By:', 'Prepared'],
            'skip' => ['Nurse II', 'Nurse ll', 'Principal I', 'Principal', 'by:', 'By:', ''],
            'maxRows' => 5,
            'maxCols' => 5
        ],
        'noted_by' => [
            'labels' => ['Noted by:', 'Noted By:', 'Noted'],
            'skip' => ['Nurse II', 'Nurse ll', 'Principal I', 'Principal', 'by:', 'By:', ''],
            'maxRows' => 5,
            'maxCols' => 5
        ],
        'date' => [
            'labels' => ['Date:', 'Date'],
            'skip' => [''],
            'maxRows' => 5,
            'maxCols' => 5
        ],
        'approved_by' => [
            'labels' => ['Approved by:', 'Approved By:', 'Approved'],
            'skip' => ['Nurse II', 'Nurse ll', 'Principal I', 'Principal', 'by:', 'By:', ''],
            'maxRows' => 5,
            'maxCols' => 5
        ]
    ];

    foreach ($signatureConfig as $key => $config) {
        $found = false;
        for ($i = 0; $i < count($rows) && !$found; $i++) {
            if (!isset($rows[$i])) continue;
            $row = $rows[$i];
            for ($colIdx = 0; $colIdx < count($row) && !$found; $colIdx++) {
                $cell = isset($row[$colIdx]) ? $row[$colIdx] : '';
                if (!is_string($cell)) continue;
                $cellTrim = trim($cell);

                // Check if this cell starts with any label
                $labelMatch = null;
                foreach ($config['labels'] as $label) {
                    if (stripos($cellTrim, $label) === 0) {
                        $labelMatch = $label;
                        break;
                    }
                }
                if ($labelMatch === null) continue;

                // 1) Try to get value from the same cell (after the label)
                $value = trim(substr($cellTrim, strlen($labelMatch)));
                if (in_array($value, $config['skip'])) {
                    $value = '';
                }

                // 2) If empty, scan a bounded area: down and right
                if (empty($value)) {
                    $maxR = min($config['maxRows'], count($rows) - $i - 1);
                    $maxC = min($config['maxCols'], count($row) - $colIdx - 1);
                    for ($r = 0; $r <= $maxR && empty($value); $r++) {
                        for ($c = 0; $c <= $maxC && empty($value); $c++) {
                            if ($r === 0 && $c === 0) continue; // skip the label cell itself
                            if (!isset($rows[$i + $r][$colIdx + $c])) continue;
                            $candidate = trim($rows[$i + $r][$colIdx + $c]);
                            if ($candidate !== '' && !in_array($candidate, $config['skip'])) {
                                $value = $candidate;
                            }
                        }
                    }
                }

                // 3) If still empty, check the next row's first non‑empty (skip titles)
                if (empty($value) && isset($rows[$i + 1])) {
                    foreach ($rows[$i + 1] as $cell2) {
                        if (!is_string($cell2)) continue;
                        $candidate = trim($cell2);
                        if ($candidate !== '' && !in_array($candidate, $config['skip'])) {
                            $value = $candidate;
                            break;
                        }
                    }
                }

                $data[$key] = $value;
                $found = true; // exit both loops
            }
        }
    }


        return $data;
    }
}