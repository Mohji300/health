<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_dashboard_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('nutritional_model');
        $this->load->model('user_model');
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
        $assessment_type = $this->input->get('assessment_type') ?: 
            ($this->session->userdata('assessment_type') ?: 'baseline');
    
        $this->session->set_userdata('assessment_type', $assessment_type);
        
        $user_id = $this->session->userdata('user_id');

        // Define school_id first
        $school_id = $this->session->userdata('school_id');
        $school_name = $this->input->get('school_name') ?: $this->session->userdata('school_name') ?: null;

        // Force school_id from database if empty
        if (empty($school_id) && !empty($user_id)) {
            $this->db->select('school_id, name, school_district, legislative_district, school_level');
            $this->db->from('users');
            $this->db->where('id', $user_id);
            $user_query = $this->db->get();
            if ($user_query->num_rows() > 0) {
                $user_row = $user_query->row();
                $school_id = $user_row->school_id;
                $school_name = $user_row->name;
                // Update session with correct values
                $this->session->set_userdata('school_id', $school_id);
                $this->session->set_userdata('school_name', $school_name);
                
            }
        }

        $user_school_level = $this->get_user_school_level($user_id);
        $session_school_level = $this->session->userdata('school_level');
        $filter_school_level = $this->input->get('school_level');
        
        if ($filter_school_level) {
            $school_level = $filter_school_level;
            $this->session->set_userdata('school_level', $school_level);
        } else {
            if ($session_school_level && $session_school_level !== 'all') {
                $school_level = $session_school_level;
            } else {
                $school_level = $user_school_level;
                $this->session->set_userdata('school_level', $school_level);
            }
        }
        
        // Pass school_id to model
        $result = $this->nutritional_model->get_processed_data(
            $assessment_type, 
            $school_name, 
            $school_level,
            $school_id
        );

        $data = [];
        $data['assessment_type'] = $assessment_type;
        $data['school_level'] = $school_level;
        $data['user_actual_school_level'] = $user_school_level;
        $data['school_id'] = $school_id;  // Pass to view
        $data['school_name'] = $school_name;  // Pass to view
        
        // Set display mode based on school level
        $display_mode = 'normal'; // default
        
        if ($school_level === 'stand alone shs') {
            $display_mode = 'shs_only';
        } elseif ($school_level === 'elementary') {
            $display_mode = 'elementary_only';
        } elseif ($school_level === 'secondary') {
            $display_mode = 'secondary_only';
        } elseif (in_array($school_level, ['integrated', 'integrated_elementary', 'integrated_secondary'])) {
            $display_mode = 'integrated';
        }
        
        $data['display_mode'] = $display_mode;
        $data['nutritionalData'] = $result['nutritionalData'];
        $data['grandTotal'] = $result['grandTotal'];
        $data['has_data'] = $result['has_data'];
        $data['processed_count'] = $result['processed_count'];
        $data['selected_school'] = $school_name;
        
        // Get assessment counts with school_level and school_id filters
        $data['baseline_count'] = $this->nutritional_model->get_assessment_count_by_type(
            'baseline', 
            $school_name, 
            $school_level,
            $school_id
        );
        $data['midline_count'] = $this->nutritional_model->get_assessment_count_by_type(
            'midline', 
            $school_name, 
            $school_level,
            $school_id
        );
        $data['endline_count'] = $this->nutritional_model->get_assessment_count_by_type(
            'endline', 
            $school_name, 
            $school_level,
            $school_id
        );
        
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
        
        $this->db->select('school_level');
        $this->db->from('users');
        $this->db->where('id', $user_id);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            
            if (isset($row->school_level) && !empty($row->school_level)) {
                $school_level = trim($row->school_level);
                $school_level_lower = strtolower($school_level);
                
                if ($school_level_lower === 'elementary') {
                    return 'elementary';
                } elseif ($school_level_lower === 'secondary') {
                    return 'secondary';
                } elseif ($school_level_lower === 'integrated') {
                    return 'integrated';
                } elseif ($school_level_lower === 'stand alone shs' || 
                          $school_level_lower === 'standalone_shs' || 
                          $school_level_lower === 'shs' || 
                          $school_level_lower === 'senior high school') {
                    return 'stand alone shs';
                } else {
                    return 'all';
                }
            }
        }
        
        return 'all';
    }
    
    public function set_school_level()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $school_level = $this->input->post('school_level', TRUE);
        
        $valid_levels = ['all', 'elementary', 'secondary', 'integrated', 'integrated_elementary', 'integrated_secondary', 'shs_only', 'stand alone shs'];
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
            'redirect' => site_url('users')
        ]));
    }

    public function set_assessment_type()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $assessment_type = $this->input->post('assessment_type', TRUE);

        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Invalid assessment type'
            ]));
            return;
        }

        $this->session->set_userdata('assessment_type', $assessment_type);

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'Assessment type set to ' . $assessment_type
        ]));
    }
    
    public function get_filtered_data($type = 'baseline')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        if (!in_array($type, ['baseline', 'midline', 'endline'])) {
            $type = 'baseline';
        }
        
        $result = $this->nutritional_model->get_processed_data($type);
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'assessment_type' => $type,
            'data' => $result['nutritionalData'],
            'grandTotal' => $result['grandTotal'],
            'has_data' => $result['has_data'],
            'processed_count' => $result['processed_count']
        ]));
    }
    
    public function fix_session()
    {
        $user_id = $this->session->userdata('user_id');
        
        if ($user_id) {
            $this->db->select('school_level, school_id, name, school_district');
            $this->db->from('users');
            $this->db->where('id', $user_id);
            $query = $this->db->get();
            
            if ($query->num_rows() > 0) {
                $row = $query->row();
                $school_level = trim($row->school_level);
                $school_id = $row->school_id;
                $school_name = $row->name;
                
                $this->session->set_userdata('school_level', $school_level);
                $this->session->set_userdata('school_id', $school_id);
                $this->session->set_userdata('school_name', $school_name);
                
                echo "Session fixed!<br>";
                echo "School level set to: " . $school_level . "<br>";
                echo "School ID set to: " . $school_id . "<br>";
                echo "School Name set to: " . $school_name . "<br><br>";
                echo "<a href='" . site_url('users') . "'>Go to Dashboard</a>";
            } else {
                echo "User not found in database";
            }
        } else {
            echo "No user logged in";
        }
    }
}