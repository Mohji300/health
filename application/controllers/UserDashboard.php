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
        
        // FIX 1: The session key is 'user_id', not 'id'
        $user_id = $this->session->userdata('user_id');
        
        // IMPORTANT: Get the user's actual school level from the users table
        $user_school_level = $this->get_user_school_level($user_id);
        
        // FIX 2: Check if session already has school_level, if not, set it
        $session_school_level = $this->session->userdata('school_level');
        
        // School level filter - if coming from URL parameter, use that
        $filter_school_level = $this->input->get('school_level');
        
        if ($filter_school_level) {
            // If filter is applied via URL, use that
            $school_level = $filter_school_level;
            // Update session with filter
            $this->session->set_userdata('school_level', $school_level);
        } else {
            // If session has school_level and it's not 'all', use it
            if ($session_school_level && $session_school_level !== 'all') {
                $school_level = $session_school_level;
            } else {
                // Otherwise, use the user's actual school level from the database
                $school_level = $user_school_level;
                // Update session with the correct value
                $this->session->set_userdata('school_level', $school_level);
            }
        }
        
        $school_name = $this->input->get('school_name') ?: $this->session->userdata('school_name') ?: null;

        // Pass school_level to model
        $result = $this->Nutritional_model->get_processed_data($assessment_type, $school_name, $school_level);

        $data = [];
        $data['assessment_type'] = $assessment_type;
        $data['school_level'] = $school_level;
        $data['user_actual_school_level'] = $user_school_level; // Pass this for debugging
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
    
    /**
     * Get the user's actual school level from the users table
     */
    private function get_user_school_level($user_id)
    {
        if (!$user_id) {
            return 'all';
        }
        
        // FIX 3: The column name is 'id' in users table, not 'user_id'
        $this->db->select('school_level');
        $this->db->from('users');
        $this->db->where('id', $user_id); // Changed from 'user_id' to 'id'
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            
            // Check if school_level exists and is not null
            if (isset($row->school_level) && !empty($row->school_level)) {
                $school_level = strtolower(trim($row->school_level));
                
                // Map database values to our expected values
                switch ($school_level) {
                    case 'elementary':
                        return 'elementary'; // Return lowercase for consistency
                    case 'secondary':
                        return 'secondary'; // Return lowercase for consistency
                    case 'integrated':
                        return 'integrated'; // Return lowercase for consistency
                    case 'standalone_shs':
                    case 'shs':
                    case 'senior high school':
                    case 'senior high':
                        return 'standalone_shs';
                    default:
                        return 'all';
                }
            }
        }
        
        // If no school level found, return 'all'
        return 'all';
    }
    
    // Update the validation in set_school_level method
    public function set_school_level()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $school_level = $this->input->post('school_level', TRUE);
        
        // FIX 4: Use lowercase for consistency
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary', 'standalone_shs'];
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
    
    /**
     * Temporary method to fix session
     */
    public function fix_session()
    {
        $user_id = $this->session->userdata('user_id');
        
        if ($user_id) {
            // Get school level from database
            $this->db->select('school_level');
            $this->db->from('users');
            $this->db->where('id', $user_id);
            $query = $this->db->get();
            
            if ($query->num_rows() > 0) {
                $row = $query->row();
                $school_level = strtolower(trim($row->school_level));
                
                // Update session
                $this->session->set_userdata('school_level', $school_level);
                
                echo "Session fixed! School level set to: " . $school_level;
                echo "<br><br>";
                echo "Current session data:<br>";
                echo "<pre>";
                print_r($this->session->userdata());
                echo "</pre>";
                echo "<br><br><a href='" . site_url('userdashboard') . "'>Go to Dashboard</a>";
            } else {
                echo "User not found in database";
            }
        } else {
            echo "No user logged in";
        }
    }
}