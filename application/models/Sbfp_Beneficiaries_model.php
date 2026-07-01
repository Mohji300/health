<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class sbfp_beneficiaries_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get beneficiaries with role-based filtering and additional filters
     */
    public function get_beneficiaries($assessment_type = 'baseline', $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '', $section_id = '') {
        
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        // Treat Kindergarten and Grade 1 as beneficiaries regardless of the sbfp_beneficiary flag
        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();
        // Enforce explicit school_id filtering when provided (prevents name collisions)
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        
        // Apply role-based filtering (mandatory)
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply additional filters (based on permissions)
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        
        // Apply school level filtering (grade restrictions)
        $this->apply_school_level_filter($school_level);
        
        // Custom grade order: Kindergarten, Grade 1, Grade 2, ... Grade 12
        $this->db->order_by("CASE
            WHEN n.grade_level = 'Kindergarten' THEN 0
            WHEN n.grade_level = 'Grade 1' THEN 1
            WHEN n.grade_level = 'Grade 2' THEN 2
            WHEN n.grade_level = 'Grade 3' THEN 3
            WHEN n.grade_level = 'Grade 4' THEN 4
            WHEN n.grade_level = 'Grade 5' THEN 5
            WHEN n.grade_level = 'Grade 6' THEN 6
            WHEN n.grade_level = 'Grade 7' THEN 7
            WHEN n.grade_level = 'Grade 8' THEN 8
            WHEN n.grade_level = 'Grade 9' THEN 9
            WHEN n.grade_level = 'Grade 10' THEN 10
            WHEN n.grade_level = 'Grade 11' THEN 11
            WHEN n.grade_level = 'Grade 12' THEN 12
            ELSE 99 END", '', FALSE);
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        
        $results = $query->result_array();
        
        return $results;
    }
    
    /**
     * Apply additional filters based on user role permissions
     */
    private function apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter) {
        // Grade Level Filter - All roles can use this
        if (!empty($grade_level_filter)) {
            $this->db->where('n.grade_level', $grade_level_filter);
            log_message('debug', 'Grade level filter applied: ' . $grade_level_filter);
        }
        
        // School Name Filter - District, division, and admin roles can use
        if (!empty($school_name_filter) && in_array($user_role, ['district', 'division', 'admin'])) {
            $this->db->where('n.school_name', $school_name_filter);
            log_message('debug', 'School name filter applied: ' . $school_name_filter);
        }
        
        // District Filter - Only division and admin roles can use (district users don't need this)
        if (!empty($district_filter) && in_array($user_role, ['division', 'admin'])) {
            $this->db->where('n.school_district', $district_filter);
            log_message('debug', 'District filter applied: ' . $district_filter);
        }
    }
    
    /**
     * Apply role-based filter to queries
     */
    private function apply_role_filter($user_role, $school_id, $district, $school_name = '', $selected_school = '') {
        
        $user_role_lower = strtolower($user_role);
        
        switch($user_role_lower) {
            case 'school':
            case 'user':
                //  ALWAYS filter by school_id if available
                if (!empty($school_id)) {
                    $this->db->where('n.school_id', $school_id);
                }
                
                //  ALSO filter by school_name for extra safety
                if (!empty($school_name)) {
                    $this->db->where('n.school_name', $school_name);                  
                }
                
                //  If no filters available, show nothing
                if (empty($school_id) && empty($school_name)) {
                    $this->db->where('1=0');
                }
                break;
                
            case 'district':
                if (!empty($district)) {
                    $this->db->where('n.school_district', $district);
                    log_message('debug', 'District filter: school_district = ' . $district);
                } else {
                    $this->db->where('1=0');
                }
                break;
                
            case 'division':
            case 'admin':
                if (!empty($selected_school)) {
                    $this->db->like('n.school_name', $selected_school);
                }
                break;
                
            default:
                $this->db->where('1=0');
                break;
        }
    }
    
    /**
     * Apply school level filtering (grade restrictions)
     */
    private function apply_school_level_filter($school_level) {
        
        if ($school_level === 'all') {
            return;
        }
        
        log_message('debug', 'Applying school level filter: ' . $school_level);
        
        if ($school_level === 'elementary') {
            $this->db->where_in('n.grade_level', ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
        } 
        elseif ($school_level === 'secondary') {
            $this->db->where_in('n.grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
        }
        elseif ($school_level === 'integrated') {
            // No grade filtering for integrated
        }
        elseif ($school_level === 'integrated_elementary') {
            $this->db->where_in('n.grade_level', ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
        }
        elseif ($school_level === 'integrated_secondary') {
            $this->db->where_in('n.grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
        }
        elseif ($school_level === 'Stand Alone SHS') {
            $this->db->where_in('n.grade_level', ['Grade 11', 'Grade 12']);
        }
    }
    
    // Get distinct sections for a given assessment type and school
    public function get_sections($assessment_type, $school_id, $grade_level = '', $school_name = '') {
        $this->db->distinct();
        $this->db->select('section_id as id, section');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        if (!empty($school_name)) {
            $this->db->where('school_name', $school_name);
        }
        if (!empty($grade_level)) {
            $this->db->where('grade_level', $grade_level);
        }
        $this->db->where('section_id IS NOT NULL');
        $this->db->where('section !=', '');
        $this->db->order_by('section', 'ASC');
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Count assessments by type with role-based filtering
     */
    public function count_by_assessment_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '', $section_id = '') {
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();

        if (!empty($section_id)) {
        $this->db->where('n.section_id', $section_id);
        }
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply additional filters
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        
        // Apply school level filtering
        $this->apply_school_level_filter($school_level);
        
        return $this->db->count_all_results();
    }
    
    /**
     * Get nutritional statistics with role-based filtering
     */
    public function get_nutritional_stats_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '', $section_id = '') {
        $this->db->select('n.nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();

        if (!empty($section_id)) {
        $this->db->where('n.section_id', $section_id);
        }
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply additional filters
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        
        // Apply school level filtering
        $this->apply_school_level_filter($school_level);
        
        $this->db->group_by('n.nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get schools list based on user role
     */
    public function get_schools_by_role($user_role, $school_id, $district) {
        $this->db->select('DISTINCT(n.school_name)');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();
        
        log_message('debug', 'Getting schools by role - Role: ' . $user_role . ', District: ' . $district);
        
        if ($user_role === 'school' && !empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        } elseif ($user_role === 'district' && !empty($district)) {
            $this->db->where('n.school_district', $district);
            log_message('debug', 'Filtering schools by district: ' . $district);
        }
        
        $this->db->order_by('n.school_name', 'ASC');
        $query = $this->db->get();
        $results = $query->result_array();
        
        log_message('debug', 'Schools found: ' . count($results));
        
        return $results;
    }
    
    /**
     * Get SBFP beneficiary count by school
     */
    public function get_count_by_school($assessment_type = 'baseline', $school_name = '', $school_id = '') {
        $this->db->select('COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();

        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        
        $query = $this->db->get();
        $result = $query->row();
        return $result ? $result->count : 0;
    }
    
    /**
     * Get SBFP beneficiaries by nutritional status
     */
    public function get_beneficiaries_by_status($nutritional_status, $assessment_type = 'baseline', $school_name = '', $school_id = '', $section_id = '') {
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();

        $this->db->where('LOWER(n.nutritional_status)', strtolower($nutritional_status));
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        
        $this->db->order_by("CASE
            WHEN n.grade_level = 'Kindergarten' THEN 0
            WHEN n.grade_level = 'Grade 1' THEN 1
            WHEN n.grade_level = 'Grade 2' THEN 2
            WHEN n.grade_level = 'Grade 3' THEN 3
            WHEN n.grade_level = 'Grade 4' THEN 4
            WHEN n.grade_level = 'Grade 5' THEN 5
            WHEN n.grade_level = 'Grade 6' THEN 6
            WHEN n.grade_level = 'Grade 7' THEN 7
            WHEN n.grade_level = 'Grade 8' THEN 8
            WHEN n.grade_level = 'Grade 9' THEN 9
            WHEN n.grade_level = 'Grade 10' THEN 10
            WHEN n.grade_level = 'Grade 11' THEN 11
            WHEN n.grade_level = 'Grade 12' THEN 12
            ELSE 99 END", '', FALSE);
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get summary statistics for SBFP beneficiaries
     */
    public function get_summary_stats($assessment_type = 'baseline', $school_name = '', $school_id = '') {
        $this->db->select('
            COUNT(*) as total_beneficiaries,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "severely wasted" THEN 1 ELSE 0 END) as severely_wasted,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "wasted" THEN 1 ELSE 0 END) as wasted,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "normal" THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "overweight" THEN 1 ELSE 0 END) as overweight,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "obese" THEN 1 ELSE 0 END) as obese,
            SUM(CASE WHEN LOWER(n.height_for_age) = "tall" THEN 1 ELSE 0 END) as tall,
            SUM(CASE WHEN n.sex = "M" THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN n.sex = "F" THEN 1 ELSE 0 END) as female_count
        ');
        
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        
        $query = $this->db->get();
        return $query->row();
    }
    
    /**
     * Get grade level distribution for SBFP beneficiaries
     */
    public function get_grade_distribution($assessment_type = 'baseline', $school_name = '', $school_id = '') {
        $this->db->select('n.grade_level, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        
        $this->db->group_by('n.grade_level');
        // Order groups using the same custom ordering
        $this->db->order_by("CASE
            WHEN n.grade_level = 'Kindergarten' THEN 0
            WHEN n.grade_level = 'Grade 1' THEN 1
            WHEN n.grade_level = 'Grade 2' THEN 2
            WHEN n.grade_level = 'Grade 3' THEN 3
            WHEN n.grade_level = 'Grade 4' THEN 4
            WHEN n.grade_level = 'Grade 5' THEN 5
            WHEN n.grade_level = 'Grade 6' THEN 6
            WHEN n.grade_level = 'Grade 7' THEN 7
            WHEN n.grade_level = 'Grade 8' THEN 8
            WHEN n.grade_level = 'Grade 9' THEN 9
            WHEN n.grade_level = 'Grade 10' THEN 10
            WHEN n.grade_level = 'Grade 11' THEN 11
            WHEN n.grade_level = 'Grade 12' THEN 12
            ELSE 99 END", '', FALSE);
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Export SBFP beneficiaries data
     */
    public function export_beneficiaries($assessment_type = 'baseline', $school_name = '', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $section_id = '') {
        $this->db->select('
            n.school_name,
            n.school_id,
            n.legislative_district,
            n.school_district,
            n.grade_level,
            n.section,
            n.name as student_name,
            n.nutritional_status,
            n.sex,
            n.age,
            n.weight,
            n.height,
            n.bmi,
            n.height_for_age,
            n.date_of_weighing,
            n.sbfp_beneficiary,
            n.assessment_type,
            n.year as school_year
        ');
        
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);

        $this->db->group_start();
        $this->db->where_in('n.nutritional_status', ['Severely Wasted', 'Wasted']);
        $this->db->or_where('n.sbfp_beneficiary', 'Yes');
        $this->db->group_end();

        if (!empty($section_id)) {
        $this->db->where('n.section_id', $section_id);
        }
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        $this->db->order_by('n.school_name', 'ASC');
        $this->db->order_by("CASE
            WHEN n.grade_level = 'Kindergarten' THEN 0
            WHEN n.grade_level = 'Grade 1' THEN 1
            WHEN n.grade_level = 'Grade 2' THEN 2
            WHEN n.grade_level = 'Grade 3' THEN 3
            WHEN n.grade_level = 'Grade 4' THEN 4
            WHEN n.grade_level = 'Grade 5' THEN 5
            WHEN n.grade_level = 'Grade 6' THEN 6
            WHEN n.grade_level = 'Grade 7' THEN 7
            WHEN n.grade_level = 'Grade 8' THEN 8
            WHEN n.grade_level = 'Grade 9' THEN 9
            WHEN n.grade_level = 'Grade 10' THEN 10
            WHEN n.grade_level = 'Grade 11' THEN 11
            WHEN n.grade_level = 'Grade 12' THEN 12
            ELSE 99 END", '', FALSE);
        $this->db->order_by('n.section', 'ASC');
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    // Keep your existing methods for backward compatibility
    public function count_by_assessment($assessment_type) {
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where("(sbfp_beneficiary = 'Yes' OR grade_level IN ('Kindergarten','Grade 1'))", NULL, FALSE);
        return $this->db->count_all_results('nutritional_assessments');
    }
    
    public function get_schools() {
        $this->db->select('DISTINCT(school_name)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->where("(sbfp_beneficiary = 'Yes' OR grade_level IN ('Kindergarten','Grade 1'))", NULL, FALSE);
        $this->db->order_by('school_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_nutritional_stats($assessment_type = 'baseline') {
        $this->db->select('nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where("(sbfp_beneficiary = 'Yes' OR grade_level IN ('Kindergarten','Grade 1'))", NULL, FALSE);
        $this->db->group_by('nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
}