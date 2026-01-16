<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritional_assessment_reports extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nutritional_assessment_model');
        $this->load->model('User_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
        
        // Check if user is logged in and is admin/superadmin
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
    }

    /**
     * Main reports dashboard - UPDATED
     */
    public function index()
    {
        $data = [];
        
        // Get filter parameters - ADD ASSESSMENT TYPE
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $school_name = $this->input->get('school_name', TRUE);

        // Enforce session school for non-admin users when exporting
        $session_school = $this->session->userdata('school_name');
        $role = $this->session->userdata('role');
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $school_name = $session_school;
        }

        // If the current session is tied to a specific school and the user is not an admin,
        // force the reports to only show that school's data.
        $session_school = $this->session->userdata('school_name');
        $role = $this->session->userdata('role');
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $school_name = $session_school;
        }
        $grade_level = $this->input->get('grade_level', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE);
        $date_from = $this->input->get('date_from', TRUE);
        $date_to = $this->input->get('date_to', TRUE);

        // Get reports data with filters
        $data['reports'] = $this->Nutritional_assessment_model->get_reports_with_filters(
            $legislative_district,
            $school_district,
            $school_name,
            $grade_level,
            $date_from,
            $date_to,
            $assessment_type  // assessment_type is now the 7th parameter
        );

        // Get unique values for filters
        $data['legislative_districts'] = $this->Nutritional_assessment_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->Nutritional_assessment_model->get_unique_school_districts();
        $data['school_names'] = $this->Nutritional_assessment_model->get_unique_school_names();
        $data['grade_levels'] = $this->Nutritional_assessment_model->get_unique_grade_levels();
        
        // Add assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'endline' => 'Endline'
        ];

        // Statistics - UPDATED TO SHOW BOTH BASELINE AND ENDLINE COUNTS
        $data['total_assessments'] = $this->Nutritional_assessment_model->get_total_assessments_count();
        $data['total_schools'] = $this->Nutritional_assessment_model->get_total_schools_count();
        $data['total_students'] = $this->Nutritional_assessment_model->get_total_students_count();
        
        // Get counts by assessment type
        $data['baseline_count'] = $this->Nutritional_assessment_model->get_assessment_type_count('baseline');
        $data['endline_count'] = $this->Nutritional_assessment_model->get_assessment_type_count('endline');

        // Pass filter values back to view - ADD ASSESSMENT TYPE
        $data['current_filters'] = [
            'legislative_district' => $legislative_district,
            'school_district' => $school_district,
            'school_name' => $school_name,
            'grade_level' => $grade_level,
            'assessment_type' => $assessment_type,
            'date_from' => $date_from,
            'date_to' => $date_to
        ];

        // Load the new view file
        $this->load->view('nutritional_reports', $data);
    }

    /**
     * Export reports to XLSX instead of CSV
     */
    public function export()
    {
        // Get filter parameters
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $school_name = $this->input->get('school_name', TRUE);
        $grade_level = $this->input->get('grade_level', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE);
        $date_from = $this->input->get('date_from', TRUE);
        $date_to = $this->input->get('date_to', TRUE);

        // Get data with filters
        $reports = $this->Nutritional_assessment_model->get_export_data_with_filters(
            $legislative_district,
            $school_district,
            $school_name,
            $grade_level,
            $date_from,
            $date_to,
            $assessment_type
        );

        // Use PhpSpreadsheet
        require_once APPPATH . '../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('SBFP System')
            ->setTitle('Nutritional Assessments Export')
            ->setDescription('Export of nutritional assessment data');

        // Set headers
        $headers = [
            'A1' => 'Assessment Type',
            'B1' => 'School Name',
            'C1' => 'School ID',
            'D1' => 'Legislative District',
            'E1' => 'School District',
            'F1' => 'Grade Level',
            'G1' => 'Section',
            'H1' => 'Student Name',
            'I1' => 'Birthday',
            'J1' => 'Sex',
            'K1' => 'Weight (kg)',
            'L1' => 'Height (m)',
            'M1' => 'BMI',
            'N1' => 'Nutritional Status',
            'O1' => 'SBFP Beneficiary',
            'P1' => 'Height for Age',
            'Q1' => 'Date of Weighing',
            'R1' => 'Created Date'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Add data rows
        $row = 2;
        foreach ($reports as $report) {
            $sheet->setCellValue('A' . $row, ucfirst($report->assessment_type ?? 'baseline'));
            $sheet->setCellValue('B' . $row, $report->school_name);
            $sheet->setCellValue('C' . $row, $report->school_id ?? '');
            $sheet->setCellValue('D' . $row, $report->legislative_district);
            $sheet->setCellValue('E' . $row, $report->school_district);
            $sheet->setCellValue('F' . $row, $report->grade_level);
            $sheet->setCellValue('G' . $row, $report->section);
            $sheet->setCellValue('H' . $row, $report->name);
            $sheet->setCellValue('I' . $row, $report->birthday);
            $sheet->setCellValue('J' . $row, $report->sex);
            $sheet->setCellValue('K' . $row, $report->weight);
            $sheet->setCellValue('L' . $row, $report->height);
            $sheet->setCellValue('M' . $row, $report->bmi);
            $sheet->setCellValue('N' . $row, $report->nutritional_status);
            $sheet->setCellValue('O' . $row, $report->sbfp_beneficiary);
            $sheet->setCellValue('P' . $row, $report->height_for_age);
            $sheet->setCellValue('Q' . $row, $report->date_of_weighing);
            $sheet->setCellValue('R' . $row, $report->created_at);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'R') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set page layout
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setRight(0.25)
            ->setLeft(0.25)
            ->setBottom(0.5);

        // Output file
        $filename = 'nutritional_assessments_' . date('Y-m-d');
        if ($assessment_type) {
            $filename .= '_' . $assessment_type;
        }
        $filename .= '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * View detailed report for a specific school/grade/section - UPDATED
     */
    public function view_detail()
    {
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $school_name = $this->input->get('school_name', TRUE);
        $grade_level = $this->input->get('grade_level', TRUE);
        $section = $this->input->get('section', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';

        if (!$legislative_district || !$school_district || !$school_name || !$grade_level || !$section) {
            show_error('Missing required parameters');
        }

        $data['assessments'] = $this->Nutritional_assessment_model->get_by_section(
            $legislative_district,
            $school_district,
            $grade_level,
            $section,
            $assessment_type
        );

        $data['report_info'] = [
            'school_name' => $school_name,
            'legislative_district' => $legislative_district,
            'school_district' => $school_district,
            'grade_level' => $grade_level,
            'section' => $section,
            'assessment_type' => $assessment_type
        ];

        $this->load->view('reports/detail', $data);
    }

    /**
     * Export detailed report to Excel with exact template format
     */
/**
 * Export detailed report to Excel with exact template format
 */
public function export_detail()
{
    $legislative_district = $this->input->get('legislative_district', TRUE);
    $school_district = $this->input->get('school_district', TRUE);
    $school_name = $this->input->get('school_name', TRUE);
    $school_id = $this->input->get('school_id', TRUE);
    $grade_level = $this->input->get('grade_level', TRUE);
    $section = $this->input->get('section', TRUE);
    $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';

    if (!$legislative_district || !$school_district || !$school_name || !$grade_level || !$section) {
        show_error('Missing required parameters');
    }

    $assessments = $this->Nutritional_assessment_model->get_by_section(
        $legislative_district,
        $school_district,
        $grade_level,
        $section,
        $assessment_type
    );

    if (empty($assessments)) {
        show_error('No data found for the specified criteria');
    }

    // Use PhpSpreadsheet to build a formatted Excel (.xlsx) report from template
    require_once APPPATH . '../vendor/autoload.php';

    // Load the template file
    $templatePath = APPPATH . '../assets/templates/Book1.xlsx';
    
    if (!file_exists($templatePath)) {
        show_error('Template file not found at: ' . $templatePath);
    }

    // Load template
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // School Year calculation (June to March)
    $currentYear = date('Y');
    $currentMonth = date('n');
    if ($currentMonth >= 6) { // June to December
        $schoolYear = $currentYear . '-' . ($currentYear + 1);
    } else { // January to May
        $schoolYear = ($currentYear - 1) . '-' . $currentYear;
    }

    // Calculate date of weighing (use earliest date from assessments)
    $dates = array_filter(array_map(function($a) {
        return !empty($a->date_of_weighing) ? $a->date_of_weighing : null;
    }, $assessments));
    
    if (!empty($dates)) {
        sort($dates);
        $date_of_weighing = date('F d, Y', strtotime($dates[0]));
    } else {
        $date_of_weighing = date('F d, Y');
    }

    // === POPULATE HEADER INFORMATION ===
    
    // ROW 2: School Name (centered, bold)
    // Let's merge cells A2 through N2 and center the school name
    $sheet->mergeCells('A2:N2');
    $sheet->setCellValue('A2', $school_name);
    $sheet->getStyle('A2')->getFont()->setBold(true);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // ROW 3: School District (centered)
    $sheet->mergeCells('A3:N3');
    $sheet->setCellValue('A3', $school_district);
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // ROW 4: Assessment Type and School Year (centered)
    $assessmentDisplay = ucfirst($assessment_type);
    // Merge cells to center the text
    $sheet->mergeCells('A4:N4');
    $sheet->setCellValue('A4', $assessmentDisplay . ' Assessment SY ' . $schoolYear);
    $sheet->getStyle('A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // COLUMN D6: Date of Weighing
    $sheet->setCellValue('D6', $date_of_weighing);
    
    // COLUMN M6: Grade Level
    $sheet->setCellValue('M6', 'Grade: ' . $grade_level);
    
    // COLUMN N6: Section
    $sheet->setCellValue('N6', 'Section: ' . $section);

    // === POPULATE STUDENT DETAILS STARTING AT ROW 9 ===
    
    $startRow = 9; // Student data starts at row 9 as you specified
    
    foreach ($assessments as $index => $student) {
        $currentRow = $startRow + $index;
        
        // A9: Student Number
        $sheet->setCellValue('A' . $currentRow, $index + 1);
        
        // B9: Period (You mentioned this is for period - what data goes here?)
        // If you have period data, add it. Otherwise leave empty or add placeholder
        $sheet->setCellValue('B' . $currentRow, ''); // Period placeholder
        
        // C9: Student Names
        $sheet->setCellValue('C' . $currentRow, $student->name ?? '');
        
        // D9: Birthday (format: mm/dd/yyyy)
        if (!empty($student->birthday)) {
            $birthday = date('m/d/Y', strtotime($student->birthday));
            $sheet->setCellValue('D' . $currentRow, $birthday);
        } else {
            $sheet->setCellValue('D' . $currentRow, '');
        }
        
        // E9: Weight (kg)
        $sheet->setCellValue('E' . $currentRow, $student->weight ?? '');
        
        // F9: Height (meters)
        $sheet->setCellValue('F' . $currentRow, $student->height ?? '');
        
        // G9: Sex (M/F)
        $sex = strtoupper(substr(trim($student->sex ?? ''), 0, 1));
        $sheet->setCellValue('G' . $currentRow, $sex);
        
        // H9: Height² (m²) - calculate if height exists
        if (!empty($student->height) && is_numeric($student->height)) {
            $heightSquared = pow(floatval($student->height), 2);
            $sheet->setCellValue('H' . $currentRow, round($heightSquared, 4));
        } else {
            $sheet->setCellValue('H' . $currentRow, '');
        }
        
        // I9: Age Years
        // J9: Comma
        // K9: Age Months
        if (!empty($student->birthday) && !empty($date_of_weighing)) {
            $birthDate = new DateTime($student->birthday);
            $weighingDate = new DateTime($date_of_weighing);
            $interval = $birthDate->diff($weighingDate);
            
            // I9: Years
            $sheet->setCellValue('I' . $currentRow, $interval->y);
            
            // J9: Comma (literal comma)
            $sheet->setCellValue('J' . $currentRow, ',');
            
            // K9: Months
            $sheet->setCellValue('K' . $currentRow, $interval->m);
        } else {
            $sheet->setCellValue('I' . $currentRow, '');
            $sheet->setCellValue('J' . $currentRow, '');
            $sheet->setCellValue('K' . $currentRow, '');
        }
        
        // L9: Body Mass Index (BMI)
        $sheet->setCellValue('L' . $currentRow, $student->bmi ?? '');
        
        // M9: Nutritional Status
        $sheet->setCellValue('M' . $currentRow, $student->nutritional_status ?? '');
        
        // N9: Height-for-Age
        $sheet->setCellValue('N' . $currentRow, $student->height_for_age ?? '');
        
        // Apply borders to the row if your template has borders
        $borderStyle = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $sheet->getStyle('A' . $currentRow . ':N' . $currentRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle($borderStyle);
    }

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
    
    $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $school_name) 
        . '_Grade' . $grade_level 
        . '_Section' . $section 
        . '_' . $assessment_type 
        . '_' . date('Y-m-d') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

    /**
     * Get nutritional statistics summary - UPDATED VERSION
     */
    public function statistics() {
        // Get filter values from GET request
        $filters = [
            'legislative_district' => $this->input->get('legislative_district'),
            'school_district' => $this->input->get('school_district'),
            'school_name' => $this->input->get('school_name'),
            'grade_level' => $this->input->get('grade_level'),
            'assessment_type' => $this->input->get('assessment_type'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'nutritional_status' => $this->input->get('nutritional_status')
        ];

        // Get UNFILTERED aggregated statistics for the summary cards (always show all data)
        $unfiltered_stats = $this->Nutritional_assessment_model->get_nutritional_statistics_summary([]);
        
        // Get filtered detailed nutritional statistics for the overview section
        $data['nutritional_stats'] = $this->Nutritional_assessment_model->get_detailed_nutritional_statistics($filters);
        
        // Get students based on nutritional status filter
        $status_filter = $filters['nutritional_status'] ?? '';
        $data['filtered_students'] = [];
        
        if ($status_filter === '') {
            // When "All Statuses" is selected (empty string), show ALL students
            $data['filtered_students'] = $this->Nutritional_assessment_model->get_all_students_for_export($filters);
        } else if ($status_filter === 'sbfp_beneficiary') {
            // When "SBFP Beneficiary" is selected, show only SBFP beneficiaries
            $data['filtered_students'] = $this->Nutritional_assessment_model->get_sbfp_beneficiaries($filters);
        } else if (!empty($status_filter)) {
            if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
                // Use the new method to get students by any nutritional status
                $data['filtered_students'] = $this->Nutritional_assessment_model->get_students_by_nutritional_status($status_filter, $filters);
            }
        }
        
        // For backward compatibility - still get wasted/severely wasted separately
        $all_wasted = $this->Nutritional_assessment_model->get_all_wasted_students($filters);
        $data['severely_wasted_students'] = [];
        $data['wasted_students'] = [];
        
        foreach ($all_wasted as $student) {
            $status = strtolower(trim($student->nutritional_status));
            if ($status === 'severely wasted') {
                $data['severely_wasted_students'][] = $student;
            } elseif ($status === 'wasted') {
                $data['wasted_students'][] = $student;
            }
        }
        
        // Calculate totals for the statistics cards - USE UNFILTERED DATA
        $data['total_severely_wasted'] = $unfiltered_stats->severely_wasted ?? 0;
        $data['total_wasted'] = $unfiltered_stats->wasted ?? 0;
        $data['total_normal'] = $unfiltered_stats->normal ?? 0;
        $data['total_students'] = $unfiltered_stats->total_students ?? 0;
        $data['total_overweight'] = $unfiltered_stats->overweight ?? 0;
        $data['total_obese'] = $unfiltered_stats->obese ?? 0;

        // Get filter dropdown options
        $data['legislative_districts'] = $this->Nutritional_assessment_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->Nutritional_assessment_model->get_unique_school_districts();
        $data['school_names'] = $this->Nutritional_assessment_model->get_unique_school_names();
        $data['grade_levels'] = $this->Nutritional_assessment_model->get_unique_grade_levels();
        
        // Add assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'endline' => 'Endline'
        ];
        
        // Store current filters for form persistence
        $data['current_filters'] = $filters;

        $this->load->view('view_statistics', $data);
    }

    /**
     * Export statistics to CSV - UPDATED VERSION
     */
    public function export_statistics() {
        // Get filter values from GET request
        $filters = [
            'legislative_district' => $this->input->get('legislative_district'),
            'school_district' => $this->input->get('school_district'),
            'school_name' => $this->input->get('school_name'),
            'grade_level' => $this->input->get('grade_level'),
            'assessment_type' => $this->input->get('assessment_type'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'nutritional_status' => $this->input->get('nutritional_status')
        ];

        // Get students based on nutritional status filter
        $status_filter = $filters['nutritional_status'] ?? '';
        $students_to_export = [];
        
        if ($status_filter === '') {
            // When "All Statuses" is selected, export all students
            $students_to_export = $this->Nutritional_assessment_model->get_all_students_for_export($filters);
        } else if ($status_filter === 'sbfp_beneficiary') {
            // When "SBFP Beneficiary" is selected, export only SBFP beneficiaries
            $students_to_export = $this->Nutritional_assessment_model->get_sbfp_beneficiaries($filters);
        } else if (!empty($status_filter)) {
            if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
                $students_to_export = $this->Nutritional_assessment_model->get_students_by_nutritional_status($status_filter, $filters);
            }
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        
        // Create filename based on filter
        if ($status_filter === '') {
            $filename = 'all_students_' . date('Y-m-d') . '.csv';
        } else if ($status_filter === 'sbfp_beneficiary') {
            $filename = 'sbfp_beneficiaries_' . date('Y-m-d') . '.csv';
        } else {
            $filename = str_replace(' ', '_', $status_filter) . '_students_' . date('Y-m-d') . '.csv';
        }
        
        if (!empty($filters['assessment_type'])) {
            $filename = str_replace('.csv', '_' . $filters['assessment_type'] . '.csv', $filename);
        }
        
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // CSV headers
        fputcsv($output, [
            'Assessment Type',
            'School Name',
            'School ID',
            'Legislative District', 
            'School District', 
            'Grade Level', 
            'Section',
            'Student Name',
            'Nutritional Status',
            'SBFP Beneficiary',
            'Birthday',
            'Sex',
            'Age',
            'Weight (kg)',
            'Height (m)',
            'BMI',
            'Height for Age',
            'Date of Weighing',
            'Created Date'
        ]);
        
        // Add students
        foreach ($students_to_export as $student) {
            fputcsv($output, [
                ucfirst($student->assessment_type ?? 'baseline'),
                $student->school_name ?? 'N/A',
                $student->school_id ?? 'N/A',
                $student->legislative_district ?? 'N/A',
                $student->school_district ?? 'N/A',
                $student->grade_level ?? 'N/A',
                $student->section ?? 'N/A',
                $student->name ?? 'N/A',
                $student->nutritional_status ?? 'N/A',
                $student->sbfp_beneficiary ?? 'N/A',
                $student->birthday ?? 'N/A',
                $student->sex ?? 'N/A',
                $student->age ?? 'N/A',
                $student->weight ?? 'N/A',
                $student->height ?? 'N/A',
                $student->bmi ?? 'N/A',
                $student->height_for_age ?? 'N/A',
                $student->date_of_weighing ?? 'N/A',
                !empty($student->created_at) ? date('M j, Y', strtotime($student->created_at)) : 'N/A'
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Get assessment comparison report
     */
    public function comparison_report()
    {
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $school_name = $this->input->get('school_name', TRUE);
        $grade_level = $this->input->get('grade_level', TRUE);
        $section = $this->input->get('section', TRUE);

        $data = [];
        
        if ($legislative_district && $school_district && $school_name && $grade_level && $section) {
            // Get baseline data
            $data['baseline'] = $this->Nutritional_assessment_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                'baseline'
            );
            
            // Get endline data
            $data['endline'] = $this->Nutritional_assessment_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                'endline'
            );
            
            $data['comparison_info'] = [
                'school_name' => $school_name,
                'legislative_district' => $legislative_district,
                'school_district' => $school_district,
                'grade_level' => $grade_level,
                'section' => $section
            ];
        }

        $data['legislative_districts'] = $this->Nutritional_assessment_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->Nutritional_assessment_model->get_unique_school_districts();
        $data['school_names'] = $this->Nutritional_assessment_model->get_unique_school_names();
        $data['grade_levels'] = $this->Nutritional_assessment_model->get_unique_grade_levels();

        $this->load->view('reports/comparison', $data);
    }
}