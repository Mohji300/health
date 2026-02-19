<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class sbfp_beneficiaries_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get beneficiaries with role-based filtering
     */
    public function get_beneficiaries($assessment_type = 'baseline', $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '') {
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply school level filtering (grade restrictions)
        $this->apply_school_level_filter($school_level);
        
        $this->db->order_by('n.school_name', 'ASC');
        $this->db->order_by('n.grade_level', 'ASC');
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        $results = $query->result_array();
        
        // Log for debugging
        log_message('debug', '=== MODEL GET_BENEFICIARIES ===');
        log_message('debug', 'User Role: ' . $user_role);
        log_message('debug', 'District Filter: ' . $district);
        log_message('debug', 'Total Results: ' . count($results));
        
        return $results;
    }
    
    /**
     * Apply role-based filter to queries - FIXED VERSION
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
                    // FIX: Use proper district filtering - this should match the school_district field
                    $this->db->where('n.school_district', $district);
                    log_message('debug', 'District filter: school_district = ' . $district);
                } else {
                    log_message('debug', 'WARNING: District user with empty district value!');
                    // If no district, return no results
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
    public function count_by_assessment_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '') {
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply school level filtering
        $this->apply_school_level_filter($school_level);
        
        return $this->db->count_all_results();
    }
    
    /**
     * Get nutritional statistics with role-based filtering
     */
    public function get_nutritional_stats_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '') {
        $this->db->select('n.nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        
        // Apply role-based filtering
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        
        // Apply school level filtering
        $this->apply_school_level_filter($school_level);
        
        $this->db->group_by('n.nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get schools list based on user role - FIXED
     */
    public function get_schools_by_role($user_role, $school_id, $district) {
        $this->db->select('DISTINCT(n.school_name)');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.is_deleted', 0);
        
        log_message('debug', 'Getting schools by role - Role: ' . $user_role . ', District: ' . $district);
        
        if ($user_role === 'school' && !empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        } elseif ($user_role === 'district' && !empty($district)) {
            // FIX: Use proper district filtering
            $this->db->where('n.school_district', $district);
            log_message('debug', 'Filtering schools by district: ' . $district);
        }
        
        $this->db->order_by('n.school_name', 'ASC');
        $query = $this->db->get();
        $results = $query->result_array();
        
        log_message('debug', 'Schools found: ' . count($results));
        
        return $results;
    }
    
    // Keep your existing methods for backward compatibility
    public function count_by_assessment($assessment_type) {
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        return $this->db->count_all_results('nutritional_assessments');
    }
    
    public function get_schools() {
        $this->db->select('DISTINCT(school_name)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->order_by('school_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_nutritional_stats($assessment_type = 'baseline') {
        $this->db->select('nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->group_by('nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
}