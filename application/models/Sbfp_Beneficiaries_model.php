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
    public function get_beneficiaries($assessment_type = 'baseline', $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '') {
        
        log_message('debug', '=== GET_BENEFICIARIES CALLED ===');
        log_message('debug', 'Params: assessment_type=' . $assessment_type . ', school_name=' . $school_name . ', school_level=' . $school_level . ', user_role=' . $user_role . ', district=' . $district);
        log_message('debug', 'Filters: grade_level=' . $grade_level_filter . ', school_name_filter=' . $school_name_filter . ', district_filter=' . $district_filter);
        
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
        // Apply role-based filtering (mandatory)
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply additional filters (based on permissions)
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        
        // Apply school level filtering (grade restrictions)
        $this->apply_school_level_filter($school_level);
        
        $this->db->order_by('n.school_name', 'ASC');
        $this->db->order_by('n.grade_level', 'ASC');
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        
        log_message('debug', 'SQL Query: ' . $this->db->last_query());
        
        $results = $query->result_array();
        
        log_message('debug', 'Results found: ' . count($results));
        
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
        
        log_message('debug', 'Applying role filter - User Role: ' . $user_role . ', District: ' . $district);
        
        switch($user_role) {
            case 'school':
                // School users ONLY see their own school's data
                if (!empty($school_name)) {
                    $this->db->where('n.school_name', $school_name);
                    log_message('debug', 'School filter: school_name = ' . $school_name);
                } elseif (!empty($school_id)) {
                    $this->db->where('n.school_id', $school_id);
                    log_message('debug', 'School filter: school_id = ' . $school_id);
                }
                break;
                
            case 'district':
                // District users see all schools in their district
                if (!empty($district)) {
                    $this->db->where('n.school_district', $district);
                    log_message('debug', 'District filter: school_district = ' . $district);
                } else {
                    log_message('debug', 'WARNING: District user with empty district value!');
                    $this->db->where('1=0');
                }
                break;
                
            case 'division':
            case 'admin':
                // Division/Admin users see all data
                if (!empty($selected_school)) {
                    $this->db->like('n.school_name', $selected_school);
                    log_message('debug', 'Admin filter: selected_school = ' . $selected_school);
                }
                break;
                
            default:
                // Default security
                if (!empty($school_name)) {
                    $this->db->where('n.school_name', $school_name);
                } elseif (!empty($school_id)) {
                    $this->db->where('n.school_id', $school_id);
                }
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
    
    /**
     * Count assessments by type with role-based filtering
     */
    public function count_by_assessment_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '') {
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
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
    public function get_nutritional_stats_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '') {
        $this->db->select('n.nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
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
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
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
    public function get_count_by_school($assessment_type = 'baseline', $school_name = '') {
        $this->db->select('COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        
        $query = $this->db->get();
        $result = $query->row();
        return $result ? $result->count : 0;
    }
    
    /**
     * Get SBFP beneficiaries by nutritional status
     */
    public function get_beneficiaries_by_status($nutritional_status, $assessment_type = 'baseline', $school_name = '') {
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        $this->db->where('LOWER(n.nutritional_status)', strtolower($nutritional_status));
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        
        $this->db->order_by('n.school_name', 'ASC');
        $this->db->order_by('n.grade_level', 'ASC');
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get summary statistics for SBFP beneficiaries
     */
    public function get_summary_stats($assessment_type = 'baseline', $school_name = '') {
        $this->db->select('
            COUNT(*) as total_beneficiaries,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "severely wasted" THEN 1 ELSE 0 END) as severely_wasted,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "wasted" THEN 1 ELSE 0 END) as wasted,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "normal" THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "overweight" THEN 1 ELSE 0 END) as overweight,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "obese" THEN 1 ELSE 0 END) as obese,
            SUM(CASE WHEN n.sex = "M" THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN n.sex = "F" THEN 1 ELSE 0 END) as female_count
        ');
        
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        
        $query = $this->db->get();
        return $query->row();
    }
    
    /**
     * Get grade level distribution for SBFP beneficiaries
     */
    public function get_grade_distribution($assessment_type = 'baseline', $school_name = '') {
        $this->db->select('n.grade_level, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        
        $this->db->group_by('n.grade_level');
        $this->db->order_by('n.grade_level', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Export SBFP beneficiaries data
     */
    public function export_beneficiaries($assessment_type = 'baseline', $school_name = '', $user_role = 'school', $school_id = '', $district = '', $selected_school = '') {
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
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        $this->db->order_by('n.school_name', 'ASC');
        $this->db->order_by('n.grade_level', 'ASC');
        $this->db->order_by('n.section', 'ASC');
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    // Keep your existing methods for backward compatibility
    public function count_by_assessment($assessment_type) {
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        return $this->db->count_all_results('nutritional_assessments');
    }
    
    public function get_schools() {
        $this->db->select('DISTINCT(school_name)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        $this->db->order_by('school_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_nutritional_stats($assessment_type = 'baseline') {
        $this->db->select('nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        $this->db->group_by('nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
}