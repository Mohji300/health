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
        // Allow overriding school via GET (for redirects) otherwise use session
        $school_name = $this->input->get('school_name') ?: $this->session->userdata('school_name') ?: null;

        // Fetch processed nutritional data from model with optional school filter
        $result = $this->Nutritional_model->get_processed_data($assessment_type, $school_name);

        $data = [];
        $data['assessment_type'] = $assessment_type;
        $data['nutritionalData'] = $result['nutritionalData'];
        $data['grandTotal'] = $result['grandTotal'];
        $data['has_data'] = $result['has_data'];
        $data['processed_count'] = $result['processed_count'];
        $data['selected_school'] = $school_name;
        
        // Get assessment counts for the toggle switch
        $data['baseline_count'] = $this->Nutritional_model->get_assessment_count_by_type('baseline');
        $data['midline_count'] = $this->Nutritional_model->get_assessment_count_by_type('midline');
        $data['endline_count'] = $this->Nutritional_model->get_assessment_count_by_type('endline');
        
        // Check if current type has data
        $data['current_type_has_data'] = $this->Nutritional_model->has_assessment_data($assessment_type);

        // Load view
        $this->load->view('user_dashboard', $data);
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