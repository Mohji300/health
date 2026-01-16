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
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->order_by('grade', 'ASC');
        $this->db->order_by('section', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Create a new section
     */
    public function create_section($grade, $section, $legislative_district, $school_district)
    {
        // Get current user ID from session
        $user_id = $this->session->userdata('user_id');
        
        if (!$user_id) {
            return false; // No user logged in
        }

        // Check if section already exists
        $this->db->where('grade', $grade);
        $this->db->where('section', $section);
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
        $this->db->where('user_id', $user_id);
        $this->db->order_by('grade', 'ASC');
        $this->db->order_by('section', 'ASC');
        return $this->db->get($this->table)->result();
    }
}