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

        $this->load->view('sbfp_beneficiaries', $data);
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
     * Export to Excel
     */
    public function export_excel() {
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

        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $school_level = $this->session->userdata('school_level') ?: 'all';
        $selected_school = $this->session->userdata('selected_school') ?: '';

        $model_school_name = $school_name;
        if ($user_role === 'district') {
            $model_school_name = '';
            $school_level = 'all';
        }

        $beneficiaries = $this->sbfp_beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $model_school_name,
            $school_level,
            $user_role,
            $school_id,
            $school_district,
            $selected_school
        );
        
        if (empty($beneficiaries)) {
            show_error('No data found for the specified criteria');
        }

        require_once APPPATH . '../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator('SBFP System')
            ->setLastModifiedBy('SBFP System')
            ->setTitle("SBFP Beneficiaries - {$assessment_type}")
            ->setSubject("SBFP Form 1A: Master List Beneficiaries")
            ->setDescription("School-Based Feeding Program (SY 2025-2026) - {$assessment_type} Data");
        
        // === HEADER SECTION ===
        
        // Row 1-2: Department of Education
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'Department of Education');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', 'Region V-Bicol');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 4: Title
        $sheet->mergeCells('A4:P4');
        $sheet->setCellValue('A4', 'Master List Beneficiaries for School-Based Feeding Program (SBFP) ( SY 2025-2026 ) - ' . strtoupper($assessment_type));
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
        $display_school = !empty($selected_school) ? $selected_school : ($user_role === 'school' ? $school_name : 'All Schools');
        $sheet->mergeCells('A8:P8');
        $sheet->setCellValue('A8', 'Name of School / School District: ' . $display_school);
        $sheet->getStyle('A8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 9: School ID
        $sheet->mergeCells('A9:P9');
        $sheet->setCellValue('A9', 'School ID Number: ' . $school_id);
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
        
        // === POPULATE DATA ===
        $startRow = 13;
        $counter = 1;
        
        foreach ($beneficiaries as $student) {
            $currentRow = $startRow + ($counter - 1);
            
            // No.
            $sheet->setCellValue('A' . $currentRow, $counter);
            
            // Name
            $sheet->setCellValue('B' . $currentRow, $student['name'] ?? '');
            
            // Sex
            $sex = isset($student['sex']) ? substr(strtoupper(trim($student['sex'])), 0, 1) : '';
            $sheet->setCellValue('C' . $currentRow, $sex);
            
            // Grade/Section
            $sheet->setCellValue('D' . $currentRow, ($student['grade_level'] ?? '') . '/' . ($student['section'] ?? ''));
            
            // Date of Birth
            if (!empty($student['birthday'])) {
                $birthday = date('m/d/Y', strtotime($student['birthday']));
                $sheet->setCellValue('E' . $currentRow, $birthday);
            }
            
            // Date of Weighing
            if (!empty($student['date_of_weighing'])) {
                $weighingDate = date('m/d/Y', strtotime($student['date_of_weighing']));
                $sheet->setCellValue('F' . $currentRow, $weighingDate);
            }
            
            // Age
            $sheet->setCellValue('G' . $currentRow, $student['age'] ?? '');
            
            // Weight
            if (!empty($student['weight'])) {
                $sheet->setCellValue('H' . $currentRow, number_format($student['weight'], 1));
            }
            
            // Height
            if (!empty($student['height'])) {
                $sheet->setCellValue('I' . $currentRow, number_format($student['height'], 1));
            }
            
            // BMI
            if (!empty($student['bmi'])) {
                $sheet->setCellValue('J' . $currentRow, number_format($student['bmi'], 1));
            }
            
            // BMI-A (Nutritional Status)
            $sheet->setCellValue('K' . $currentRow, $student['nutritional_status'] ?? '');
            
            // HFA (Height for Age)
            $sheet->setCellValue('L' . $currentRow, $student['height_for_age'] ?? '');
            
            // Apply borders to data row
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            
            $sheet->getStyle('A' . $currentRow . ':P' . $currentRow)->applyFromArray($dataStyle);
            
            // Center align specific columns
            $centerColumns = ['A', 'C', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
            foreach ($centerColumns as $col) {
                $sheet->getStyle($col . $currentRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            
            $counter++;
        }
        
        // === SET PAGE LAYOUT ===
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        
        // === SAVE AND DOWNLOAD ===
        $filename = 'SBFP_Form1A_Beneficiaries_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
        
        if (!empty($selected_school)) {
            $schoolPart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $selected_school);
            $filename = 'SBFP_Form1A_' . $schoolPart . '_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
        } elseif (!empty($school_name) && $user_role === 'school') {
            $schoolPart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $school_name);
            $filename = 'SBFP_Form1A_' . $schoolPart . '_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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