<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Division_reports_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('division_reports_model');
        $this->load->model('user_model');
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
        $grade_level = $this->input->get('grade_level', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE);
        $date_from = $this->input->get('date_from', TRUE);
        $date_to = $this->input->get('date_to', TRUE);

        // Get reports data with filters
        $data['reports'] = $this->division_reports_model->get_reports_with_filters(
            $legislative_district,
            $school_district,
            $school_name,
            $grade_level,
            $date_from,
            $date_to,
            $assessment_type  // assessment_type is now the 7th parameter
        );

        // Get unique values for filters
        $data['legislative_districts'] = $this->division_reports_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->division_reports_model->get_unique_school_districts();
        $data['school_names'] = $this->division_reports_model->get_unique_school_names();
        $data['grade_levels'] = $this->division_reports_model->get_unique_grade_levels();
        
        // Add assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'midline' => 'Midline',
            'endline' => 'Endline'
        ];

        // Statistics - UPDATED TO SHOW BOTH BASELINE AND ENDLINE COUNTS
        $data['total_assessments'] = $this->division_reports_model->get_total_assessments_count();
        $data['total_schools'] = $this->division_reports_model->get_total_schools_count();
        $data['total_students'] = $this->division_reports_model->get_total_students_count();
        
        // Get counts by assessment type
        $data['baseline_count'] = $this->division_reports_model->get_assessment_type_count('baseline');
        $data['midline_count'] = $this->division_reports_model->get_assessment_type_count('midline');
        $data['endline_count'] = $this->division_reports_model->get_assessment_type_count('endline');

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
        $this->load->view('division_reports', $data);
    }

    /**
     * Export reports using nutritional_report_template.xlsx template
     */
    public function export()
    {
        try {
            // Load Composer autoloader
            require_once APPPATH . '../vendor/autoload.php';

            // Get filter parameters
            $legislative_district = $this->input->get('legislative_district', TRUE);
            $school_district = $this->input->get('school_district', TRUE);
            $school_name = $this->input->get('school_name', TRUE);
            $grade_level = $this->input->get('grade_level', TRUE);
            $assessment_type = $this->input->get('assessment_type', TRUE);
            $date_from = $this->input->get('date_from', TRUE);
            $date_to = $this->input->get('date_to', TRUE);

            // Get data with filters
            $reports = $this->division_reports_model->get_export_data_with_filters(
                $legislative_district,
                $school_district,
                $school_name,
                $grade_level,
                $date_from,
                $date_to,
                $assessment_type
            );

            if (empty($reports)) {
                show_error('No data found for the specified criteria');
            }

            // Define template path
            $templatePath = FCPATH . 'assets/templates/nutritional_report_template.xlsx';
            
            if (!file_exists($templatePath)) {
                show_error('Template file not found. Please ensure nutritional_report_template.xlsx is in assets/templates/');
            }

            // Load the template
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            
            // Clear existing data rows
            for ($row = 9; $row <= 200; $row++) {
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
            }
            
            // Get the actual year from the first record if available
            $actual_year = !empty($reports) && isset($reports[0]->year) ? $reports[0]->year : '';
            
            // Calculate date of weighing (get earliest date)
            $dates = array_filter(array_map(function($r) {
                return !empty($r->date_of_weighing) ? $r->date_of_weighing : null;
            }, $reports));
            
            if (!empty($dates)) {
                sort($dates);
                $date_of_weighing_display = date('F d, Y', strtotime($dates[0]));
            } else {
                $date_of_weighing_display = date('F d, Y');
            }
            
            // Format date for A6 (short format)
            $date_of_weighing_short = date('m/d/y');
            
            // POPULATE HEADER INFORMATION
            $sheet->setCellValue('A1', 'NUTRITIONAL STATUS REPORT');
            $sheet->setCellValue('A2', $school_name ?: 'All Schools');
            $sheet->setCellValue('A3', 'School Year: ' . ($actual_year ?: 'N/A'));
            $sheet->setCellValue('A4', 'Assessment Type: ' . ucfirst($assessment_type ?: 'All'));
            $sheet->setCellValue('A6', 'Date of Weighing: ' . $date_of_weighing_short);
            $sheet->setCellValue('N6', $grade_level ?: 'All Grade Levels');

            // Add grade level if filtered
            if ($grade_level) {
                $sheet->setCellValue('A8', 'Grade Level: ' . $grade_level);
            }

            // Initialize counters
            $bmi_summary = [
                'male' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0],
                'female' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0],
                'total' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0]
            ];

            $hfa_summary = [
                'male' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0],
                'female' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0],
                'total' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0]
            ];
            
            // Populate data starting from row 9
            $startRow = 9;
            $counter = 1;
            
            foreach ($reports as $report) {
                $currentRow = $startRow + ($counter - 1);
                
                // A: Student Number
                $sheet->setCellValue('A' . $currentRow, $counter);

                // B: Punctuation before name
                $sheet->setCellValue('B' . $currentRow,  '.');
                
                // C: Names
                $sheet->setCellValue('C' . $currentRow, $report->name ?? '');
                
                // D: Birthday
                if (!empty($report->birthday) && $report->birthday != '0000-00-00') {
                    $sheet->setCellValue('D' . $currentRow, date('m/d/Y', strtotime($report->birthday)));
                }

                // E: Weight
                $sheet->setCellValue('E' . $currentRow, $report->weight ?? '');
                
                // F: Height (meters)
                $height = $report->height ?? '';
                if (!empty($height) && is_numeric($height)) {
                    // If height is in cm (>10), convert to meters
                    if ($height > 10) {
                        $height = $height / 100;
                    }
                    $sheet->setCellValue('F' . $currentRow, number_format((float)$height, 2));
                }
                
                // G: Sex
                $sex = !empty($report->sex) ? strtoupper(substr(trim($report->sex), 0, 1)) : '';
                $sheet->setCellValue('G' . $currentRow, $sex);
                
                // H: Height²
                if (!empty($report->height_squared)) {
                    $sheet->setCellValue('H' . $currentRow, $report->height_squared);
                } elseif (!empty($height) && is_numeric($height)) {
                    $height_m = ($height > 10) ? $height/100 : $height;
                    $sheet->setCellValue('H' . $currentRow, round(pow((float)$height_m, 2), 4));
                }
                
                // I-K: Age
                if (!empty($report->birthday) && !empty($report->date_of_weighing)) {
                    try {
                        $birthDate = new DateTime($report->birthday);
                        $weighingDate = new DateTime($report->date_of_weighing);
                        $age = $birthDate->diff($weighingDate);
                        
                        $sheet->setCellValue('I' . $currentRow, $age->y);
                        $sheet->setCellValue('J' . $currentRow, ',');
                        $sheet->setCellValue('K' . $currentRow, $age->m);
                    } catch (Exception $e) {
                        log_message('error', 'Age calculation error: ' . $e->getMessage());
                    }
                }
                
                // L: BMI
                $sheet->setCellValue('L' . $currentRow, $report->bmi ?? '');
                
                // M: Nutritional Status (BMI)
                $nutritional_status = strtolower(trim($report->nutritional_status ?? ''));
                $sheet->setCellValue('M' . $currentRow, $report->nutritional_status ?? '');
                
                // N: Height-For-Age
                $height_for_age = strtolower(trim($report->height_for_age ?? ''));
                $sheet->setCellValue('N' . $currentRow, $report->height_for_age ?? '');
                
                // Update BMI counters based on sex and nutritional status
                if ($sex == 'M') {
                    $bmi_summary['male']['total']++;
                    $bmi_summary['total']['total']++;
                    
                    if ($nutritional_status == 'severely wasted') {
                        $bmi_summary['male']['severely_wasted']++;
                        $bmi_summary['total']['severely_wasted']++;
                    } elseif ($nutritional_status == 'wasted') {
                        $bmi_summary['male']['wasted']++;
                        $bmi_summary['total']['wasted']++;
                    } elseif ($nutritional_status == 'normal') {
                        $bmi_summary['male']['normal']++;
                        $bmi_summary['total']['normal']++;
                    } elseif ($nutritional_status == 'overweight') {
                        $bmi_summary['male']['overweight']++;
                        $bmi_summary['total']['overweight']++;
                    } elseif ($nutritional_status == 'obese') {
                        $bmi_summary['male']['obese']++;
                        $bmi_summary['total']['obese']++;
                    }
                } elseif ($sex == 'F') {
                    $bmi_summary['female']['total']++;
                    $bmi_summary['total']['total']++;
                    
                    if ($nutritional_status == 'severely wasted') {
                        $bmi_summary['female']['severely_wasted']++;
                        $bmi_summary['total']['severely_wasted']++;
                    } elseif ($nutritional_status == 'wasted') {
                        $bmi_summary['female']['wasted']++;
                        $bmi_summary['total']['wasted']++;
                    } elseif ($nutritional_status == 'normal') {
                        $bmi_summary['female']['normal']++;
                        $bmi_summary['total']['normal']++;
                    } elseif ($nutritional_status == 'overweight') {
                        $bmi_summary['female']['overweight']++;
                        $bmi_summary['total']['overweight']++;
                    } elseif ($nutritional_status == 'obese') {
                        $bmi_summary['female']['obese']++;
                        $bmi_summary['total']['obese']++;
                    }
                }
                
                // Update HFA counters based on sex and height-for-age status
                if ($sex == 'M') {
                    $hfa_summary['male']['total']++;
                    $hfa_summary['total']['total']++;
                    
                    if ($height_for_age == 'severely stunted') {
                        $hfa_summary['male']['severely_stunted']++;
                        $hfa_summary['total']['severely_stunted']++;
                    } elseif ($height_for_age == 'stunted') {
                        $hfa_summary['male']['stunted']++;
                        $hfa_summary['total']['stunted']++;
                    } elseif ($height_for_age == 'normal') {
                        $hfa_summary['male']['normal']++;
                        $hfa_summary['total']['normal']++;
                    } elseif ($height_for_age == 'tall' || $height_for_age == 'above normal') {
                        $hfa_summary['male']['tall']++;
                        $hfa_summary['total']['tall']++;
                    }
                } elseif ($sex == 'F') {
                    $hfa_summary['female']['total']++;
                    $hfa_summary['total']['total']++;
                    
                    if ($height_for_age == 'severely stunted') {
                        $hfa_summary['female']['severely_stunted']++;
                        $hfa_summary['total']['severely_stunted']++;
                    } elseif ($height_for_age == 'stunted') {
                        $hfa_summary['female']['stunted']++;
                        $hfa_summary['total']['stunted']++;
                    } elseif ($height_for_age == 'normal') {
                        $hfa_summary['female']['normal']++;
                        $hfa_summary['total']['normal']++;
                    } elseif ($height_for_age == 'tall' || $height_for_age == 'above normal') {
                        $hfa_summary['female']['tall']++;
                        $hfa_summary['total']['tall']++;
                    }
                }
                
                $counter++;
            }

            // ============================================
            // CREATE BOTTOM SUMMARY TABLE WITH HEADERS AT ROW 76
            // ============================================
            
            // HEADER ROW (Row 76)
            $sheet->setCellValue('C76', 'Body Mass Index');
            $sheet->setCellValue('D76', 'M');
            $sheet->setCellValue('E76', 'F');
            $sheet->setCellValue('F76', 'T');
            $sheet->setCellValue('G76', 'HFA');
            $sheet->setCellValue('I76', 'M');
            $sheet->setCellValue('L76', 'F');
            $sheet->setCellValue('M76', 'TOTAL');
            
            // Row 77: No. of Cases
            $sheet->setCellValue('C77', 'No. of Cases');
            $sheet->setCellValue('D77', $bmi_summary['male']['total'] ?: '0');
            $sheet->setCellValue('E77', $bmi_summary['female']['total'] ?: '0');
            $sheet->setCellValue('F77', $bmi_summary['total']['total'] ?: '0');
            $sheet->setCellValue('G77', 'No. of Cases');
            $sheet->setCellValue('I77', $hfa_summary['male']['total'] ?: '0');
            $sheet->setCellValue('L77', $hfa_summary['female']['total'] ?: '0');
            $sheet->setCellValue('M77', $hfa_summary['total']['total'] ?: '0');
            
            // Row 78: Severely Wasted / Sev. Stunted
            $sheet->setCellValue('C78', 'Severely Wasted');
            $sheet->setCellValue('D78', $bmi_summary['male']['severely_wasted'] ?: '0');
            $sheet->setCellValue('E78', $bmi_summary['female']['severely_wasted'] ?: '0');
            $sheet->setCellValue('F78', $bmi_summary['total']['severely_wasted'] ?: '0');
            $sheet->setCellValue('G78', 'Sev. Stunted');
            $sheet->setCellValue('I78', $hfa_summary['male']['severely_stunted'] ?: '0');
            $sheet->setCellValue('L78', $hfa_summary['female']['severely_stunted'] ?: '0');
            $sheet->setCellValue('M78', $hfa_summary['total']['severely_stunted'] ?: '0');
            
            // Row 79: Wasted / Stunted
            $sheet->setCellValue('C79', 'Wasted');
            $sheet->setCellValue('D79', $bmi_summary['male']['wasted'] ?: '0');
            $sheet->setCellValue('E79', $bmi_summary['female']['wasted'] ?: '0');
            $sheet->setCellValue('F79', $bmi_summary['total']['wasted'] ?: '0');
            $sheet->setCellValue('G79', 'Stunted');
            $sheet->setCellValue('I79', $hfa_summary['male']['stunted'] ?: '0');
            $sheet->setCellValue('L79', $hfa_summary['female']['stunted'] ?: '0');
            $sheet->setCellValue('M79', $hfa_summary['total']['stunted'] ?: '0');
            
            // Row 80: Normal (both)
            $sheet->setCellValue('C80', 'Normal');
            $sheet->setCellValue('D80', $bmi_summary['male']['normal'] ?: '0');
            $sheet->setCellValue('E80', $bmi_summary['female']['normal'] ?: '0');
            $sheet->setCellValue('F80', $bmi_summary['total']['normal'] ?: '0');
            $sheet->setCellValue('G80', 'Normal');
            $sheet->setCellValue('I80', $hfa_summary['male']['normal'] ?: '0');
            $sheet->setCellValue('L80', $hfa_summary['female']['normal'] ?: '0');
            $sheet->setCellValue('M80', $hfa_summary['total']['normal'] ?: '0');
            
            // Row 81: Overweight / Tall
            $sheet->setCellValue('C81', 'Overweight');
            $sheet->setCellValue('D81', $bmi_summary['male']['overweight'] ?: '0');
            $sheet->setCellValue('E81', $bmi_summary['female']['overweight'] ?: '0');
            $sheet->setCellValue('F81', $bmi_summary['total']['overweight'] ?: '0');
            $sheet->setCellValue('G81', 'Tall');
            $sheet->setCellValue('I81', $hfa_summary['male']['tall'] ?: '0');
            $sheet->setCellValue('L81', $hfa_summary['female']['tall'] ?: '0');
            $sheet->setCellValue('M81', $hfa_summary['total']['tall'] ?: '0');
            
            // Row 82: Obese
            $sheet->setCellValue('C82', 'Obese');
            $sheet->setCellValue('D82', $bmi_summary['male']['obese'] ?: '0');
            $sheet->setCellValue('E82', $bmi_summary['female']['obese'] ?: '0');
            $sheet->setCellValue('F82', $bmi_summary['total']['obese'] ?: '0');
            $sheet->setCellValue('I82', 'Prepared by:');
            $sheet->setCellValue('J82', '');

            // Generate filename
            $filename = 'nutritional_report_of_all_students_from_division_';
            if ($assessment_type) {
                $filename .= $assessment_type . '_';
            }
            if ($school_name) {
                $schoolPart = preg_replace('/[^A-Za-z0-9]/', '_', $school_name);
                $filename .= $schoolPart . '_';
            }
            $filename .= date('Y-m-d') . '.xlsx';

            // Clear output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            // Save to output
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            log_message('error', 'Export failed: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            show_error('Export failed: ' . $e->getMessage());
        }
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

        $data['assessments'] = $this->division_reports_model->get_by_section(
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
     * Export detailed report using nutritional_report_template.xlsx template
     */
    public function export_detail()
    {
        try {
            // Load Composer autoloader
            require_once APPPATH . '../vendor/autoload.php';

            $legislative_district = $this->input->get('legislative_district', TRUE);
            $school_district = $this->input->get('school_district', TRUE);
            $school_name = $this->input->get('school_name', TRUE);
            $school_id = $this->input->get('school_id', TRUE);
            $grade_level = $this->input->get('grade_level', TRUE);
            $section = $this->input->get('section', TRUE);
            $year = $this->input->get('year', TRUE);
            $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';

            // Validate required parameters
            if (!$legislative_district || !$school_district || !$school_name || !$grade_level || !$section) {
                show_error('Missing required parameters. Please provide legislative_district, school_district, school_name, grade_level, and section.');
            }

            // Get the data
            $assessments = $this->division_reports_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                $year,
                $assessment_type
            );

            if (empty($assessments)) {
                log_message('error', 'No assessments found for: ' . $legislative_district . ', ' . $school_district . ', ' . $grade_level . ', ' . $section);
                show_error('No data found for the specified criteria');
            }

            // Get the actual year from the first record for display
            $actual_year = $assessments[0]->year ?? $year;

            // Load the template
            $templatePath = FCPATH . 'assets/templates/nutritional_report_template.xlsx';
            
            if (!file_exists($templatePath)) {
                show_error('Template file not found at: ' . $templatePath);
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            $sheet = $spreadsheet->setActiveSheetIndex(0);

            // Clear existing data rows
            for ($row = 9; $row <= 200; $row++) {
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
            }

            // Calculate date of weighing
            $dates = array_filter(array_map(function($a) {
                return !empty($a->date_of_weighing) ? $a->date_of_weighing : null;
            }, $assessments));
            
            if (!empty($dates)) {
                sort($dates);
                $date_of_weighing = date('m/d/y', strtotime($dates[0]));
            } else {
                $date_of_weighing = date('m/d/y');
            }

            // POPULATE HEADER INFORMATION
            $sheet->setCellValue('A1', 'NUTRITIONAL STATUS REPORT');
            $sheet->setCellValue('A2', $school_name);
            $sheet->setCellValue('A3', 'School Year: ' . ($actual_year ?: 'N/A'));
            $sheet->setCellValue('A4', 'Assessment Type: ' . ucfirst($assessment_type));
            $sheet->setCellValue('A6', 'Date of Weighing: ' . $date_of_weighing);
            $sheet->setCellValue('M6', 'Section: ' . $section);
            $sheet->setCellValue('N6', $grade_level);

            // Initialize counters
            $bmi_summary = [
                'male' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0],
                'female' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0],
                'total' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0]
            ];

            $hfa_summary = [
                'male' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0],
                'female' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0],
                'total' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0]
            ];

            // POPULATE STUDENT DETAILS
            $startRow = 9;
            $counter = 1;
            
            foreach ($assessments as $assessment) {
                $currentRow = $startRow + ($counter - 1);
                
                // A: Student Number
                $sheet->setCellValue('A' . $currentRow, $counter);
                $sheet->setCellValue('B' . $currentRow, '.');
                $sheet->setCellValue('C' . $currentRow, $assessment->name ?? '');
                
                // D: Birthday
                if (!empty($assessment->birthday) && $assessment->birthday != '0000-00-00') {
                    $sheet->setCellValue('D' . $currentRow, date('m/d/Y', strtotime($assessment->birthday)));
                }

                // E: Weight
                $sheet->setCellValue('E' . $currentRow, $assessment->weight ?? '');
                
                // F: Height
                $height = $assessment->height ?? '';
                if (!empty($height) && is_numeric($height)) {
                    if ($height > 10) {
                        $height = $height / 100;
                    }
                    $sheet->setCellValue('F' . $currentRow, number_format((float)$height, 2));
                }
                
                // G: Sex
                $sex = !empty($assessment->sex) ? strtoupper(substr(trim($assessment->sex), 0, 1)) : '';
                $sheet->setCellValue('G' . $currentRow, $sex);
                
                // H: Height²
                if (!empty($assessment->height_squared)) {
                    $sheet->setCellValue('H' . $currentRow, $assessment->height_squared);
                } elseif (!empty($height) && is_numeric($height)) {
                    $height_m = ($height > 10) ? $height/100 : $height;
                    $sheet->setCellValue('H' . $currentRow, round(pow((float)$height_m, 2), 4));
                }
                
                // I-K: Age
                if (!empty($assessment->birthday) && !empty($assessment->date_of_weighing)) {
                    try {
                        $birthDate = new DateTime($assessment->birthday);
                        $weighingDate = new DateTime($assessment->date_of_weighing);
                        $age = $birthDate->diff($weighingDate);
                        
                        $sheet->setCellValue('I' . $currentRow, $age->y);
                        $sheet->setCellValue('J' . $currentRow, ',');
                        $sheet->setCellValue('K' . $currentRow, $age->m);
                    } catch (Exception $e) {
                        log_message('error', 'Age calculation error: ' . $e->getMessage());
                    }
                }
                
                // L: BMI
                $sheet->setCellValue('L' . $currentRow, $assessment->bmi ?? '');
                
                // M: Nutritional Status (BMI)
                $nutritional_status = strtolower(trim($assessment->nutritional_status ?? ''));
                $sheet->setCellValue('M' . $currentRow, $assessment->nutritional_status ?? '');
                
                // N: Height-For-Age
                $height_for_age = strtolower(trim($assessment->height_for_age ?? ''));
                $sheet->setCellValue('N' . $currentRow, $assessment->height_for_age ?? '');
                
                // Update BMI counters
                if ($sex == 'M') {
                    $bmi_summary['male']['total']++;
                    $bmi_summary['total']['total']++;
                    
                    if ($nutritional_status == 'severely wasted') {
                        $bmi_summary['male']['severely_wasted']++;
                        $bmi_summary['total']['severely_wasted']++;
                    } elseif ($nutritional_status == 'wasted') {
                        $bmi_summary['male']['wasted']++;
                        $bmi_summary['total']['wasted']++;
                    } elseif ($nutritional_status == 'normal') {
                        $bmi_summary['male']['normal']++;
                        $bmi_summary['total']['normal']++;
                    } elseif ($nutritional_status == 'overweight') {
                        $bmi_summary['male']['overweight']++;
                        $bmi_summary['total']['overweight']++;
                    } elseif ($nutritional_status == 'obese') {
                        $bmi_summary['male']['obese']++;
                        $bmi_summary['total']['obese']++;
                    }
                } elseif ($sex == 'F') {
                    $bmi_summary['female']['total']++;
                    $bmi_summary['total']['total']++;
                    
                    if ($nutritional_status == 'severely wasted') {
                        $bmi_summary['female']['severely_wasted']++;
                        $bmi_summary['total']['severely_wasted']++;
                    } elseif ($nutritional_status == 'wasted') {
                        $bmi_summary['female']['wasted']++;
                        $bmi_summary['total']['wasted']++;
                    } elseif ($nutritional_status == 'normal') {
                        $bmi_summary['female']['normal']++;
                        $bmi_summary['total']['normal']++;
                    } elseif ($nutritional_status == 'overweight') {
                        $bmi_summary['female']['overweight']++;
                        $bmi_summary['total']['overweight']++;
                    } elseif ($nutritional_status == 'obese') {
                        $bmi_summary['female']['obese']++;
                        $bmi_summary['total']['obese']++;
                    }
                }
                
                // Update HFA counters
                if ($sex == 'M') {
                    $hfa_summary['male']['total']++;
                    $hfa_summary['total']['total']++;
                    
                    if ($height_for_age == 'severely stunted') {
                        $hfa_summary['male']['severely_stunted']++;
                        $hfa_summary['total']['severely_stunted']++;
                    } elseif ($height_for_age == 'stunted') {
                        $hfa_summary['male']['stunted']++;
                        $hfa_summary['total']['stunted']++;
                    } elseif ($height_for_age == 'normal') {
                        $hfa_summary['male']['normal']++;
                        $hfa_summary['total']['normal']++;
                    } elseif ($height_for_age == 'tall' || $height_for_age == 'above normal') {
                        $hfa_summary['male']['tall']++;
                        $hfa_summary['total']['tall']++;
                    }
                } elseif ($sex == 'F') {
                    $hfa_summary['female']['total']++;
                    $hfa_summary['total']['total']++;
                    
                    if ($height_for_age == 'severely stunted') {
                        $hfa_summary['female']['severely_stunted']++;
                        $hfa_summary['total']['severely_stunted']++;
                    } elseif ($height_for_age == 'stunted') {
                        $hfa_summary['female']['stunted']++;
                        $hfa_summary['total']['stunted']++;
                    } elseif ($height_for_age == 'normal') {
                        $hfa_summary['female']['normal']++;
                        $hfa_summary['total']['normal']++;
                    } elseif ($height_for_age == 'tall' || $height_for_age == 'above normal') {
                        $hfa_summary['female']['tall']++;
                        $hfa_summary['total']['tall']++;
                    }
                }
                
                $counter++;
            }
            
            // row 76: Headers for summary table
            $sheet->setCellValue('C76', 'Body Mass Index');
            $sheet->setCellValue('D76', 'M');
            $sheet->setCellValue('E76', 'F');
            $sheet->setCellValue('F76', 'T');
            $sheet->setCellValue('G76', 'HFA');
            $sheet->setCellValue('I76', 'M');
            $sheet->setCellValue('L76', 'F');
            $sheet->setCellValue('M76', 'TOTAL');
            
            // row 77: No. of Cases
            $sheet->setCellValue('C77', 'No. of Cases');
            $sheet->setCellValue('D77', $bmi_summary['male']['total'] ?: '0');
            $sheet->setCellValue('E77', $bmi_summary['female']['total'] ?: '0');
            $sheet->setCellValue('F77', $bmi_summary['total']['total'] ?: '0');
            $sheet->setCellValue('G77', 'No. of Cases');
            $sheet->setCellValue('I77', $hfa_summary['male']['total'] ?: '0');
            $sheet->setCellValue('L77', $hfa_summary['female']['total'] ?: '0');
            $sheet->setCellValue('M77', $hfa_summary['total']['total'] ?: '0');
            
            // row 78: Severely Wasted / Sev. Stunted
            $sheet->setCellValue('C78', 'Severely Wasted');
            $sheet->setCellValue('D78', $bmi_summary['male']['severely_wasted'] ?: '0');
            $sheet->setCellValue('E78', $bmi_summary['female']['severely_wasted'] ?: '0');
            $sheet->setCellValue('F78', $bmi_summary['total']['severely_wasted'] ?: '0');
            $sheet->setCellValue('G78', 'Sev. Stunted');
            $sheet->setCellValue('I78', $hfa_summary['male']['severely_stunted'] ?: '0');
            $sheet->setCellValue('L78', $hfa_summary['female']['severely_stunted'] ?: '0');
            $sheet->setCellValue('M78', $hfa_summary['total']['severely_stunted'] ?: '0');
            
            // row 79: Wasted / Stunted
            $sheet->setCellValue('C79', 'Wasted');
            $sheet->setCellValue('D79', $bmi_summary['male']['wasted'] ?: '0');
            $sheet->setCellValue('E79', $bmi_summary['female']['wasted'] ?: '0');
            $sheet->setCellValue('F79', $bmi_summary['total']['wasted'] ?: '0');
            $sheet->setCellValue('G79', 'Stunted');
            $sheet->setCellValue('I79', $hfa_summary['male']['stunted'] ?: '0');
            $sheet->setCellValue('L79', $hfa_summary['female']['stunted'] ?: '0');
            $sheet->setCellValue('M79', $hfa_summary['total']['stunted'] ?: '0');
            
            // row 80: Normal / Tall
            $sheet->setCellValue('C80', 'Normal');
            $sheet->setCellValue('D80', $bmi_summary['male']['normal'] ?: '0');
            $sheet->setCellValue('E80', $bmi_summary['female']['normal'] ?: '0');
            $sheet->setCellValue('F80', $bmi_summary['total']['normal'] ?: '0');
            $sheet->setCellValue('G80', 'Normal');
            $sheet->setCellValue('I80', $hfa_summary['male']['normal'] ?: '0');
            $sheet->setCellValue('L80', $hfa_summary['female']['normal'] ?: '0');
            $sheet->setCellValue('M80', $hfa_summary['total']['normal'] ?: '0');
            
            // row 81: Overweight / Tall
            $sheet->setCellValue('C81', 'Overweight');
            $sheet->setCellValue('D81', $bmi_summary['male']['overweight'] ?: '0');
            $sheet->setCellValue('E81', $bmi_summary['female']['overweight'] ?: '0');
            $sheet->setCellValue('F81', $bmi_summary['total']['overweight'] ?: '0');
            $sheet->setCellValue('G81', 'Tall');
            $sheet->setCellValue('I81', $hfa_summary['male']['tall'] ?: '0');
            $sheet->setCellValue('L81', $hfa_summary['female']['tall'] ?: '0');
            $sheet->setCellValue('M81', $hfa_summary['total']['tall'] ?: '0');
            
            // row 82: Obese
            $sheet->setCellValue('C82', 'Obese');
            $sheet->setCellValue('D82', $bmi_summary['male']['obese'] ?: '0');
            $sheet->setCellValue('E82', $bmi_summary['female']['obese'] ?: '0');
            $sheet->setCellValue('F82', $bmi_summary['total']['obese'] ?: '0');
            $sheet->setCellValue('I82', 'Prepared by:');
            $sheet->setCellValue('J82', '');

            // Generate filename
            $filename = 'nutritional_report_' . preg_replace('/[^A-Za-z0-9]/', '_', $school_name) 
                . '_' . $grade_level 
                . '_Section: ' . $section 
                . '_' . $assessment_type 
                . '_' . date('Y-m-d') . '.xlsx';

            // Clear output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            // Save to output
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            log_message('error', 'Export detail failed: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            show_error('Export failed: ' . $e->getMessage());
        }
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
    $unfiltered_stats = $this->division_reports_model->get_nutritional_statistics_summary([]);
    
    // Get filtered detailed nutritional statistics for the overview section
    $data['nutritional_stats'] = $this->division_reports_model->get_detailed_nutritional_statistics($filters);
    
    // Get students based on nutritional status filter
    $status_filter = $filters['nutritional_status'] ?? '';
    $data['filtered_students'] = [];
    
    if ($status_filter === '') {
        // When "All Statuses" is selected (empty string), show ALL students
        $data['filtered_students'] = $this->division_reports_model->get_all_students_for_export($filters);
    } else if ($status_filter === 'sbfp_beneficiary') {
        // When "SBFP Beneficiary" is selected, show only SBFP beneficiaries
        $data['filtered_students'] = $this->division_reports_model->get_sbfp_beneficiaries($filters);
    } else if (!empty($status_filter)) {
        if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
            // Use the new method to get students by any nutritional status
            $data['filtered_students'] = $this->division_reports_model->get_students_by_nutritional_status($status_filter, $filters);
        }
    }
    
    // For backward compatibility - still get wasted/severely wasted separately
    $all_wasted = $this->division_reports_model->get_all_wasted_students($filters);
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
    $data['legislative_districts'] = $this->division_reports_model->get_unique_legislative_districts();
    $data['school_districts'] = $this->division_reports_model->get_unique_school_districts();
    $data['school_names'] = $this->division_reports_model->get_unique_school_names();
    $data['grade_levels'] = $this->division_reports_model->get_unique_grade_levels();
    
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
        $students_to_export = $this->division_reports_model->get_all_students_for_export($filters);
    } else if ($status_filter === 'sbfp_beneficiary') {
        // When "SBFP Beneficiary" is selected, export only SBFP beneficiaries
        $students_to_export = $this->division_reports_model->get_sbfp_beneficiaries($filters);
    } else if (!empty($status_filter)) {
        if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
            $students_to_export = $this->division_reports_model->get_students_by_nutritional_status($status_filter, $filters);
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
            $data['baseline'] = $this->division_reports_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                'baseline'
            );
            
            // Get endline data
            $data['endline'] = $this->division_reports_model->get_by_section(
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

        $data['legislative_districts'] = $this->division_reports_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->division_reports_model->get_unique_school_districts();
        $data['school_names'] = $this->division_reports_model->get_unique_school_names();
        $data['grade_levels'] = $this->division_reports_model->get_unique_grade_levels();

        $this->load->view('reports/comparison', $data);
    }
}