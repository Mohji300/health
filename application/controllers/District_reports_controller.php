<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class District_reports_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('District_reports_model');
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
        $grade_level = $this->input->get('grade_level', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE);
        $date_from = $this->input->get('date_from', TRUE);
        $date_to = $this->input->get('date_to', TRUE);

        // If current user is a district account, restrict results to their district
        $role = $this->session->userdata('role');
        if ($role === 'district') {
            $user_district = $this->session->userdata('school_district') ?? $this->session->userdata('district') ?? null;
            $parsed = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : null;
            if ($parsed) {
                $school_district = $parsed;
            }
        }

        // Get reports data with filters
        $data['reports'] = $this->District_reports_model->get_reports_with_filters(
            $legislative_district,
            $school_district,
            $school_name,
            $grade_level,
            $date_from,
            $date_to,
            $assessment_type  // assessment_type is now the 7th parameter
        );

        // Get unique values for filters
        $data['legislative_districts'] = $this->District_reports_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->District_reports_model->get_unique_school_districts();
        $data['school_names'] = $this->District_reports_model->get_unique_school_names();
        $data['grade_levels'] = $this->District_reports_model->get_unique_grade_levels();
        
        // Add assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'endline' => 'Endline'
        ];

        // Statistics - compute totals from the currently fetched reports (already district-filtered above)
        $reports_for_totals = $data['reports'] ?? [];
        $data['total_assessments'] = is_array($reports_for_totals) ? count($reports_for_totals) : (is_object($reports_for_totals) ? count($reports_for_totals) : 0);
        $data['total_students'] = $data['total_assessments'];

        // Count unique schools
        $unique_schools = [];
        $baseline_count = 0;
        $endline_count = 0;

        foreach ($reports_for_totals as $r) {
            $school_name_val = isset($r->school_name) ? trim($r->school_name) : '';
            if ($school_name_val !== '') {
                $unique_schools[$school_name_val] = true;
            }
            $atype = strtolower(trim($r->assessment_type ?? ''));
            if ($atype === 'baseline' || $atype === '') {
                $baseline_count++;
            } elseif ($atype === 'endline') {
                $endline_count++;
            }
        }

        $data['total_schools'] = count($unique_schools);
        $data['baseline_count'] = $baseline_count;
        $data['endline_count'] = $endline_count;

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
        $this->load->view('district_reports', $data);
    }

    /**
     * Export reports to CSV - UPDATED
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

        // If district account, limit export to their district
        $role = $this->session->userdata('role');
        if ($role === 'district') {
            $user_district = $this->session->userdata('school_district') ?? $this->session->userdata('district') ?? null;
            $parsed = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : null;
            if ($parsed) {
                $school_district = $parsed;
            }
        }

        // Get data with filters - UPDATED
        $reports = $this->District_reports_model->get_export_data_with_filters(
            $legislative_district,
            $school_district,
            $school_name,
            $grade_level,
            $date_from,
            $date_to,
            $assessment_type  // assessment_type is now the 7th parameter
        );

        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        $filename = 'nutritional_assessments_' . date('Y-m-d');
        if ($assessment_type) {
            $filename .= '_' . $assessment_type;
        }
        $filename .= '.csv';
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // CSV headers - ADD ASSESSMENT TYPE
        fputcsv($output, [
            'Assessment Type',
            'School Name',
            'School ID',
            'Legislative District',
            'School District',
            'Grade Level',
            'Section',
            'Student Name',
            'Birthday',
            'Sex',
            'Weight (kg)',
            'Height (m)',
            'BMI',
            'Nutritional Status',
            'SBFP Beneficiary',
            'Height for Age',
            'Date of Weighing',
            'Created Date'
        ]);

        // Data rows
        foreach ($reports as $report) {
            fputcsv($output, [
                ucfirst($report->assessment_type ?? 'baseline'),
                $report->school_name,
                $report->school_id ?? '',
                $report->legislative_district,
                $report->school_district,
                $report->grade_level,
                $report->section,
                $report->name,
                $report->birthday,
                $report->sex,
                $report->weight,
                $report->height,
                $report->bmi,
                $report->nutritional_status,
                $report->sbfp_beneficiary,
                $report->height_for_age,
                $report->date_of_weighing,
                $report->created_at
            ]);
        }

        fclose($output);
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

        $data['assessments'] = $this->District_reports_model->get_by_section(
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
 * Export detailed report to Excel/CSV
 */
public function export_detail()
{
    $legislative_district = $this->input->get('legislative_district', TRUE);
    $school_district = $this->input->get('school_district', TRUE);
    $school_name = $this->input->get('school_name', TRUE);
    $school_id = $this->input->get('school_id', TRUE);
    $grade_level = $this->input->get('grade_level', TRUE);
    $section = $this->input->get('section', TRUE);
    $assessment_type = $this->input->get('assessment_type', TRUE) ?: '';

        // If district account, allow using session district when GET not provided
        $role = $this->session->userdata('role');
        if ($role === 'district') {
            $user_district = $this->session->userdata('school_district') ?? $this->session->userdata('district') ?? null;
            $parsed = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : null;
            if ($parsed && empty($school_district)) {
                $school_district = $parsed;
            }
        }

    if (!$legislative_district || !$school_district || !$school_name || !$grade_level || !$section) {
        show_error('Missing required parameters');
    }

    $assessments = $this->District_reports_model->get_by_section(
        $legislative_district,
        $school_district,
        $grade_level,
        $section,
        $assessment_type
    );

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    $filename = str_replace(' ', '_', $school_name) . '_' . $grade_level . '_' . $section . '_' . $assessment_type . '_' . date('Y-m-d') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

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
        'Birthday',
        'Age',
        'Sex',
        'Weight (kg)',
        'Height (m)',
        'Height Squared',
        'BMI',
        'Nutritional Status',
        'SBFP Beneficiary',
        'Height for Age',
        'Date of Weighing',
        'Created Date'
    ]);

    // Data rows
    foreach ($assessments as $assessment) {
        fputcsv($output, [
            ucfirst($assessment->assessment_type ?? 'baseline'),
            $assessment->school_name,
            $assessment->school_id ?? '',
            $assessment->legislative_district,
            $assessment->school_district,
            $assessment->grade_level,
            $assessment->section,
            $assessment->name,
            $assessment->birthday,
            $assessment->age ?? '',
            $assessment->sex,
            $assessment->weight,
            $assessment->height,
            $assessment->height_squared ?? '',
            $assessment->bmi,
            $assessment->nutritional_status,
            $assessment->sbfp_beneficiary,
            $assessment->height_for_age,
            $assessment->date_of_weighing,
            $assessment->created_at
        ]);
    }

    fclose($output);
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

        // If district account, restrict filters to their district
        $role = $this->session->userdata('role');
        if ($role === 'district') {
            $user_district = $this->session->userdata('school_district') ?? $this->session->userdata('district') ?? null;
            $parsed = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : null;
            if ($parsed) {
                $filters['school_district'] = $parsed;
            }
        }

    // Get aggregated statistics for the summary cards (use $filters so district accounts get district-scoped totals)
    $unfiltered_stats = $this->District_reports_model->get_nutritional_statistics_summary($filters);

    // Get filtered detailed nutritional statistics for the overview section
    $data['nutritional_stats'] = $this->District_reports_model->get_detailed_nutritional_statistics($filters);
    
    // Get students based on nutritional status filter
    $status_filter = $filters['nutritional_status'] ?? '';
    $data['filtered_students'] = [];
    
    if ($status_filter === '') {
        // When "All Statuses" is selected (empty string), show ALL students
        $data['filtered_students'] = $this->District_reports_model->get_all_students_for_export($filters);
    } else if ($status_filter === 'sbfp_beneficiary') {
        // When "SBFP Beneficiary" is selected, show only SBFP beneficiaries
        $data['filtered_students'] = $this->District_reports_model->get_sbfp_beneficiaries($filters);
    } else if (!empty($status_filter)) {
        if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
            // Use the new method to get students by any nutritional status
            $data['filtered_students'] = $this->District_reports_model->get_students_by_nutritional_status($status_filter, $filters);
        }
    }
    
    // For backward compatibility - still get wasted/severely wasted separately
    $all_wasted = $this->District_reports_model->get_all_wasted_students($filters);
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
    $data['legislative_districts'] = $this->District_reports_model->get_unique_legislative_districts();
    $data['school_districts'] = $this->District_reports_model->get_unique_school_districts();
    $data['school_names'] = $this->District_reports_model->get_unique_school_names();
    $data['grade_levels'] = $this->District_reports_model->get_unique_grade_levels();
    
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

        // If district account, restrict exports to their district
        $role = $this->session->userdata('role');
        if ($role === 'district') {
            $user_district = $this->session->userdata('school_district') ?? $this->session->userdata('district') ?? null;
            $parsed = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : null;
            if ($parsed) {
                $filters['school_district'] = $parsed;
            }
        }

    // Get students based on nutritional status filter
    $status_filter = $filters['nutritional_status'] ?? '';
    $students_to_export = [];
    
    if ($status_filter === '') {
        // When "All Statuses" is selected, export all students
        $students_to_export = $this->District_reports_model->get_all_students_for_export($filters);
    } else if ($status_filter === 'sbfp_beneficiary') {
        // When "SBFP Beneficiary" is selected, export only SBFP beneficiaries
        $students_to_export = $this->District_reports_model->get_sbfp_beneficiaries($filters);
    } else if (!empty($status_filter)) {
        if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
            $students_to_export = $this->District_reports_model->get_students_by_nutritional_status($status_filter, $filters);
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
            $data['baseline'] = $this->District_reports_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                'baseline'
            );
            
            // Get endline data
            $data['endline'] = $this->District_reports_model->get_by_section(
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

        $data['legislative_districts'] = $this->District_reports_model->get_unique_legislative_districts();
        $data['school_districts'] = $this->District_reports_model->get_unique_school_districts();
        $data['school_names'] = $this->District_reports_model->get_unique_school_names();
        $data['grade_levels'] = $this->District_reports_model->get_unique_grade_levels();

        $this->load->view('reports/comparison', $data);
    }
}