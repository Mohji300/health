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

        $school_name = trim($this->input->post('school_name', TRUE));
        $school_year = trim($this->input->post('school_year', TRUE));

        if (empty($school_name) || empty($school_year)) {
            echo json_encode(['status' => 'error', 'message' => 'School name and year are required.']);
            return;
        }

        $id = $this->shd_reports_model->create_school_report([
            'school_name' => $school_name,
            'school_year' => $school_year
        ]);

        echo json_encode(['status' => 'success', 'report_id' => $id]);
    }

    public function report_entry($id)
    {
        $report = $this->shd_reports_model->get_report($id);
        if (!$report) show_404();

        // Fix: check if report_data is null before decoding
        $data['report'] = $report;
        $data['report_data'] = ($report->report_data !== null) ? json_decode($report->report_data, true) : [];
        $this->load->view('shd_report_entry', $data);
    }

    public function save_report_data($id)
    {
        $raw_input = file_get_contents('php://input');
        
        // Fix: handle empty or null input
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
        $autoload_path = FCPATH . 'vendor/autoload.php';
        if (!file_exists($autoload_path)) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode([
                             'status' => 'error',
                             'message' => 'PhpSpreadsheet library not found. Please run "composer require phpoffice/phpspreadsheet" in your project root.'
                         ]));
            return;
        }
        require_once $autoload_path;

        $report = $this->shd_reports_model->get_report($report_id);
        if (!$report) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['status' => 'error', 'message' => 'Report not found']));
            return;
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['status' => 'error', 'message' => 'File upload failed']));
            return;
        }

        $file = $_FILES['excel_file']['tmp_name'];
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $parsed_data = $this->parse_shd_excel($rows);
            $existing = ($report->report_data !== null) ? json_decode($report->report_data, true) : [];
            $merged = array_merge_recursive($existing, $parsed_data);

            $this->shd_reports_model->update_report_data($report_id, json_encode($merged));

            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['status' => 'success', 'message' => 'Data imported successfully']));
        } catch (Exception $e) {
            log_message('error', 'Excel import error: ' . $e->getMessage());
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['status' => 'error', 'message' => 'Failed to parse Excel: ' . $e->getMessage()]));
        }
    }

    private function parse_shd_excel($rows)
    {
        $data = [];

        // Mapping: Excel row index (0-based) => field key
        // Row numbers from the original file (subtract 1 for zero-index)
        $row_mapping = [
            20 => 'enrol_male',                     // row 21
            21 => 'enrol_female',                   // row 22
            25 => 'assessed_1st_learners',          // row 26
            26 => 'assessed_1st_male',              // row 27
            27 => 'assessed_1st_female',            // row 28
            28 => 'assessed_1st_ntp',               // row 29
            29 => 'assessed_1st_ntp_male',          // row 30
            30 => 'assessed_1st_ntp_female',        // row 31
            32 => 'assessed_rev_learners',          // row 33
            33 => 'assessed_rev_male',              // row 34
            34 => 'assessed_rev_female',            // row 35
            35 => 'assessed_rev_ntp',               // row 36
            36 => 'assessed_rev_ntp_male',          // row 37
            37 => 'assessed_rev_ntp_female',        // row 38
            39 => 'health_prob_learners',           // row 40
            40 => 'health_prob_male',               // row 41
            41 => 'health_prob_female',             // row 42
            42 => 'health_prob_ntp',                // row 43
            43 => 'health_prob_ntp_male',           // row 44
            44 => 'health_prob_ntp_female',         // row 45
            46 => 'vision_learners',                // row 47
            47 => 'vision_male',                    // row 48
            48 => 'vision_female',                  // row 49
            49 => 'vision_ntp',                     // row 50
            50 => 'vision_ntp_male',                // row 51
            51 => 'vision_ntp_female',              // row 52
            53 => 'treatment_learners',             // row 54
            54 => 'treatment_male',                 // row 55
            55 => 'treatment_female',               // row 56
            56 => 'treatment_ntp',                  // row 57
            57 => 'treatment_ntp_male',             // row 58
            58 => 'treatment_ntp_female',           // row 59
            60 => 'deworm_1st',                     // row 61
            61 => 'deworm_1st_male',                // row 62
            62 => 'deworm_1st_female',              // row 63
            63 => 'deworm_2nd',                     // row 64
            64 => 'deworm_2nd_male',                // row 65
            65 => 'deworm_2nd_female',              // row 66
            67 => 'iron_learners',                  // row 68
            69 => 'immunized_learners',             // row 70
            71 => 'consultation_learners',          // row 72
            73 => 'referral_physician',             // row 74
            74 => 'referral_dentist',               // row 75
            75 => 'referral_guidance',              // row 76
            76 => 'referral_other',                 // row 77
            77 => 'referral_hospital',              // row 78
            // Health Education rows (rows 82-95)
            81 => 'health_lectures',                // row 82
            84 => 'orientation_learners',           // row 85
            86 => 'meeting_teachers',               // row 87
            87 => 'meeting_health_officials',       // row 88
            88 => 'meeting_learners',               // row 89
            89 => 'meeting_parents',                // row 90
            90 => 'meeting_lgu',                    // row 91
            91 => 'meeting_ngo',                    // row 92
            93 => 'resource_health_activities',     // row 94
            94 => 'resource_class_discussion',      // row 95
            95 => 'resource_health_clubs',          // row 96
            // Community Activities (rows 98-101)
            97 => 'community_pta',                  // row 98
            98 => 'community_parent_seminar',       // row 99
            99 => 'community_home_visits',          // row 100
            100 => 'community_hospital_visits',     // row 101
            // Nutritional Status (rows 104-112)
            103 => 'nutrition_normal_weight',       // row 104
            104 => 'nutrition_wasted',              // row 105
            105 => 'nutrition_severe_wasted',       // row 106
            106 => 'nutrition_overweight',          // row 107
            107 => 'nutrition_obese',               // row 108
            108 => 'nutrition_normal_height',       // row 109
            109 => 'nutrition_stunted',             // row 110
            110 => 'nutrition_severe_stunted',      // row 111
            111 => 'nutrition_tall',                // row 112
            // Vision/Auditory (rows 114-117)
            113 => 'vision_passed',                 // row 114
            114 => 'vision_failed',                 // row 115
            115 => 'auditory_passed',               // row 116
            116 => 'auditory_failed',               // row 117
            // Skin (rows 119-130)
            118 => 'skin_lice',                     // row 119
            119 => 'skin_redness',                  // row 120
            120 => 'skin_white_spots',              // row 121
            121 => 'skin_flaky',                    // row 122
            122 => 'skin_impetigo',                 // row 123
            123 => 'skin_hematoma',                 // row 124
            124 => 'skin_bruises',                  // row 125
            125 => 'skin_itchiness',                // row 126
            126 => 'skin_lesions',                  // row 127
            127 => 'skin_acne',                     // row 128
            128 => 'skin_capillary_refill',         // row 129
            129 => 'skin_others',                   // row 130
            // Eye/Ears (rows 132-142)
            131 => 'eye_inflamed_fluid',            // row 132
            132 => 'eye_redness',                   // row 133
            133 => 'eye_misalignment',              // row 134
            134 => 'eye_pale_conjunctiva',          // row 135
            135 => 'eye_matted_lashes',             // row 136
            136 => 'eye_discharge',                 // row 137
            137 => 'ear_discharge',                 // row 138
            138 => 'ear_impacted_cerumen',          // row 139
            139 => 'ear_mucus',                     // row 140
            140 => 'nosebleed',                     // row 141
            141 => 'eye_ear_other',                 // row 142
            // Mouth/Neck/Throat (rows 144-147)
            143 => 'mouth_lesions',                 // row 144
            144 => 'mouth_inflamed_pharynx',        // row 145
            145 => 'mouth_enlarged_tonsils',        // row 146
            146 => 'mouth_lymph_nodes',             // row 147
            // Heart/Lungs (rows 149-155)
            148 => 'heart_rales',                   // row 149
            149 => 'heart_wheeze',                  // row 150
            150 => 'heart_murmur',                  // row 151
            151 => 'heart_irregular',               // row 152
            152 => 'heart_colds',                   // row 153
            153 => 'heart_cough',                   // row 154
            154 => 'heart_other',                   // row 155
            // Deformities (rows 157-158)
            156 => 'deformity_acquired',            // row 157
            157 => 'deformity_congenital',          // row 158
            // Abdomen (rows 160-164)
            159 => 'abdomen_distended',             // row 160
            160 => 'abdomen_pain',                  // row 161
            161 => 'abdomen_tenderness',            // row 162
            162 => 'abdomen_dysmenorrhea',          // row 163
            163 => 'abdomen_other',                 // row 164
        ];

        // Column indices: elementary grades (E to K = 4 to 10), secondary grades (M to S = 12 to 18)
        // Note: Our table expects 7 elem columns (Gr1-6 + SPED) and 6 sec columns (Gr7-12, no SPED).
        // The Excel file might have different column layout – adjust accordingly.
        $elem_cols = [4,5,6,7,8,9,10]; // E, F, G, H, I, J, K
        $sec_cols  = [12,13,14,15,16,17,18]; // M, N, O, P, Q, R, S (but we only use first 6 in our view)

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
            // Keep only first 6 secondary values (Gr7-12, drop SPED)
            $sec_vals = array_slice($sec_vals, 0, 6);

            $data[$field_key] = [
                'elem' => $elem_vals,
                'sec'  => $sec_vals
            ];
        }

        // Extract remarks (assumed row 166, column A)
        if (isset($rows[165][0])) {
            $data['remarks'] = trim($rows[165][0]);
        }

        return $data;
    }

}