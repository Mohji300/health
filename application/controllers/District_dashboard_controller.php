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
        
        // Get assessment type from URL or session
        $assessment_type = $this->input->get('assessment_type') ?? 'baseline';
        $data['assessment_type'] = $assessment_type;
        
        // Get nutritional data for district with assessment type filter
        $data['nutritional_data'] = $this->district_dashboard_model->get_district_nutritional_data($parsed_district, $assessment_type);
        $data['grand_total'] = $this->district_dashboard_model->get_district_grand_total($parsed_district, $assessment_type);
        
        // Get assessment counts
        $assessment_counts = $this->district_dashboard_model->get_assessment_counts($parsed_district);
        $data['baseline_count'] = $assessment_counts['baseline'];
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
        
        // Check if we have data for the current assessment type
        $data['has_data'] = !empty($data['nutritional_data']);
        $data['processed_count'] = $data['grand_total'];
        
        // Set title for template
        $data['title'] = 'District Dashboard';
        
        // Load view
        $this->load->view('district_dashboard', $data);
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