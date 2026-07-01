<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class division_dashboard_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Build division nutritional data using a single aggregated query.
     * The dashboard displays the full BMI and HFA breakdown for all assessed students.
     */
    public function get_division_nutritional_data($assessment_type = 'baseline', $school_level = 'all', $legislative_district_id = null) {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return [];
        }

        $grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'SPED',
                   'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        if ($school_level === 'shs_only') {
            $grades = ['Grade 11', 'Grade 12'];
        }

        $data = [];
        foreach ($grades as $grade) {
            $data[$grade . '_m'] = $this->create_empty_grade_data();
            $data[$grade . '_f'] = $this->create_empty_grade_data();
            $data[$grade . '_total'] = $this->create_empty_grade_data();
        }

        $this->db->select("CASE
                WHEN LOWER(TRIM(n.grade_level)) IN ('kindergarten', 'kinder') THEN 'Kinder'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 1' THEN 'Grade 1'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 2' THEN 'Grade 2'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 3' THEN 'Grade 3'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 4' THEN 'Grade 4'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 5' THEN 'Grade 5'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 6' THEN 'Grade 6'
                WHEN LOWER(TRIM(n.grade_level)) = 'sped' THEN 'SPED'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 7' THEN 'Grade 7'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 8' THEN 'Grade 8'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 9' THEN 'Grade 9'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 10' THEN 'Grade 10'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 11' THEN 'Grade 11'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 12' THEN 'Grade 12'
                ELSE NULL
            END AS grade_label,
            CASE
                WHEN UPPER(TRIM(n.sex)) = 'M' THEN 'm'
                WHEN UPPER(TRIM(n.sex)) = 'F' THEN 'f'
                ELSE NULL
            END AS sex_key,
            COUNT(*) AS enrolment,
            SUM(CASE WHEN n.weight IS NOT NULL AND n.weight <> '' AND n.weight > 0 THEN 1 ELSE 0 END) AS pupils_weighed,
            SUM(CASE WHEN n.height IS NOT NULL AND n.height <> '' AND n.height > 0 THEN 1 ELSE 0 END) AS pupils_height,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'severely wasted' THEN 1 ELSE 0 END) AS severely_wasted,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'wasted' THEN 1 ELSE 0 END) AS wasted,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'normal' THEN 1 ELSE 0 END) AS normal_bmi,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'overweight' THEN 1 ELSE 0 END) AS overweight,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'obese' THEN 1 ELSE 0 END) AS obese,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) = 'severely stunted' THEN 1 ELSE 0 END) AS severely_stunted,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) = 'stunted' THEN 1 ELSE 0 END) AS stunted,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) = 'normal' THEN 1 ELSE 0 END) AS normal_hfa,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) IN ('tall', 'above normal') THEN 1 ELSE 0 END) AS tall")
                 ->from('nutritional_assessments n')
                 ->join('schools s', 'n.school_id = s.school_id', 'left')
                 ->join('school_districts sd', 's.school_district_id = sd.id', 'left')
                 ->where('n.is_deleted', 0)
                 ->where('n.assessment_type', $assessment_type);

        if ($legislative_district_id) {
            $this->db->where('sd.legislative_district_id', $legislative_district_id);
        }

        $this->apply_school_level_filter($school_level);

        if ($school_level === 'shs_only') {
            $this->db->where_in('n.grade_level', ['Grade 11', 'Grade 12']);
        }

        $this->db->group_by('grade_label, sex_key');

        $query = $this->db->get();
        $rows = $query->result();

        foreach ($rows as $row) {
            $grade = isset($row->grade_label) ? trim($row->grade_label) : null;
            if (!$grade) {
                continue;
            }

            $sexKey = isset($row->sex_key) ? trim($row->sex_key) : '';
            if ($sexKey === 'm') {
                $genderKey = '_m';
            } elseif ($sexKey === 'f') {
                $genderKey = '_f';
            } else {
                continue;
            }

            $gradeKey = $grade . $genderKey;

            if (!isset($data[$gradeKey])) {
                continue;
            }

            $data[$gradeKey]['enrolment'] = (int)($row->enrolment ?? 0);
            $data[$gradeKey]['pupils_weighed'] = (int)($row->pupils_weighed ?? 0);
            $data[$gradeKey]['pupils_height'] = (int)($row->pupils_height ?? 0);
            $data[$gradeKey]['severely_wasted'] = (int)($row->severely_wasted ?? 0);
            $data[$gradeKey]['wasted'] = (int)($row->wasted ?? 0);
            $data[$gradeKey]['normal_bmi'] = (int)($row->normal_bmi ?? 0);
            $data[$gradeKey]['overweight'] = (int)($row->overweight ?? 0);
            $data[$gradeKey]['obese'] = (int)($row->obese ?? 0);
            $data[$gradeKey]['severely_stunted'] = (int)($row->severely_stunted ?? 0);
            $data[$gradeKey]['stunted'] = (int)($row->stunted ?? 0);
            $data[$gradeKey]['normal_hfa'] = (int)($row->normal_hfa ?? 0);
            $data[$gradeKey]['tall'] = (int)($row->tall ?? 0);
        }

        $numericFields = [
            'enrolment',
            'pupils_weighed',
            'pupils_height',
            'severely_wasted',
            'wasted',
            'normal_bmi',
            'overweight',
            'obese',
            'severely_stunted',
            'stunted',
            'normal_hfa',
            'tall'
        ];

        foreach ($grades as $grade) {
            $maleKey = $grade . '_m';
            $femaleKey = $grade . '_f';
            $totalKey = $grade . '_total';

            if (!isset($data[$maleKey], $data[$femaleKey], $data[$totalKey])) {
                continue;
            }

            foreach ($numericFields as $field) {
                $data[$totalKey][$field] = (int)($data[$maleKey][$field] ?? 0) + (int)($data[$femaleKey][$field] ?? 0);
            }
        }

        return $data;
    }

    /**
     * Create an empty grade data structure.
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
        elseif ($school_level === 'shs_only') {
        $this->db->where("(
            school_name LIKE '%Senior High%' OR 
            school_name LIKE '%SHS%' OR
            school_name NOT LIKE '%Elementary%' AND school_name NOT LIKE '%High School%' AND school_name NOT LIKE '%NHS%'
        )");
        }

        elseif ($school_level === 'integrated') {
            $this->db->where("school_name LIKE '%Integrated%'");
        }
        elseif ($school_level === 'integrated_elementary') {
            $this->db->where("school_name LIKE '%Integrated%'");
            if ($grade) {
                $elementary_grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'SPED'];
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
        elseif ($school_level === 'shs_only') {
            if ($grade) {
                $this->db->where_in('grade_level', ['Grade 11', 'Grade 12']);
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
    public function get_division_grand_total($assessment_type = 'baseline', $school_level = 'all', $legislative_district_id = null) {
        $this->db->select('COUNT(*) as total')
                 ->from('nutritional_assessments n')
                 ->join('schools s', 'n.school_id = s.school_id', 'left')
                 ->join('school_districts sd', 's.school_district_id = sd.id', 'left')
                 ->where('n.is_deleted', 0)
                 ->where('n.assessment_type', $assessment_type);
        if ($legislative_district_id) {
            $this->db->where('sd.legislative_district_id', $legislative_district_id);
        }
        $this->apply_school_level_filter($school_level);
        if ($school_level === 'shs_only') {
            $this->db->where_in('n.grade_level', ['Grade 11', 'Grade 12']);
        }
        $q = $this->db->get();
        return (int)($q->row()->total ?? 0);
    }
    
    /**
     * ENHANCED: Get assessment counts for division with school level filter
     */
    public function get_assessment_counts_division($school_level = 'all', $legislative_district_id = null) {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return ['baseline' => 0, 'midline' => 0, 'endline' => 0];
        }

        $types = ['baseline', 'midline', 'endline'];
        $counts = [];
        foreach ($types as $type) {
            $this->db->select('COUNT(DISTINCT n.school_id) as count')
                     ->from('nutritional_assessments n')
                     ->join('schools s', 'n.school_id = s.school_id', 'left')
                     ->join('school_districts sd', 's.school_district_id = sd.id', 'left')
                     ->where('n.is_deleted', 0)
                     ->where('n.assessment_type', $type);
            if ($legislative_district_id) {
                $this->db->where('sd.legislative_district_id', $legislative_district_id);
            }
            $this->apply_school_level_filter($school_level);
            if ($school_level === 'shs_only') {
                $this->db->where_in('n.grade_level', ['Grade 11', 'Grade 12']);
            }
            $q = $this->db->get();
            $counts[$type] = (int)($q->row()->count ?? 0);
        }
        return $counts;
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
    public function get_all_districts($legislative_district_id = null) {
        $this->db->select('id, name')
                 ->from('school_districts')
                 ->order_by('name');
        if ($legislative_district_id) {
            $this->db->where('legislative_district_id', $legislative_district_id);
        }
        $query = $this->db->get();
        return $query->result_array();
    }

    // New method: get legislative districts for dropdown
    public function get_legislative_districts() {
        return $this->db->select('id, name')
                        ->from('legislative_districts')
                        ->order_by('name')
                        ->get()
                        ->result();
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