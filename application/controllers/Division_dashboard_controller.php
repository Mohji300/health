<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Division_dashboard_controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('division_dashboard_model');
        $this->load->helper('url');
        $this->load->library('session');
        
        // Require login to access dashboard
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        // Check user role - division dashboard only for division accounts
        $role = $this->session->userdata('role');
        $allowed_roles = ['division', 'admin', 'super_admin'];
        
        if (!in_array($role, $allowed_roles)) {
            // Set error message and redirect
            $this->session->set_flashdata('error', 'Access denied. You do not have permission to access the division dashboard.');
            // Redirect based on role
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

    public function index() {
        $user_id = $this->session->userdata('user_id');
        $user_type = $this->session->userdata('role');
        $user_district = $this->session->userdata('district') ?? 'Unknown District';
        
        $data = array();
        
        // Parse user district
        $parsed_district = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : 'Unknown';
        
        // ===== ASSESSMENT TYPE HANDLING =====
        // Get assessment type from URL, POST, or session
        $assessment_type = $this->input->get('assessment_type') ?: 
                          ($this->input->post('assessment_type') ?: 
                          ($this->session->userdata('division_assessment_type') ?: 'baseline'));
        
        // Validate assessment type
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            $assessment_type = 'baseline';
        }
        
        // Save to session
        $this->session->set_userdata('division_assessment_type', $assessment_type);
        $data['assessment_type'] = $assessment_type;
        
        // ===== SCHOOL LEVEL FILTER HANDLING =====
        // Get school level filter from URL or session
        $school_level = $this->input->get('school_level') ?: 
                       ($this->session->userdata('division_school_level') ?: 'all');
        
        // Validate school level
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary'];
        if (!in_array($school_level, $valid_levels)) {
            $school_level = 'all';
        }
        
        // Save to session
        $this->session->set_userdata('division_school_level', $school_level);
        $data['school_level'] = $school_level;
        
        // ===== GET NUTRITIONAL DATA WITH FILTERS =====
        // Get nutritional data for entire division with assessment type AND school level filter
        $data['nutritional_data'] = $this->division_dashboard_model->get_division_nutritional_data($assessment_type, $school_level);
        $data['grand_total'] = $this->division_dashboard_model->get_division_grand_total($assessment_type, $school_level);
        
        // Get assessment counts
        $assessment_counts = $this->division_dashboard_model->get_assessment_counts_division($school_level);
        $data['baseline_count'] = $assessment_counts['baseline'];
        $data['midline_count'] = $assessment_counts['midline']; // NEW: Midline count
        $data['endline_count'] = $assessment_counts['endline'];
        
        // ===== DISTRICT AND SCHOOLS DATA =====
        // Get all districts with their schools and submission stats DIRECTLY
        $all_districts = $this->division_dashboard_model->get_all_districts();
        $data['district_schools_summary'] = [];
        $data['all_schools_by_district'] = [];
        
        $total_schools = 0;
        $submitted_schools = 0;
        
        foreach ($all_districts as $district) {
            $district_name = $district['name'];
            $schools = $this->division_dashboard_model->get_schools_by_district($district_name);
            
            $data['all_schools_by_district'][$district_name] = $schools;
            
            $district_total = count($schools);
            $district_submitted = 0;
            
            foreach ($schools as $school) {
                if ($school['has_submitted']) {
                    $district_submitted++;
                }
            }
            
            $total_schools += $district_total;
            $submitted_schools += $district_submitted;
            
            $district_completion = $district_total > 0 ? round(($district_submitted / $district_total) * 100) : 0;
            $district_status = $district_total > 0 ? 
                ($district_submitted == $district_total ? 'Completed' : 
                 ($district_submitted > 0 ? 'In Progress' : 'Not Started')) : 'No Schools';
            
            $data['district_schools_summary'][$district_name] = [
                'total_schools' => $district_total,
                'submitted_schools' => $district_submitted,
                'completion_rate' => $district_completion,
                'status' => $district_status,
                'schools' => $schools
            ];
        }
        
        // Calculate overall completion rate
        $overall_completion = $total_schools > 0 ? round(($submitted_schools / $total_schools) * 100) : 0;
        
        // Set overall stats
        $data['overall_stats'] = [
            'total_schools' => $total_schools,
            'total_submitted' => $submitted_schools,
            'overall_completion' => $overall_completion
        ];
        
        // For backward compatibility
        $data['user_district'] = $user_district;
        $data['user_schools'] = $this->division_dashboard_model->get_user_schools($user_id, $user_type, $user_district);
        $data['is_division_account'] = strpos(strtolower($user_district), 'division') !== false;
        $data['parsed_user_district'] = $parsed_district;
        
        // Get district reports for compatibility (optional)
        $data['district_reports'] = $this->division_dashboard_model->get_district_reports();
        $data['district_stats'] = $this->calculate_division_stats($data['district_reports']);
        
        // Check if we have data
        $data['has_data'] = !empty($data['nutritional_data']);
        $data['processed_count'] = $data['grand_total'];
        
        $data['title'] = 'Division Dashboard';
        
        // Load the full-page division dashboard view
        $this->load->view('division_dashboard', $data);
    }
    
    /**
     * AJAX: Set assessment type in session
     */
    public function set_assessment_type()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $assessment_type = $this->input->post('assessment_type', TRUE);
        
        // Validate assessment type - NOW INCLUDES MIDLINE
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Invalid assessment type'
            ]));
            return;
        }

        // Set assessment type in session
        $this->session->set_userdata('division_assessment_type', $assessment_type);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'Assessment type set to ' . $assessment_type
        ]));
    }
    
    /**
     * AJAX: Set school level filter in session
     */
    public function set_school_level()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $school_level = $this->input->post('school_level', TRUE);
        
        // Validate school level
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary'];
        if (!in_array($school_level, $valid_levels)) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Invalid school level'
            ]));
            return;
        }

        $this->session->set_userdata('division_school_level', $school_level);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'School level filter updated'
        ]));
    }
    
    public function get_school_details($school_name) {
        $this->output->set_content_type('application/json');
        
        $school_details = $this->division_dashboard_model->get_school_details(urldecode($school_name));
        
        if ($school_details) {
            echo json_encode(['success' => true, 'data' => $school_details]);
        } else {
            echo json_encode(['success' => false, 'message' => 'School not found']);
        }
    }
    
    private function calculate_division_stats($district_reports) {
        $stats = array();
        
        // Flatten the district reports structure
        foreach ($district_reports as $legislative_district => $districts) {
            foreach ($districts as $district_name => $district_data) {
                $total = $district_data['total'] ?? 0;
                $submitted = $district_data['submitted'] ?? 0;
                $completion_rate = $total > 0 ? round(($submitted / $total) * 100) : 0;
                
                $stats[$district_name] = array(
                    'submitted_reports' => $submitted,
                    'total_schools' => $total,
                    'completion_rate' => $completion_rate,
                    'status' => $total > 0 ? 
                        ($submitted === $total ? 'Completed' : 
                         ($submitted > 0 ? 'In Progress' : 'Not Started')) : 'No Schools',
                    'legislative_district' => $legislative_district
                );
            }
        }
        
        return $stats;
    }
}