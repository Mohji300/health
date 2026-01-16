<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class District_dashboard_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * SIMPLIFIED: Get district ID by name
     */
    private function get_district_id_by_name($district_name) {
        $query = $this->db->select('id, name')
                         ->from('school_districts')
                         ->where('name', trim($district_name))
                         ->or_where('name', trim($district_name) . ' District')
                         ->limit(1)
                         ->get();
        
        return $query->num_rows() > 0 ? $query->row()->id : null;
    }
    
    /**
     * SIMPLIFIED: Get schools in a district
     */
    private function get_district_schools_data($district_name) {
        $district_id = $this->get_district_id_by_name($district_name);
        if (!$district_id) {
            return array();
        }
        
        $schools = $this->db->select('id, name, school_id as code')
                           ->from('schools')
                           ->where('school_district_id', $district_id)
                           ->get()
                           ->result_array();
        
        return $schools;
    }
    
    /**
     * SIMPLIFIED: Get nutritional data for district
     */
    public function get_district_nutritional_data($district_name, $assessment_type = 'baseline') {
        // Get all schools in the district
        $schools = $this->get_district_schools_data($district_name);
        
        if (empty($schools)) {
            return array();
        }
        
        // Get school names for filtering
        $school_names = array_column($schools, 'name');
        
        // Define grade levels
        $grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        
        $data = array();
        
        foreach ($grades as $grade) {
            // SIMPLE APPROACH: Get data by school name and district name
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
            ->from('nutritional_assessments')  // NOTE: with 's'
            ->where_in('school_name', $school_names)  // Filter by school names
            ->where('school_district', $district_name)  // Filter by district name
            ->where('is_deleted', 0)
            ->where('assessment_type', $assessment_type)
            ->where('grade_level', $grade)
            ->get();
            
            $row = $query->row();
            
            // Store data
            $data[$grade . '_m'] = array(
                'enrolment' => (int)($row->male_count ?? 0),
                'pupils_weighed' => (int)($row->male_count ?? 0),
                'severely_wasted' => (int)($this->calculate_bmi_by_gender('Severely Wasted', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'wasted' => (int)($this->calculate_bmi_by_gender('Wasted', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'normal_bmi' => (int)($this->calculate_bmi_by_gender('Normal', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'overweight' => (int)($this->calculate_bmi_by_gender('Overweight', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'obese' => (int)($this->calculate_bmi_by_gender('Obese', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'severely_stunted' => (int)($this->calculate_hfa_by_gender('Severely Stunted', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'stunted' => (int)($this->calculate_hfa_by_gender('Stunted', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'normal_hfa' => (int)($this->calculate_hfa_by_gender('Normal', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'tall' => (int)($this->calculate_hfa_by_gender('Tall', $grade, $district_name, $school_names, 'M', $assessment_type)),
                'pupils_height' => (int)($row->male_count ?? 0)
            );
            
            $data[$grade . '_f'] = array(
                'enrolment' => (int)($row->female_count ?? 0),
                'pupils_weighed' => (int)($row->female_count ?? 0),
                'severely_wasted' => (int)($this->calculate_bmi_by_gender('Severely Wasted', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'wasted' => (int)($this->calculate_bmi_by_gender('Wasted', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'normal_bmi' => (int)($this->calculate_bmi_by_gender('Normal', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'overweight' => (int)($this->calculate_bmi_by_gender('Overweight', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'obese' => (int)($this->calculate_bmi_by_gender('Obese', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'severely_stunted' => (int)($this->calculate_hfa_by_gender('Severely Stunted', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'stunted' => (int)($this->calculate_hfa_by_gender('Stunted', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'normal_hfa' => (int)($this->calculate_hfa_by_gender('Normal', $grade, $district_name, $school_names, 'F', $assessment_type)),
                'tall' => (int)($this->calculate_hfa_by_gender('Tall', $grade, $district_name, $school_names, 'F', $assessment_type)),
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
    private function calculate_bmi_by_gender($bmi_status, $grade, $district_name, $school_names, $gender, $assessment_type) {
        $count = $this->db->from('nutritional_assessments')
                         ->where_in('school_name', $school_names)
                         ->where('school_district', $district_name)
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
    private function calculate_hfa_by_gender($hfa_status, $grade, $district_name, $school_names, $gender, $assessment_type) {
        $count = $this->db->from('nutritional_assessments')
                         ->where_in('school_name', $school_names)
                         ->where('school_district', $district_name)
                         ->where('is_deleted', 0)
                         ->where('assessment_type', $assessment_type)
                         ->where('grade_level', $grade)
                         ->where('sex', $gender)
                         ->where('height_for_age', $hfa_status)
                         ->count_all_results();
        
        return $count;
    }
    
    /**
     * SIMPLIFIED: Get total enrolment for district
     */
    public function get_district_grand_total($district_name, $assessment_type = 'baseline') {
        // Get all schools in the district
        $schools = $this->get_district_schools_data($district_name);
        
        if (empty($schools)) {
            return 0;
        }
        
        // Get school names for filtering
        $school_names = array_column($schools, 'name');
        
        $query = $this->db->select('COUNT(*) as enrolment')
                         ->from('nutritional_assessments')
                         ->where_in('school_name', $school_names)
                         ->where('school_district', $district_name)
                         ->where('is_deleted', 0)
                         ->where('assessment_type', $assessment_type)
                         ->get();
        
        return $query->row()->enrolment ?? 0;
    }
    
    /**
     * SIMPLIFIED: Get assessment counts
     */
    public function get_assessment_counts($district_name) {
        // Get all schools in the district
        $schools = $this->get_district_schools_data($district_name);
        
        if (empty($schools)) {
            return ['baseline' => 0, 'endline' => 0];
        }
        
        // Get school names for filtering
        $school_names = array_column($schools, 'name');
        
        $baseline = $this->db->from('nutritional_assessments')
                            ->where_in('school_name', $school_names)
                            ->where('school_district', $district_name)
                            ->where('is_deleted', 0)
                            ->where('assessment_type', 'baseline')
                            ->count_all_results();
        
        $endline = $this->db->from('nutritional_assessments')
                           ->where_in('school_name', $school_names)
                           ->where('school_district', $district_name)
                           ->where('is_deleted', 0)
                           ->where('assessment_type', 'endline')
                           ->count_all_results();
        
        return [
            'baseline' => $baseline,
            'endline' => $endline
        ];
    }
    
    /**
     * SIMPLIFIED: Get user schools (for district view)
     */
    public function get_user_schools($user_id, $user_type, $user_district) {
        return $this->get_district_schools($user_district);
    }
    
    /**
     * SIMPLIFIED: Get schools for UI display
     */
    public function get_district_schools($district_name) {
        $schools = $this->get_district_schools_data($district_name);
        
        $result = array();
        foreach ($schools as $school) {
            // Check if school has assessments by name
            $has_submitted = $this->db->from('nutritional_assessments')
                                     ->where('school_name', $school['name'])
                                     ->where('school_district', $district_name)
                                     ->where('is_deleted', 0)
                                     ->count_all_results() > 0;
            
            $result[] = array(
                'name' => $school['name'],
                'code' => $school['code'],
                'has_submitted' => $has_submitted
            );
        }
        
        return $result;
    }
    
    /**
     * SIMPLIFIED: Get district summary
     */
    public function get_district_reports_summary($district_name) {
        // Get all schools in district
        $schools = $this->get_district_schools_data($district_name);
        $total_schools = count($schools);
        
        $submitted_schools = 0;
        foreach ($schools as $school) {
            // Check if school has any assessments
            $has_assessments = $this->db->from('nutritional_assessments')
                                       ->where('school_name', $school['name'])
                                       ->where('school_district', $district_name)
                                       ->where('is_deleted', 0)
                                       ->count_all_results() > 0;
            
            if ($has_assessments) {
                $submitted_schools++;
            }
        }
        
        return array(
            $district_name => array(
                'total' => $total_schools,
                'submitted' => $submitted_schools
            )
        );
    }
    
    /**
     * SIMPLIFIED: Get school details
     */
    public function get_school_details($school_name) {
        $query = $this->db->select('name, school_id, address, school_level, school_head_name, email')
                         ->from('schools')
                         ->where('name', $school_name)
                         ->limit(1)
                         ->get();
        
        return $query->num_rows() > 0 ? $query->row_array() : null;
    }
    
    /**
     * SIMPLIFIED: Get district submission statistics
     */
    public function get_district_submission_stats($district_name) {
        // Get all schools in district
        $schools = $this->get_district_schools_data($district_name);
        $total_schools = count($schools);
        
        $submitted_schools = 0;
        $total_assessments = 0;
        
        foreach ($schools as $school) {
            // Check if school has any assessments
            $has_assessments = $this->db->from('nutritional_assessments')
                                       ->where('school_name', $school['name'])
                                       ->where('school_district', $district_name)
                                       ->where('is_deleted', 0)
                                       ->count_all_results() > 0;
            
            if ($has_assessments) {
                $submitted_schools++;
                
                // Count assessments for this school
                $school_assessments = $this->db->from('nutritional_assessments')
                                              ->where('school_name', $school['name'])
                                              ->where('school_district', $district_name)
                                              ->where('is_deleted', 0)
                                              ->count_all_results();
                
                $total_assessments += $school_assessments;
            }
        }
        
        $submission_rate = $total_schools > 0 ? round(($submitted_schools / $total_schools) * 100) : 0;
        
        return array(
            'total_schools' => $total_schools,
            'submitted_schools' => $submitted_schools,
            'submission_rate' => $submission_rate,
            'total_assessments' => $total_assessments
        );
    }
    
    /**
     * SIMPLIFIED: Debug data consistency
     */
    public function debug_data_consistency($district_name) {
        $debug_info = array();
        
        $district_id = $this->get_district_id_by_name($district_name);
        $debug_info['district_id'] = $district_id;
        $debug_info['district_name_input'] = $district_name;
        
        if ($district_id) {
            // Get district info
            $district_info = $this->db->select('*')
                                     ->from('school_districts')
                                     ->where('id', $district_id)
                                     ->get()
                                     ->row();
            $debug_info['district_info'] = $district_info;
            
            // Get schools in district
            $schools = $this->db->select('id, name, school_id as code')
                               ->from('schools')
                               ->where('school_district_id', $district_id)
                               ->get()
                               ->result_array();
            $debug_info['schools_in_district'] = $schools;
            $debug_info['schools_count'] = count($schools);
            
            // Check assessments for these schools
            $schools_with_assessments = array();
            foreach ($schools as $school) {
                $count = $this->db->from('nutritional_assessments')
                                 ->where('school_name', $school['name'])
                                 ->where('school_district', $district_name)
                                 ->where('is_deleted', 0)
                                 ->count_all_results();
                
                if ($count > 0) {
                    $schools_with_assessments[] = array(
                        'school_id' => $school['id'],
                        'school_name' => $school['name'],
                        'assessment_count' => $count
                    );
                }
            }
            
            $debug_info['schools_with_assessments'] = $schools_with_assessments;
            $debug_info['schools_with_assessments_count'] = count($schools_with_assessments);
            
            // Sample assessments
            if (!empty($schools)) {
                $school_names = array_column($schools, 'name');
                $debug_info['sample_assessments'] = $this->db->select('school_name, school_district, grade_level, assessment_type')
                                                             ->from('nutritional_assessments')
                                                             ->where_in('school_name', $school_names)
                                                             ->where('school_district', $district_name)
                                                             ->where('is_deleted', 0)
                                                             ->limit(5)
                                                             ->get()
                                                             ->result_array();
            }
        }
        
        return $debug_info;
    }
}