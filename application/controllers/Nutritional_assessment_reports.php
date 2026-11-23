<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Style\Protection;
class Nutritional_assessment_reports extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('nutritional_assessment_model');
        $this->load->model('user_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
        
        // Check if user is logged in
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
    }

    /**
     * Main reports dashboard 
     */
    public function index()
    {
        $data = [];

        // Get user session data
        $session_school = $this->session->userdata('school_name');
        $session_school_id = $this->session->userdata('school_id');
        $role = $this->session->userdata('role');
        
        // Get filter parameters 
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $school_name = $this->input->get('school_name', TRUE);
        $school_id = $this->input->get('school_id', TRUE);

        // For non-admin users, force filter to their school
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $school_name = $session_school;
            $school_id = $session_school_id;
        }

        $grade_level = $this->input->get('grade_level', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE);
        $date_from = $this->input->get('date_from', TRUE);
        $date_to = $this->input->get('date_to', TRUE);
        $section_id = $this->input->get('section_id', TRUE);

        $data['reports'] = $this->nutritional_assessment_model->get_reports_with_filters(
            $legislative_district,
            $school_district,
            $school_name,
            $grade_level,
            $date_from,
            $date_to,
            $assessment_type,
            $school_id,
            $section_id
        );

        if (in_array($role, ['admin', 'super_admin', 'district', 'division'])) {
            $data['legislative_districts'] = $this->nutritional_assessment_model->get_unique_legislative_districts();
            $data['school_districts'] = $this->nutritional_assessment_model->get_unique_school_districts();
            $data['school_names'] = $this->nutritional_assessment_model->get_unique_school_names();
            $data['grade_levels'] = $this->nutritional_assessment_model->get_unique_grade_levels();
        } else {
            $data['legislative_districts'] = $this->nutritional_assessment_model->get_unique_legislative_districts_by_user($session_school, $session_school_id);
            $data['school_districts'] = $this->nutritional_assessment_model->get_unique_school_districts_by_user($session_school, $session_school_id);
            $data['school_names'] = $this->nutritional_assessment_model->get_unique_school_names_by_user($session_school, $role, $session_school_id);
            $data['grade_levels'] = $this->nutritional_assessment_model->get_unique_grade_levels_by_user($session_school, $session_school_id);
        }
        
        // Add assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'midline' => 'Midline',
            'endline' => 'Endline'
        ];

        // Statistics - filtered by user's school
        $data['total_assessments'] = $this->nutritional_assessment_model->get_total_assessments_count($school_id);
        $data['total_schools'] = $this->nutritional_assessment_model->get_total_schools_count($school_id);
        $data['total_students'] = $this->nutritional_assessment_model->get_total_students_count($school_id);
        
        // Get counts by assessment type - filtered by school_id
        $data['baseline_count'] = $this->nutritional_assessment_model->get_assessment_type_count('baseline', $school_id);
        $data['midline_count'] = $this->nutritional_assessment_model->get_assessment_type_count('midline', $school_id);
        $data['endline_count'] = $this->nutritional_assessment_model->get_assessment_type_count('endline', $school_id);

        // Pass filter values back to view
        $data['current_filters'] = [
            'legislative_district' => $legislative_district,
            'school_district' => $school_district,
            'school_name' => $school_name,
            'school_id' => $school_id,
            'grade_level' => $grade_level,
            'assessment_type' => $assessment_type,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'section_id' => $section_id
        ];

        $this->load->view('nutritional_reports', $data);
    }

    /**
     * Export reports using nutritional_report_template.xlsx template
     */
    public function export()
    {
        try {
            $session_school    = $this->session->userdata('school_name');
            $session_school_id = $this->session->userdata('school_id');
            $role              = $this->session->userdata('role');

            $this->load->library('phpspreadsheet_lib');

            // FIX: correct template for all-sections export
            $templatePath = FCPATH . ASSETS_PATH . '/excel_templates/export_all_sections.xlsx';
            if (!file_exists($templatePath)) {
                show_error('Template file not found at: ' . $templatePath);
            }

            $spreadsheet = $this->phpspreadsheet_lib->loadTemplate($templatePath);
            $sheet       = $spreadsheet->getActiveSheet();

            // Filters
            $legislative_district = $this->input->get('legislative_district', TRUE);
            $school_district      = $this->input->get('school_district', TRUE);
            $school_name          = $this->input->get('school_name', TRUE);
            $school_id            = $this->input->get('school_id', TRUE);
            $grade_level          = $this->input->get('grade_level', TRUE);
            $assessment_type      = $this->input->get('assessment_type', TRUE);
            $date_from            = $this->input->get('date_from', TRUE);
            $date_to              = $this->input->get('date_to', TRUE);
            $section_id           = $this->input->get('section_id', TRUE);

            if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
                $school_name = $session_school;
                $school_id   = $session_school_id;
            }

            $reports = $this->nutritional_assessment_model->get_export_data_with_filters(
                $legislative_district,
                $school_district,
                $school_name,
                $grade_level,
                $date_from,
                $date_to,
                $assessment_type,
                $school_id,
                $section_id
            );

            if (empty($reports)) {
                show_error('No data found for the specified criteria');
            }

            // FIX: only clear student data cells B–O from row 7 down.
            // Leave A formulas intact; do NOT touch P–Z (summary block lives there).
            $lastRow = 100;
            for ($row = 7; $row <= $lastRow; $row++) {
                foreach (['B','C','D','E','F','G','H','I','J','K','L','M','N','O'] as $col) {
                    $sheet->setCellValue($col . $row, '');
                }
            }

            $actual_year = $reports[0]->year ?? '';

            // Earliest date of weighing
            $dates = array_filter(array_map(function ($r) {
                return !empty($r->date_of_weighing) ? $r->date_of_weighing : null;
            }, $reports));
            sort($dates);
            $date_of_weighing = !empty($dates) ? date('m/d/Y', strtotime($dates[0])) : date('m/d/Y');

            // FIX: header cells match template (row 3)
            $sheet->setCellValue('C3', $date_of_weighing);   // Date of Weighing
            $sheet->setCellValue('E3', $grade_level ?: '');   // Grade
            $sheet->setCellValue('G3', '');                   // Section (n/a for all-sections)

            // Optional metadata in row 2 (blank in template)
            $sheet->setCellValue('A2', $school_name ?: 'All Schools');
            $sheet->setCellValue('P2', 'School Year: ' . ($actual_year ?: 'N/A'));
            $sheet->setCellValue('U2', 'Assessment: ' . ucfirst($assessment_type ?: 'All'));

            // Summary counters
            $bmi = [
                'male'   => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0],
                'female' => ['severely_wasted' => 0, 'wasted' => 0, 'normal' => 0, 'overweight' => 0, 'obese' => 0, 'total' => 0],
            ];
            $hfa = [
                'male'   => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0],
                'female' => ['severely_stunted' => 0, 'stunted' => 0, 'normal' => 0, 'tall' => 0, 'total' => 0],
            ];

            $startRow = 7;
            $counter  = 1;

            foreach ($reports as $report) {
                $currentRow = $startRow + ($counter - 1);

                // Split name
                [$lastName, $firstName, $mi] = $this->split_name($report->name ?? '');

                // B–D: name parts
                $sheet->setCellValue('B' . $currentRow, $lastName);
                $sheet->setCellValue('C' . $currentRow, $firstName);
                $sheet->setCellValue('D' . $currentRow, $mi);

                // E: Birthday
                if (!empty($report->birthday) && $report->birthday != '0000-00-00') {
                    $sheet->setCellValue('E' . $currentRow, date('m/d/Y', strtotime($report->birthday)));
                }

                // F: Weight
                $sheet->setCellValue('F' . $currentRow, $report->weight ?? '');

                // G: Height (m)
                $height = $report->height ?? '';
                $heightM = null;
                if (!empty($height) && is_numeric($height)) {
                    $heightM = ($height > 10) ? $height / 100 : (float)$height;
                    $sheet->setCellValue('G' . $currentRow, number_format($heightM, 2));
                }

                // H: Sex
                $sex = !empty($report->sex) ? strtoupper(substr(trim($report->sex), 0, 1)) : '';
                $sheet->setCellValue('H' . $currentRow, $sex);

                // I: Height²
                if ($heightM !== null) {
                    $sheet->setCellValue('I' . $currentRow, round(pow($heightM, 2), 4));
                }

                // J/K/L: Age
                if (!empty($report->birthday) && !empty($report->date_of_weighing)) {
                    try {
                        $age = (new DateTime($report->birthday))
                            ->diff(new DateTime($report->date_of_weighing));
                        $sheet->setCellValue('J' . $currentRow, $age->y);
                        $sheet->setCellValue('K' . $currentRow, ',');
                        $sheet->setCellValue('L' . $currentRow, $age->m);
                    } catch (Exception $e) {
                        log_message('error', 'Age calc: ' . $e->getMessage());
                    }
                }

                // M: BMI
                $sheet->setCellValue('M' . $currentRow, $report->bmi ?? '');
                // N: Nutritional Status
                $sheet->setCellValue('N' . $currentRow, $report->nutritional_status ?? '');
                // O: Height-For-Age
                $sheet->setCellValue('O' . $currentRow, $report->height_for_age ?? '');

                // Update BMI counters
                $ns = strtolower(trim($report->nutritional_status ?? ''));
                if ($sex === 'M') {
                    $bmi['male']['total']++;
                    if (isset($bmi['male'][$ns])) $bmi['male'][$ns]++;
                } elseif ($sex === 'F') {
                    $bmi['female']['total']++;
                    if (isset($bmi['female'][$ns])) $bmi['female'][$ns]++;
                }

                // Update HFA counters
                $hfaStatus = strtolower(trim($report->height_for_age ?? ''));
                if ($hfaStatus === 'above normal') $hfaStatus = 'tall';
                if ($sex === 'M') {
                    $hfa['male']['total']++;
                    if (isset($hfa['male'][$hfaStatus])) $hfa['male'][$hfaStatus]++;
                } elseif ($sex === 'F') {
                    $hfa['female']['total']++;
                    if (isset($hfa['female'][$hfaStatus])) $hfa['female'][$hfaStatus]++;
                }

                $counter++;
            }

            // ============================================================
            // SUMMARY BLOCK (template rows 3–10, columns P–Z)
            // Template labels are already in place — write only VALUES.
            // ============================================================
            // BMI columns: Q = M, R = F, S = T
            // HFA columns: U = M, W = F, X = TOTAL  (V is spacer)
            //
            // Row mapping (from template):
            //  row 3  header labels (Body Mass Index / M / F / T / HFA / M / F / TOTAL)
            //  row 4  No. of Cases
            //  row 5  Severely Wasted / Sev. Stunted
            //  row 6  Wasted / Stunted
            //  row 7  Normal / Normal
            //  row 8  Overweight / Tall
            //  row 9  Obese
            //  row 10 (blank)
            // ============================================================

            $bmiTotalAll = $bmi['male']['total'] + $bmi['female']['total'];
            $hfaTotalAll = $hfa['male']['total'] + $hfa['female']['total'];

            // Row 4: No. of Cases
            $sheet->setCellValue('Q4', $bmi['male']['total']);
            $sheet->setCellValue('R4', $bmi['female']['total']);
            $sheet->setCellValue('S4', $bmiTotalAll);
            $sheet->setCellValue('U4', $hfa['male']['total']);
            $sheet->setCellValue('W4', $hfa['female']['total']);
            $sheet->setCellValue('X4', $hfaTotalAll);

            // Row 5: Severely Wasted / Sev. Stunted
            $sheet->setCellValue('Q5', $bmi['male']['severely_wasted']);
            $sheet->setCellValue('R5', $bmi['female']['severely_wasted']);
            $sheet->setCellValue('S5', $bmi['male']['severely_wasted'] + $bmi['female']['severely_wasted']);
            $sheet->setCellValue('U5', $hfa['male']['severely_stunted']);
            $sheet->setCellValue('W5', $hfa['female']['severely_stunted']);
            $sheet->setCellValue('X5', $hfa['male']['severely_stunted'] + $hfa['female']['severely_stunted']);

            // Row 6: Wasted / Stunted
            $sheet->setCellValue('Q6', $bmi['male']['wasted']);
            $sheet->setCellValue('R6', $bmi['female']['wasted']);
            $sheet->setCellValue('S6', $bmi['male']['wasted'] + $bmi['female']['wasted']);
            $sheet->setCellValue('U6', $hfa['male']['stunted']);
            $sheet->setCellValue('W6', $hfa['female']['stunted']);
            $sheet->setCellValue('X6', $hfa['male']['stunted'] + $hfa['female']['stunted']);

            // Row 7: Normal / Normal
            $sheet->setCellValue('Q7', $bmi['male']['normal']);
            $sheet->setCellValue('R7', $bmi['female']['normal']);
            $sheet->setCellValue('S7', $bmi['male']['normal'] + $bmi['female']['normal']);
            $sheet->setCellValue('U7', $hfa['male']['normal']);
            $sheet->setCellValue('W7', $hfa['female']['normal']);
            $sheet->setCellValue('X7', $hfa['male']['normal'] + $hfa['female']['normal']);

            // Row 8: Overweight / Tall
            $sheet->setCellValue('Q8', $bmi['male']['overweight']);
            $sheet->setCellValue('R8', $bmi['female']['overweight']);
            $sheet->setCellValue('S8', $bmi['male']['overweight'] + $bmi['female']['overweight']);
            $sheet->setCellValue('U8', $hfa['male']['tall']);
            $sheet->setCellValue('W8', $hfa['female']['tall']);
            $sheet->setCellValue('X8', $hfa['male']['tall'] + $hfa['female']['tall']);

            // Row 9: Obese
            $sheet->setCellValue('Q9', $bmi['male']['obese']);
            $sheet->setCellValue('R9', $bmi['female']['obese']);
            $sheet->setCellValue('S9', $bmi['male']['obese'] + $bmi['female']['obese']);

            // Filename
            $filename = 'nutritional_report_';
            if ($assessment_type) {
                $filename .= $assessment_type . '_';
            }
            if ($school_name) {
                $filename .= preg_replace('/[^A-Za-z0-9]/', '_', $school_name) . '_';
            }
            $filename .= date('Y-m-d') . '.xlsx';

            $this->phpspreadsheet_lib->saveToOutput($spreadsheet, $filename);

        } catch (Exception $e) {
            log_message('error', 'Export failed: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            show_error('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export detailed report using nutritional_report_template.xlsx template
     */
public function export_detail()
{
    try {
        $session_school    = $this->session->userdata('school_name');
        $session_school_id = $this->session->userdata('school_id');
        $role              = $this->session->userdata('role');

        $this->load->library('phpspreadsheet_lib');

        // ---- Filters ----
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district      = $this->input->get('school_district', TRUE);
        $school_name          = $this->input->get('school_name', TRUE);
        $school_id            = $this->input->get('school_id', TRUE);
        $grade_level          = $this->input->get('grade_level', TRUE);
        $section              = $this->input->get('section', TRUE);
        $section_id           = $this->input->get('section_id', TRUE);
        $year                 = $this->input->get('year', TRUE);

        // ---- Mode ----
        $mode = strtolower($this->input->get('mode', TRUE) ?: 'section');
        if (!in_array($mode, ['midline', 'endline', 'section'])) {
            $mode = 'section';
        }

        $config = [
            'midline' => [
                'template'        => 'export_per_section.xlsx',
                'include_height'  => false,
                'include_weight'  => false,
                'include_date'    => false,
                'include_summary' => false,
            ],
            'endline' => [
                'template'        => 'export_per_section.xlsx',
                'include_height'  => false,
                'include_weight'  => false,
                'include_date'    => false,
                'include_summary' => false,
            ],
            'section' => [
                'template'        => 'export_all_sections.xlsx',
                'include_height'  => true,
                'include_weight'  => true,
                'include_date'    => true,
                'include_summary' => true,
            ],
        ];

        $cfg          = $config[$mode];
        $templateName = $cfg['template'];

        // ---- Force school for non-admins ----
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $school_name = $session_school;
            $school_id   = $session_school_id;
        }

        if (!$legislative_district || !$school_district || !$school_name || !$grade_level || !$section) {
            show_error('Missing required parameters.');
        }

        // ============ Fetch rows (ALL modes anchor on latest baseline, except
        //              section mode which prefers latest endline if it exists) ====
        $assessments      = [];
        $source_type      = 'baseline';
        $actual_year_used = null;

        if ($mode === 'midline') {
            // Latest baseline, only SBFP beneficiaries (matches Manage Beneficiaries)
            $assessments = $this->nutritional_assessment_model->get_latest_baseline_beneficiaries_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                $section_id,
                $school_id
            );
            $actual_year_used = $assessments[0]->year ?? null;
            $source_type      = 'baseline';

        } elseif ($mode === 'endline') {
            // Latest baseline, ALL students (compact list)
            $assessments = $this->nutritional_assessment_model->get_latest_baseline_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                $section_id,
                $school_id
            );
            $actual_year_used = $assessments[0]->year ?? null;
            $source_type      = 'baseline';

        } else { // section
            // Latest endline if present, else latest baseline
            $has_endline = $this->nutritional_assessment_model->section_has_endline(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                $section_id,
                $school_id
            );

            if ($has_endline) {
                $assessments = $this->nutritional_assessment_model->get_latest_endline_by_section(
                    $legislative_district,
                    $school_district,
                    $grade_level,
                    $section,
                    $section_id,
                    $school_id
                );
                $source_type = 'endline';
            } else {
                $assessments = $this->nutritional_assessment_model->get_latest_baseline_by_section(
                    $legislative_district,
                    $school_district,
                    $grade_level,
                    $section,
                    $section_id,
                    $school_id
                );
                $source_type = 'baseline';
            }
            $actual_year_used = $assessments[0]->year ?? null;
        }

        if (empty($assessments)) {
            show_error('No data found for the specified criteria (mode: ' . $mode . ').');
        }

        $actual_year = $actual_year_used ?: $year;

        // ---- Load template ----
        $templatePath = FCPATH . ASSETS_PATH . '/excel_templates/' . $templateName;
        if (!file_exists($templatePath)) {
            show_error('Template file not found: ' . $templatePath);
        }

        $spreadsheet = $this->phpspreadsheet_lib->loadTemplate($templatePath);
        $sheet       = $spreadsheet->getActiveSheet();

        // ---- Clear previous data ----
        if ($templateName === 'export_per_section.xlsx') {
            for ($row = 7; $row <= 510; $row++) {
                foreach (['B','C','D','E','F','G','H'] as $col) {
                    $sheet->setCellValue($col . $row, '');
                }
            }
        } else {
            for ($row = 7; $row <= 100; $row++) {
                foreach (['B','C','D','E','F','G','H','I','J','K','L','M','N','O'] as $col) {
                    $sheet->setCellValue($col . $row, '');
                }
            }
        }

        // ---- Date of weighing (section mode only) ----
        $date_of_weighing = '';
        if ($cfg['include_date']) {
            $dates = array_filter(array_map(function ($a) {
                return !empty($a->date_of_weighing) ? $a->date_of_weighing : null;
            }, $assessments));
            sort($dates);
            $date_of_weighing = !empty($dates)
                ? date('m/d/Y', strtotime($dates[0]))
                : date('m/d/Y');
        }

        // ---- Header metadata ----
        $sheet->setCellValue('A1', 'NUTRITIONAL STATUS REPORT');
        $sheet->setCellValue('A2', $school_name);
        $sheet->setCellValue('C3', $cfg['include_date'] ? $date_of_weighing : '');
        $sheet->setCellValue('E3', $grade_level);
        $sheet->setCellValue('G3', $section);

        if ($templateName === 'export_all_sections.xlsx') {
            $sheet->setCellValue('P2', 'School Year: ' . ($actual_year ?: 'N/A'));
            $sheet->setCellValue('U2', 'Source: ' . ucfirst($source_type) . ' (latest)');
        } else {
            $sheet->setCellValue('E2', 'School Year: ' . ($actual_year ?: 'N/A'));
            $sheet->setCellValue('G2', ucfirst($source_type) . ' (latest)');
        }

        // ---- Summary counters ----
        $bmi = [
            'male'   => ['severely_wasted'=>0,'wasted'=>0,'normal'=>0,'overweight'=>0,'obese'=>0,'total'=>0],
            'female' => ['severely_wasted'=>0,'wasted'=>0,'normal'=>0,'overweight'=>0,'obese'=>0,'total'=>0],
        ];
        $hfa = [
            'male'   => ['severely_stunted'=>0,'stunted'=>0,'normal'=>0,'tall'=>0,'total'=>0],
            'female' => ['severely_stunted'=>0,'stunted'=>0,'normal'=>0,'tall'=>0,'total'=>0],
        ];

        $startRow = 7;
        $counter  = 1;

        foreach ($assessments as $assessment) {
            $currentRow = $startRow + ($counter - 1);

            [$lastName, $firstName, $mi] = $this->split_name($assessment->name ?? '');

            $sheet->setCellValue('B' . $currentRow, $lastName);
            $sheet->setCellValue('C' . $currentRow, $firstName);
            $sheet->setCellValue('D' . $currentRow, $mi);

            if (!empty($assessment->birthday) && $assessment->birthday != '0000-00-00') {
                $sheet->setCellValue('E' . $currentRow, date('m/d/Y', strtotime($assessment->birthday)));
            }

            $sex = !empty($assessment->sex) ? strtoupper(substr(trim($assessment->sex), 0, 1)) : '';

            if ($templateName === 'export_per_section.xlsx') {
                // Midline & Endline: name, birthday, sex only
                $sheet->setCellValue('F' . $currentRow, '');
                $sheet->setCellValue('G' . $currentRow, '');
                $sheet->setCellValue('H' . $currentRow, $sex);
            } else {
                // Export Section: full fields
                $sheet->setCellValue('F' . $currentRow,
                    $cfg['include_weight'] ? ($assessment->weight ?? '') : '');

                $heightM = null;
                if ($cfg['include_height'] && !empty($assessment->height) && is_numeric($assessment->height)) {
                    $h = (float)$assessment->height;
                    $heightM = ($h > 10) ? $h / 100 : $h;
                    $sheet->setCellValue('G' . $currentRow, number_format($heightM, 2));
                    $sheet->setCellValue('I' . $currentRow, round(pow($heightM, 2), 4));
                } else {
                    $sheet->setCellValue('G' . $currentRow, '');
                    $sheet->setCellValue('I' . $currentRow, '');
                }

                $sheet->setCellValue('H' . $currentRow, $sex);

                if (!empty($assessment->birthday) && !empty($assessment->date_of_weighing)) {
                    try {
                        $age = (new DateTime($assessment->birthday))
                            ->diff(new DateTime($assessment->date_of_weighing));
                        $sheet->setCellValue('J' . $currentRow, $age->y);
                        $sheet->setCellValue('K' . $currentRow, ',');
                        $sheet->setCellValue('L' . $currentRow, $age->m);
                    } catch (Exception $e) {
                        log_message('error', 'Age calc: ' . $e->getMessage());
                    }
                }

                $sheet->setCellValue('M' . $currentRow, $assessment->bmi ?? '');
                $sheet->setCellValue('N' . $currentRow, $assessment->nutritional_status ?? '');
                $sheet->setCellValue('O' . $currentRow, $assessment->height_for_age ?? '');

                // Accumulate summary counters
                $ns = strtolower(trim($assessment->nutritional_status ?? ''));
                if ($sex === 'M') {
                    $bmi['male']['total']++;
                    if (isset($bmi['male'][$ns])) $bmi['male'][$ns]++;
                } elseif ($sex === 'F') {
                    $bmi['female']['total']++;
                    if (isset($bmi['female'][$ns])) $bmi['female'][$ns]++;
                }

                $hfaStatus = strtolower(trim($assessment->height_for_age ?? ''));
                if ($hfaStatus === 'above normal') $hfaStatus = 'tall';
                if ($sex === 'M') {
                    $hfa['male']['total']++;
                    if (isset($hfa['male'][$hfaStatus])) $hfa['male'][$hfaStatus]++;
                } elseif ($sex === 'F') {
                    $hfa['female']['total']++;
                    if (isset($hfa['female'][$hfaStatus])) $hfa['female'][$hfaStatus]++;
                }
            }

            $counter++;
        }

        // ---- Summary block (section mode only) ----
        if ($cfg['include_summary'] && $templateName === 'export_all_sections.xlsx') {
            $bmiTotalAll = $bmi['male']['total'] + $bmi['female']['total'];
            $hfaTotalAll = $hfa['male']['total'] + $hfa['female']['total'];

            $sheet->setCellValue('Q4', $bmi['male']['total']);
            $sheet->setCellValue('R4', $bmi['female']['total']);
            $sheet->setCellValue('S4', $bmiTotalAll);
            $sheet->setCellValue('U4', $hfa['male']['total']);
            $sheet->setCellValue('W4', $hfa['female']['total']);
            $sheet->setCellValue('X4', $hfaTotalAll);

            $sheet->setCellValue('Q5', $bmi['male']['severely_wasted']);
            $sheet->setCellValue('R5', $bmi['female']['severely_wasted']);
            $sheet->setCellValue('S5', $bmi['male']['severely_wasted'] + $bmi['female']['severely_wasted']);
            $sheet->setCellValue('U5', $hfa['male']['severely_stunted']);
            $sheet->setCellValue('W5', $hfa['female']['severely_stunted']);
            $sheet->setCellValue('X5', $hfa['male']['severely_stunted'] + $hfa['female']['severely_stunted']); 

            $sheet->setCellValue('Q6', $bmi['male']['wasted']);
            $sheet->setCellValue('R6', $bmi['female']['wasted']);
            $sheet->setCellValue('S6', $bmi['male']['wasted'] + $bmi['female']['wasted']);
            $sheet->setCellValue('U6', $hfa['male']['stunted']);
            $sheet->setCellValue('W6', $hfa['female']['stunted']);
            $sheet->setCellValue('X6', $hfa['male']['stunted'] + $hfa['female']['stunted']); 

            $sheet->setCellValue('Q7', $bmi['male']['normal']);
            $sheet->setCellValue('R7', $bmi['female']['normal']);
            $sheet->setCellValue('S7', $bmi['male']['normal'] + $bmi['female']['normal']);
            $sheet->setCellValue('U7', $hfa['male']['normal']);
            $sheet->setCellValue('W7', $hfa['female']['normal']);
            $sheet->setCellValue('X7', $hfa['male']['normal'] + $hfa['female']['normal']);

            $sheet->setCellValue('Q8', $bmi['male']['overweight']);
            $sheet->setCellValue('R8', $bmi['female']['overweight']);
            $sheet->setCellValue('S8', $bmi['male']['overweight'] + $bmi['female']['overweight']);
            $sheet->setCellValue('U8', $hfa['male']['tall']);
            $sheet->setCellValue('W8', $hfa['female']['tall']);
            $sheet->setCellValue('X8', $hfa['male']['tall'] + $hfa['female']['tall']);

            $sheet->setCellValue('Q9', $bmi['male']['obese']);
            $sheet->setCellValue('R9', $bmi['female']['obese']);
            $sheet->setCellValue('S9', $bmi['male']['obese'] + $bmi['female']['obese']);
        }

        // ---- Filename ----
        $filename = 'nutritional_report_' . $mode . '_'
            . preg_replace('/[^A-Za-z0-9]/', '_', $school_name)
            . '_Grade' . $grade_level
            . '_Section' . $section
            . '_' . date('Y-m-d') . '.xlsx';

        $this->phpspreadsheet_lib->saveToOutput($spreadsheet, $filename);

    } catch (Exception $e) {
        log_message('error', 'Export detail failed: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        show_error('Export failed: ' . $e->getMessage());
    }
}

    private function split_name($fullName)
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['', '', ''];
        }

        $last  = '';
        $first = '';
        $mi    = '';

        // ---- Format: "Last, First MI" ----
        if (strpos($fullName, ',') !== false) {
            [$last, $rest] = array_map('trim', explode(',', $fullName, 2));
            $parts = preg_split('/\s+/', $rest);

            // Detect trailing MI (single letter, optional dot)
            $lastToken = end($parts);
            if (!empty($parts) && preg_match('/^[A-Za-z]\.?$/', $lastToken)) {
                $mi = strtoupper(rtrim($lastToken, '.')) . '.';
                array_pop($parts);
            }

            $first = implode(' ', $parts);
            return [$last, $first, $mi];
        }

        // ---- Format: "First MI Last" or "First Middle Last" (no comma) ----
        // ASSUMPTION: last name is a SINGLE token (e.g. "Cruz", "Santos").
        // Multi-word last names like "Dela Cruz" REQUIRE the comma format.
        $parts = preg_split('/\s+/', $fullName);

        if (count($parts) < 2) {
            // Only one token — best guess: treat as last name
            return [$fullName, '', ''];
        }

        // Last token = last name
        $last = array_pop($parts);

        // Detect trailing MI in what's left (single letter, optional dot)
        if (!empty($parts)) {
            $lastOfRest = end($parts);
            if (preg_match('/^[A-Za-z]\.?$/', $lastOfRest)) {
                $mi = strtoupper(rtrim($lastOfRest, '.')) . '.';
                array_pop($parts);
            }
        }

        // Everything remaining is first + middle names (preserved intact)
        $first = implode(' ', $parts);

        return [$last, $first, $mi];
    }


    /**
     * Get nutritional statistics summary
     */
    public function statistics() {
        // Get user session data
        $session_school = $this->session->userdata('school_name');
        $session_school_id = $this->session->userdata('school_id');
        $role = $this->session->userdata('role');

        $filters = [
            'legislative_district' => $this->input->get('legislative_district'),
            'school_district' => $this->input->get('school_district'),
            'school_name' => $this->input->get('school_name'),
            'school_id' => $this->input->get('school_id'),
            'grade_level' => $this->input->get('grade_level'),
            'assessment_type' => $this->input->get('assessment_type'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'nutritional_status' => $this->input->get('nutritional_status')
        ];

        // For non-admin users, force filter to their school
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $filters['school_name'] = $session_school;
            $filters['school_id'] = $session_school_id;
        }

        // Get UNFILTERED aggregated statistics for the summary cards
        $unfiltered_stats = $this->nutritional_assessment_model->get_nutritional_statistics_summary([]);
        
        // Get filtered detailed nutritional statistics
        $data['nutritional_stats'] = $this->nutritional_assessment_model->get_detailed_nutritional_statistics($filters);
        
        // Get students based on nutritional status filter
        $status_filter = $filters['nutritional_status'] ?? '';
        $data['filtered_students'] = [];
        
        if ($status_filter === '') {
            $data['filtered_students'] = $this->nutritional_assessment_model->get_all_students_for_export($filters);
        } else if ($status_filter === 'sbfp_beneficiary') {
            $data['filtered_students'] = $this->nutritional_assessment_model->get_sbfp_beneficiaries($filters);
        } else if (!empty($status_filter)) {
            if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
                $data['filtered_students'] = $this->nutritional_assessment_model->get_students_by_nutritional_status($status_filter, $filters);
            }
        }
        
        // Get wasted students
        $all_wasted = $this->nutritional_assessment_model->get_all_wasted_students($filters);
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
        
        // Calculate totals
        $data['total_severely_wasted'] = $unfiltered_stats->severely_wasted ?? 0;
        $data['total_wasted'] = $unfiltered_stats->wasted ?? 0;
        $data['total_normal'] = $unfiltered_stats->normal ?? 0;
        $data['total_students'] = $unfiltered_stats->total_students ?? 0;
        $data['total_overweight'] = $unfiltered_stats->overweight ?? 0;
        $data['total_obese'] = $unfiltered_stats->obese ?? 0;

        // Get filter dropdown options - filtered by user's school
        if (in_array($role, ['admin', 'super_admin'])) {
            $data['legislative_districts'] = $this->nutritional_assessment_model->get_unique_legislative_districts();
            $data['school_districts'] = $this->nutritional_assessment_model->get_unique_school_districts();
            $data['school_names'] = $this->nutritional_assessment_model->get_unique_school_names();
            $data['grade_levels'] = $this->nutritional_assessment_model->get_unique_grade_levels();
        } else {
            $data['legislative_districts'] = $this->nutritional_assessment_model->get_unique_legislative_districts_by_user($session_school, $session_school_id);
            $data['school_districts'] = $this->nutritional_assessment_model->get_unique_school_districts_by_user($session_school, $session_school_id);
            $data['school_names'] = $this->nutritional_assessment_model->get_unique_school_names_by_user($session_school, $role, $session_school_id);
            $data['grade_levels'] = $this->nutritional_assessment_model->get_unique_grade_levels_by_user($session_school, $session_school_id);
        }

        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'endline' => 'Endline'
        ];
        
        // Store current filters
        $data['current_filters'] = $filters;

        $this->load->view('view_statistics', $data);
    }

    /**
     * Export statistics to CSV
     */
    public function export_statistics() {
        // Get user session data
        $session_school = $this->session->userdata('school_name');
        $session_school_id = $this->session->userdata('school_id');
        $role = $this->session->userdata('role');

        $filters = [
            'legislative_district' => $this->input->get('legislative_district'),
            'school_district' => $this->input->get('school_district'),
            'school_name' => $this->input->get('school_name'),
            'school_id' => $this->input->get('school_id'),
            'grade_level' => $this->input->get('grade_level'),
            'assessment_type' => $this->input->get('assessment_type'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'nutritional_status' => $this->input->get('nutritional_status')
        ];

        // For non-admin users, force filter to their school
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $filters['school_name'] = $session_school;
            $filters['school_id'] = $session_school_id;
        }

        // Get students based on nutritional status filter
        $status_filter = $filters['nutritional_status'] ?? '';
        $students_to_export = [];
        
        if ($status_filter === '') {
            $students_to_export = $this->nutritional_assessment_model->get_all_students_for_export($filters);
        } else if ($status_filter === 'sbfp_beneficiary') {
            $students_to_export = $this->nutritional_assessment_model->get_sbfp_beneficiaries($filters);
        } else if (!empty($status_filter)) {
            if (in_array($status_filter, ['severely wasted', 'wasted', 'normal', 'overweight', 'obese'])) {
                $students_to_export = $this->nutritional_assessment_model->get_students_by_nutritional_status($status_filter, $filters);
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

        $session_school = $this->session->userdata('school_name');
        $session_school_id = $this->session->userdata('school_id');
        $role = $this->session->userdata('role');

        // For non-admin users, force filter to their school
        if (!empty($session_school) && !in_array($role, ['admin', 'super_admin'])) {
            $school_name = $session_school;
        }

        $data = [];
        
        if ($legislative_district && $school_district && $school_name && $grade_level && $section) {
            // Get baseline data
            $data['baseline'] = $this->nutritional_assessment_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                null,
                'baseline'
            );
            
            // Get endline data
            $data['endline'] = $this->nutritional_assessment_model->get_by_section(
                $legislative_district,
                $school_district,
                $grade_level,
                $section,
                null,
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

        // Get filter dropdown options - FILTERED BY USER'S SCHOOL
        if (in_array($role, ['admin', 'super_admin'])) {
            $data['legislative_districts'] = $this->nutritional_assessment_model->get_unique_legislative_districts();
            $data['school_districts'] = $this->nutritional_assessment_model->get_unique_school_districts();
            $data['school_names'] = $this->nutritional_assessment_model->get_unique_school_names();
            $data['grade_levels'] = $this->nutritional_assessment_model->get_unique_grade_levels();
        } else {
            $data['legislative_districts'] = $this->nutritional_assessment_model->get_unique_legislative_districts_by_user($session_school, $session_school_id);
            $data['school_districts'] = $this->nutritional_assessment_model->get_unique_school_districts_by_user($session_school, $session_school_id);
            $data['school_names'] = $this->nutritional_assessment_model->get_unique_school_names_by_user($session_school, $role, $session_school_id);
            $data['grade_levels'] = $this->nutritional_assessment_model->get_unique_grade_levels_by_user($session_school, $session_school_id);
        }

        $this->load->view('reports/comparison', $data);
    }
}