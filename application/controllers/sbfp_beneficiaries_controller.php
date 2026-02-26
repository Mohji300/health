<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class sbfp_beneficiaries_controller extends CI_Controller {
    
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

        $user_role = 'school'; // default
        $school_name = '';
        $school_id = '';
        $school_district = '';

        if (!empty($auth_user)) {
            // Handle object
            if (is_object($auth_user)) {
                $user_role = !empty($auth_user->role) ? $auth_user->role : $user_role;
                $school_name = !empty($auth_user->name) ? $auth_user->name : $school_name;
                $school_id = !empty($auth_user->school_id) ? $auth_user->school_id : $school_id;
                $school_district = !empty($auth_user->school_district) ? $auth_user->school_district : $school_district;
            } 
            // Handle array
            elseif (is_array($auth_user)) {
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
            log_message('debug', 'Using district from direct session: ' . $school_district);
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
        
        log_message('debug', 'FINAL School District for user: ' . $school_district);
        
        //GET USER'S SCHOOL LEVEL
        $user_school_level = $this->get_user_school_level($user_id);
        
        //ASSESSMENT TYPE
        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $data['assessment_type'] = $assessment_type;
        $data['is_baseline'] = ($assessment_type == 'baseline');
        $data['is_midline'] = ($assessment_type == 'midline');
        $data['is_endline'] = ($assessment_type == 'endline');
        
        // GET SCHOOL YEAR FROM DATABASE
        $data['school_year'] = $this->get_current_school_year();
        
        // SCHOOL LEVEL FILTERING FOR district users
        $session_school_level = $this->session->userdata('school_level');
        
        if ($user_role === 'school') {
            $school_level = $user_school_level;
            $this->session->set_userdata('school_level', $school_level);
        } elseif ($user_role === 'district') {
            $school_level = 'all';
            log_message('debug', 'District user - forcing school_level to "all"');
        } else {
            $school_level = !empty($session_school_level) ? $session_school_level : 'all';
        }
        
        $data['school_level'] = $school_level;
        $data['user_actual_school_level'] = $user_school_level;
        
        //SELECTED SCHOOL FILTER (for division/admin)
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

        $data['beneficiaries'] = $this->sbfp_beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $model_school_name,
            $school_level,      
            $user_role,
            $school_id,
            $school_district,    
            $selected_school
        );

        $data['baseline_count'] = $this->sbfp_beneficiaries_model->count_by_assessment_with_filter(
            'baseline', 
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school
        );
        
        $data['midline_count'] = $this->sbfp_beneficiaries_model->count_by_assessment_with_filter(
            'midline', 
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school
        );
        
        $data['endline_count'] = $this->sbfp_beneficiaries_model->count_by_assessment_with_filter(
            'endline', 
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school
        );

        $data['nutritional_stats'] = $this->sbfp_beneficiaries_model->get_nutritional_stats_with_filter(
            $assessment_type,
            $model_school_name,
            $school_level,       
            $user_role,
            $school_id,
            $school_district,
            $selected_school
        );
        
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

        $data['schools'] = $this->sbfp_beneficiaries_model->get_schools_by_role(
            $user_role,
            $school_id,
            $school_district
        );
        $data['school_count'] = count($data['schools']);

        // Load the view ONCE with all data
        $this->load->view('sbfp_beneficiaries', $data);
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
     * With proper template usage and Arial font
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
            
            log_message('debug', 'User ID: ' . $user_id);
            
            // Get user role and information (same as in index method)
            $user_role = 'school'; // default
            $school_name = '';
            $school_id = '';
            $school_district = '';

            if (!empty($auth_user)) {
                // Handle object
                if (is_object($auth_user)) {
                    $user_role = !empty($auth_user->role) ? $auth_user->role : $user_role;
                    $school_name = !empty($auth_user->name) ? $auth_user->name : $school_name;
                    $school_id = !empty($auth_user->school_id) ? $auth_user->school_id : $school_id;
                    $school_district = !empty($auth_user->school_district) ? $auth_user->school_district : $school_district;
                } 
                // Handle array
                elseif (is_array($auth_user)) {
                    $user_role = !empty($auth_user['role']) ? $auth_user['role'] : $user_role;
                    $school_name = !empty($auth_user['name']) ? $auth_user['name'] : $school_name;
                    $school_id = !empty($auth_user['school_id']) ? $auth_user['school_id'] : $school_id;
                    $school_district = !empty($auth_user['school_district']) ? $auth_user['school_district'] : $school_district;
                }
            }

            // Check session for role (same as index method)
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

            // Fallback to database if needed (same as index method)
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
            
            // Get session filters (same as index method)
            $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
            $school_level = $this->session->userdata('school_level') ?: 'all';
            $selected_school = $this->session->userdata('selected_school') ?: '';
            
            // For district users, ensure school_level is 'all'
            if ($user_role === 'district') {
                $school_level = 'all';
                log_message('debug', 'District user - forcing school_level to "all" for export');
            }
            
            log_message('debug', 'Export filters - Assessment Type: ' . $assessment_type);
            log_message('debug', 'Export filters - School Level: ' . $school_level);
            log_message('debug', 'Export filters - Selected School: ' . $selected_school);
            
            // GET SCHOOL YEAR FOR EXPORT
            $school_year = $this->get_current_school_year();
            log_message('debug', 'School Year: ' . $school_year);
            
            // Determine what to pass to the model (same as index method)
            $model_school_name = $school_name;
            if ($user_role === 'district') {
                $model_school_name = '';
            }
            
            // Get the data with the SAME filtering logic as the index method
            log_message('debug', 'Fetching beneficiaries from model with role filters...');
            $beneficiaries = $this->sbfp_beneficiaries_model->get_beneficiaries(
                $assessment_type,
                $model_school_name,
                $school_level,
                $user_role,
                $school_id,
                $school_district,
                $selected_school
            );
            
            log_message('debug', 'Beneficiaries found for export: ' . count($beneficiaries));
            
            if (empty($beneficiaries)) {
                log_message('error', 'No beneficiaries found for export');
                // Set flash message and redirect back
                $this->session->set_flashdata('error', 'No data found for the specified criteria');
                redirect('sbfp_beneficiaries_controller');
                return;
            }

            if (count($beneficiaries) > 0) {
                log_message('debug', 'First beneficiary: ' . print_r($beneficiaries[0], true));
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
            $templatePath = FCPATH . 'assets/templates/sbfp_form1a_template.xlsx';
            log_message('debug', 'Template path: ' . $templatePath);
            
            if (!file_exists($templatePath)) {
                log_message('error', 'Template file not found at: ' . $templatePath);
                show_error('Template file not found. Please ensure sbfp_form1a_template.xlsx exists in assets/templates/');
                return;
            }
            
            log_message('debug', 'Template file found, loading...');
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
                
                // ===== WORK WITH SBFP-FORM_1A SHEET (First Sheet) =====
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
                log_message('debug', 'Working with sheet: ' . $sheet->getTitle());
                
                // ===== SET DEFAULT FONT TO ARIAL FOR ENTIRE WORKBOOK =====
                $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
                log_message('debug', 'Set default font to Arial for entire workbook');
                
                // ===== UPDATE HEADER INFORMATION =====
                log_message('debug', 'Updating header information...');
                
                // Row 1: Department of Education (already in template, but ensure font is Arial)
                $sheet->getStyle('A1')->getFont()->setName('Arial')->setBold(true);
                
                // Row 2: Region V-Bicol (already in template)
                $sheet->getStyle('A2')->getFont()->setName('Arial');
                
                // Row 4: Title - Update with school year and assessment type
                $title = 'Master List Beneficiaries for School-Based Feeding Program (SBFP) ( SY ' . $school_year . ' ) - ' . strtoupper($assessment_type);
                $sheet->setCellValue('A4', $title);
                $sheet->getStyle('A4')->getFont()->setName('Arial')->setBold(true);
                log_message('debug', 'Set title: ' . $title);
                
                // Row 6: Division
                $sheet->getStyle('A6')->getFont()->setName('Arial');
                
                // Row 7: City/Municipality/Barangay
                $sheet->getStyle('A7')->getFont()->setName('Arial');
                
                // Row 8: Name of School / School District - Update based on role
                if ($user_role === 'school') {
                    $display_school = $school_name;
                } elseif ($user_role === 'district') {
                    $display_school = 'District: ' . $school_district . ' - All Schools';
                } elseif (!empty($selected_school)) {
                    $display_school = $selected_school;
                } else {
                    $display_school = 'All Schools (Division Level)';
                }
                //$sheet->setCellValue('A8', 'Name of School / School District: ' . $display_school);
                $sheet->getStyle('A8')->getFont()->setName('Arial');
                log_message('debug', 'Set school name: ' . $display_school);
                
                // Row 9: School ID Number - Update based on role
                if ($user_role === 'school' && !empty($school_id)) {
                    $display_school_id = $school_id;
                } else {
                    $display_school_id = 'N/A (Multiple Schools)';
                }
                //$sheet->setCellValue('A9', 'School ID Number: ' . $display_school_id);
                $sheet->getStyle('A9')->getFont()->setName('Arial');
                log_message('debug', 'Set school ID: ' . $display_school_id);
                
                // Headers are in rows 11-12
                $sheet->getStyle('A11:P12')->getFont()->setName('Arial')->setBold(true);
                
                // ===== CLEAR EXISTING DATA ROWS =====
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
                }
                
                // ===== POPULATE DATA STARTING FROM ROW 13 =====
                log_message('debug', 'Populating data rows starting from row 13...');
                $startRow = 14;
                $counter = 1;
                $populatedRows = 0;
                
                foreach ($beneficiaries as $student) {
                    $currentRow = $startRow + ($counter - 1);
                    
                    // Debug first row
                    if ($counter === 1) {
                        log_message('debug', 'Populating first row at row: ' . $currentRow);
                    }
                    
                    // Column B: No.
                    $sheet->setCellValue('B' . $currentRow, $counter);
                    
                    // Column C: Name
                    $name = $student['name'] ?? '';
                    $sheet->setCellValue('C' . $currentRow, $name);
                    
                    // Column D: Sex (first letter only - M or F)
                    $sex = isset($student['sex']) ? substr(strtoupper(trim($student['sex'])), 0, 1) : '';
                    if (!in_array($sex, ['M', 'F'])) {
                        $sex = '';
                    }
                    $sheet->setCellValue('D' . $currentRow, $sex);
                    
                    // Column E: Grade/Section
                    $grade = $student['grade_level'] ?? '';
                    $section = $student['section'] ?? '';
                    $grade_section = $grade;
                    if (!empty($grade) && !empty($section)) {
                        $grade_section = $grade . '/' . $section;
                    } elseif (!empty($section)) {
                        $grade_section = $section;
                    }
                    $sheet->setCellValue('E' . $currentRow, $grade_section);
                    
                    // Column F: Date of Birth (MM/DD/YYYY)
                    if (!empty($student['birthday'])) {
                        try {
                            $dob = date('m/d/Y', strtotime($student['birthday']));
                            $sheet->setCellValue('F' . $currentRow, $dob);
                        } catch (Exception $e) {
                            log_message('error', 'Error formatting DOB: ' . $e->getMessage());
                        }
                    }
                    
                    // Column G: Date of Weighing (MM/DD/YYYY)
                    if (!empty($student['date_of_weighing'])) {
                        try {
                            $weighing_date = date('m/d/Y', strtotime($student['date_of_weighing']));
                            $sheet->setCellValue('G' . $currentRow, $weighing_date);
                        } catch (Exception $e) {
                            log_message('error', 'Error formatting weighing date: ' . $e->getMessage());
                        }
                    }
                    
                    // Column H: Age (Years/Months)
                    $sheet->setCellValue('H' . $currentRow, $student['age'] ?? '');
                    
                    // Column I: Weight (Kg)
                    $weight = !empty($student['weight']) ? number_format($student['weight'], 1) : '';
                    $sheet->setCellValue('I' . $currentRow, $weight);
                    
                    // Column J: Height (cm)
                    $height = !empty($student['height']) ? number_format($student['height'], 1) : '';
                    $sheet->setCellValue('J' . $currentRow, $height);
                    
                    // Column K: BMI
                    $bmi = !empty($student['bmi']) ? number_format($student['bmi'], 1) : '';
                    $sheet->setCellValue('K' . $currentRow, $bmi);
                    
                    // Column L: BMI-A (Nutritional Status)
                    $sheet->setCellValue('L' . $currentRow, $student['nutritional_status'] ?? '');
                    $sheet->setCellValue('L13', 'BMI-A');
                    $sheet->setCellValue('M13', 'HFA');
                    
                    // Column M: HFA (Height for Age)
                    $sheet->setCellValue('M' . $currentRow, $student['height_for_age'] ?? '');
                    
                    // Columns N, O, P are left empty for manual checkboxes
                    $sheet->setCellValue('N' . $currentRow, '');
                    $sheet->setCellValue('O' . $currentRow, '');
                    $sheet->setCellValue('P' . $currentRow, '');
                    
                    $counter++;
                    $populatedRows++;
                    
                    if ($currentRow > 1000) {
                        log_message('warning', 'Reached maximum row limit (1000)');
                        break;
                    }
                }
                
                log_message('debug', 'Populated rows: ' . $populatedRows);
                log_message('debug', 'Final counter: ' . $counter);
                
                // ===== APPLY ARIAL FONT TO ALL DATA ROWS =====
                if ($populatedRows > 0) {
                    $lastPopulatedRow = $startRow + $populatedRows - 1;
                    $dataRange = 'A' . $startRow . ':P' . $lastPopulatedRow;
                    $sheet->getStyle($dataRange)->getFont()->setName('Arial')->setSize(10);
                    
                    // Also set text color to black for all data cells
                    $sheet->getStyle($dataRange)->getFont()->getColor()->setARGB('FF000000');
                    
                    log_message('debug', 'Applied Arial font to range: ' . $dataRange);
                }
                
                // ===== GENERATE FILENAME =====
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
                
                // ===== OUTPUT THE FILE =====
                // Clear any output buffers
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
                
                // Create writer and save to output
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                
                log_message('debug', 'File written to output');
                log_message('debug', '=== EXPORT EXCEL COMPLETED SUCCESSFULLY ===');
                
                exit;
                
            } catch (Exception $e) {
                log_message('error', 'Error loading/processing template: ' . $e->getMessage());
                log_message('error', 'Exception trace: ' . $e->getTraceAsString());
                
                // Fallback to creating from scratch if template fails
                log_message('debug', 'Falling back to createSpreadsheetFromScratch()');
                $spreadsheet = $this->createSpreadsheetFromScratch($school_year);
                $sheet = $spreadsheet->getActiveSheet();
                
                // Set default font to Arial
                $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
                
                // Update header information with role-based display
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
                
                // Update title
                $title = 'Master List Beneficiaries for School-Based Feeding Program (SBFP) ( SY ' . $school_year . ' ) - ' . strtoupper($assessment_type);
                $sheet->setCellValue('A4', $title);
                
                // Apply Arial font to headers
                $sheet->getStyle('A1:P12')->getFont()->setName('Arial');
                
                // Populate data
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
                    $sheet->setCellValue('I' . $currentRow, !empty($student['height']) ? number_format($student['height'], 1) : '');
                    $sheet->setCellValue('J' . $currentRow, !empty($student['bmi']) ? number_format($student['bmi'], 1) : '');
                    $sheet->setCellValue('K' . $currentRow, $student['nutritional_status'] ?? '');
                    $sheet->setCellValue('L' . $currentRow, $student['height_for_age'] ?? '');
                    
                    $counter++;
                }
                
                // Apply Arial font to all data rows
                $lastRow = $startRow + count($beneficiaries) - 1;
                $sheet->getStyle('A' . $startRow . ':P' . $lastRow)->getFont()->setName('Arial')->setSize(10);
                
                // Output the file
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
            
            // Show error page
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
            $selected_school
        );
        
        $this->load->view('print_sbfp_beneficiaries', $data);
    }
}