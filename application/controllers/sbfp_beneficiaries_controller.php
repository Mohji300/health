<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sbfp_beneficiaries_controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('sbfp_beneficiaries_model');
        $this->load->helper('url');
        $this->load->library('session');

        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }
    }
    
    public function index() {
        $data = array();

        $user_id = $this->session->userdata('user_id');
        $auth_user = $this->session->userdata('auth_user');
        $session_role = $this->session->userdata('role');
        $session_school_name = $this->session->userdata('school_name');
        $session_school_id = $this->session->userdata('school_id');
        $session_district = $this->session->userdata('school_district');

        $user_role = 'school';
        $school_name = '';
        $school_id = '';
        $school_district = '';

        if (!empty($auth_user)) {
            if (is_object($auth_user)) {
                $user_role = !empty($auth_user->role) ? $auth_user->role : $user_role;
                $school_name = !empty($auth_user->name) ? $auth_user->name : $school_name;
                $school_id = !empty($auth_user->school_id) ? $auth_user->school_id : $school_id;
                $school_district = !empty($auth_user->school_district) ? $auth_user->school_district : $school_district;
            } elseif (is_array($auth_user)) {
                $user_role = !empty($auth_user['role']) ? $auth_user['role'] : $user_role;
                $school_name = !empty($auth_user['name']) ? $auth_user['name'] : $school_name;
                $school_id = !empty($auth_user['school_id']) ? $auth_user['school_id'] : $school_id;
                $school_district = !empty($auth_user['school_district']) ? $auth_user['school_district'] : $school_district;
            }
        }

        if (empty($user_role) || $user_role === 'school') {
            if (!empty($session_role)) {
                $user_role = $session_role;
            }
        }
        
        if (empty($school_name) && !empty($session_school_name)) {
            $school_name = $session_school_name;
        }

        if (!empty($session_district)) {
            $school_district = $session_district;
        }

        if (empty($school_district) || (empty($school_name) && $user_role === 'school') || empty($user_role)) {
            $user_data = $this->get_user_data_from_db($user_id);
            if (!empty($user_data)) {
                $user_role = !empty($user_data['role']) ? $user_data['role'] : $user_role;
                $school_name = !empty($user_data['name']) ? $user_data['name'] : $school_name;
                $school_id = !empty($user_data['school_id']) ? $user_data['school_id'] : $school_id;
                $school_district = !empty($user_data['school_district']) ? $user_data['school_district'] : $school_district;
            }
        }

        if ($user_role === 'district' && empty($school_district)) {
            $this->db->select('school_district');
            $this->db->from('users');
            $this->db->where('id', $user_id);
            $district_query = $this->db->get();
            if ($district_query->num_rows() > 0) {
                $district_row = $district_query->row();
                $school_district = !empty($district_row->school_district) ? $district_row->school_district : '';
            }
        }

        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $grade_level_filter = $this->session->userdata('grade_level_filter') ?: '';
        $school_name_filter = $this->session->userdata('school_name_filter') ?: '';
        $district_filter = $this->session->userdata('district_filter') ?: '';
        
        $data['assessment_type'] = $assessment_type;
        $data['is_baseline'] = ($assessment_type == 'baseline');
        $data['is_midline'] = ($assessment_type == 'midline');
        $data['is_endline'] = ($assessment_type == 'endline');
        $data['school_year'] = $this->get_current_school_year();
        $session_school_level = $this->session->userdata('school_level');
        $user_school_level = $this->get_user_school_level($user_id);
        
        if ($user_role === 'school') {
            $school_level = $user_school_level;
            $this->session->set_userdata('school_level', $school_level);
        } elseif ($user_role === 'district') {
            $school_level = 'all';
        } else {
            $school_level = !empty($session_school_level) ? $session_school_level : 'all';
        }
        
        $data['school_level'] = $school_level;
        $data['user_actual_school_level'] = $user_school_level;

        $selected_school = $this->session->userdata('selected_school') ?: '';
        $data['selected_school'] = $selected_school;
        $model_school_name = $school_name;
        if ($user_role === 'district') {
            $model_school_name = '';
        }
        
        $data['user_role'] = $user_role;
        $data['school_id'] = $school_id;
        $data['district'] = $school_district;
        $data['school_name'] = $school_name;
        $data['grade_level_filter'] = $grade_level_filter;
        $data['school_name_filter'] = $school_name_filter;
        $data['district_filter'] = $district_filter;
        $data['available_schools'] = $this->get_available_schools($user_role, $school_id, $school_district);
        $data['available_districts'] = $this->get_available_districts($user_role, $school_district);
        $data['available_grade_levels'] = $this->get_available_grade_levels();
        
        $data['beneficiaries'] = $this->sbfp_beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $model_school_name,
            $school_level,
            $user_role,
            $school_id,
            $school_district,
            $selected_school,
            $grade_level_filter,
            $school_name_filter,
            $district_filter
        );

        $data['baseline_count'] = $this->sbfp_beneficiaries_model->count_by_assessment_with_filter(
            'baseline', 
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school,
            $grade_level_filter,
            $school_name_filter,
            $district_filter
        );
        
        $data['midline_count'] = $this->sbfp_beneficiaries_model->count_by_assessment_with_filter(
            'midline', 
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school,
            $grade_level_filter,
            $school_name_filter,
            $district_filter
        );
        
        $data['endline_count'] = $this->sbfp_beneficiaries_model->count_by_assessment_with_filter(
            'endline', 
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school,
            $grade_level_filter,
            $school_name_filter,
            $district_filter
        );
        
        // Get nutritional stats with filters
        $data['nutritional_stats'] = $this->sbfp_beneficiaries_model->get_nutritional_stats_with_filter(
            $assessment_type,
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school,
            $grade_level_filter,
            $school_name_filter,
            $district_filter
        );

        // Format BMI and height (convert meters to cm)
        foreach ($data['beneficiaries'] as &$student) {
            // BMI: two decimals, truncated
            if (isset($student['bmi'])) {
                $student['bmi'] = $this->format_bmi($student['bmi']);
            }
            // Height: convert meters to centimeters
            if (isset($student['height'])) {
                $student['height'] = $this->format_height_cm($student['height']);
            }
        }
        unset($student);
        
        $normal_count = 0;
        $intervention_count = 0;
        foreach ($data['nutritional_stats'] as $stat) {
            if ($stat['nutritional_status'] == 'Normal') {
                $normal_count = $stat['count'];
            }
            if (in_array($stat['nutritional_status'], ['Severely Wasted', 'Wasted', 'Overweight', 'Obese'])) {
                $intervention_count += $stat['count'];
            }
        }
        $data['normal_count'] = $normal_count;
        $data['intervention_count'] = $intervention_count;
        // Get summary stats (includes 'tall' HFA count)
        $summary_stats = $this->sbfp_beneficiaries_model->get_summary_stats($assessment_type, $model_school_name);
        $data['tall_count'] = ($summary_stats && isset($summary_stats->tall)) ? (int)$summary_stats->tall : 0;
        
        $data['schools'] = $this->sbfp_beneficiaries_model->get_schools_by_role(
            $user_role,
            $school_id,
            $school_district
        );
        $data['school_count'] = count($data['schools']);
        
        $this->load->view('sbfp_beneficiaries', $data);
    }
    
    /**
     * Get available schools for filter dropdown based on user role
     */
    private function get_available_schools($user_role, $school_id, $district) {
        $this->db->select('DISTINCT(school_name)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->where("(sbfp_beneficiary = 'Yes' OR grade_level IN ('Kindergarten','Grade 1'))", NULL, FALSE);

        if ($user_role === 'district' && !empty($district)) {
            $this->db->where('school_district', $district);
        } elseif ($user_role === 'school' && !empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        
        $this->db->order_by('school_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get available districts for filter dropdown
     * District users don't need this filter
     */
    private function get_available_districts($user_role, $user_district) {
        if ($user_role === 'district') {
            return [];
        }
        
        $this->db->select('DISTINCT(school_district)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->where('school_district IS NOT NULL');
        $this->db->where('school_district !=', '');
        $this->db->where("(sbfp_beneficiary = 'Yes' OR grade_level IN ('Kindergarten','Grade 1'))", NULL, FALSE);
        
        $this->db->order_by('school_district', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get available grade levels for filter dropdown
     */
    private function get_available_grade_levels() {
        $this->db->select('DISTINCT(grade_level)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->where('grade_level IS NOT NULL');
        $this->db->where('grade_level !=', '');
        $this->db->where("(sbfp_beneficiary = 'Yes' OR grade_level IN ('Kindergarten','Grade 1'))", NULL, FALSE);
        // Order grade levels using custom sequence
        $this->db->order_by("CASE
            WHEN grade_level = 'Kindergarten' THEN 0
            WHEN grade_level = 'Grade 1' THEN 1
            WHEN grade_level = 'Grade 2' THEN 2
            WHEN grade_level = 'Grade 3' THEN 3
            WHEN grade_level = 'Grade 4' THEN 4
            WHEN grade_level = 'Grade 5' THEN 5
            WHEN grade_level = 'Grade 6' THEN 6
            WHEN grade_level = 'Grade 7' THEN 7
            WHEN grade_level = 'Grade 8' THEN 8
            WHEN grade_level = 'Grade 9' THEN 9
            WHEN grade_level = 'Grade 10' THEN 10
            WHEN grade_level = 'Grade 11' THEN 11
            WHEN grade_level = 'Grade 12' THEN 12
            ELSE 99 END", '', FALSE);
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * AJAX: Set grade level filter
     */
    public function set_grade_level_filter() {
        $grade_level = $this->input->post('grade_level');
        $this->session->set_userdata('grade_level_filter', $grade_level);
        echo json_encode(['success' => true]);
    }
    
    /**
     * AJAX: Set school name filter
     */
    public function set_school_name_filter() {
        $school_name = $this->input->post('school_name');
        $this->session->set_userdata('school_name_filter', $school_name);
        echo json_encode(['success' => true]);
    }
    
    /**
     * AJAX: Set district filter
     */
    public function set_district_filter() {
        $district = $this->input->post('district');
        $this->session->set_userdata('district_filter', $district);
        echo json_encode(['success' => true]);
    }
    
    /**
     * AJAX: Clear all filters
     */
    public function clear_filters() {
        $this->session->unset_userdata('grade_level_filter');
        $this->session->unset_userdata('school_name_filter');
        $this->session->unset_userdata('district_filter');
        $this->session->unset_userdata('selected_school');
        echo json_encode(['success' => true]);
    }

    /**
     * AJAX: Update a simple Yes/No flag for a beneficiary record (parent consent, 4Ps, previous SBFP)
     */
    public function update_flag()
    {
        $this->output->set_content_type('application/json');

        $id = $this->input->post('id');
        $field = $this->input->post('field');
        $value = $this->input->post('value');

        if (empty($id) || empty($field) || !in_array($value, ['Yes', 'No'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        // Map logical field names to possible DB columns. We'll pick the first existing column.
        $candidates = [];
        switch ($field) {
            case 'parent_consent_milk':
                $candidates = ['parent_consent', 'parents_consent', 'parent_consent_for_milk', 'parent_consent_milk'];
                break;
            case 'participation_4ps':
                $candidates = ['participation_4ps', 'participation_in_4ps', 'is_4ps', '4ps_participation'];
                break;
            case 'previous_sbfp':
                $candidates = ['previous_sbfp', 'sbfp_previous', 'previous_beneficiary_sbfp', 'previous_sbfp_beneficiary'];
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown field']);
                return;
        }

        $targetColumn = null;
        foreach ($candidates as $col) {
            if ($this->db->field_exists($col, 'nutritional_assessments')) {
                $targetColumn = $col;
                break;
            }
        }

        if (!$targetColumn) {
            echo json_encode(['success' => false, 'message' => 'Database column for this field does not exist']);
            return;
        }

        try {
            $this->db->where('id', $id);
            $updated = $this->db->update('nutritional_assessments', [ $targetColumn => $value, 'updated_at' => date('Y-m-d H:i:s') ]);
            if ($updated) {
                echo json_encode(['success' => true, 'column' => $targetColumn, 'value' => $value]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get the current school year from database
     */
    private function get_current_school_year() {

        if ($this->db->table_exists('settings')) {
            $this->db->select('setting_value');
            $this->db->from('settings');
            $this->db->where('setting_key', 'current_school_year');
            $query = $this->db->get();
            
            if ($query->num_rows() > 0) {
                $row = $query->row();
                return $row->setting_value;
            }
        }

        if ($this->db->table_exists('sbfp_assessments')) {
            $this->db->select('school_year');
            $this->db->from('sbfp_assessments');
            $this->db->order_by('school_year', 'DESC');
            $this->db->limit(1);
            $query = $this->db->get();
            
            if ($query->num_rows() > 0) {
                $row = $query->row();
                return $row->school_year;
            }
        }

        $current_month = date('n');
        $current_year = date('Y');
        
        if ($current_month >= 8) { 
            return $current_year . '-' . ($current_year + 1);
        } else {
            return ($current_year - 1) . '-' . $current_year;
        }

        return '2025-2026';
    }
    
    /**
     * Get user data directly from database
     */
    private function get_user_data_from_db($user_id) {
        if (!$user_id) {
            return [];
        }

        $this->db->select('role, name, school_id, school_district, school_level');
        $this->db->from('users');
        $this->db->where('id', $user_id);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        return [];
    }
    
    /**
     * Get the user's actual school level from the users table
     */
    private function get_user_school_level($user_id) {
        if (!$user_id) {
            return 'all';
        }
        
        $this->db->select('school_level');
        $this->db->from('users');
        $this->db->where('id', $user_id);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            
            if (isset($row->school_level) && !empty($row->school_level)) {
                $school_level = trim($row->school_level);
                $school_level_lower = strtolower($school_level);
                
                if ($school_level_lower === 'elementary') {
                    return 'elementary';
                } elseif ($school_level_lower === 'secondary') {
                    return 'secondary';
                } elseif ($school_level_lower === 'integrated') {
                    return 'integrated';
                } elseif ($school_level_lower === 'stand alone shs' || 
                          $school_level_lower === 'standalone_shs' || 
                          $school_level_lower === 'shs') {
                    return 'Stand Alone SHS';
                }
            }
        }
        
        return 'all';
    }
    
    /**
     * AJAX: Set assessment type
     */
    public function set_assessment_type() {
        $type = $this->input->post('assessment_type');
        if (in_array($type, ['baseline', 'midline', 'endline'])) {
            $this->session->set_userdata('assessment_type', $type);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid assessment type']);
        }
    }
    
    /**
     * AJAX: Set school level filter - Modified for district users
     */
    public function set_school_level() {
        $level = $this->input->post('school_level');
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary', 'Stand Alone SHS'];
        
        if (in_array($level, $valid_levels)) {
            $user_role = $this->session->userdata('role');

            if ($user_role === 'district' && $level !== 'all') {
                echo json_encode(['success' => false, 'message' => 'District accounts can only view all schools']);
                return;
            }
            
            $this->session->set_userdata('school_level', $level);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid school level']);
        }
    }
    
    /**
     * AJAX: Set selected school filter
     */
    public function set_selected_school() {
        $school = $this->input->post('school_name');
        $this->session->set_userdata('selected_school', $school);
        echo json_encode(['success' => true]);
    }
        
    /**
     * Export to Excel using template file
     */
    public function export_excel() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        log_message('debug', '=== EXPORT EXCEL STARTED ===');
        log_message('debug', 'Time: ' . date('Y-m-d H:i:s'));

        if (!$this->session->userdata('logged_in')) {
            log_message('error', 'Export failed: User not logged in');
            redirect('login');
        }

        try {
            $auth_user = $this->session->userdata('auth_user');
            $user_id = $this->session->userdata('user_id');

            // Get user role and information (same as in index method)
            $user_role = 'school';
            $school_name = '';
            $school_id = '';
            $school_district = '';

            if (!empty($auth_user)) {
                if (is_object($auth_user)) {
                    $user_role = !empty($auth_user->role) ? $auth_user->role : $user_role;
                    $school_name = !empty($auth_user->name) ? $auth_user->name : $school_name;
                    $school_id = !empty($auth_user->school_id) ? $auth_user->school_id : $school_id;
                    $school_district = !empty($auth_user->school_district) ? $auth_user->school_district : $school_district;
                } elseif (is_array($auth_user)) {
                    $user_role = !empty($auth_user['role']) ? $auth_user['role'] : $user_role;
                    $school_name = !empty($auth_user['name']) ? $auth_user['name'] : $school_name;
                    $school_id = !empty($auth_user['school_id']) ? $auth_user['school_id'] : $school_id;
                    $school_district = !empty($auth_user['school_district']) ? $auth_user['school_district'] : $school_district;
                }
            }

            // Check session for role
            $session_role = $this->session->userdata('role');
            $session_school_name = $this->session->userdata('school_name');
            $session_district = $this->session->userdata('school_district');

            if (empty($user_role) || $user_role === 'school') {
                if (!empty($session_role)) {
                    $user_role = $session_role;
                }
            }

            if (empty($school_name) && !empty($session_school_name)) {
                $school_name = $session_school_name;
            }

            if (!empty($session_district)) {
                $school_district = $session_district;
                log_message('debug', 'Using district from direct session: ' . $school_district);
            }

            // Fallback to database if needed
            if (empty($school_district) || (empty($school_name) && $user_role === 'school') || empty($user_role)) {
                $user_data = $this->get_user_data_from_db($user_id);
                if (!empty($user_data)) {
                    $user_role = !empty($user_data['role']) ? $user_data['role'] : $user_role;
                    $school_name = !empty($user_data['name']) ? $user_data['name'] : $school_name;
                    $school_id = !empty($user_data['school_id']) ? $user_data['school_id'] : $school_id;
                    $school_district = !empty($user_data['school_district']) ? $user_data['school_district'] : $school_district;
                }
            }

            // Special handling for district users with empty district
            if ($user_role === 'district' && empty($school_district)) {
                log_message('debug', 'WARNING: District user with empty district! Attempting to recover...');
                $this->db->select('school_district');
                $this->db->from('users');
                $this->db->where('id', $user_id);
                $district_query = $this->db->get();
                if ($district_query->num_rows() > 0) {
                    $district_row = $district_query->row();
                    $school_district = !empty($district_row->school_district) ? $district_row->school_district : '';
                    log_message('debug', 'Recovered district from database: ' . $school_district);
                }
            }

            log_message('debug', 'FINAL School District for export: ' . $school_district);

            $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
            $school_level = $this->session->userdata('school_level') ?: 'all';
            $selected_school = $this->session->userdata('selected_school') ?: '';
            $district_filter = $this->session->userdata('district_filter') ?: '';
            if ($user_role === 'district') {
                $school_level = 'all';
            }

            $school_year = $this->get_current_school_year();
            $model_school_name = $school_name;
            if ($user_role === 'district') {
                $model_school_name = '';
            }

            log_message('debug', 'Fetching beneficiaries from model with role filters...');
            $beneficiaries = $this->sbfp_beneficiaries_model->get_beneficiaries(
                $assessment_type,
                $model_school_name,
                $school_level,
                $user_role,
                $school_id,
                $school_district,
                $selected_school,
                $district_filter
            );

            log_message('debug', 'Beneficiaries found for export: ' . count($beneficiaries));

            if (empty($beneficiaries)) {
                log_message('error', 'No beneficiaries found for export');
                $this->session->set_flashdata('error', 'No data found for the specified criteria');
                redirect('sbfp_beneficiaries_controller');
                return;
            }

            // Retrieve local flags from POST (sent by JavaScript)
            $localFlags = [];
            $localFlagsRaw = $this->input->post('local_flags');
            if (!empty($localFlagsRaw)) {
                $decoded = json_decode($localFlagsRaw, true);
                if (is_array($decoded)) {
                    $localFlags = $decoded;
                    log_message('debug', 'Local flags received for export: ' . print_r($localFlags, true));
                }
            }

            // Load PhpSpreadsheet
            log_message('debug', 'Loading PhpSpreadsheet...');
            $autoload_path = APPPATH . '../vendor/autoload.php';
            if (!file_exists($autoload_path)) {
                log_message('error', 'Autoload file not found at: ' . $autoload_path);
                show_error('Vendor autoload not found. Please run composer install.');
                return;
            }
            require_once $autoload_path;
            log_message('debug', 'PhpSpreadsheet loaded successfully');

            // ===== LOAD TEMPLATE =====
            $templatePath = FCPATH . ASSETS_PATH . '/excel_templates/sbfp1a.xlsx';
            log_message('debug', 'Template path: ' . $templatePath);
            if (!file_exists($templatePath)) {
                log_message('error', 'Template file not found at: ' . $templatePath);
                show_error('Template file not found. Please ensure sbfp1a.xlsx exists at: ' . $templatePath);
                return;
            }

            log_message('debug', 'Template file found, loading...');
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
                log_message('debug', 'Working with sheet: ' . $sheet->getTitle());

                // Set default font to Arial
                $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
                log_message('debug', 'Set default font to Arial for entire workbook');

                // Update header information
                log_message('debug', 'Updating header information...');
                $sheet->getStyle('A1')->getFont()->setName('Arial')->setBold(true);
                $sheet->getStyle('A2')->getFont()->setName('Arial');

                $title = 'Master List Beneficiaries for School-Based Feeding Program (SBFP) ( SY ' . $school_year . ' ) - ' . strtoupper($assessment_type);
                $sheet->setCellValue('A4', $title);
                $sheet->getStyle('A4')->getFont()->setName('Arial')->setBold(true);
                log_message('debug', 'Set title: ' . $title);

                $sheet->getStyle('A6')->getFont()->setName('Arial');
                $sheet->getStyle('A7')->getFont()->setName('Arial');

                if ($user_role === 'school') {
                    $display_school = $school_name;
                } elseif ($user_role === 'district') {
                    $display_school = 'District: ' . $school_district . ' - All Schools';
                } elseif (!empty($selected_school)) {
                    $display_school = $selected_school;
                } else {
                    $display_school = 'All Schools (Division Level)';
                }
                $sheet->getStyle('A8')->getFont()->setName('Arial');
                log_message('debug', 'Set school name: ' . $display_school);

                if ($user_role === 'school' && !empty($school_id)) {
                    $display_school_id = $school_id;
                } else {
                    $display_school_id = 'N/A (Multiple Schools)';
                }
                $sheet->getStyle('A9')->getFont()->setName('Arial');
                log_message('debug', 'Set school ID: ' . $display_school_id);

                // Headers are in rows 11-12
                $sheet->getStyle('A11:P12')->getFont()->setName('Arial')->setBold(true);

                // ===== CLEAR EXISTING DATA ROWS (rows 13 to 1000, columns A to T) =====
                log_message('debug', 'Clearing existing data rows from 13 to 1000...');
                for ($row = 13; $row <= 1000; $row++) {
                    $sheet->setCellValue('A' . $row, '');
                    $sheet->setCellValue('B' . $row, '');
                    $sheet->setCellValue('C' . $row, '');
                    $sheet->setCellValue('D' . $row, '');
                    $sheet->setCellValue('E' . $row, '');
                    $sheet->setCellValue('F' . $row, '');
                    $sheet->setCellValue('G' . $row, '');
                    $sheet->setCellValue('H' . $row, '');
                    $sheet->setCellValue('I' . $row, '');
                    $sheet->setCellValue('J' . $row, '');
                    $sheet->setCellValue('K' . $row, '');
                    $sheet->setCellValue('L' . $row, '');
                    $sheet->setCellValue('M' . $row, '');
                    $sheet->setCellValue('N' . $row, '');
                    $sheet->setCellValue('O' . $row, '');
                    $sheet->setCellValue('P' . $row, '');
                    $sheet->setCellValue('Q' . $row, '');
                    $sheet->setCellValue('R' . $row, '');
                    $sheet->setCellValue('S' . $row, '');
                    $sheet->setCellValue('T' . $row, '');
                }

                // ===== POPULATE DATA STARTING FROM ROW 14 =====
                log_message('debug', 'Populating data rows starting from row 14...');
                $startRow = 14;
                $counter = 1;
                $populatedRows = 0;

                foreach ($beneficiaries as $student) {
                    $currentRow = $startRow + ($counter - 1);

                    // --- Columns B to M (unchanged) ---
                    $sheet->setCellValue('B' . $currentRow, $counter);
                    $sheet->setCellValue('C' . $currentRow, $student['name'] ?? '');
                    $sex = isset($student['sex']) ? substr(strtoupper(trim($student['sex'])), 0, 1) : '';
                    $sheet->setCellValue('D' . $currentRow, $sex);
                    $grade = $student['grade_level'] ?? '';
                    $section = $student['section'] ?? '';
                    $gradeSection = $grade . ($section ? '/' . $section : '');
                    $sheet->setCellValue('E' . $currentRow, $gradeSection);
                    if (!empty($student['birthday'])) {
                        $sheet->setCellValue('F' . $currentRow, date('m/d/Y', strtotime($student['birthday'])));
                    }
                    if (!empty($student['date_of_weighing'])) {
                        $sheet->setCellValue('G' . $currentRow, date('m/d/Y', strtotime($student['date_of_weighing'])));
                    }
                    $sheet->setCellValue('H' . $currentRow, $student['age'] ?? '');
                    $weight = !empty($student['weight']) ? number_format($student['weight'], 1) : '';
                    $sheet->setCellValue('I' . $currentRow, $weight);
                    $heightRaw = $student['height'] ?? '';
                    $heightCm = is_numeric($heightRaw) && $heightRaw < 3 ? $heightRaw * 100 : $heightRaw;
                    $height = !empty($heightCm) ? number_format($heightCm, 1) : '';
                    $sheet->setCellValue('J' . $currentRow, $height);
                    $bmi = !empty($student['bmi']) ? number_format($student['bmi'], 1) : '';
                    $sheet->setCellValue('K' . $currentRow, $bmi);
                    $sheet->setCellValue('L' . $currentRow, $student['nutritional_status'] ?? '');
                    $sheet->setCellValue('M' . $currentRow, $student['height_for_age'] ?? '');

                    // ----- Retrieve default values from database (fallback column names) -----
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

                    // ----- Apply local overrides from JavaScript (if any) -----
                    $studentId = $student['id'] ?? ($student['assessment_id'] ?? '');
                    if (!empty($studentId) && isset($localFlags[$studentId]) && is_array($localFlags[$studentId])) {
                        $over = $localFlags[$studentId];
                        if (isset($over['classification_of_beneficiary'])) $classification = $over['classification_of_beneficiary'];
                        if (isset($over['pregnant'])) $pregnant = $over['pregnant'];
                        if (isset($over['with_0_1_year_old_child'])) $child01 = $over['with_0_1_year_old_child'];
                        if (isset($over['dewormed'])) $dewormed = $over['dewormed'];
                        if (isset($over['parent_consent_milk'])) $parentConsent = $over['parent_consent_milk'];
                        if (isset($over['participation_4ps'])) $ps4 = $over['participation_4ps'];
                        if (isset($over['previous_sbfp'])) $prevSbfp = $over['previous_sbfp'];
                    }

                    // ----- Write the (possibly overridden) values to Excel columns N through T -----
                    $sheet->setCellValue('N' . $currentRow, $classification);   // Classification of Beneficiary
                    $sheet->setCellValue('O' . $currentRow, $pregnant);        // Pregnant
                    $sheet->setCellValue('P' . $currentRow, $child01);         // With 0-1 year-old child
                    $sheet->setCellValue('Q' . $currentRow, $dewormed);        // Dewormed
                    $sheet->setCellValue('R' . $currentRow, $parentConsent);   // Parent's consent for milk
                    $sheet->setCellValue('S' . $currentRow, $ps4);             // Participation in 4Ps
                    $sheet->setCellValue('T' . $currentRow, $prevSbfp);        // Beneficiary of SBFP in Previous Years

                    $counter++;
                    $populatedRows++;

                    if ($currentRow > 1000) {
                        log_message('warning', 'Reached maximum row limit (1000)');
                        break;
                    }
                }

                log_message('debug', 'Populated rows: ' . $populatedRows);
                log_message('debug', 'Final counter: ' . $counter);

                if ($populatedRows > 0) {
                    $lastPopulatedRow = $startRow + $populatedRows - 1;
                    $dataRange = 'A' . $startRow . ':T' . $lastPopulatedRow;
                    $sheet->getStyle($dataRange)->getFont()->setName('Arial')->setSize(10);
                    $sheet->getStyle($dataRange)->getFont()->getColor()->setARGB('FF000000');
                    log_message('debug', 'Applied Arial font to range: ' . $dataRange);
                }

                // Generate filename
                $filename = 'SBFP_Form1A_Beneficiaries_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
                if ($user_role === 'school' && !empty($school_name)) {
                    $schoolPart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $school_name);
                    $filename = 'SBFP_Form1A_' . $schoolPart . '_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
                } elseif ($user_role === 'district' && !empty($school_district)) {
                    $districtPart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $school_district);
                    $filename = 'SBFP_Form1A_District_' . $districtPart . '_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
                } elseif (!empty($selected_school)) {
                    $schoolPart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $selected_school);
                    $filename = 'SBFP_Form1A_' . $schoolPart . '_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
                }
                log_message('debug', 'Filename: ' . $filename);

                // Clear output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Set headers
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Pragma: public');

                log_message('debug', 'Headers set, creating writer...');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');

                log_message('debug', 'File written to output');
                log_message('debug', '=== EXPORT EXCEL COMPLETED SUCCESSFULLY ===');
                exit;

            } catch (Exception $e) {
                log_message('error', 'Error loading/processing template: ' . $e->getMessage());
                log_message('error', 'Exception trace: ' . $e->getTraceAsString());

                // Fallback to creating from scratch
                log_message('debug', 'Falling back to createSpreadsheetFromScratch()');
                $spreadsheet = $this->createSpreadsheetFromScratch($school_year);
                $sheet = $spreadsheet->getActiveSheet();

                $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

                if ($user_role === 'school') {
                    $display_school = $school_name;
                } elseif ($user_role === 'district') {
                    $display_school = 'District: ' . $school_district . ' - All Schools';
                } elseif (!empty($selected_school)) {
                    $display_school = $selected_school;
                } else {
                    $display_school = 'All Schools (Division Level)';
                }
                $sheet->setCellValue('A8', 'Name of School / School District: ' . $display_school);

                if ($user_role === 'school' && !empty($school_id)) {
                    $display_school_id = $school_id;
                } else {
                    $display_school_id = 'N/A (Multiple Schools)';
                }
                $sheet->setCellValue('A9', 'School ID Number: ' . $display_school_id);

                $title = 'Master List Beneficiaries for School-Based Feeding Program (SBFP) ( SY ' . $school_year . ' ) - ' . strtoupper($assessment_type);
                $sheet->setCellValue('A4', $title);

                $sheet->getStyle('A1:P12')->getFont()->setName('Arial');

                // Populate data for fallback
                $startRow = 13;
                $counter = 1;
                foreach ($beneficiaries as $student) {
                    $currentRow = $startRow + ($counter - 1);
                    $sheet->setCellValue('A' . $currentRow, $counter);
                    $sheet->setCellValue('B' . $currentRow, $student['name'] ?? '');
                    $sex = isset($student['sex']) ? substr(strtoupper(trim($student['sex'])), 0, 1) : '';
                    $sheet->setCellValue('C' . $currentRow, $sex);
                    $sheet->setCellValue('D' . $currentRow, ($student['grade_level'] ?? '') . '/' . ($student['section'] ?? ''));
                    if (!empty($student['birthday'])) {
                        $sheet->setCellValue('E' . $currentRow, date('m/d/Y', strtotime($student['birthday'])));
                    }
                    if (!empty($student['date_of_weighing'])) {
                        $sheet->setCellValue('F' . $currentRow, date('m/d/Y', strtotime($student['date_of_weighing'])));
                    }
                    $sheet->setCellValue('G' . $currentRow, $student['age'] ?? '');
                    $sheet->setCellValue('H' . $currentRow, !empty($student['weight']) ? number_format($student['weight'], 1) : '');
                    $sheet->setCellValue('I' . $currentRow, !empty($student['height']) ? $this->format_height_cm($student['height']) : '');
                    $sheet->setCellValue('J' . $currentRow, !empty($student['bmi']) ? $this->format_bmi($student['bmi']) : '');
                    $sheet->setCellValue('K' . $currentRow, $student['nutritional_status'] ?? '');
                    $sheet->setCellValue('L' . $currentRow, $student['height_for_age'] ?? '');
                    $counter++;
                }

                $lastRow = $startRow + count($beneficiaries) - 1;
                $sheet->getStyle('A' . $startRow . ':P' . $lastRow)->getFont()->setName('Arial')->setSize(10);

                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }
        } catch (Exception $e) {
            log_message('error', 'EXPORT EXCEL EXCEPTION: ' . $e->getMessage());
            log_message('error', 'Exception trace: ' . $e->getTraceAsString());
            show_error('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Create spreadsheet from scratch (fallback method) - Keep as backup
     */
    private function createSpreadsheetFromScratch($school_year = '2025-2026') {
        log_message('debug', 'Creating spreadsheet from scratch with school year: ' . $school_year);
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // === HEADER SECTION ===
        
        // Row 1-2: Department of Education
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'Department of Education');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', 'Region V-Bicol');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 4: Title - USING DYNAMIC SCHOOL YEAR
        $sheet->mergeCells('A4:P4');
        $sheet->setCellValue('A4', 'Master List Beneficiaries for School-Based Feeding Program (SBFP) ( SY ' . $school_year . ' ) - BASELINE');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 6: Division
        $sheet->mergeCells('A6:P6');
        $sheet->setCellValue('A6', 'Division: MASBATE PROVINCE');
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 7: City/Municipality/Barangay
        $sheet->mergeCells('A7:P7');
        $sheet->setCellValue('A7', 'City/ Municipality/Barangay:');
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 8: Name of School
        $sheet->mergeCells('A8:P8');
        $sheet->setCellValue('A8', 'Name of School / School District: ');
        $sheet->getStyle('A8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 9: School ID
        $sheet->mergeCells('A9:P9');
        $sheet->setCellValue('A9', 'School ID Number: ');
        $sheet->getStyle('A9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // === TABLE HEADERS ===
        // Row 11: Column Headers (Main)
        $headers = [
            'No.', 'Name', 'Sex', 'Grade/ Section', 'Date of Birth (MM/DD/YYYY)',
            'Date of Weighing / Measuring (MM/DD/YYYY)', 'Age in Years / Months',
            'Weight (Kg)', 'Height (cm)', 'BMI for 6 y.o. and above',
            'Nutritional Status (NS)', '', "Parent's consent for milk? (Y or N)",
            'Participation in 4Ps (Y or N)', 'Beneficiary of SBFP in Previous Years (Y or N)'
        ];
        
        $sheet->fromArray($headers, null, 'A11');
        
        // Row 12: Sub-headers for Nutritional Status
        $sheet->setCellValue('K12', 'BMI-A');
        $sheet->setCellValue('L12', 'HFA');
        
        // Apply styles to header cells
        $sheet->getStyle('K12')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000']
            ],
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);
        
        $sheet->getStyle('L12')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000']
            ],
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);
        
        // Merge cells for Nutritional Status header
        $sheet->mergeCells('K11:L11');
        $sheet->setCellValue('K11', 'Nutritional Status (NS)');
        
        // Style main headers
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        
        $sheet->getStyle('A11:P12')->applyFromArray($headerStyle);
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(5);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(10);
        $sheet->getColumnDimension('K')->setWidth(10);
        $sheet->getColumnDimension('L')->setWidth(10);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(25);
        $sheet->getColumnDimension('P')->setWidth(5);
        
        log_message('debug', 'Spreadsheet created from scratch with school year: ' . $school_year);
        
        return $spreadsheet;
    }
    
    /**
     * Print report
     */
    public function print_report() {
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }

        $auth_user = $this->session->userdata('auth_user');
        $user_id = $this->session->userdata('user_id');

        $user_role = 'school';
        $school_name = '';
        $school_id = '';
        $school_district = '';
        
        if (!empty($auth_user)) {
            if (is_object($auth_user)) {
                $user_role = !empty($auth_user->role) ? $auth_user->role : $user_role;
                $school_name = !empty($auth_user->name) ? $auth_user->name : $school_name;
                $school_id = !empty($auth_user->school_id) ? $auth_user->school_id : $school_id;
                $school_district = !empty($auth_user->school_district) ? $auth_user->school_district : $school_district;
            } elseif (is_array($auth_user)) {
                $user_role = !empty($auth_user['role']) ? $auth_user['role'] : $user_role;
                $school_name = !empty($auth_user['name']) ? $auth_user['name'] : $school_name;
                $school_id = !empty($auth_user['school_id']) ? $auth_user['school_id'] : $school_id;
                $school_district = !empty($auth_user['school_district']) ? $auth_user['school_district'] : $school_district;
            }
        }

        $session_district = $this->session->userdata('school_district');
        if (!empty($session_district)) {
            $school_district = $session_district;
        }

        $model_school_name = $school_name;
        if ($user_role === 'district') {
            $model_school_name = '';
        }
        
        $data = array();
        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $data['assessment_type'] = $assessment_type;
        $data['is_baseline'] = ($assessment_type == 'baseline');
        $data['is_midline'] = ($assessment_type == 'midline');
        $data['is_endline'] = ($assessment_type == 'endline');

        // Get the new filters
        $grade_level_filter = $this->session->userdata('grade_level_filter') ?: '';
        $school_name_filter = $this->session->userdata('school_name_filter') ?: '';
        $district_filter = $this->session->userdata('district_filter') ?: '';

        // GET SCHOOL YEAR FOR PRINT REPORT
        $data['school_year'] = $this->get_current_school_year();

        $school_level = $this->session->userdata('school_level') ?: 'all';
        if ($user_role === 'district') {
            $school_level = 'all';
        }
        $data['school_level'] = $school_level;
        
        $selected_school = $this->session->userdata('selected_school') ?: '';
        $data['selected_school'] = $selected_school;

        $data['user_role'] = $user_role;
        $data['school_name'] = $school_name;
        
        $data['beneficiaries'] = $this->sbfp_beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $model_school_name,
            $school_level,
            $user_role,
            $school_id,
            $school_district,
            $selected_school,
            $district_filter
        );
        
        $this->load->view('print_sbfp_beneficiaries', $data);
    }
        /**
         * Truncate BMI to 2 decimal places without rounding.
         * Returns empty string if value is not numeric.
         */
        private function format_bmi($bmi) {
            if (!is_numeric($bmi)) {
                return '';
            }
            $truncated = floor($bmi * 100) / 100;
            return number_format($truncated, 2, '.', '');
        }

        /**
         * Convert height from meters to centimeters and format to 2 decimals.
         * Returns empty string if value is not numeric.
         */
        private function format_height_cm($height_meters) {
            if (!is_numeric($height_meters)) {
                return '';
            }
            $cm = $height_meters * 100;
            return number_format($cm, 1, '.', '');
        }
}