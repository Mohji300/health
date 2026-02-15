<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class District_dashboard_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get district ID by name
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
     * Get school names in a district
     */
    private function get_district_school_names($district_name) {
        $district_id = $this->get_district_id_by_name($district_name);
        if (!$district_id) {
            return array();
        }
        
        $query = $this->db->select('name')
                         ->from('schools')
                         ->where('school_district_id', $district_id)
                         ->get();
        
        $schools = $query->result_array();
        return array_column($schools, 'name');
    }
    
    /**
     * COMPLETELY REBUILT: Get processed nutritional data for district
     * Now uses the efficient approach from Nutritional_model with school level filtering
     */
    public function get_district_nutritional_data($district_name, $assessment_type = 'baseline', $school_level = 'all') {
        // Get all school names in the district
        $school_names = $this->get_district_school_names($district_name);
        
        if (empty($school_names)) {
            return [
                'nutritionalData' => [],
                'grandTotal' => 0,
                'has_data' => false,
                'processed_count' => 0
            ];
        }
        
        // Build query with filters
        $this->db->where_in('school_name', $school_names);
        $this->db->where('school_district', $district_name);
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        
        // NEW: Apply school level filtering (same logic as Nutritional_model)
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                // For secondary, check for High School indicators
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
                // For elementary, exclude secondary/integrated indicators
                $this->db->where("(
                    school_name NOT LIKE '%High%' AND 
                    school_name NOT LIKE '%Secondary%' AND
                    school_name NOT LIKE '%Integrated%' AND
                    school_name NOT LIKE '%NHS%' AND
                    school_name NOT LIKE '%HighSchool%'
                )");
            }
            elseif ($school_level === 'integrated') {
                // All grades from integrated schools
                $this->db->where("school_name LIKE '%Integrated%'");
            }
            elseif ($school_level === 'integrated_elementary') {
                // Only elementary grades (K-6) from integrated schools
                $this->db->where("school_name LIKE '%Integrated%'");
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            }
            elseif ($school_level === 'integrated_secondary') {
                // Only secondary grades (7-12) from integrated schools
                $this->db->where("school_name LIKE '%Integrated%'");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            }
        }
        
        $assessments = $this->db->get('nutritional_assessments')->result();
        
        // Define all grade levels
        $allGrades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                    'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        
        // Initialize data structure
        $data = [];
        foreach ($allGrades as $grade) {
            $data[$grade . '_m'] = $this->create_empty_grade_data();
            $data[$grade . '_f'] = $this->create_empty_grade_data();
            $data[$grade . '_total'] = $this->create_empty_grade_data();
        }
        
        // Grade mapping
        $gradeMapping = [
            'Kindergarten' => 'Kinder',
            'Kinder' => 'Kinder',
            'Grade 1' => 'Grade 1',
            'Grade 2' => 'Grade 2',
            'Grade 3' => 'Grade 3',
            'Grade 4' => 'Grade 4',
            'Grade 5' => 'Grade 5',
            'Grade 6' => 'Grade 6',
            'Grade 7' => 'Grade 7',
            'Grade 8' => 'Grade 8',
            'Grade 9' => 'Grade 9',
            'Grade 10' => 'Grade 10',
            'Grade 11' => 'Grade 11',
            'Grade 12' => 'Grade 12',
        ];
        
        $processedCount = 0;
        
        foreach ($assessments as $item) {
            // Extra safety check for deleted records
            if (isset($item->is_deleted) && $item->is_deleted == 1) {
                continue;
            }
            
            $originalGrade = isset($item->grade_level) ? trim($item->grade_level) : null;
            $grade = isset($gradeMapping[$originalGrade]) ? $gradeMapping[$originalGrade] : null;
            if (!$grade) continue;
            
            $gender = isset($item->sex) ? strtoupper(trim($item->sex)) : '';
            if ($gender === 'M') {
                $genderKey = '_m';
            } elseif ($gender === 'F') {
                $genderKey = '_f';
            } else {
                continue;
            }
            
            $gradeKey = $grade . $genderKey;
            $totalKey = $grade . '_total';
            
            if (!isset($data[$gradeKey]) || !isset($data[$totalKey])) continue;
            
            // Increment enrolment
            $data[$gradeKey]['enrolment'] += 1;
            $data[$totalKey]['enrolment'] += 1;
            
            // Pupils weighed
            if (isset($item->weight) && is_numeric($item->weight) && floatval($item->weight) > 0) {
                $data[$gradeKey]['pupils_weighed'] += 1;
                $data[$totalKey]['pupils_weighed'] += 1;
            }
            
            // Pupils height counted
            if (isset($item->height) && is_numeric($item->height) && floatval($item->height) > 0) {
                $data[$gradeKey]['pupils_height'] += 1;
                $data[$totalKey]['pupils_height'] += 1;
            }
            
            // BMI categories
            $bmiStatus = isset($item->nutritional_status) ? strtolower(trim($item->nutritional_status)) : '';
            if ($bmiStatus === 'severely wasted') {
                $data[$gradeKey]['severely_wasted'] += 1;
                $data[$totalKey]['severely_wasted'] += 1;
            } elseif ($bmiStatus === 'wasted') {
                $data[$gradeKey]['wasted'] += 1;
                $data[$totalKey]['wasted'] += 1;
            } elseif ($bmiStatus === 'normal') {
                $data[$gradeKey]['normal_bmi'] += 1;
                $data[$totalKey]['normal_bmi'] += 1;
            } elseif ($bmiStatus === 'overweight') {
                $data[$gradeKey]['overweight'] += 1;
                $data[$totalKey]['overweight'] += 1;
            } elseif ($bmiStatus === 'obese') {
                $data[$gradeKey]['obese'] += 1;
                $data[$totalKey]['obese'] += 1;
            }
            
            // Height-for-age
            $hfaStatus = isset($item->height_for_age) ? strtolower(trim($item->height_for_age)) : '';
            if ($hfaStatus === 'severely stunted') {
                $data[$gradeKey]['severely_stunted'] += 1;
                $data[$totalKey]['severely_stunted'] += 1;
            } elseif ($hfaStatus === 'stunted') {
                $data[$gradeKey]['stunted'] += 1;
                $data[$totalKey]['stunted'] += 1;
            } elseif ($hfaStatus === 'normal') {
                $data[$gradeKey]['normal_hfa'] += 1;
                $data[$totalKey]['normal_hfa'] += 1;
            } elseif ($hfaStatus === 'tall' || $hfaStatus === 'above normal') {
                $data[$gradeKey]['tall'] += 1;
                $data[$totalKey]['tall'] += 1;
            }
            
            $processedCount++;
        }
        
        // Calculate grand total
        $grandTotal = 0;
        foreach ($data as $key => $vals) {
            if (substr($key, -6) === '_total') {
                $grandTotal += $vals['enrolment'];
            }
        }
        
        return [
            'nutritionalData' => $data,
            'grandTotal' => $grandTotal,
            'has_data' => $processedCount > 0,
            'processed_count' => $processedCount
        ];
    }
    
    /**
     * Create empty grade data structure
     */
    private function create_empty_grade_data() {
        return [
            'enrolment' => 0,
            'pupils_weighed' => 0,
            'severely_wasted' => 0,
            'wasted' => 0,
            'normal_bmi' => 0,
            'overweight' => 0,
            'obese' => 0,
            'severely_stunted' => 0,
            'stunted' => 0,
            'normal_hfa' => 0,
            'tall' => 0,
            'pupils_height' => 0,
        ];
    }
    
    /**
     * UPDATED: Get assessment counts with school level filtering
     */
    public function get_assessment_counts($district_name, $school_level = 'all') {
        $school_names = $this->get_district_school_names($district_name);
        
        if (empty($school_names)) {
            return ['baseline' => 0, 'midline' => 0, 'endline' => 0];
        }
        
        $this->db->where_in('school_name', $school_names);
        $this->db->where('school_district', $district_name);
        $this->db->where('is_deleted', 0);
        
        // Apply school level filtering
        if ($school_level !== 'all') {
            $this->apply_school_level_filter($school_level);
        }
        
        $baseline = $this->db->where('assessment_type', 'baseline')->count_all_results('nutritional_assessments');
        
        // Reset for midline
        $this->db->reset_query();
        $this->db->where_in('school_name', $school_names);
        $this->db->where('school_district', $district_name);
        $this->db->where('is_deleted', 0);
        if ($school_level !== 'all') {
            $this->apply_school_level_filter($school_level);
        }
        $midline = $this->db->where('assessment_type', 'midline')->count_all_results('nutritional_assessments');
        
        // Reset for endline
        $this->db->reset_query();
        $this->db->where_in('school_name', $school_names);
        $this->db->where('school_district', $district_name);
        $this->db->where('is_deleted', 0);
        if ($school_level !== 'all') {
            $this->apply_school_level_filter($school_level);
        }
        $endline = $this->db->where('assessment_type', 'endline')->count_all_results('nutritional_assessments');
        
        return [
            'baseline' => $baseline,
            'midline' => $midline,
            'endline' => $endline
        ];
    }
    
    /**
     * Apply school level filter to query
     */
    private function apply_school_level_filter($school_level) {
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
            $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
        }
        elseif ($school_level === 'integrated_secondary') {
            $this->db->where("school_name LIKE '%Integrated%'");
            $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
        }
    }
    
    /**
     * UPDATED: Get district grand total with school level filtering
     */
    public function get_district_grand_total($district_name, $assessment_type = 'baseline', $school_level = 'all') {
        $result = $this->get_district_nutritional_data($district_name, $assessment_type, $school_level);
        return $result['grandTotal'];
    }
    
    /**
     * Get schools for UI display
     */
    public function get_district_schools($district_name) {
        $district_id = $this->get_district_id_by_name($district_name);
        if (!$district_id) {
            return array();
        }
        
        $query = $this->db->select('id, name, school_id as code')
                         ->from('schools')
                         ->where('school_district_id', $district_id)
                         ->get();
        
        $schools = $query->result_array();
        
        $result = array();
        foreach ($schools as $school) {
            // Check if school has any assessments
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
     * Get district reports summary
     */
    public function get_district_reports_summary($district_name) {
        $schools = $this->get_district_schools($district_name);
        $total_schools = count($schools);
        
        $submitted_schools = 0;
        foreach ($schools as $school) {
            if ($school['has_submitted']) {
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
     * Get school details
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
     * Get district submission statistics
     */
    public function get_district_submission_stats($district_name) {
        $schools = $this->get_district_schools($district_name);
        $total_schools = count($schools);
        
        $submitted_schools = 0;
        $total_assessments = 0;
        
        foreach ($schools as $school) {
            if ($school['has_submitted']) {
                $submitted_schools++;
                
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
}