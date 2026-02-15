<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserDashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nutritional_model');
        $this->load->model('User_model');
        $this->load->library('form_validation');
        $this->load->helper('url');

        // Require login to access user dashboard
        $this->load->library('session');
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        // Allow appropriate roles (adjust as needed)
        $allowed = ['user', 'admin', 'district', 'division', 'super_admin'];
        $role = $this->session->userdata('role');
        if (!in_array($role, $allowed)) {
            show_error('Access denied. Insufficient privileges.', 403);
            return;
        }
    }

    public function index()
    {
        // Get current assessment type from session
        $assessment_type = $this->session->userdata('assessment_type') ?: 'baseline';
        
        // Get school level filter from GET or session or default to 'all'
        $school_level = $this->input->get('school_level') ?: 
                    ($this->session->userdata('school_level') ?: 'all');
        
        // Always set it in session
        $this->session->set_userdata('school_level', $school_level);
        
        $school_name = $this->input->get('school_name') ?: $this->session->userdata('school_name') ?: null;

        // Pass school_level to model
        $result = $this->Nutritional_model->get_processed_data($assessment_type, $school_name, $school_level);

        $data = [];
        $data['assessment_type'] = $assessment_type;
        $data['school_level'] = $school_level;
        $data['nutritionalData'] = $result['nutritionalData'];
        $data['grandTotal'] = $result['grandTotal'];
        $data['has_data'] = $result['has_data'];
        $data['processed_count'] = $result['processed_count'];
        $data['selected_school'] = $school_name;
        
        // Get assessment counts with school_level filter
        $data['baseline_count'] = $this->Nutritional_model->get_assessment_count_by_type('baseline', $school_name, $school_level);
        $data['midline_count'] = $this->Nutritional_model->get_assessment_count_by_type('midline', $school_name, $school_level);
        $data['endline_count'] = $this->Nutritional_model->get_assessment_count_by_type('endline', $school_name, $school_level);
        
        $this->load->view('user_dashboard', $data);
    }
    
    // Update the validation in set_school_level method
    public function set_school_level()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $school_level = $this->input->post('school_level', TRUE);
        
        // Update validation for new levels
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary'];
        if (!in_array($school_level, $valid_levels)) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Invalid school level'
            ]));
            return;
        }

        $this->session->set_userdata('school_level', $school_level);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'School level filter updated',
            'redirect' => site_url('userdashboard')
        ]));
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
        
        // Add 'midline' to the allowed assessment types
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Invalid assessment type'
            ]));
            return;
        }

        // Set assessment type in session
        $this->session->set_userdata('assessment_type', $assessment_type);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'Assessment type set to ' . $assessment_type,
            'redirect' => site_url('userdashboard')
        ]));
    }
    
    /**
     * Get filtered processed data by assessment type (for AJAX)
     */
    public function get_filtered_data($type = 'baseline')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        // Validate type
        if (!in_array($type, ['baseline', 'midline', 'endline'])) {
            $type = 'baseline';
        }
        
        // Fetch processed nutritional data from model with assessment_type filter
        $result = $this->Nutritional_model->get_processed_data($type);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'assessment_type' => $type,
            'data' => $result['nutritionalData'],
            'grandTotal' => $result['grandTotal'],
            'has_data' => $result['has_data'],
            'processed_count' => $result['processed_count']
        ]));
    }
    

}