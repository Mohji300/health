<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class District_dashboard_controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('district_dashboard_model');
        $this->load->helper('url');
        $this->load->library('session');
        
        // Require login to access dashboard
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        // Check user role - district dashboard only for district accounts
        $role = $this->session->userdata('role');
        $allowed_roles = ['district', 'admin', 'super_admin'];
        
        if (!in_array($role, $allowed_roles)) {
            // Set error message and redirect to login
            $this->session->set_flashdata('error', 'Access denied. You do not have permission to access the district dashboard.');
            // Redirect based on role
            if ($role == 'division') {
                redirect('division_dashboard');
            } elseif ($role == 'user') {
                redirect('user');
            } else {
                redirect('superadmin');
            }
            return;
        }
    }

    public function index() {
        $user_id = $this->session->userdata('user_id');
        $user_type = $this->session->userdata('role');
        $user_district = $this->session->userdata('school_district') ?? 'Unknown District';
        
        $data = array();
        
        // Parse user district
        $parsed_district = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : 'Unknown';
        
        // Get assessment type from URL, session, or default to baseline
        $assessment_type = $this->input->get('assessment_type') ?: 
                          ($this->session->userdata('district_assessment_type') ?: 'baseline');
        
        // Get school level filter from URL, session, or default to 'all'
        $school_level = $this->input->get('school_level') ?: 
                       ($this->session->userdata('district_school_level') ?: 'all');
        
        // Store in session
        $this->session->set_userdata('district_assessment_type', $assessment_type);
        $this->session->set_userdata('district_school_level', $school_level);
        
        $data['assessment_type'] = $assessment_type;
        $data['school_level'] = $school_level;
        
        // Get nutritional data for district with assessment type and school level filters
        $result = $this->district_dashboard_model->get_district_nutritional_data($parsed_district, $assessment_type, $school_level);
        $data['nutritional_data'] = $result['nutritionalData'];
        $data['grand_total'] = $result['grandTotal'];
        $data['has_data'] = $result['has_data'];
        $data['processed_count'] = $result['processed_count'];
        
        // Get assessment counts with school level filter
        $assessment_counts = $this->district_dashboard_model->get_assessment_counts($parsed_district, $school_level);
        $data['baseline_count'] = $assessment_counts['baseline'];
        $data['midline_count'] = $assessment_counts['midline'];
        $data['endline_count'] = $assessment_counts['endline'];
        
        // Get district reports
        if ($parsed_district == 'Unknown' || empty($parsed_district)) {
            $data['district_reports'] = array();
        } else {
            $data['district_reports'] = $this->district_dashboard_model->get_district_reports_summary($parsed_district);
        }
        
        // Get user info
        $data['user_district'] = $user_district;
        $data['user_schools'] = $this->district_dashboard_model->get_district_schools($parsed_district);
        
        // Check user type
        $data['is_division_account'] = strpos(strtolower($user_district), 'division') !== false;
        $data['is_district_account'] = strpos(strtolower($user_district), 'district') !== false;
        $data['parsed_user_district'] = $parsed_district;
        
        // Calculate district stats
        $data['district_stats'] = $this->calculate_district_stats($data['district_reports'], $parsed_district);
        
        // Calculate overall stats for the summary card
        if (!empty($data['district_stats'])) {
            $district_stat = reset($data['district_stats']);
            $data['overall_stats'] = array(
                'total_schools' => $district_stat['total_schools'],
                'total_submitted' => $district_stat['submitted_reports'],
                'overall_completion' => $district_stat['completion_rate']
            );
        } else {
            $data['overall_stats'] = array(
                'total_schools' => 0,
                'total_submitted' => 0,
                'overall_completion' => 0
            );
        }
        
        // Define grade arrays for the view
        $data['elementaryGrades'] = [
            'Kinder_m' => 'Kinder (M)', 'Kinder_f' => 'Kinder (F)', 'Kinder_total' => 'Kinder (Total)',
            'Grade 1_m' => 'Grade 1 (M)', 'Grade 1_f' => 'Grade 1 (F)', 'Grade 1_total' => 'Grade 1 (Total)',
            'Grade 2_m' => 'Grade 2 (M)', 'Grade 2_f' => 'Grade 2 (F)', 'Grade 2_total' => 'Grade 2 (Total)',
            'Grade 3_m' => 'Grade 3 (M)', 'Grade 3_f' => 'Grade 3 (F)', 'Grade 3_total' => 'Grade 3 (Total)',
            'Grade 4_m' => 'Grade 4 (M)', 'Grade 4_f' => 'Grade 4 (F)', 'Grade 4_total' => 'Grade 4 (Total)',
            'Grade 5_m' => 'Grade 5 (M)', 'Grade 5_f' => 'Grade 5 (F)', 'Grade 5_total' => 'Grade 5 (Total)',
            'Grade 6_m' => 'Grade 6 (M)', 'Grade 6_f' => 'Grade 6 (F)', 'Grade 6_total' => 'Grade 6 (Total)'
        ];
        
        $data['secondaryGrades'] = [
            'Grade 7_m' => 'Grade 7 (M)', 'Grade 7_f' => 'Grade 7 (F)', 'Grade 7_total' => 'Grade 7 (Total)',
            'Grade 8_m' => 'Grade 8 (M)', 'Grade 8_f' => 'Grade 8 (F)', 'Grade 8_total' => 'Grade 8 (Total)',
            'Grade 9_m' => 'Grade 9 (M)', 'Grade 9_f' => 'Grade 9 (F)', 'Grade 9_total' => 'Grade 9 (Total)',
            'Grade 10_m' => 'Grade 10 (M)', 'Grade 10_f' => 'Grade 10 (F)', 'Grade 10_total' => 'Grade 10 (Total)',
            'Grade 11_m' => 'Grade 11 (M)', 'Grade 11_f' => 'Grade 11 (F)', 'Grade 11_total' => 'Grade 11 (Total)',
            'Grade 12_m' => 'Grade 12 (M)', 'Grade 12_f' => 'Grade 12 (F)', 'Grade 12_total' => 'Grade 12 (Total)'
        ];
        
        // Set title for template
        $data['title'] = 'District Dashboard';
        
        // Load view
        $this->load->view('district_dashboard', $data);
    }
    
    /**
     * AJAX: Set school level filter in session
     */
    public function set_school_level() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $school_level = $this->input->post('school_level', TRUE);
        $assessment_type = $this->input->post('assessment_type', TRUE);
        
        // Validate school level
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary'];
        if (!in_array($school_level, $valid_levels)) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Invalid school level'
            ]));
            return;
        }

        $this->session->set_userdata('district_school_level', $school_level);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'School level filter updated',
            'redirect' => site_url('district_dashboard?assessment_type=' . $assessment_type . '&school_level=' . $school_level)
        ]));
    }
    
    public function get_school_details($school_name) {
        $this->output->set_content_type('application/json');
        
        $school_details = $this->district_dashboard_model->get_school_details(urldecode($school_name));
        
        if ($school_details) {
            echo json_encode(array('success' => true, 'data' => $school_details));
        } else {
            echo json_encode(array('success' => false, 'message' => 'School not found'));
        }
    }
    
    private function calculate_district_stats($district_reports, $district_name) {
        $stats = array();
        
        if (isset($district_reports[$district_name])) {
            $district_data = $district_reports[$district_name];
            $total = $district_data['total'] ?? 0;
            $submitted = $district_data['submitted'] ?? 0;
            $completion_rate = $total > 0 ? round(($submitted / $total) * 100) : 0;
            
            $stats[$district_name] = array(
                'submitted_reports' => $submitted,
                'total_schools' => $total,
                'completion_rate' => $completion_rate,
                'status' => $total > 0 ? 
                    ($submitted === $total ? 'Completed' : 'In Progress') : 'No Data'
            );
        } else {
            // If no reports found, create empty stats
            $stats[$district_name] = array(
                'submitted_reports' => 0,
                'total_schools' => 0,
                'completion_rate' => 0,
                'status' => 'No Data'
            );
        }
        
        return $stats;
    }
}