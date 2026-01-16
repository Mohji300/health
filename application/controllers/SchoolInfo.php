<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SchoolInfo extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('User_model');
        $this->load->model('Legislative_district_model');
        $this->load->helper('url');
        
        // Check if user is logged in
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }
        
        // Check if user already completed school info
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        // For ALL roles, check if school info is completed
        if ($user && $user->school_info_completed) {
            // Redirect to appropriate dashboard based on role
            $this->redirect_by_role($user->role);
        }
    }

    public function show_form() {
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        $role = $this->session->userdata('role');
        $title_prefix = '';
        
        // Set different title based on role
        switch ($role) {
            case 'division':
                $title_prefix = 'Division Office - ';
                break;
            case 'district':
                $title_prefix = 'District Office - ';
                break;
            case 'admin':
            case 'super_admin':
                $title_prefix = 'Administrator - ';
                break;
            case 'user':
                $title_prefix = 'School - ';
                break;
        }

        // Fetch districts with related school districts (needed for school and district users)
        $districts = [];
        if ($role == 'user' || $role == 'district') {
            $districts = $this->Legislative_district_model->get_districts_with_school_districts();
        }

        $data = [
            'user' => $user,
            'districts' => $districts,
            'errors' => $this->session->flashdata('errors'),
            'error_message' => $this->session->flashdata('error_message'),
            'input_data' => $this->session->flashdata('input_data'),
            'title' => $title_prefix . 'Complete Profile Information',
            'user_role' => $role
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('school_info_form', $data);
        $this->load->view('templates/footer');
    }

    public function store() {
        $user_role = $this->session->userdata('role');
        
        // Set validation rules based on user role
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[255]');
        $this->form_validation->set_rules('address', 'Address', 'required|max_length[500]');
        // Only require SchoolDistricts when the role has that field in the form
        if ($user_role == 'user' || $user_role == 'division' || $user_role == 'district') {
            $this->form_validation->set_rules('SchoolDistricts', 'District/Division/Region', 'required');
        }
        $this->form_validation->set_rules('head_name', 'Head/Officer/Position Name', 'required|max_length[255]');
        
        // Note: `school_id` field removed from form; admin IDs will be assigned server-side
        
        // Only require level for roles that show it (school, division, district)
        if ($user_role == 'user' || $user_role == 'division' || $user_role == 'district') {
            $this->form_validation->set_rules('level', 'Level/Type', 'required');
        }
        
        // Only require legislative district for school users
        if ($user_role == 'user') {
            $this->form_validation->set_rules('legislativeDistricts', 'Legislative District', 'required');
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', $this->form_validation->error_array());
            $this->session->set_flashdata('input_data', $this->input->post());
            $this->session->set_flashdata('error_message', 'Please correct the errors below.');
            redirect('school-info/form');
        }

        try {
            $user_id = $this->session->userdata('user_id');
            $user = $this->User_model->get_user_by_id($user_id);
            
            // Prevent duplicate completion
            if ($user->school_info_completed) {
                $this->session->set_flashdata('error_message', 'Profile information has already been completed');
                redirect('school-info/form');
            }
            
            // Prepare update data (set school_district only if provided)
            $update_data = [
                'name' => $this->input->post('name'),
                'school_address' => $this->input->post('address'),
                'school_head_name' => $this->input->post('head_name'),
                'school_info_completed' => true,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add role-specific fields
            if ($user_role == 'user') {
                $update_data['legislative_district'] = $this->input->post('legislativeDistricts');
                $update_data['school_level'] = $this->input->post('level');
            } elseif ($user_role == 'district' || $user_role == 'division') {
                $update_data['school_level'] = $this->input->post('level');
                // District/Division users don't have legislative district
                $update_data['legislative_district'] = null;
            } elseif ($user_role == 'admin' || $user_role == 'super_admin') {
                // Admin users get a server-assigned school_id
                $update_data['school_id'] = 'ADMIN-' . strtoupper($user_role) . '-' . $user_id;
                $update_data['school_level'] = 'Administrative';
                $update_data['legislative_district'] = null;
            }

            // Only include school_district if present in POST
            $postedSchoolDistrict = $this->input->post('SchoolDistricts');
            if ($postedSchoolDistrict) {
                $update_data['school_district'] = $postedSchoolDistrict;
            }

            // Debug log: record posted district values (helps diagnose missing POST)
            log_message('debug', 'SchoolInfo::store postedSchoolDistrict=' . var_export($postedSchoolDistrict, true) . ' legislativeDistricts=' . var_export($this->input->post('legislativeDistricts'), true));

            $success = $this->User_model->update_user($user_id, $update_data);

            if ($success) {
                // Update session data
                $session_data = [
                    'name' => $this->input->post('name'),
                    'school_info_completed' => true,
                ];
                
                // Add role-specific session data (only for fields present in the form)
                if ($user_role == 'user') {
                    $session_data['legislative_district'] = $this->input->post('legislativeDistricts');
                    if ($postedSchoolDistrict) {
                        $session_data['school_district'] = $postedSchoolDistrict;
                        // Also set `district` session key to maintain compatibility with other controllers
                        $session_data['district'] = $postedSchoolDistrict;
                    }
                } elseif ($user_role == 'district') {
                    if ($postedSchoolDistrict) {
                        $session_data['school_district'] = $postedSchoolDistrict;
                        $session_data['district'] = $postedSchoolDistrict;
                    }
                } elseif ($user_role == 'division') {
                    if ($postedSchoolDistrict) {
                        $session_data['school_district'] = $postedSchoolDistrict;
                        $session_data['district'] = $postedSchoolDistrict;
                    }
                } elseif ($user_role == 'admin' || $user_role == 'super_admin') {
                    // Only set server-assigned school_id for admin users
                    $session_data['school_id'] = $update_data['school_id'];
                }
                
                $this->session->set_userdata($session_data);
                
                $this->session->set_flashdata('success', 'Profile information saved successfully');
                
                // Redirect based on role
                $this->redirect_by_role($user_role);
            } else {
                throw new Exception('Failed to update user information');
            }

        } catch (Exception $e) {
            $this->session->set_flashdata('error_message', 'Failed to save profile information: ' . $e->getMessage());
            $this->session->set_flashdata('input_data', $this->input->post());
            redirect('school-info/form');
        }
    }

    private function redirect_by_role($role) {
        switch ($role) {
            case 'division':
                redirect('division_dashboard');
                break;
            case 'district':
                redirect('district_dashboard');
                break;
            case 'super_admin':
            case 'admin':
                redirect('superadmin');
                break;
            case 'user':
                redirect('user');
                break;
            default:
                redirect('dashboard');
                break;
        }
    }

    public function get_school_districts() {
        try {
            $legislative_district = $this->input->get('legislative_district');
            
            if (!$legislative_district) {
                header('Content-Type: application/json');
                echo json_encode([]);
                return;
            }
            
            log_message('debug', 'Fetching school districts for: ' . $legislative_district);
            
            $school_districts = $this->Legislative_district_model->get_school_districts_by_legislative($legislative_district);
            
            log_message('debug', 'School districts found: ' . count($school_districts));
            
            header('Content-Type: application/json');
            echo json_encode($school_districts);
            
        } catch (Exception $e) {
            log_message('error', 'Error in get_school_districts: ' . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function get_schools() {
        $school_district = $this->input->get('school_district');
        
        if (!$school_district) {
            echo json_encode([]);
            return;
        }
        
        $schools = $this->Legislative_district_model->get_schools_by_district($school_district);
        
        header('Content-Type: application/json');
        echo json_encode($schools);
    }
}