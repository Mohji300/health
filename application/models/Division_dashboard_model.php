<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class division_dashboard_model extends CI_Model {
    
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
        
        // Use COUNT(DISTINCT school_id) to count unique schools with assessments
        // This prevents counting the same school multiple times
        
        // Baseline count - count unique schools with baseline assessments
        $this->db->select('COUNT(DISTINCT n.school_id) as count')
                ->from('nutritional_assessments n')
                ->join('schools s', 'n.school_id = s.id', 'left')
                ->where('n.is_deleted', 0)
                ->where('n.assessment_type', 'baseline');
        
        $this->apply_school_level_filter($school_level);
        $baseline_query = $this->db->get();
        $baseline = $baseline_query->row()->count ?? 0;
        
        // Midline count - count unique schools with midline assessments
        $this->db->select('COUNT(DISTINCT n.school_id) as count')
                ->from('nutritional_assessments n')
                ->join('schools s', 'n.school_id = s.id', 'left')
                ->where('n.is_deleted', 0)
                ->where('n.assessment_type', 'midline');
        
        $this->apply_school_level_filter($school_level);
        $midline_query = $this->db->get();
        $midline = $midline_query->row()->count ?? 0;
        
        // Endline count - count unique schools with endline assessments
        $this->db->select('COUNT(DISTINCT n.school_id) as count')
                ->from('nutritional_assessments n')
                ->join('schools s', 'n.school_id = s.id', 'left')
                ->where('n.is_deleted', 0)
                ->where('n.assessment_type', 'endline');
        
        $this->apply_school_level_filter($school_level);
        $endline_query = $this->db->get();
        $endline = $endline_query->row()->count ?? 0;
        
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
        
        // Get schools with their IDs - IMPORTANT: Get both id and school_id
        $schools_query = $this->db->select('id, name, school_id as code, school_id')
                                ->from('schools')
                                ->where('school_district_id', $district->id)
                                ->order_by('name')
                                ->get();
        
        $schools = array();
        if ($schools_query->num_rows() > 0) {
            foreach ($schools_query->result() as $row) {
                // Use school_id (the official school code) for checking, NOT the auto-increment id
                // This matches with nutritional_assessments.school_id
                
                // Check for baseline assessments using school_id (official code)
                $has_baseline = $this->db->from('nutritional_assessments')
                                        ->where('school_id', $row->school_id)  // Use school_code, not id
                                        ->where('is_deleted', 0)
                                        ->where('assessment_type', 'baseline')
                                        ->count_all_results() > 0;
                
                // Check for midline assessments using school_id (official code)
                $has_midline = $this->db->from('nutritional_assessments')
                                    ->where('school_id', $row->school_id)  // Use school_code, not id
                                    ->where('is_deleted', 0)
                                    ->where('assessment_type', 'midline')
                                    ->count_all_results() > 0;
                
                // Check for endline assessments using school_id (official code)
                $has_endline = $this->db->from('nutritional_assessments')
                                    ->where('school_id', $row->school_id)  // Use school_code, not id
                                    ->where('is_deleted', 0)
                                    ->where('assessment_type', 'endline')
                                    ->count_all_results() > 0;
                
                // If a specific assessment type is requested, also include the filtered result
                $has_submitted = false;
                if (!empty($assessment_type)) {
                    switch($assessment_type) {
                        case 'baseline':
                            $has_submitted = $has_baseline;
                            break;
                        case 'midline':
                            $has_submitted = $has_midline;
                            break;
                        case 'endline':
                            $has_submitted = $has_endline;
                            break;
                        default:
                            $has_submitted = $has_baseline || $has_midline || $has_endline;
                    }
                } else {
                    $has_submitted = $has_baseline || $has_midline || $has_endline;
                }
                
                $schools[] = array(
                    'id' => $row->id,
                    'name' => $row->name,
                    'code' => $row->code,
                    'school_id' => $row->school_id, // Add the official school code
                    'has_baseline' => $has_baseline,
                    'has_midline' => $has_midline,
                    'has_endline' => $has_endline,
                    'has_submitted' => $has_submitted,
                    'assessments' => array(
                        'baseline' => $has_baseline,
                        'midline' => $has_midline,
                        'endline' => $has_endline,
                        'any' => $has_baseline || $has_midline || $has_endline
                    )
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
     * Get school details by ID or name
     */
    public function get_school_details($identifier) {
        // Build the base query
        $this->db->select('s.id, s.name, s.school_id as code, s.address, s.school_level as level, 
                        s.school_head_name as contact_person, s.contact_number, s.email, 
                        sd.name as district, s.region, s.division, s.school_type as type')
                ->from('schools s')
                ->join('school_districts sd', 's.school_district_id = sd.id', 'left');
        
        // Check if identifier is numeric (ID) or string (name)
        if (is_numeric($identifier)) {
            $this->db->where('s.id', $identifier);
        } else {
            // When searching by name, be more specific to avoid duplicates
            $this->db->where('s.name', $identifier);
        }
        
        $query = $this->db->limit(1)->get();
        $school = $query->row_array();
        
        if ($school) {
            // Add assessment information using school_id for accuracy
            $school['assessments'] = array(
                'has_baseline' => $this->db->from('nutritional_assessments')
                                        ->where('school_id', $school['id'])  // Use school_id, not name
                                        ->where('is_deleted', 0)
                                        ->where('assessment_type', 'baseline')
                                        ->count_all_results() > 0,
                'has_midline' => $this->db->from('nutritional_assessments')
                                        ->where('school_id', $school['id'])
                                        ->where('is_deleted', 0)
                                        ->where('assessment_type', 'midline')
                                        ->count_all_results() > 0,
                'has_endline' => $this->db->from('nutritional_assessments')
                                        ->where('school_id', $school['id'])
                                        ->where('is_deleted', 0)
                                        ->where('assessment_type', 'endline')
                                        ->count_all_results() > 0,
                'last_assessment_date' => $this->get_last_assessment_date($school['id'])
            );
        }
        
        return $school;
    }

    /**
     * Helper method to get last assessment date for a school using school_id
     */
    private function get_last_assessment_date($school_id) {
        $query = $this->db->select('created_at')
                        ->from('nutritional_assessments')
                        ->where('school_id', $school_id)
                        ->where('is_deleted', 0)
                        ->order_by('created_at', 'DESC')
                        ->limit(1)
                        ->get();
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            return date('Y-m-d', strtotime($row->created_at));
        }
        
        return null;
    }

    /**
     * Get user schools based on user type and district
     * This method is called by the controller to get schools for the current user
     */
    public function get_user_schools($user_id, $user_type, $user_district) {
        // For division-level users, return all schools in the division
        if ($user_type === 'division') {
            // Get all districts first
            $districts = $this->get_all_districts();
            
            $all_schools = array();
            
            foreach ($districts as $district) {
                // Get schools for each district
                $district_schools = $this->db->select('id, name, school_id as code')
                                            ->from('schools')
                                            ->where('school_district_id', $district['id'])
                                            ->order_by('name')
                                            ->get()
                                            ->result_array();
                
                // Add district info to each school
                foreach ($district_schools as &$school) {
                    $school['district'] = $district['name'];
                    $school['district_id'] = $district['id'];
                    
                    // Check assessment status for each school
                    $school['has_baseline'] = $this->db->from('nutritional_assessments')
                                                    ->where('school_id', $school['id'])
                                                    ->where('is_deleted', 0)
                                                    ->where('assessment_type', 'baseline')
                                                    ->count_all_results() > 0;
                    
                    $school['has_midline'] = $this->db->from('nutritional_assessments')
                                                    ->where('school_id', $school['id'])
                                                    ->where('is_deleted', 0)
                                                    ->where('assessment_type', 'midline')
                                                    ->count_all_results() > 0;
                    
                    $school['has_endline'] = $this->db->from('nutritional_assessments')
                                                    ->where('school_id', $school['id'])
                                                    ->where('is_deleted', 0)
                                                    ->where('assessment_type', 'endline')
                                                    ->count_all_results() > 0;
                    
                    $school['has_submitted'] = $school['has_baseline'] || $school['has_midline'] || $school['has_endline'];
                }
                
                $all_schools = array_merge($all_schools, $district_schools);
            }
            
            return $all_schools;
        }
        // For district-level users, return only schools in their district
        elseif ($user_type === 'district' && !empty($user_district)) {
            // Get district ID from name
            $district = $this->db->select('id')
                                ->from('school_districts')
                                ->where('name', $user_district)
                                ->limit(1)
                                ->get()
                                ->row();
            
            if (!$district) {
                return array();
            }
            
            // Get schools for this district
            $schools = $this->db->select('id, name, school_id as code')
                            ->from('schools')
                            ->where('school_district_id', $district->id)
                            ->order_by('name')
                            ->get()
                            ->result_array();
            
            // Add assessment status
            foreach ($schools as &$school) {
                $school['district'] = $user_district;
                $school['district_id'] = $district->id;
                
                $school['has_baseline'] = $this->db->from('nutritional_assessments')
                                                ->where('school_id', $school['id'])
                                                ->where('is_deleted', 0)
                                                ->where('assessment_type', 'baseline')
                                                ->count_all_results() > 0;
                
                $school['has_midline'] = $this->db->from('nutritional_assessments')
                                                ->where('school_id', $school['id'])
                                                ->where('is_deleted', 0)
                                                ->where('assessment_type', 'midline')
                                                ->count_all_results() > 0;
                
                $school['has_endline'] = $this->db->from('nutritional_assessments')
                                                ->where('school_id', $school['id'])
                                                ->where('is_deleted', 0)
                                                ->where('assessment_type', 'endline')
                                                ->count_all_results() > 0;
                
                $school['has_submitted'] = $school['has_baseline'] || $school['has_midline'] || $school['has_endline'];
            }
            
            return $schools;
        }
        
        // Default return empty array
        return array();
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
        
        // Use COUNT(DISTINCT school_id) to count unique schools with assessments
        $schools_with_assessments = 0;
        if ($this->db->table_exists('nutritional_assessments')) {
            $schools_with_assessments = $this->db->select('COUNT(DISTINCT school_id) as count')
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