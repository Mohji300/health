<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sbfp_Beneficiaries_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    public function get_beneficiaries($assessment_type = 'baseline', $school_level = 'all', $school_name = '') {
        $this->db->select('*');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        
        // Apply school level filter
        if ($school_level !== 'all') {
            if ($school_level === 'elementary') {
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            } elseif ($school_level === 'secondary') {
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            } elseif ($school_level === 'integrated_elementary') {
                $this->db->where('school_level', 'integrated');
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            } elseif ($school_level === 'integrated_secondary') {
                $this->db->where('school_level', 'integrated');
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            }
        }
        
        // Apply school name filter
        if (!empty($school_name)) {
            $this->db->like('school_name', $school_name);
        }
        
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
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
?>