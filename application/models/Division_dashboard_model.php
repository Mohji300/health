<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Division_dashboard_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * SIMPLIFIED: Get nutritional data for entire division
     */
    public function get_division_nutritional_data($assessment_type = 'baseline') {
        // Check if table exists
        if (!$this->db->table_exists('nutritional_assessments')) {
            return array();
        }
        
        // Define grade levels
        $grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        
        $data = array();
        
        foreach ($grades as $grade) {
            // Get data for each grade with gender breakdown - NO JOINS
            $query = $this->db->select("
                COUNT(*) as enrolment,
                SUM(CASE WHEN sex = 'M' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN sex = 'F' THEN 1 ELSE 0 END) as female_count,
                SUM(CASE WHEN nutritional_status = 'Severely Wasted' THEN 1 ELSE 0 END) as severely_wasted,
                SUM(CASE WHEN nutritional_status = 'Wasted' THEN 1 ELSE 0 END) as wasted,
                SUM(CASE WHEN nutritional_status = 'Normal' THEN 1 ELSE 0 END) as normal_bmi,
                SUM(CASE WHEN nutritional_status = 'Overweight' THEN 1 ELSE 0 END) as overweight,
                SUM(CASE WHEN nutritional_status = 'Obese' THEN 1 ELSE 0 END) as obese,
                SUM(CASE WHEN height_for_age = 'Severely Stunted' THEN 1 ELSE 0 END) as severely_stunted,
                SUM(CASE WHEN height_for_age = 'Stunted' THEN 1 ELSE 0 END) as stunted,
                SUM(CASE WHEN height_for_age = 'Normal' THEN 1 ELSE 0 END) as normal_hfa,
                SUM(CASE WHEN height_for_age = 'Tall' THEN 1 ELSE 0 END) as tall,
                COUNT(*) as pupils_height
            ")
            ->from('nutritional_assessments')
            ->where('is_deleted', 0)
            ->where('assessment_type', $assessment_type)
            ->where('grade_level', $grade)
            ->get();
            
            $row = $query->row();
            
            // Create data structure for each gender and total
            $data[$grade . '_m'] = array(
                'enrolment' => (int)($row->male_count ?? 0),
                'pupils_weighed' => (int)($row->male_count ?? 0),
                'severely_wasted' => $this->calculate_bmi_by_gender('Severely Wasted', $grade, 'M', $assessment_type),
                'wasted' => $this->calculate_bmi_by_gender('Wasted', $grade, 'M', $assessment_type),
                'normal_bmi' => $this->calculate_bmi_by_gender('Normal', $grade, 'M', $assessment_type),
                'overweight' => $this->calculate_bmi_by_gender('Overweight', $grade, 'M', $assessment_type),
                'obese' => $this->calculate_bmi_by_gender('Obese', $grade, 'M', $assessment_type),
                'severely_stunted' => $this->calculate_hfa_by_gender('Severely Stunted', $grade, 'M', $assessment_type),
                'stunted' => $this->calculate_hfa_by_gender('Stunted', $grade, 'M', $assessment_type),
                'normal_hfa' => $this->calculate_hfa_by_gender('Normal', $grade, 'M', $assessment_type),
                'tall' => $this->calculate_hfa_by_gender('Tall', $grade, 'M', $assessment_type),
                'pupils_height' => (int)($row->male_count ?? 0)
            );
            
            $data[$grade . '_f'] = array(
                'enrolment' => (int)($row->female_count ?? 0),
                'pupils_weighed' => (int)($row->female_count ?? 0),
                'severely_wasted' => $this->calculate_bmi_by_gender('Severely Wasted', $grade, 'F', $assessment_type),
                'wasted' => $this->calculate_bmi_by_gender('Wasted', $grade, 'F', $assessment_type),
                'normal_bmi' => $this->calculate_bmi_by_gender('Normal', $grade, 'F', $assessment_type),
                'overweight' => $this->calculate_bmi_by_gender('Overweight', $grade, 'F', $assessment_type),
                'obese' => $this->calculate_bmi_by_gender('Obese', $grade, 'F', $assessment_type),
                'severely_stunted' => $this->calculate_hfa_by_gender('Severely Stunted', $grade, 'F', $assessment_type),
                'stunted' => $this->calculate_hfa_by_gender('Stunted', $grade, 'F', $assessment_type),
                'normal_hfa' => $this->calculate_hfa_by_gender('Normal', $grade, 'F', $assessment_type),
                'tall' => $this->calculate_hfa_by_gender('Tall', $grade, 'F', $assessment_type),
                'pupils_height' => (int)($row->female_count ?? 0)
            );
            
            $data[$grade . '_total'] = array(
                'enrolment' => (int)($row->enrolment ?? 0),
                'pupils_weighed' => (int)($row->enrolment ?? 0),
                'severely_wasted' => (int)($row->severely_wasted ?? 0),
                'wasted' => (int)($row->wasted ?? 0),
                'normal_bmi' => (int)($row->normal_bmi ?? 0),
                'overweight' => (int)($row->overweight ?? 0),
                'obese' => (int)($row->obese ?? 0),
                'severely_stunted' => (int)($row->severely_stunted ?? 0),
                'stunted' => (int)($row->stunted ?? 0),
                'normal_hfa' => (int)($row->normal_hfa ?? 0),
                'tall' => (int)($row->tall ?? 0),
                'pupils_height' => (int)($row->pupils_height ?? 0)
            );
        }
        
        return $data;
    }
    
    /**
     * Helper to calculate BMI by gender - SIMPLIFIED
     */
    private function calculate_bmi_by_gender($bmi_status, $grade, $gender, $assessment_type) {
        $count = $this->db->from('nutritional_assessments')
                         ->where('is_deleted', 0)
                         ->where('assessment_type', $assessment_type)
                         ->where('grade_level', $grade)
                         ->where('sex', $gender)
                         ->where('nutritional_status', $bmi_status)
                         ->count_all_results();
        
        return $count;
    }
    
    /**
     * Helper to calculate HFA by gender - SIMPLIFIED
     */
    private function calculate_hfa_by_gender($hfa_status, $grade, $gender, $assessment_type) {
        $count = $this->db->from('nutritional_assessments')
                         ->where('is_deleted', 0)
                         ->where('assessment_type', $assessment_type)
                         ->where('grade_level', $grade)
                         ->where('sex', $gender)
                         ->where('height_for_age', $hfa_status)
                         ->count_all_results();
        
        return $count;
    }
    
    /**
     * SIMPLIFIED: Get total enrolment for division
     */
    public function get_division_grand_total($assessment_type = 'baseline') {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return 0;
        }
        
        $query = $this->db->select('COUNT(*) as enrolment')
                         ->from('nutritional_assessments')
                         ->where('is_deleted', 0)
                         ->where('assessment_type', $assessment_type)
                         ->get();
        
        return $query->row()->enrolment ?? 0;
    }
    
    /**
     * SIMPLIFIED: Get assessment counts for division
     */
    public function get_assessment_counts_division() {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return ['baseline' => 0, 'endline' => 0];
        }
        
        $baseline = $this->db->from('nutritional_assessments')
                            ->where('is_deleted', 0)
                            ->where('assessment_type', 'baseline')
                            ->count_all_results();
        
        $endline = $this->db->from('nutritional_assessments')
                           ->where('is_deleted', 0)
                           ->where('assessment_type', 'endline')
                           ->count_all_results();
        
        return [
            'baseline' => $baseline,
            'endline' => $endline
        ];
    }
    
    /**
     * SIMPLIFIED: Get all districts in division
     */
    public function get_all_districts() {
        if (!$this->db->table_exists('school_districts')) {
            return array();
        }
        
        $query = $this->db->select('id, name')
                         ->from('school_districts')
                         ->order_by('name')
                         ->get();
        
        $districts = array();
        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $districts[] = array(
                    'id' => $row->id,
                    'name' => $row->name
                );
            }
        }
        
        return $districts;
    }
    
    /**
     * SIMPLIFIED: Get district reports for entire division
     */
    public function get_district_reports() {
        // Get all districts
        $districts = $this->get_all_districts();
        
        $reports = array();
        
        foreach ($districts as $district) {
            // Get schools in this district
            $schools = $this->db->select('name')
                               ->from('schools')
                               ->where('school_district_id', $district['id'])
                               ->get()
                               ->result_array();
            
            $school_names = array_column($schools, 'name');
            $total_schools = count($school_names);
            
            $submitted_schools = 0;
            if ($total_schools > 0) {
                // Count schools that have assessments
                foreach ($school_names as $school_name) {
                    $has_assessments = $this->db->from('nutritional_assessments')
                                               ->where('school_name', $school_name)
                                               ->where('is_deleted', 0)
                                               ->count_all_results() > 0;
                    
                    if ($has_assessments) {
                        $submitted_schools++;
                    }
                }
            }
            
            // Group by legislative district (simplified - use placeholder)
            $legislative_district = 'District ' . substr($district['name'], 0, 3); // Simple grouping
            
            if (!isset($reports[$legislative_district])) {
                $reports[$legislative_district] = array();
            }
            
            $reports[$legislative_district][$district['name']] = array(
                'total' => $total_schools,
                'submitted' => $submitted_schools
            );
        }
        
        return $reports;
    }
    
    /**
     * SIMPLIFIED: Get all districts with submission stats
     */
    public function get_all_districts_submission_stats() {
        $districts = $this->get_all_districts();
        
        $stats = array();
        
        foreach ($districts as $district) {
            // Get schools in this district
            $schools = $this->db->select('name')
                               ->from('schools')
                               ->where('school_district_id', $district['id'])
                               ->get()
                               ->result_array();
            
            $school_names = array_column($schools, 'name');
            $total_schools = count($school_names);
            
            $submitted_schools = 0;
            if ($total_schools > 0) {
                foreach ($school_names as $school_name) {
                    $has_assessments = $this->db->from('nutritional_assessments')
                                               ->where('school_name', $school_name)
                                               ->where('is_deleted', 0)
                                               ->count_all_results() > 0;
                    
                    if ($has_assessments) {
                        $submitted_schools++;
                    }
                }
            }
            
            $submission_rate = $total_schools > 0 ? round(($submitted_schools / $total_schools) * 100) : 0;
            
            $stats[$district['name']] = array(
                'district_id' => $district['id'],
                'district_name' => $district['name'],
                'legislative_district' => 'District Group', // Simplified
                'total_schools' => $total_schools,
                'submitted_schools' => $submitted_schools,
                'submission_rate' => $submission_rate,
                'status' => $total_schools > 0 ? 
                    ($submitted_schools == $total_schools ? 'Completed' : 
                     ($submitted_schools > 0 ? 'In Progress' : 'Not Started')) : 'No Schools'
            );
        }
        
        return $stats;
    }
    
    /**
     * SIMPLIFIED: Get schools by district name
     */
    public function get_schools_by_district($district_name) {
        // Get district ID
        $district = $this->db->select('id')
                            ->from('school_districts')
                            ->where('name', $district_name)
                            ->limit(1)
                            ->get()
                            ->row();
        
        if (!$district) {
            return array();
        }
        
        // Get schools
        $schools_query = $this->db->select('name, school_id as code')
                                 ->from('schools')
                                 ->where('school_district_id', $district->id)
                                 ->order_by('name')
                                 ->get();
        
        $schools = array();
        if ($schools_query->num_rows() > 0) {
            foreach ($schools_query->result() as $row) {
                // Check if school has assessments
                $has_submitted = $this->db->from('nutritional_assessments')
                                         ->where('school_name', $row->name)
                                         ->where('is_deleted', 0)
                                         ->count_all_results() > 0;
                
                $schools[] = array(
                    'name' => $row->name,
                    'code' => $row->code,
                    'has_submitted' => $has_submitted
                );
            }
        }
        
        return $schools;
    }
    
    /**
     * SIMPLIFIED: Get user schools (for division view)
     */
    public function get_user_schools($user_id, $user_type, $user_district) {
        // For division dashboard, return summary of all schools
        $summary = $this->get_division_summary();
        
        return array(
            array(
                'name' => 'Division Summary',
                'has_submitted' => $summary['schools_with_assessments'] > 0,
                'total_schools' => $summary['total_schools'],
                'submitted_schools' => $summary['schools_with_assessments']
            )
        );
    }
    
    /**
     * SIMPLIFIED: Get school details
     */
    public function get_school_details($school_name) {
        $query = $this->db->select('name, address, school_level, school_head_name, email')
                         ->from('schools')
                         ->where('name', $school_name)
                         ->limit(1)
                         ->get();
        
        return $query->num_rows() > 0 ? $query->row_array() : null;
    }
    
    /**
     * SIMPLIFIED: Get recent submissions
     */
    public function get_recent_submissions($limit = 10) {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return array();
        }
        
        $query = $this->db->select("
            school_name,
            school_district,
            grade_level,
            assessment_type,
            created_at,
            COUNT(*) as student_count
        ")
        ->from('nutritional_assessments')
        ->where('is_deleted', 0)
        ->group_by('school_name, school_district, grade_level, assessment_type, DATE(created_at)')
        ->order_by('created_at', 'DESC')
        ->limit($limit)
        ->get();
        
        return $query->result_array();
    }
    
    /**
     * SIMPLIFIED: Get division summary
     */
    public function get_division_summary() {
        // Get total schools
        $total_schools = $this->db->from('schools')->count_all_results();
        
        // Get total districts
        $total_districts = $this->db->from('school_districts')->count_all_results();
        
        // Get total assessments
        $total_assessments = 0;
        if ($this->db->table_exists('nutritional_assessments')) {
            $total_assessments = $this->db->from('nutritional_assessments')
                                         ->where('is_deleted', 0)
                                         ->count_all_results();
        }
        
        // Get schools with assessments
        $schools_with_assessments = 0;
        if ($this->db->table_exists('nutritional_assessments')) {
            $schools_with_assessments = $this->db->select('COUNT(DISTINCT school_name) as count')
                                                ->from('nutritional_assessments')
                                                ->where('is_deleted', 0)
                                                ->get()
                                                ->row()->count ?? 0;
        }
        
        return array(
            'total_schools' => $total_schools,
            'total_districts' => $total_districts,
            'total_assessments' => $total_assessments,
            'schools_with_assessments' => $schools_with_assessments,
            'coverage_rate' => $total_schools > 0 ? round(($schools_with_assessments / $total_schools) * 100) : 0
        );
    }
    
    /**
     * SIMPLIFIED: Get nutritional data (backward compatibility)
     */
    public function get_nutritional_data() {
        return $this->get_division_nutritional_data('baseline');
    }
    
    /**
     * SIMPLIFIED: Get grand total (backward compatibility)
     */
    public function get_grand_total() {
        return $this->get_division_grand_total('baseline');
    }
    
    /**
     * SIMPLIFIED: Get legislative district name by ID
     */
    private function get_legislative_district_name($legislative_district_id) {
        if (!$this->db->table_exists('legislative_districts') || !$legislative_district_id) {
            return 'General';
        }
        
        $query = $this->db->select('name')
                         ->from('legislative_districts')
                         ->where('id', $legislative_district_id)
                         ->limit(1)
                         ->get();
        
        return $query->num_rows() > 0 ? $query->row()->name : 'General';
    }
    
    /**
     * SIMPLIFIED: Get district ID by name
     */
    private function get_district_id_by_name($district_name) {
        if (!$this->db->table_exists('school_districts')) {
            return null;
        }
        
        $clean_name = preg_replace('/\s+District$/', '', $district_name);
        
        $query = $this->db->select('id')
                         ->from('school_districts')
                         ->where('name', $clean_name)
                         ->or_like('name', $district_name)
                         ->limit(1)
                         ->get();
        
        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }
        
        return null;
    }
}