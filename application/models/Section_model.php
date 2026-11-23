<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class section_model extends CI_Model {

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
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            return false;
        }

        // Server-side lookup — never trust a posted school_id
        $user = $this->db->select('school_id')->where('id', $user_id)->get('users')->row();
        $school_id = $user ? $user->school_id : null;

        if (empty($school_id)) {
            return false;   // refuse to insert a section with no school_id
        }

        $this->db->where('grade', $grade);
        $this->db->where('section', $section);
        $this->db->where('year', $school_year);
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('school_id', $school_id);
        $this->db->where('user_id', $user_id);
        $existing = $this->db->get($this->table)->row();

        if ($existing) {
            return false;
        }

        $data = [
            'grade' => $grade,
            'section' => $section,
            'year' => $school_year,
            'legislative_district' => $legislative_district,
            'school_district' => $school_district,
            'user_id' => $user_id,
            'school_id' => $school_id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert($this->table, $data);
    }

    public function get_section_id($grade, $section, $school_year, $legislative_district, $school_district, $user_id)
    {
        $this->db->select('id');
        $this->db->where('grade', $grade);
        $this->db->where('section', $section);
        $this->db->where('year', $school_year);
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('grade_sections');
        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }
        return null;
    }

    /**
     * Set school_id for all sections belonging to a user where school_id is missing/different.
     * Returns number of affected rows, or false on failure.
     */
    public function backfill_school_id_for_user($user_id, $school_id)
    {
        if (empty($user_id) || empty($school_id)) {
            return false;
        }

        $this->db->where('user_id', $user_id);
        $this->db->group_start();
            $this->db->where('school_id IS NULL', null, false);
            $this->db->or_where('school_id', '');
            $this->db->or_where('school_id !=', $school_id);
        $this->db->group_end();

        $this->db->update($this->table, ['school_id' => $school_id]);

        $affected = $this->db->affected_rows();
        if ($affected < 0) {           // -1 = error in CI3
            return false;
        }
        return (int)$affected;
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
     * Clear assessment data for a section.
     *
     * @param int         $section_id
     * @param string|null $assessment_type  'baseline'|'midline'|'endline', or null for all
     * @return int|false  Rows deleted, or false if unauthorized/not found
     */
    public function clear_section_data($section_id, $assessment_type = null)
    {
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            return false;
        }

        // Ownership check — bails before any DELETE
        $section = $this->db->where('id', $section_id)
                            ->where('user_id', $user_id)
                            ->get($this->table)
                            ->row();
        if (!$section) {
            return false;
        }

        // Restrict to this section by section_id
            $this->db->where('section_id', $section_id);

        // Optional: restrict to one assessment type (matches the active toggle)
        if ($assessment_type && in_array($assessment_type, ['baseline', 'midline', 'endline'], true)) {
            $this->db->where('assessment_type', $assessment_type);
        }

        $this->db->delete('nutritional_assessments');

        $affected = $this->db->affected_rows();
        return $affected < 0 ? false : (int) $affected;
    }

    /**
     * Backfill the section_id on all assessment rows that belong to this
     * section (matched by legislative_district + school_district + school_id
     * + grade + section + school_year).
     *
     * Rows that already have the correct section_id are skipped.
     *
     * @return int|false  Affected rows (0 = already correct), false on error/unauthorized
     */
    public function backfill_section_id_for_assessments($section_id)
    {
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            return false;
        }

        // Section must belong to the current user
        $section = $this->db->where('id', $section_id)
                            ->where('user_id', $user_id)
                            ->get($this->table)
                            ->row();
        if (!$section) {
            return false;
        }

        // NOTE: nutritional_assessments uses `grade_level` and `year`
        // (not `grade` / `school_year` like grade_sections).
        $this->db->where('legislative_district', $section->legislative_district);
        $this->db->where('school_district',      $section->school_district);
        $this->db->where('school_id',            $section->school_id);
        $this->db->where('grade_level',          $section->grade);   // <-- was 'grade'
        $this->db->where('section',              $section->section);
        $this->db->where('year',                 $section->year);    // <-- was 'school_year'
        $this->db->group_start();
            $this->db->where('section_id IS NULL', null, false);
            $this->db->or_where('section_id', 0);
            $this->db->or_where('section_id !=', $section_id);
        $this->db->group_end();
        $this->db->update('nutritional_assessments', ['section_id' => $section_id]);

        $affected = $this->db->affected_rows();
        return $affected < 0 ? false : (int) $affected;
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
        $this->db->select('id, grade, section, year as school_year, legislative_district, school_district, school_id, created_at');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('grade', 'ASC');
        $this->db->order_by('section', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function check_and_remove_sections_with_deleted_assessments($user_id)
    {
        $user = $this->user_model->get_user_by_id($user_id);
        if (!$user) return false;

        $sections = $this->get_by_user($user_id);
        $removed_count = 0;

        foreach ($sections as $section) {
            // Total assessments for this section
            $this->db->where('section_id', $section->id);
            $total = $this->db->count_all_results('nutritional_assessments');

            if ($total == 0) continue; // brand new section, keep it

            // Active (non-deleted) assessments
            $this->db->where('section_id', $section->id);
            $this->db->where('is_deleted', 0);
            $active = $this->db->count_all_results('nutritional_assessments');

            if ($active == 0) {
                $this->delete_section($section->id);
                $removed_count++;
            }
        }
        return $removed_count;
    }
}