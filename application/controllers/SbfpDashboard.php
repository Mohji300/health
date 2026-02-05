<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SbfpDashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Section_model');
        $this->load->model('Nutritional_assessment_model');
        $this->load->model('User_model');
        $this->load->model('Legislative_district_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
        $this->load->database();
    }

    /**
     * Show SBFP Dashboard (sections and submitted assessments)
     */
    public function index()
    {
        // Get user data from session
        $user_id = $this->session->userdata('user_id');
        
        if (!$user_id) {
            redirect('auth/login');
            return;
        }

        // Get complete user data with school information
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user) {
            redirect('auth/login');
            return;
        }

        $data = [];
        $data['auth_user'] = [
            'legislative_district' => $user->legislative_district,
            'school_district' => $user->school_district,
            'school_id' => $user->school_id,
            'school_name' => $user->name,
            'school_address' => $user->school_address,
            'school_level' => $user->school_level,
            'school_head_name' => $user->school_head_name
        ];

        // Fetch sections for the current user
        $data['sections'] = $this->Section_model->get_by_user($user_id);

        // Get list of submitted assessments (distinct grade, section)
        if ($user->legislative_district && $user->school_district) {
            $data['submittedAssessments'] = $this->Nutritional_assessment_model->get_submitted_summary($user->legislative_district, $user->school_district);

            // Get school data summary and related schools
            $data['school_data'] = $this->get_school_data_summary($user->legislative_district, $user->school_district, $user->school_id);
            $data['related_schools'] = $this->get_related_schools($user->school_district);
            
            // Get current school detailed information
            $data['current_school'] = $this->get_current_school_info($user->school_id);
        } else {
            $data['submittedAssessments'] = [];
            $data['school_data'] = [];
            $data['related_schools'] = [];
            $data['current_school'] = null;
        }

        // Flash messages (if any)
        $data['flash'] = $this->session->flashdata();

        $this->load->view('sbfp_dashboard', $data);
    }

    /**
     * Set assessment type in session (AJAX)
     */
    public function set_assessment_type()
    {
        // Check if this is an AJAX request
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
            'message' => 'Assessment type set to ' . $assessment_type
        ]));
    }

    /**
     * Navigate to nutritional assessment
     */
    public function go_to_assessment()
    {
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';
        
        // Get user data for the required parameters
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user || !$grade || !$section) {
            $this->session->set_flashdata('error', 'Missing required parameters');
            redirect('sbfpdashboard');
            return;
        }

        // Redirect to nutritional assessment with all required parameters
        redirect('nutritionalassessment?legislative_district=' . urlencode($user->legislative_district) . 
                '&school_district=' . urlencode($user->school_district) . 
                '&grade=' . urlencode($grade) . 
                '&section=' . urlencode($section) . 
                '&assessment_type=' . urlencode($assessment_type) . 
                '&school_name=' . urlencode($user->name));
    }

    /**
     * Delete assessment
     */
    public function delete_assessment()
    {
        // Check if this is an AJAX request
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $grade = $this->input->post('grade', TRUE);
        $section = $this->input->post('section', TRUE);
        $assessment_type = $this->input->post('assessment_type', TRUE) ?: 'baseline';
        
        // Get user data for the required parameters
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user || !$grade || !$section) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Missing required parameters'
            ]));
            return;
        }

        // Delete the assessment
        $deleted = $this->Nutritional_assessment_model->delete_assessment(
            $user->legislative_district,
            $user->school_district,
            $grade,
            $section,
            $school_year,
            $assessment_type
        );

        if ($deleted) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => true,
                'message' => ucfirst($assessment_type) . ' assessment deleted successfully'
            ]));
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Failed to delete assessment'
            ]));
        }
    }

    /**
     * Toggle assessment lock
     */
    public function toggle_lock()
    {
        // Check if this is an AJAX request
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $grade = $this->input->post('grade', TRUE);
        $section = $this->input->post('section', TRUE);
        $assessment_type = $this->input->post('assessment_type', TRUE) ?: 'baseline';
        
        // Get user data for the required parameters
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user || !$grade || !$section) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Missing required parameters'
            ]));
            return;
        }

        // Here you would implement your locking logic
        // For example, you might have a separate table for locks
        // or add a column to the assessments table
        
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'message' => 'Assessment locked/unlocked'
        ]));
    }

    /**
     * Get assessment types for a section (AJAX)
     */
    public function get_assessment_types()
    {
        // Check if this is an AJAX request
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user || !$grade || !$section) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Missing parameters'
            ]));
            return;
        }

        $types = $this->Nutritional_assessment_model->get_assessment_types(
            $user->legislative_district,
            $user->school_district,
            $grade,
            $section
        );

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'types' => $types
        ]));
    }
    
    /**
     * Get existing assessment data for a section (AJAX)
     */
    public function get_existing_data()
    {
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';
        
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user || !$grade || !$section) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Missing parameters'
            ]));
            return;
        }

        $data = $this->Nutritional_assessment_model->get_by_section(
            $user->legislative_district,
            $user->school_district,
            $grade,
            $section,
            $assessment_type
        );

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]));
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
                $summary['total_schools_in_district'] = $this->db->count_all_results('schools');

                // Get district rank (example: based on number of schools)
                $this->db->select('sd.name, COUNT(s.id) as school_count');
                $this->db->from('school_districts sd');
                $this->db->join('schools s', 'sd.id = s.school_district_id');
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

            // Get sections count for this school/district
            $this->db->where('legislative_district', $legislative_district);
            $this->db->where('school_district', $school_district);
            $summary['total_sections'] = $this->db->count_all_results('grade_sections');

            // Get total non-deleted assessments count
            $this->db->where('legislative_district', $legislative_district);
            $this->db->where('school_district', $school_district);
            $this->db->where('is_deleted', FALSE);
            $summary['submitted_assessments'] = $this->db->count_all_results('nutritional_assessments');

            // Get unique student count (distinct names within the same district)
            $this->db->select('COUNT(DISTINCT name) as unique_students');
            $this->db->where('legislative_district', $legislative_district);
            $this->db->where('school_district', $school_district);
            $this->db->where('is_deleted', FALSE);
            $unique_result = $this->db->get('nutritional_assessments')->row();
            $summary['total_students'] = $unique_result ? $unique_result->unique_students : 0;

            // Calculate pending assessments based on sections vs assessments
            $total_sections = $summary['total_sections'];
            $total_assessments = $summary['submitted_assessments'];
            
            // If we have sections but no assessments, all are pending
            // If we have assessments but fewer than sections, some are pending
            if ($total_sections > 0) {
                // Simple logic: if we have assessments, consider some sections as submitted
                $submitted_sections = min($total_assessments, $total_sections);
                $summary['pending_assessments'] = $total_sections - $submitted_sections;
            } else {
                $summary['pending_assessments'] = 0;
            }

            // Get total assessment records (same as submitted since we filtered non-deleted)
            $summary['total_assessments'] = $summary['submitted_assessments'];

        } catch (Exception $e) {
            log_message('error', 'Error in get_school_data_summary: ' . $e->getMessage());
        }

        return $summary;
    }

    /**
     * Get related schools in the same district
     */
    private function get_related_schools($school_district)
    {
        try {
            // Only get the essential columns we need
            $this->db->select('s.school_id, s.name, ld.name as legislative_name');
            $this->db->from('schools s');
            $this->db->join('school_districts sd', 's.school_district_id = sd.id');
            $this->db->join('legislative_districts ld', 'sd.legislative_district_id = ld.id');
            $this->db->where('sd.name', $school_district);
            $this->db->order_by('s.name', 'ASC');
            
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
            // Only get basic school info
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
     * Create a section (POST)
     */
public function create_section()
{
    $grade = $this->input->post('grade', TRUE);
    $section = $this->input->post('section', TRUE);
    $school_year = $this->input->post('school_year', TRUE); // Make sure this line exists
    $leg = $this->input->post('legislative_district', TRUE);
    $school = $this->input->post('school_district', TRUE);

    if (!$grade || !$section || !$school_year || !$leg || !$school) {
        $this->session->set_flashdata('error', 'Missing required fields');
        redirect('sbfpdashboard');
        return;
    }

    $inserted = $this->Section_model->create_section($grade, $section, $school_year, $leg, $school);

    if ($inserted) {
        $this->session->set_flashdata('success', 'Section saved successfully');
    } else {
        $this->session->set_flashdata('error', 'Failed to save section (it may already exist)');
    }

    redirect('sbfpdashboard');
}

    /**
     * Remove a section (POST)
     */
    public function remove_section()
    {
        $section_id = $this->input->post('section_id', TRUE);
        if (!$section_id) {
            $this->session->set_flashdata('error', 'Missing section id');
            redirect('sbfpdashboard');
            return;
        }

        $deleted = $this->Section_model->delete_section($section_id);
        if ($deleted) {
            $this->session->set_flashdata('success', 'Section removed');
        } else {
            $this->session->set_flashdata('error', 'Failed to remove section');
        }

        redirect('sbfpdashboard');
    }

    /**
     * View submitted assessments for a specific section
     */
    public function view_assessments()
    {
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';

        if (!$grade || !$section || !$legislative_district || !$school_district) {
            $this->session->set_flashdata('error', 'Missing required parameters');
            redirect('sbfpdashboard');
            return;
        }

        $data['assessments'] = $this->Nutritional_assessment_model->get_by_section(
            $legislative_district, 
            $school_district, 
            $grade, 
            $section,
            $assessment_type
        );
        $data['grade'] = $grade;
        $data['section'] = $section;
        $data['legislative_district'] = $legislative_district;
        $data['school_district'] = $school_district;
        $data['assessment_type'] = $assessment_type;

        $this->load->view('assessment_view', $data);
    }

    /**
     * Export assessments to CSV
     */
    public function export_assessments()
    {
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';

        if (!$grade || !$section || !$legislative_district || !$school_district) {
            $this->session->set_flashdata('error', 'Missing required parameters');
            redirect('sbfpdashboard');
            return;
        }

        $assessments = $this->Nutritional_assessment_model->get_by_section(
            $legislative_district, 
            $school_district, 
            $grade, 
            $section,
            $assessment_type
        );

        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="nutritional_assessments_' . $grade . '_' . $section . '_' . $assessment_type . '.csv"');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // CSV headers
        fputcsv($output, [
            'Name',
            'Birthday',
            'Grade Level',
            'Section',
            'Weight (kg)',
            'Height (m)',
            'Sex',
            'BMI',
            'Nutritional Status',
            'SBFP Beneficiary',
            'Date of Weighing',
            'Legislative District',
            'School District',
            'School Name',
            'Assessment Type'
        ]);

        // Data rows
        foreach ($assessments as $assessment) {
            fputcsv($output, [
                $assessment->name,
                $assessment->birthday,
                $assessment->grade_level,
                $assessment->section,
                $assessment->weight,
                $assessment->height,
                $assessment->sex,
                $assessment->bmi,
                $assessment->nutritional_status,
                $assessment->sbfp_beneficiary,
                $assessment->date_of_weighing,
                $assessment->legislative_district,
                $assessment->school_district,
                $assessment->school_name,
                $assessment->assessment_type
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Get dashboard statistics (AJAX)
     */
    public function get_statistics()
    {
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);

        if (!$user) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['error' => 'User not found']));
            return;
        }

        $statistics = $this->get_school_data_summary($user->legislative_district, $user->school_district, $user->school_id);
        
        $this->output->set_content_type('application/json')->set_output(json_encode($statistics));
    }
}