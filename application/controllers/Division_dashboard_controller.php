<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Division_dashboard_controller extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('division_dashboard_model', 'division_dashboard_model', true);
        $this->load->helper('url');
        $this->load->library('session');

        // Require login
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        // Role check
        $role = $this->session->userdata('role');
        $allowed_roles = ['division', 'admin', 'super_admin'];

        if (!in_array($role, $allowed_roles)) {
            $this->session->set_flashdata(
                'error',
                'Access denied. You do not have permission to access the division dashboard.'
            );

            if ($role == 'district') {
                redirect('district_dashboard');
            } elseif ($role == 'user') {
                redirect('user');
            } else {
                redirect('superadmin');
            }
            return;
        }
    }

    /**
     * Debug method to check database relationships.
     *
     * NOTE: nutritional_assessments.school_id stores the school CODE
     * (matching schools.school_id), NOT the numeric schools.id.
     * All lookups in this method compare the CODE column.
     */
    public function debug_school_assessments() {
        $this->output->set_content_type('application/json');

        $debug_info = [];

        // 1. Check if tables exist
        $debug_info['tables_exist'] = [
            'schools'                 => $this->db->table_exists('schools'),
            'school_districts'        => $this->db->table_exists('school_districts'),
            'nutritional_assessments' => $this->db->table_exists('nutritional_assessments')
        ];

        // 2. Check nutritional_assessments table structure
        if ($this->db->table_exists('nutritional_assessments')) {
            $fields = $this->db->list_fields('nutritional_assessments');
            $debug_info['nutritional_assessments_fields'] = $fields;
            $debug_info['has_school_id_column']   = in_array('school_id', $fields);
            $debug_info['has_school_name_column'] = in_array('school_name', $fields);
        }

        // 3. Sample assessments
        $sample_assessments = $this->db
            ->select('id, school_id, school_name, assessment_type, created_at')
            ->from('nutritional_assessments')
            ->where('is_deleted', 0)
            ->limit(10)
            ->order_by('id', 'DESC')
            ->get()
            ->result_array();

        $debug_info['sample_assessments'] = $sample_assessments;

        // 4. Match assessment school_id VALUES against schools.school_id (the CODE column)
        if (!empty($sample_assessments)) {
            $school_codes_in_assessments = array_unique(array_column($sample_assessments, 'school_id'));
            $school_codes_in_assessments = array_filter($school_codes_in_assessments);

            $debug_info['school_ids_in_assessments'] = $school_codes_in_assessments;

            if (!empty($school_codes_in_assessments)) {
                $matching_schools = $this->db
                    ->select('id, name, school_id')
                    ->from('schools')
                    ->where_in('school_id', $school_codes_in_assessments)   // ← FIXED
                    ->get()
                    ->result_array();

                $debug_info['matching_schools']       = $matching_schools;
                $debug_info['matching_schools_count'] = count($matching_schools);
            } else {
                $debug_info['matching_schools'] = 'No school_ids found in assessments';
            }
        }

        // 5. Count schools with assessments
        if ($this->db->table_exists('nutritional_assessments')) {
            $count_by_id = $this->db
                ->select('COUNT(DISTINCT school_id) as count')
                ->from('nutritional_assessments')
                ->where('is_deleted', 0)
                ->where('school_id IS NOT NULL')
                ->get()
                ->row()->count ?? 0;

            $debug_info['schools_with_assessments_by_id'] = $count_by_id;

            $count_by_name = $this->db
                ->select('COUNT(DISTINCT school_name) as count')
                ->from('nutritional_assessments')
                ->where('is_deleted', 0)
                ->where('school_name IS NOT NULL')
                ->where('school_name !=', '')
                ->get()
                ->row()->count ?? 0;

            $debug_info['schools_with_assessments_by_name'] = $count_by_name;
        }

        // 6. Current session assessment
        $debug_info['current_assessment_type'] =
            $this->session->userdata('division_assessment_type') ?: 'baseline';

        // 7. Sample district with schools
        $first_district = $this->db
            ->select('id, name')
            ->from('school_districts')
            ->limit(1)
            ->get()
            ->row_array();

        if ($first_district) {
            $debug_info['sample_district'] = $first_district;

            $schools_in_district = $this->db
                ->select('id, name, school_id')
                ->from('schools')
                ->where('school_district_id', $first_district['id'])
                ->limit(5)
                ->get()
                ->result_array();

            $debug_info['sample_schools_in_district'] = $schools_in_district;

            foreach ($schools_in_district as &$school) {
                // FIXED: use $school['school_id'] (the code), not $school['id'] (the PK)
                $school['has_baseline'] = $this->db
                    ->from('nutritional_assessments')
                    ->where('school_id', $school['school_id'])
                    ->where('is_deleted', 0)
                    ->where('assessment_type', 'baseline')
                    ->count_all_results() > 0;

                $school['has_midline'] = $this->db
                    ->from('nutritional_assessments')
                    ->where('school_id', $school['school_id'])
                    ->where('is_deleted', 0)
                    ->where('assessment_type', 'midline')
                    ->count_all_results() > 0;

                $school['has_endline'] = $this->db
                    ->from('nutritional_assessments')
                    ->where('school_id', $school['school_id'])
                    ->where('is_deleted', 0)
                    ->where('assessment_type', 'endline')
                    ->count_all_results() > 0;
            }
            unset($school);

            $debug_info['schools_with_assessment_status'] = $schools_in_district;
        }

        echo json_encode($debug_info, JSON_PRETTY_PRINT);
    }

    /**
     * Dashboard shell.
     * Loads only cheap lookups; heavy assessment data is loaded via AJAX.
     */
    public function index() {
        $user_id       = $this->session->userdata('user_id');
        $user_district = $this->session->userdata('district') ?? 'Unknown District';

        $data = [];

        $parsed_district = $user_district
            ? preg_replace('/\s+(District|Division)$/', '', $user_district)
            : 'Unknown';

        /*
         * ---------------------------------------------------------
         * FILTERS
         * ---------------------------------------------------------
         */
        $assessment_type = $this->input->get('assessment_type')
                        ?: ($this->session->userdata('division_assessment_type') ?: 'baseline');

        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            $assessment_type = 'baseline';
        }

        $school_level = $this->input->get('school_level')
                      ?: ($this->session->userdata('division_school_level') ?: 'all');

        $valid_levels = [
            'all',
            'elementary',
            'secondary',
            'integrated',
            'integrated_elementary',
            'integrated_secondary',
            'shs_only'
        ];

        if (!in_array($school_level, $valid_levels)) {
            $school_level = 'all';
        }

        $legislative_district_id = $this->input->get('legislative_district_id');

        if ($legislative_district_id === null || $legislative_district_id === '') {
            $legislative_district_id = $this->session->userdata('division_legislative_district_id') ?: null;
        }

        if ($legislative_district_id !== null && $legislative_district_id !== '') {
            $legislative_district_id = (int) $legislative_district_id;
            if ($legislative_district_id < 1) {
                $legislative_district_id = null;
            }
        }

        // Save filters
        $this->session->set_userdata([
            'division_assessment_type'         => $assessment_type,
            'division_school_level'            => $school_level,
            'division_legislative_district_id' => $legislative_district_id
        ]);

        /*
         * ---------------------------------------------------------
         * PAGE DATA
         * ---------------------------------------------------------
         */
        $data['assessment_type']                  = $assessment_type;
        $data['school_level']                     = $school_level;
        $data['selected_legislative_district_id'] = $legislative_district_id;

        $data['legislative_districts'] = $this->division_dashboard_model->get_legislative_districts();

        $data['user_district']        = $user_district;
        $data['is_division_account']  = strpos(strtolower($user_district), 'division') !== false;
        $data['parsed_user_district'] = $parsed_district;

        // User name
        $display_name = '';
        if (!empty($user_id)) {
            $query = $this->db
                ->select('name')
                ->from('users')
                ->where('id', $user_id)
                ->limit(1)
                ->get();

            if ($query && $query->num_rows() > 0) {
                $display_name = $query->row()->name ?? '';
            }
        }
        $data['user_name'] = $display_name;

        /*
         * Empty placeholders — JS will populate these.
         */
        $data['nutritional_data']         = [];
        $data['grand_total']              = 0;
        $data['baseline_count']           = 0;
        $data['midline_count']            = 0;
        $data['endline_count']            = 0;
        $data['district_schools_summary'] = [];

        $data['overall_stats'] = [
            'total_schools'      => 0,
            'total_submitted'    => 0,
            'overall_completion' => 0
        ];

        $data['has_data']        = false;
        $data['processed_count'] = 0;
        $data['title']           = 'Division Dashboard';

        $this->load->view('division_dashboard', $data);
    }

    /**
     * AJAX: Load dashboard data for ONE assessment type.
     *
     * Separate from index() so the page shell loads fast.
     * Returns both JSON stats and pre-rendered HTML partials
     * so the table structure stays 100% consistent with PHP rendering.
     */
    public function get_dashboard_data() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $assessment_type = $this->input->get('assessment_type', TRUE);
        $school_level    = $this->input->get('school_level', TRUE);
        $legislative_district_id = $this->input->get('legislative_district_id', TRUE);

        /*
         * Validate assessment type.
         */
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid assessment type.'
                ]));
        }

        /*
         * Validate school level.
         */
        $valid_levels = [
            'all',
            'elementary',
            'secondary',
            'integrated',
            'integrated_elementary',
            'integrated_secondary',
            'shs_only'
        ];

        if (!in_array($school_level, $valid_levels)) {
            $school_level = 'all';
        }

        /*
         * Validate legislative district.
         */
        if ($legislative_district_id !== null && $legislative_district_id !== '') {
            $legislative_district_id = (int) $legislative_district_id;
            if ($legislative_district_id < 1) {
                $legislative_district_id = null;
            }
        } else {
            $legislative_district_id = null;
        }

        // Save current filters
        $this->session->set_userdata([
            'division_assessment_type'         => $assessment_type,
            'division_school_level'            => $school_level,
            'division_legislative_district_id' => $legislative_district_id
        ]);

        /*
         * ---------------------------------------------------------
         * LOAD ONLY THE SELECTED ASSESSMENT
         * ---------------------------------------------------------
         */
        $nutritional_data = $this->division_dashboard_model
            ->get_division_nutritional_data($assessment_type, $school_level, $legislative_district_id);

        $grand_total = $this->division_dashboard_model
            ->get_division_grand_total($assessment_type, $school_level, $legislative_district_id);

        $assessment_count = $this->division_dashboard_model
            ->get_assessment_count_division($assessment_type, $school_level, $legislative_district_id);

        $district_summaries = $this->division_dashboard_model
            ->get_district_summary(
                $assessment_type,
                $school_level,
                $legislative_district_id
            );

        /*
         * ---------------------------------------------------------
         * BUILD DISTRICT SUMMARY
         * ---------------------------------------------------------
         */
        $total_schools     = 0;
        $submitted_schools = 0;
        $district_schools_summary = [];

        foreach ($district_summaries as $row) {
            $district_name      = $row['district_name'];
            $district_total     = (int) $row['total_schools'];
            $district_submitted = (int) $row['submitted_schools'];

            $total_schools     += $district_total;
            $submitted_schools += $district_submitted;

            $district_completion = $district_total > 0
                ? round(($district_submitted / $district_total) * 100)
                : 0;

            if ($district_total <= 0) {
                $district_status = 'No Schools';
            } elseif ($district_submitted >= $district_total) {
                $district_status = 'Completed';
            } elseif ($district_submitted > 0) {
                $district_status = 'In Progress';
            } else {
                $district_status = 'Not Started';
            }

            $district_schools_summary[$district_name] = [
                'total_schools'     => $district_total,
                'submitted_schools' => $district_submitted,
                'completion_rate'   => $district_completion,
                'status'            => $district_status
            ];
        }

        $overall_completion = $total_schools > 0
            ? round(($submitted_schools / $total_schools) * 100)
            : 0;

        /*
         * ---------------------------------------------------------
         * RENDER PARTIAL HTML (server-side)
         * ---------------------------------------------------------
         */
        $table_html = $this->load->view('partials/division_report_tables', [
            'nutritional_data' => $nutritional_data,
            'school_level'     => $school_level,
            'assessment_type'  => $assessment_type,
            'has_data'         => !empty($nutritional_data)
        ], TRUE);

        $district_html = $this->load->view('partials/division_district_cards', [
            'district_schools_summary' => $district_schools_summary
        ], TRUE);

        /*
         * ---------------------------------------------------------
         * RESPONSE
         * ---------------------------------------------------------
         */
        $response = [
            'success' => true,

            'assessment_type' => $assessment_type,
            'school_level' => $school_level,

            'assessment_count' => $assessment_count,

            'overall_stats' => [
                'total_schools' => $total_schools,
                'total_submitted' => $submitted_schools,
                'overall_completion' => $overall_completion
            ],

            'table_html' => $table_html,
            'district_html' => $district_html
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * AJAX: Get all schools for a district, with assessment status.
     */
    public function get_district_schools() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $district_name = $this->input->get('district');

        if (empty($district_name)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'District name required'
                ]));
            return;
        }

        $district = $this->db
            ->select('id')
            ->from('school_districts')
            ->where('name', $district_name)
            ->get()
            ->row();

        if (!$district) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'District not found'
                ]));
            return;
        }

        $assessment_type = $this->session->userdata('division_assessment_type') ?: 'baseline';

        $schools = $this->division_dashboard_model
            ->get_schools_with_status($district->id, $assessment_type);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'schools' => $schools
            ]));
    }

    /**
     * AJAX: Get consolidated records for a district.
     */
    public function get_district_report() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $district_name = $this->input->get('district', TRUE);

        if (empty($district_name)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'District name required'
                ]));
            return;
        }

        $records = $this->division_dashboard_model->get_district_consolidated_records($district_name);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'records' => $records
            ]));
    }

    /**
     * AJAX: Set assessment type in session.
     *
     * NOTE: This only updates the session. The actual data is
     * fetched by get_dashboard_data(). No redirect.
     */
    public function set_assessment_type() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $assessment_type = $this->input->post('assessment_type', TRUE);

        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid assessment type'
                ]));
            return;
        }

        $this->session->set_userdata('division_assessment_type', $assessment_type);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'message' => 'Assessment type set to ' . $assessment_type
            ]));
    }

    /**
     * AJAX: Set school level filter in session.
     */
    public function set_school_level() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $school_level = $this->input->post('school_level', TRUE);

        $valid_levels = [
            'all',
            'elementary',
            'secondary',
            'integrated',
            'integrated_elementary',
            'integrated_secondary',
            'shs_only'
        ];

        if (!in_array($school_level, $valid_levels)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid school level'
                ]));
            return;
        }

        $this->session->set_userdata('division_school_level', $school_level);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'message' => 'School level filter updated'
            ]));
    }

    /**
     * AJAX: Get details for one school.
     */
    public function get_school_details($school_name) {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $this->output->set_content_type('application/json');

        $school_details = $this->division_dashboard_model
            ->get_school_details(urldecode($school_name));

        if ($school_details) {
            $this->output->set_output(json_encode([
                'success' => true,
                'data'    => $school_details
            ]));
        } else {
            $this->output
                ->set_status_header(404)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'School not found or could not be loaded'
                ]));
        }
    }

    /**
     * Helper: legacy division stats (kept for compatibility).
     */
    private function calculate_division_stats($district_reports) {
        $stats = [];

        foreach ($district_reports as $legislative_district => $districts) {
            foreach ($districts as $district_name => $district_data) {
                $total     = $district_data['total']     ?? 0;
                $submitted = $district_data['submitted'] ?? 0;

                $completion_rate = $total > 0 ? round(($submitted / $total) * 100) : 0;

                $stats[$district_name] = [
                    'submitted_reports'    => $submitted,
                    'total_schools'        => $total,
                    'completion_rate'      => $completion_rate,
                    'status'               => $total > 0
                        ? ($submitted === $total ? 'Completed'
                            : ($submitted > 0 ? 'In Progress' : 'Not Started'))
                        : 'No Schools',
                    'legislative_district' => $legislative_district
                ];
            }
        }

        return $stats;
    }
}