<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class sbfp_dashboard_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('section_model');
        $this->load->model('Nutritional_assessment_model');
        $this->load->model('User_model');
        $this->load->model('Legislative_district_model');
        $this->load->helper(['url', 'form', 'cookie']);
        $this->load->library(['session', 'form_validation']);
        $this->load->database();
        
        // Set timezone
        date_default_timezone_set('Asia/Manila');
        
        // CSRF protection check for AJAX requests
        if ($this->input->is_ajax_request()) {
            // For AJAX requests, we'll handle CSRF separately
            if ($this->config->item('csrf_protection')) {
                $csrf_name = $this->security->get_csrf_token_name();
                $csrf_hash = $this->security->get_csrf_hash();
                
                // Check if CSRF token is present in request
                $token = $this->input->post($csrf_name);
                if (!$token && function_exists('getallheaders')) {
                    $headers = getallheaders();
                    $token = isset($headers['X-CSRF-TOKEN']) ? $headers['X-CSRF-TOKEN'] : null;
                }
                
                if (!$token || $token !== $csrf_hash) {
                    // Log CSRF failure but don't block AJAX completely in production
                    log_message('debug', 'CSRF token missing or invalid in AJAX request');
                }
            }
        }
    }

    /**
     * Show SBFP Dashboard (sections and submitted assessments)
     */
    public function index()
    {
        try {
            // Get user data from session
            $user_id = $this->session->userdata('user_id');
            
            if (!$user_id) {
                $this->session->set_flashdata('error', 'Please login to continue');
                redirect('auth/login');
                return;
            }

            // Get complete user data with school information
            $user = $this->User_model->get_user_by_id($user_id);
            
            if (!$user) {
                $this->session->set_flashdata('error', 'User not found');
                redirect('auth/login');
                return;
            }

            // Safely remove sections with deleted assessments
            try {
                $removed_count = $this->section_model->check_and_remove_sections_with_deleted_assessments($user_id);
                if ($removed_count > 0) {
                    $this->session->set_flashdata('info', $removed_count . ' section(s) were removed because their assessments were archived or deleted.');
                }
            } catch (Exception $e) {
                log_message('error', 'Error removing sections: ' . $e->getMessage());
                // Don't block the page, just log the error
            }

            $data = [];
            
            // Sanitize user data for view
            $data['auth_user'] = [
                'legislative_district' => htmlspecialchars($user->legislative_district ?? '', ENT_QUOTES, 'UTF-8'),
                'school_district' => htmlspecialchars($user->school_district ?? '', ENT_QUOTES, 'UTF-8'),
                'school_id' => htmlspecialchars($user->school_id ?? '', ENT_QUOTES, 'UTF-8'),
                'school_name' => htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8'),
                'school_address' => htmlspecialchars($user->school_address ?? '', ENT_QUOTES, 'UTF-8'),
                'school_level' => htmlspecialchars($user->school_level ?? '', ENT_QUOTES, 'UTF-8'),
                'school_head_name' => htmlspecialchars($user->school_head_name ?? '', ENT_QUOTES, 'UTF-8')
            ];

            // Fetch sections for the current user
            try {
                $data['sections'] = $this->section_model->get_by_user($user_id);
            } catch (Exception $e) {
                log_message('error', 'Error fetching sections: ' . $e->getMessage());
                $data['sections'] = [];
                $this->session->set_flashdata('error', 'Unable to load sections. Please try again.');
            }

            // Get list of submitted assessments
            if (!empty($user->legislative_district) && !empty($user->school_district)) {
                try {
                    $data['submittedAssessments'] = $this->Nutritional_assessment_model->get_submitted_summary(
                        $user->legislative_district, 
                        $user->school_district
                    );
                } catch (Exception $e) {
                    log_message('error', 'Error fetching assessments: ' . $e->getMessage());
                    $data['submittedAssessments'] = [];
                }

                // Get school data summary and related schools
                try {
                    $data['school_data'] = $this->get_school_data_summary(
                        $user->legislative_district, 
                        $user->school_district, 
                        $user->school_id
                    );
                } catch (Exception $e) {
                    log_message('error', 'Error fetching school data: ' . $e->getMessage());
                    $data['school_data'] = [];
                }
                
                try {
                    $data['related_schools'] = $this->get_related_schools($user->school_district);
                } catch (Exception $e) {
                    log_message('error', 'Error fetching related schools: ' . $e->getMessage());
                    $data['related_schools'] = [];
                }
                
                try {
                    $data['current_school'] = $this->get_current_school_info($user->school_id);
                } catch (Exception $e) {
                    log_message('error', 'Error fetching current school: ' . $e->getMessage());
                    $data['current_school'] = null;
                }
            } else {
                $data['submittedAssessments'] = [];
                $data['school_data'] = [];
                $data['related_schools'] = [];
                $data['current_school'] = null;
            }

            // Flash messages
            $data['flash'] = $this->session->flashdata();
            
            // CSRF tokens for forms
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();

            $this->load->view('sbfp_dashboard', $data);
            
        } catch (Exception $e) {
            log_message('error', 'Critical error in dashboard index: ' . $e->getMessage());
            show_error('An unexpected error occurred. Please try again later.', 500);
        }
    }

    /**
     * Set assessment type in session (AJAX)
     */
    public function set_assessment_type()
    {
        try {
            // Check if this is an AJAX request
            if (!$this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid request method']));
                return;
            }
            
            $assessment_type = $this->input->post('assessment_type', TRUE);
            
            if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid assessment type']));
                return;
            }

            $this->session->set_userdata('assessment_type', $assessment_type);
            
            $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true, 
                    'message' => 'Assessment type set to ' . $assessment_type,
                    'csrf_token' => $this->security->get_csrf_hash() // Return new CSRF token
                ]));
                
        } catch (Exception $e) {
            log_message('error', 'Error in set_assessment_type: ' . $e->getMessage());
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Server error occurred']));
        }
    }

    /**
     * Create a section (POST)
     */
    public function create_section()
    {
        try {
            // Validate required fields
            $this->form_validation->set_rules('grade', 'Grade', 'required|trim');
            $this->form_validation->set_rules('section', 'Section', 'required|trim|max_length[100]');
            $this->form_validation->set_rules('school_year', 'School Year', 'required|trim|regex_match[/^\d{4}-\d{4}$/]');
            $this->form_validation->set_rules('legislative_district', 'Legislative District', 'required|trim');
            $this->form_validation->set_rules('school_district', 'School District', 'required|trim');

            if ($this->form_validation->run() == FALSE) {
                $this->session->set_flashdata('error', validation_errors());
                redirect('sbfp');
                return;
            }

            $grade = $this->input->post('grade', TRUE);
            $section = $this->input->post('section', TRUE);
            $school_year = $this->input->post('school_year', TRUE);
            $leg = $this->input->post('legislative_district', TRUE);
            $school = $this->input->post('school_district', TRUE);

            // Additional validation
            if (strlen($section) > 100) {
                $this->session->set_flashdata('error', 'Section name too long (maximum 100 characters)');
                redirect('sbfp');
                return;
            }

            // Validate school year format
            if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
                $this->session->set_flashdata('error', 'Invalid school year format. Use YYYY-YYYY (e.g., 2024-2025)');
                redirect('sbfp');
                return;
            }

            $inserted = $this->section_model->create_section($grade, $section, $school_year, $leg, $school);

            if ($inserted) {
                $this->session->set_flashdata('success', 'Section saved successfully');
            } else {
                $this->session->set_flashdata('error', 'Failed to save section (it may already exist)');
            }

        } catch (Exception $e) {
            log_message('error', 'Error in create_section: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while creating the section');
        }

        redirect('sbfp');
    }

    /**
     * Remove a section (POST)
     */
    public function remove_section()
    {
        try {
            $section_id = $this->input->post('section_id', TRUE);
            
            if (!$section_id || !is_numeric($section_id)) {
                $this->session->set_flashdata('error', 'Invalid section ID');
                redirect('sbfp');
                return;
            }

            // Verify section belongs to user
            $user_id = $this->session->userdata('user_id');
            $section = $this->section_model->get_by_id($section_id);
            
            if (!$section || $section->user_id != $user_id) {
                $this->session->set_flashdata('error', 'You do not have permission to remove this section');
                redirect('sbfp');
                return;
            }

            $deleted = $this->section_model->delete_section($section_id);
            
            if ($deleted) {
                $this->session->set_flashdata('success', 'Section removed successfully');
            } else {
                $this->session->set_flashdata('error', 'Failed to remove section');
            }

        } catch (Exception $e) {
            log_message('error', 'Error in remove_section: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred while removing the section');
        }

        redirect('sbfp');
    }

    /**
     * Delete assessment (AJAX)
     */
    public function delete_assessment()
    {
        try {
            if (!$this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid request method']));
                return;
            }
            
            $grade = $this->input->post('grade', TRUE);
            $section = $this->input->post('section', TRUE);
            $school_year = $this->input->post('school_year', TRUE);
            $assessment_type = $this->input->post('assessment_type', TRUE) ?: 'baseline';
            
            $user_id = $this->session->userdata('user_id');
            $user = $this->User_model->get_user_by_id($user_id);
            
            if (!$user || !$grade || !$section) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Missing required parameters']));
                return;
            }

            // Validate input lengths to prevent DOS attacks
            if (strlen($grade) > 50 || strlen($section) > 100 || strlen($school_year) > 9) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid parameter length']));
                return;
            }

            $deleted = $this->Nutritional_assessment_model->delete_assessment(
                $user->legislative_district,
                $user->school_district,
                $grade,
                $section,
                $school_year,
                $assessment_type
            );

            if ($deleted) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => true, 
                        'message' => ucfirst($assessment_type) . ' assessment deleted successfully',
                        'csrf_token' => $this->security->get_csrf_hash()
                    ]));
            } else {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Failed to delete assessment']));
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error in delete_assessment: ' . $e->getMessage());
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Server error occurred']));
        }
    }

    /**
     * Toggle assessment lock (AJAX)
     */
    public function toggle_lock()
    {
        try {
            if (!$this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid request method']));
                return;
            }
            
            $grade = $this->input->post('grade', TRUE);
            $section = $this->input->post('section', TRUE);
            $school_year = $this->input->post('school_year', TRUE);
            $assessment_type = $this->input->post('assessment_type', TRUE) ?: 'baseline';
            
            $user_id = $this->session->userdata('user_id');
            $user = $this->User_model->get_user_by_id($user_id);
            
            if (!$user || !$grade || !$section) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Missing required parameters']));
                return;
            }

            // Validate input lengths
            if (strlen($grade) > 50 || strlen($section) > 100 || strlen($school_year) > 9) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid parameter length']));
                return;
            }

            // Implement locking logic here
            // For now, return success with placeholder
            $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true, 
                    'message' => 'Assessment lock toggled',
                    'csrf_token' => $this->security->get_csrf_hash()
                ]));
                
        } catch (Exception $e) {
            log_message('error', 'Error in toggle_lock: ' . $e->getMessage());
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Server error occurred']));
        }
    }

    /**
     * Get school data summary for dashboard
     */
    private function get_school_data_summary($legislative_district, $school_district, $school_id = null)
    {
        $summary = [
            'total_schools_in_district' => 0,
            'total_sections' => 0,
            'submitted_assessments' => 0,
            'pending_assessments' => 0,
            'total_students' => 0,
            'total_assessments' => 0,
            'district_rank' => 'N/A'
        ];

        try {
            if (empty($legislative_district) || empty($school_district)) {
                return $summary;
            }

            // Get legislative district ID
            $legislative = $this->Legislative_district_model->get_by_name($legislative_district);
            if (!$legislative) {
                return $summary;
            }

            // Get school district ID
            $this->db->where('legislative_district_id', $legislative->id);
            $this->db->where('name', $school_district);
            $district = $this->db->get('school_districts')->row();

            if ($district) {
                // Get total schools in the district
                $this->db->where('school_district_id', $district->id);
                $summary['total_schools_in_district'] = (int)$this->db->count_all_results('schools');

                // Get district rank
                $this->db->select('sd.name, COUNT(s.id) as school_count');
                $this->db->from('school_districts sd');
                $this->db->join('schools s', 'sd.id = s.school_district_id', 'left');
                $this->db->where('sd.legislative_district_id', $legislative->id);
                $this->db->group_by('sd.id');
                $this->db->order_by('school_count', 'DESC');
                $districts_rank = $this->db->get()->result();
                
                $rank = 1;
                foreach ($districts_rank as $dist) {
                    if ($dist->name == $school_district) {
                        $summary['district_rank'] = $rank . ' out of ' . count($districts_rank);
                        break;
                    }
                    $rank++;
                }
            }

            // Get sections count
            $this->db->where('legislative_district', $legislative_district);
            $this->db->where('school_district', $school_district);
            $summary['total_sections'] = (int)$this->db->count_all_results('grade_sections');

            // Get total non-deleted assessments count
            $this->db->where('legislative_district', $legislative_district);
            $this->db->where('school_district', $school_district);
            $this->db->where('is_deleted', 0);
            $summary['submitted_assessments'] = (int)$this->db->count_all_results('nutritional_assessments');

            // Get unique student count
            $this->db->select('COUNT(DISTINCT CONCAT(name, birthday)) as unique_students');
            $this->db->where('legislative_district', $legislative_district);
            $this->db->where('school_district', $school_district);
            $this->db->where('is_deleted', 0);
            $unique_result = $this->db->get('nutritional_assessments')->row();
            $summary['total_students'] = $unique_result ? (int)$unique_result->unique_students : 0;

            // Calculate pending assessments
            $total_sections = $summary['total_sections'];
            $total_assessments = $summary['submitted_assessments'];
            
            if ($total_sections > 0) {
                $submitted_sections = min($total_assessments, $total_sections);
                $summary['pending_assessments'] = max(0, $total_sections - $submitted_sections);
            }

            $summary['total_assessments'] = $summary['submitted_assessments'];

        } catch (Exception $e) {
            log_message('error', 'Error in get_school_data_summary: ' . $e->getMessage());
            throw $e;
        }

        return $summary;
    }

    /**
     * Get related schools in the same district
     */
    private function get_related_schools($school_district)
    {
        try {
            if (empty($school_district)) {
                return [];
            }

            $this->db->select('s.school_id, s.name, ld.name as legislative_name');
            $this->db->from('schools s');
            $this->db->join('school_districts sd', 's.school_district_id = sd.id');
            $this->db->join('legislative_districts ld', 'sd.legislative_district_id = ld.id');
            $this->db->where('sd.name', $school_district);
            $this->db->order_by('s.name', 'ASC');
            $this->db->limit(100); // Limit results for performance
            
            return $this->db->get()->result();
            
        } catch (Exception $e) {
            log_message('error', 'Error in get_related_schools: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get current school basic information
     */
    private function get_current_school_info($school_id = null)
    {
        if (!$school_id) {
            return null;
        }

        try {
            $this->db->select('s.school_id, s.name, sd.name as school_district, ld.name as legislative_district');
            $this->db->from('schools s');
            $this->db->join('school_districts sd', 's.school_district_id = sd.id');
            $this->db->join('legislative_districts ld', 'sd.legislative_district_id = ld.id');
            $this->db->where('s.school_id', $school_id);
            
            return $this->db->get()->row();
            
        } catch (Exception $e) {
            log_message('error', 'Error in get_current_school_info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Navigate to nutritional assessment
     */
    public function go_to_assessment()
    {
        try {
            $grade = $this->input->get('grade', TRUE);
            $section = $this->input->get('section', TRUE);
            $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';
            
            $user_id = $this->session->userdata('user_id');
            $user = $this->User_model->get_user_by_id($user_id);
            
            if (!$user || !$grade || !$section) {
                $this->session->set_flashdata('error', 'Missing required parameters');
                redirect('sbfp');
                return;
            }

            // Validate input
            if (strlen($grade) > 50 || strlen($section) > 100) {
                $this->session->set_flashdata('error', 'Invalid parameter length');
                redirect('sbfp');
                return;
            }

            redirect('nutritionalassessment?legislative_district=' . urlencode($user->legislative_district) . 
                    '&school_district=' . urlencode($user->school_district) . 
                    '&grade=' . urlencode($grade) . 
                    '&section=' . urlencode($section) . 
                    '&assessment_type=' . urlencode($assessment_type) . 
                    '&school_name=' . urlencode($user->name));
                    
        } catch (Exception $e) {
            log_message('error', 'Error in go_to_assessment: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'An error occurred');
            redirect('sbfp');
        }
    }

    /**
     * Get assessment types for a section (AJAX)
     */
    public function get_assessment_types()
    {
        try {
            if (!$this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid request method']));
                return;
            }
            
            $grade = $this->input->get('grade', TRUE);
            $section = $this->input->get('section', TRUE);
            
            $user_id = $this->session->userdata('user_id');
            $user = $this->User_model->get_user_by_id($user_id);
            
            if (!$user || !$grade || !$section) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Missing parameters']));
                return;
            }

            // Validate input
            if (strlen($grade) > 50 || strlen($section) > 100) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid parameter length']));
                return;
            }

            $types = $this->Nutritional_assessment_model->get_assessment_types(
                $user->legislative_district,
                $user->school_district,
                $grade,
                $section
            );

            $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'types' => $types,
                    'csrf_token' => $this->security->get_csrf_hash()
                ]));
                
        } catch (Exception $e) {
            log_message('error', 'Error in get_assessment_types: ' . $e->getMessage());
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Server error occurred']));
        }
    }

    /**
     * Get existing assessment data for a section (AJAX)
     */
    public function get_existing_data()
    {
        try {
            if (!$this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid request method']));
                return;
            }
            
            $grade = $this->input->get('grade', TRUE);
            $section = $this->input->get('section', TRUE);
            $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';
            
            $user_id = $this->session->userdata('user_id');
            $user = $this->User_model->get_user_by_id($user_id);
            
            if (!$user || !$grade || !$section) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Missing parameters']));
                return;
            }

            // Validate input
            if (strlen($grade) > 50 || strlen($section) > 100) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Invalid parameter length']));
                return;
            }

            $data = $this->Nutritional_assessment_model->get_by_section(
                $user->legislative_district,
                $user->school_district,
                $grade,
                $section,
                null,
                $assessment_type
            );

            $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'data' => $data,
                    'count' => count($data),
                    'csrf_token' => $this->security->get_csrf_hash()
                ]));
                
        } catch (Exception $e) {
            log_message('error', 'Error in get_existing_data: ' . $e->getMessage());
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Server error occurred']));
        }
    }
}