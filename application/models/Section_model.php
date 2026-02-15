<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Section_model extends CI_Model {

    protected $table = 'grade_sections';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
    }

    /**
     * Get sections by legislative district and school district
     */
    public function get_by_location($legislative_district, $school_district)
    {
        $this->db->select('id, grade, section, year as school_year, legislative_district, school_district, created_at');
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->order_by('grade', 'ASC');
        $this->db->order_by('section', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Create a new section
     */
    public function create_section($grade, $section, $school_year, $legislative_district, $school_district)
    {
        // Get current user ID from session
        $user_id = $this->session->userdata('user_id');
        
        if (!$user_id) {
            return false; // No user logged in
        }

        // Check if section already exists (including school_year in the check)
        $this->db->where('grade', $grade);
        $this->db->where('section', $section);
        $this->db->where('year', $school_year); // Changed to 'year'
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('user_id', $user_id);
        $existing = $this->db->get($this->table)->row();

        if ($existing) {
            return false; // Section already exists
        }

        $data = [
            'grade' => $grade,
            'section' => $section,
            'year' => $school_year, // Changed to 'year'
            'legislative_district' => $legislative_district,
            'school_district' => $school_district,
            'user_id' => $user_id, // Add the user_id
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert($this->table, $data);
    }

    /**
     * Delete a section by ID
     */
    public function delete_section($section_id)
    {
        // Get current user ID from session
        $user_id = $this->session->userdata('user_id');
        
        if (!$user_id) {
            return false;
        }

        // Only allow deletion if the section belongs to the current user
        $this->db->where('id', $section_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->table);
    }

    /**
     * Get section by ID
     */
    public function get_by_id($section_id)
    {
        $this->db->where('id', $section_id);
        return $this->db->get($this->table)->row();
    }

    /**
     * Get sections by user ID
     */
    public function get_by_user($user_id)
    {
        $this->db->select('id, grade, section, year as school_year, legislative_district, school_district, created_at');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('grade', 'ASC');
        $this->db->order_by('section', 'ASC');
        return $this->db->get($this->table)->result();
    }

    // Checks for deleted assessments and removes sections accordingly
    public function check_and_remove_sections_with_deleted_assessments($user_id)
    {
        // Get user info
        $this->load->model('User_model');
        $user = $this->User_model->get_user_by_id($user_id);
        
        if (!$user) {
            return false;
        }
        
        // Get all sections for this user
        $sections = $this->get_by_user($user_id);
        
        $removed_count = 0;
        
        foreach ($sections as $section) {
            // Check if there's a deleted assessment for this section
            $this->db->where('legislative_district', $user->legislative_district);
            $this->db->where('school_district', $user->school_district);
            $this->db->where('grade_level', $section->grade);
            $this->db->where('section', $section->section);
            $this->db->where('is_deleted', 1); // Check for deleted assessments
            
            $deleted_assessment = $this->db->get('nutritional_assessments')->row();
            
            // If a deleted assessment exists for this section, remove the section
            if ($deleted_assessment) {
                $this->delete_section($section->id);
                $removed_count++;
            }
        }
        
        return $removed_count;
    }

}