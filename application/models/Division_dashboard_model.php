<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Division_dashboard_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * ENHANCED: Get nutritional data for entire division with assessment type AND school level filter
     */
    public function get_division_nutritional_data($assessment_type = 'baseline', $school_level = 'all') {
        // Check if table exists
        if (!$this->db->table_exists('nutritional_assessments')) {
            return array();
        }
        
        // Define grade levels
        $grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        
        $data = array();
        
        foreach ($grades as $grade) {
            // Apply school level filter to base query
            $this->apply_school_level_filter($school_level, $grade);
            
            // Get data for each grade with gender breakdown
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
                'severely_wasted' => $this->calculate_bmi_by_gender('Severely Wasted', $grade, 'M', $assessment_type, $school_level),
                'wasted' => $this->calculate_bmi_by_gender('Wasted', $grade, 'M', $assessment_type, $school_level),
                'normal_bmi' => $this->calculate_bmi_by_gender('Normal', $grade, 'M', $assessment_type, $school_level),
                'overweight' => $this->calculate_bmi_by_gender('Overweight', $grade, 'M', $assessment_type, $school_level),
                'obese' => $this->calculate_bmi_by_gender('Obese', $grade, 'M', $assessment_type, $school_level),
                'severely_stunted' => $this->calculate_hfa_by_gender('Severely Stunted', $grade, 'M', $assessment_type, $school_level),
                'stunted' => $this->calculate_hfa_by_gender('Stunted', $grade, 'M', $assessment_type, $school_level),
                'normal_hfa' => $this->calculate_hfa_by_gender('Normal', $grade, 'M', $assessment_type, $school_level),
                'tall' => $this->calculate_hfa_by_gender('Tall', $grade, 'M', $assessment_type, $school_level),
                'pupils_height' => (int)($row->male_count ?? 0)
            );
            
            $data[$grade . '_f'] = array(
                'enrolment' => (int)($row->female_count ?? 0),
                'pupils_weighed' => (int)($row->female_count ?? 0),
                'severely_wasted' => $this->calculate_bmi_by_gender('Severely Wasted', $grade, 'F', $assessment_type, $school_level),
                'wasted' => $this->calculate_bmi_by_gender('Wasted', $grade, 'F', $assessment_type, $school_level),
                'normal_bmi' => $this->calculate_bmi_by_gender('Normal', $grade, 'F', $assessment_type, $school_level),
                'overweight' => $this->calculate_bmi_by_gender('Overweight', $grade, 'F', $assessment_type, $school_level),
                'obese' => $this->calculate_bmi_by_gender('Obese', $grade, 'F', $assessment_type, $school_level),
                'severely_stunted' => $this->calculate_hfa_by_gender('Severely Stunted', $grade, 'F', $assessment_type, $school_level),
                'stunted' => $this->calculate_hfa_by_gender('Stunted', $grade, 'F', $assessment_type, $school_level),
                'normal_hfa' => $this->calculate_hfa_by_gender('Normal', $grade, 'F', $assessment_type, $school_level),
                'tall' => $this->calculate_hfa_by_gender('Tall', $grade, 'F', $assessment_type, $school_level),
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
     * NEW: Apply school level filter to queries
     */
    private function apply_school_level_filter($school_level, $grade = null) {
        if ($school_level === 'all') {
            return;
        }
        
        if ($school_level === 'secondary') {
            $this->db->where("(
                school_name LIKE '%High%' OR 
                school_name LIKE '%National High School%' OR
                school_name LIKE '%NHS%' OR
                school_name LIKE '%Secondary%' OR
                school_name LIKE '%HighSchool%'
                AND school_name NOT LIKE '%Integrated%'
            )");
        } 
        elseif ($school_level === 'elementary') {
            $this->db->where("(
                school_name NOT LIKE '%High%' AND 
                school_name NOT LIKE '%Secondary%' AND
                school_name NOT LIKE '%Integrated%' AND
                school_name NOT LIKE '%NHS%' AND
                school_name NOT LIKE '%HighSchool%'
            )");
        }
        elseif ($school_level === 'integrated') {
            $this->db->where("school_name LIKE '%Integrated%'");
        }
        elseif ($school_level === 'integrated_elementary') {
            $this->db->where("school_name LIKE '%Integrated%'");
            if ($grade) {
                $elementary_grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
                $this->db->where_in('grade_level', $elementary_grades);
            }
        }
        elseif ($school_level === 'integrated_secondary') {
            $this->db->where("school_name LIKE '%Integrated%'");
            if ($grade) {
                $secondary_grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
                $this->db->where_in('grade_level', $secondary_grades);
            }
        }
    }
    
    /**
     * ENHANCED: Calculate BMI by gender with school level filter
     */
    private function calculate_bmi_by_gender($bmi_status, $grade, $gender, $assessment_type, $school_level = 'all') {
        $this->db->from('nutritional_assessments')
                 ->where('is_deleted', 0)
                 ->where('assessment_type', $assessment_type)
                 ->where('grade_level', $grade)
                 ->where('sex', $gender)
                 ->where('nutritional_status', $bmi_status);
        
        $this->apply_school_level_filter($school_level);
        
        return $this->db->count_all_results();
    }
    
    /**
     * ENHANCED: Calculate HFA by gender with school level filter
     */
    private function calculate_hfa_by_gender($hfa_status, $grade, $gender, $assessment_type, $school_level = 'all') {
        $this->db->from('nutritional_assessments')
                 ->where('is_deleted', 0)
                 ->where('assessment_type', $assessment_type)
                 ->where('grade_level', $grade)
                 ->where('sex', $gender)
                 ->where('height_for_age', $hfa_status);
        
        $this->apply_school_level_filter($school_level);
        
        return $this->db->count_all_results();
    }
    
    /**
     * ENHANCED: Get total enrolment for division with filters
     */
    public function get_division_grand_total($assessment_type = 'baseline', $school_level = 'all') {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return 0;
        }
        
        $this->db->select('COUNT(*) as enrolment')
                 ->from('nutritional_assessments')
                 ->where('is_deleted', 0)
                 ->where('assessment_type', $assessment_type);
        
        $this->apply_school_level_filter($school_level);
        
        $query = $this->db->get();
        
        return $query->row()->enrolment ?? 0;
    }
    
    /**
     * ENHANCED: Get assessment counts for division with school level filter
     */
    public function get_assessment_counts_division($school_level = 'all') {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return ['baseline' => 0, 'midline' => 0, 'endline' => 0];
        }
        
        // Baseline count
        $this->db->from('nutritional_assessments')
                 ->where('is_deleted', 0)
                 ->where('assessment_type', 'baseline');
        $this->apply_school_level_filter($school_level);
        $baseline = $this->db->count_all_results();
        
        // Midline count - NEW
        $this->db->from('nutritional_assessments')
                 ->where('is_deleted', 0)
                 ->where('assessment_type', 'midline');
        $this->apply_school_level_filter($school_level);
        $midline = $this->db->count_all_results();
        
        // Endline count
        $this->db->from('nutritional_assessments')
                 ->where('is_deleted', 0)
                 ->where('assessment_type', 'endline');
        $this->apply_school_level_filter($school_level);
        $endline = $this->db->count_all_results();
        
        return [
            'baseline' => $baseline,
            'midline' => $midline,
            'endline' => $endline
        ];
    }
    
    /**
     * ENHANCED: Get schools by district name with submission status
     */
    public function get_schools_by_district($district_name, $assessment_type = null) {
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
                // Check if school has assessments for the requested assessment type (if provided)
                $this->db->from('nutritional_assessments')
                         ->where('school_name', $row->name)
                         ->where('is_deleted', 0);
                if (!empty($assessment_type)) {
                    $this->db->where('assessment_type', $assessment_type);
                }
                $has_submitted = $this->db->count_all_results() > 0;
                
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
     * Get all districts in division
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
     * Get district reports for entire division
     */
    public function get_district_reports() {
        $districts = $this->get_all_districts();
        
        $reports = array();
        
        foreach ($districts as $district) {
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
            
            $legislative_district = 'District ' . substr($district['name'], 0, 3);
            
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
     * Get user schools (for division view)
     */
    public function get_user_schools($user_id, $user_type, $user_district) {
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
     * Get school details
     */
    public function get_school_details($school_name) {
        $query = $this->db->select('name, address, school_level, school_head_name, email, school_district')
                         ->from('schools')
                         ->where('name', $school_name)
                         ->limit(1)
                         ->get();
        
        return $query->num_rows() > 0 ? $query->row_array() : null;
    }
    
    /**
     * Get division summary
     */
    public function get_division_summary() {
        $total_schools = $this->db->from('schools')->count_all_results();
        $total_districts = $this->db->from('school_districts')->count_all_results();
        
        $total_assessments = 0;
        if ($this->db->table_exists('nutritional_assessments')) {
            $total_assessments = $this->db->from('nutritional_assessments')
                                         ->where('is_deleted', 0)
                                         ->count_all_results();
        }
        
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
}