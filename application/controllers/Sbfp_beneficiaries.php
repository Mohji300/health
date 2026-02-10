<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sbfp_beneficiaries extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Sbfp_Beneficiaries_model');
        $this->load->helper('url');
        $this->load->library('session');
    }
    
    public function index() {
        // Check if user is logged in
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }
        
        $data = array();
        
        // Get current assessment type from session or default to baseline
        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $data['assessment_type'] = $assessment_type;
        $data['is_baseline'] = ($assessment_type == 'baseline');
        $data['is_midline'] = ($assessment_type == 'midline');
        $data['is_endline'] = ($assessment_type == 'endline');
        
        // Get school filter from session
        $school_level = $this->session->userdata('school_level') ?: 'all';
        $data['school_level'] = $school_level;
        
        // Get selected school if any
        $selected_school = $this->session->userdata('selected_school') ?: '';
        $data['selected_school'] = $selected_school;
        
        // Get beneficiaries data
        $data['beneficiaries'] = $this->Sbfp_Beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $school_level,
            $selected_school
        );
        
        // Count data
        $data['baseline_count'] = $this->Sbfp_Beneficiaries_model->count_by_assessment('baseline');
        $data['midline_count'] = $this->Sbfp_Beneficiaries_model->count_by_assessment('midline');
        $data['endline_count'] = $this->Sbfp_Beneficiaries_model->count_by_assessment('endline');
        
        // Load view
        $this->load->view('sbfp_beneficiaries', $data);
    }
    
    public function set_assessment_type() {
        $type = $this->input->post('assessment_type');
        if (in_array($type, ['baseline', 'midline', 'endline'])) {
            $this->session->set_userdata('assessment_type', $type);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid assessment type']);
        }
    }
    
    public function set_school_level() {
        $level = $this->input->post('school_level');
        $this->session->set_userdata('school_level', $level);
        echo json_encode(['success' => true]);
    }
    
    public function export_excel()
    {
        // Check if user is logged in
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }
        
        // Get data from session
        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $school_level = $this->session->userdata('school_level') ?: 'all';
        $selected_school = $this->session->userdata('selected_school') ?: '';
        
        // Get beneficiaries data
        $beneficiaries = $this->Sbfp_Beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $school_level,
            $selected_school
        );
        
        if (empty($beneficiaries)) {
            show_error('No data found for the specified criteria');
        }
        
        // Use PhpSpreadsheet
        require_once APPPATH . '../vendor/autoload.php';
        
        // Create new Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
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
        $sheet->mergeCells('A8:P8');
        $sheet->setCellValue('A8', 'Name of School / School District: ' . $selected_school);
        $sheet->getStyle('A8')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Row 9: School ID
        $sheet->mergeCells('A9:P9');
        $sheet->setCellValue('A9', 'School ID Number:');
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
        
        // Row 12: Sub-headers for Nutritional Status - ONLY HEADER CELLS COLORED
        $sheet->setCellValue('K12', 'BMI-A');
        $sheet->setCellValue('L12', 'HFA');
        
        // Apply ONLY to header cell K12 (BMI-A) - Yellow/Orange
        $sheet->getStyle('K12')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000'] // Yellow/Orange
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'] // Black text
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        
        // Apply ONLY to header cell L12 (HFA) - Yellow/Orange
        $sheet->getStyle('L12')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000'] // Yellow/Orange
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'] // Black text
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        
        // Merge cells for Nutritional Status header
        $sheet->mergeCells('K11:L11');
        $sheet->setCellValue('K11', 'Nutritional Status (NS)');
        
        // Style main headers (excluding K12 and L12 which already have styles)
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
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        
        // Apply to all headers except K12 and L12
        $allHeaderRange = 'A11:P12';
        $sheet->getStyle($allHeaderRange)->applyFromArray($headerStyle);
        
        // Now re-apply the specific styles to K12 and L12 (overwrites the general style)
        $sheet->getStyle('K12')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000'] // Yellow/Orange
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'] // Black text
            ]
        ]);
        
        $sheet->getStyle('L12')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC000'] // Yellow/Orange
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'] // Black text
            ]
        ]);
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);   // No.
        $sheet->getColumnDimension('B')->setWidth(25);  // Name
        $sheet->getColumnDimension('C')->setWidth(8);   // Sex
        $sheet->getColumnDimension('D')->setWidth(15);  // Grade/Section
        $sheet->getColumnDimension('E')->setWidth(15);  // DOB
        $sheet->getColumnDimension('F')->setWidth(20);  // Date of Weighing
        $sheet->getColumnDimension('G')->setWidth(15);  // Age
        $sheet->getColumnDimension('H')->setWidth(12);  // Weight
        $sheet->getColumnDimension('I')->setWidth(12);  // Height
        $sheet->getColumnDimension('J')->setWidth(15);  // BMI
        $sheet->getColumnDimension('K')->setWidth(10);  // BMI-A
        $sheet->getColumnDimension('L')->setWidth(10);  // HFA
        $sheet->getColumnDimension('M')->setWidth(20);  // Parent Consent
        $sheet->getColumnDimension('N')->setWidth(20);  // 4Ps
        $sheet->getColumnDimension('O')->setWidth(25);  // Previous SBFP
        $sheet->getColumnDimension('P')->setWidth(5);   // Empty
        
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
            $sex = strtoupper(substr(trim($student['sex'] ?? ''), 0, 1));
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
            $nutritionalStatus = $student['nutritional_status'] ?? '';
            $sheet->setCellValue('K' . $currentRow, $nutritionalStatus);
            
            // HFA (Height for Age)
            $hfaStatus = $student['height_for_age'] ?? '';
            $sheet->setCellValue('L' . $currentRow, $hfaStatus);
            
            // Parent's Consent (default to yes for now)
            $sheet->setCellValue('M' . $currentRow, '');
            
            // 4Ps Participation (default to yes for now)
            $sheet->setCellValue('N' . $currentRow, '');
            
            // Previous SBFP (default to yes for now)
            $sheet->setCellValue('O' . $currentRow, '');
            
            // Apply borders to data row
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            
            // Center align for specific columns
            $centerColumns = ['A', 'C', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
            foreach ($centerColumns as $col) {
                $sheet->getStyle($col . $currentRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            
            // Left align for name and grade/section
            $sheet->getStyle('B' . $currentRow . ':D' . $currentRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            
            // Apply data style to entire row
            $sheet->getStyle('A' . $currentRow . ':P' . $currentRow)->applyFromArray($dataStyle);
            
            $counter++;
        }
        
        // Auto size for name column
        $sheet->getColumnDimension('B')->setAutoSize(true);
        
        // === SET PAGE LAYOUT ===
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        
        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setRight(0.25)
            ->setLeft(0.25)
            ->setBottom(0.5);
        
        // === SAVE AND DOWNLOAD ===
        $filename = 'SBFP_Form1A_Beneficiaries_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
        
        if (!empty($selected_school)) {
            $schoolPart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $selected_school);
            $filename = 'SBFP_Form1A_' . $schoolPart . '_' . $assessment_type . '_' . date('Y-m-d') . '.xlsx';
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    public function print_report() {
        $data = array();
        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        $data['assessment_type'] = $assessment_type;
        $data['is_baseline'] = ($assessment_type == 'baseline');
        $data['is_midline'] = ($assessment_type == 'midline');
        $data['is_endline'] = ($assessment_type == 'endline');
        
        $school_level = $this->session->userdata('school_level') ?: 'all';
        $data['school_level'] = $school_level;
        
        $selected_school = $this->session->userdata('selected_school') ?: '';
        $data['selected_school'] = $selected_school;
        
        $data['beneficiaries'] = $this->Sbfp_Beneficiaries_model->get_beneficiaries(
            $assessment_type,
            $school_level,
            $selected_school
        );
        
        $this->load->view('print_sbfp_beneficiaries', $data);
    }
}
?>