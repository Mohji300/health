<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class nutritional_model extends CI_Model {

    protected $table = 'nutritional_assessments';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Return processed nutritional data structured for the table.
     *accepts assessment_type, school_name, school_level, and school_id parameters
     */
    public function get_processed_data($assessment_type = null, $school_name = null, $school_level = 'all', $school_id = null)
    {
        // Build query with optional assessment_type filter
        if ($assessment_type) {
            $this->db->where('assessment_type', $assessment_type);
        }

        // Filter out deleted records
        $this->db->where('is_deleted', 0);

        //  Filter by school_id if provided
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }

        // Secondary school filter (filter by school_name)
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }

        // Add school level filtering using the actual school_level from users table
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'secondary')");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            } 
            elseif ($school_level === 'elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'elementary')");
                $this->db->where_in('grade_level', ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'SPED']);
            }
            elseif ($school_level === 'integrated') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
            }
            elseif ($school_level === 'integrated_elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
                $this->db->where_in('grade_level', ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'SPED']);
            }
            elseif ($school_level === 'integrated_secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            }
            elseif ($school_level === 'Stand Alone SHS') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE school_level = 'Stand Alone SHS' OR LOWER(school_level) IN ('shs', 'senior high school', 'senior high'))");
                $this->db->where_in('grade_level', ['Grade 11', 'Grade 12']);
            }
        }

        $assessments = $this->db->get($this->table)->result();

        $allGrades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6','SPED',
                    'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        // Initialize data structure
        $data = [];
        foreach ($allGrades as $grade) {
            $data[$grade . '_m'] = $this->create_empty_grade_data();
            $data[$grade . '_f'] = $this->create_empty_grade_data();
            $data[$grade . '_total'] = $this->create_empty_grade_data();
        }

        // Simple grade mapping
        $gradeMapping = [
            'Kindergarten' => 'Kinder',
            'Kinder' => 'Kinder',
            'Grade 1' => 'Grade 1',
            'Grade 2' => 'Grade 2',
            'Grade 3' => 'Grade 3',
            'Grade 4' => 'Grade 4',
            'Grade 5' => 'Grade 5',
            'Grade 6' => 'Grade 6',
            'SPED' => 'SPED',
            'Grade 7' => 'Grade 7',
            'Grade 8' => 'Grade 8',
            'Grade 9' => 'Grade 9',
            'Grade 10' => 'Grade 10',
            'Grade 11' => 'Grade 11',
            'Grade 12' => 'Grade 12',
        ];

        $processedCount = 0;

        foreach ($assessments as $item) {
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

            $data[$gradeKey]['enrolment'] += 1;
            $data[$totalKey]['enrolment'] += 1;

            if (isset($item->weight) && is_numeric($item->weight) && floatval($item->weight) > 0) {
                $data[$gradeKey]['pupils_weighed'] += 1;
                $data[$totalKey]['pupils_weighed'] += 1;
            }

            if (isset($item->height) && is_numeric($item->height) && floatval($item->height) > 0) {
                $data[$gradeKey]['pupils_height'] += 1;
                $data[$totalKey]['pupils_height'] += 1;
            }

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

    private function create_empty_grade_data()
    {
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
     * Get assessment count by type with optional filtering
     */
    public function get_assessment_count_by_type($type, $school_name = null, $school_level = 'all', $school_id = null)
    {
        $this->db->where('assessment_type', $type);
        $this->db->where('is_deleted', 0);
        
        // Filter by school_id if provided
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        
        // Secondary school filter
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }
        
        // Add school level filtering
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'secondary')");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            } 
            elseif ($school_level === 'elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'elementary')");
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            }
            elseif ($school_level === 'integrated') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
            }
            elseif ($school_level === 'integrated_elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            }
            elseif ($school_level === 'integrated_secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            }
            elseif ($school_level === 'Stand Alone SHS') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE school_level = 'Stand Alone SHS' OR LOWER(school_level) IN ('shs', 'senior high school', 'senior high'))");
                $this->db->where_in('grade_level', ['Grade 11', 'Grade 12']);
            }
        }
        
        return $this->db->count_all_results('nutritional_assessments');
    }

    /**
     * Check if assessment type has data with optional filtering
     */
    public function has_assessment_data($type, $school_name = null, $school_level = 'all', $school_id = null)
    {
        $this->db->where('assessment_type', $type);
        $this->db->where('is_deleted', 0);
        
        // Filter by school_id if provided
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        
        // Secondary school filter
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }
        
        // Add school level filtering
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'secondary')");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            } 
            elseif ($school_level === 'elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'elementary')");
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            }
            elseif ($school_level === 'integrated') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
            }
            elseif ($school_level === 'integrated_elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
                $this->db->where_in('grade_level', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            }
            elseif ($school_level === 'integrated_secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
                $this->db->where_in('grade_level', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            }
            elseif ($school_level === 'Stand Alone SHS') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE school_level = 'Stand Alone SHS' OR LOWER(school_level) IN ('shs', 'senior high school', 'senior high'))");
                $this->db->where_in('grade_level', ['Grade 11', 'Grade 12']);
            }
        }
        
        $count = $this->db->count_all_results('nutritional_assessments');
        return $count > 0;
    }

    /**
     * Get all nutritional assessments with optional filtering
     */
    public function get_all_assessments($school_name = null, $school_level = 'all', $school_id = null)
    {
        //  Filter by school_id if provided
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        
        // Secondary school filter
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }
        
        // Add school level filtering
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'secondary')");
            } 
            elseif ($school_level === 'elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'elementary')");
            }
            elseif ($school_level === 'integrated') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
            }
            elseif ($school_level === 'Stand Alone SHS') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE school_level = 'Stand Alone SHS' OR LOWER(school_level) IN ('shs', 'senior high school', 'senior high'))");
            }
        }
        
        return $this->db->get($this->table)->result();
    }

    /**
     * Get assessments by grade and gender with optional filtering
     */
    public function get_by_grade_gender($grade, $gender = null, $school_name = null, $school_level = 'all', $school_id = null)
    {
        $this->db->where('grade_level', $grade);
        if ($gender) {
            $this->db->where('sex', $gender);
        }
        
        // Filter by school_id if provided
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        
        // Secondary school filter
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }
        
        // Add school level filtering
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'secondary')");
            } 
            elseif ($school_level === 'elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'elementary')");
            }
            elseif ($school_level === 'integrated') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
            }
            elseif ($school_level === 'Stand Alone SHS') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE school_level = 'Stand Alone SHS' OR LOWER(school_level) IN ('shs', 'senior high school', 'senior high'))");
            }
        }
        
        return $this->db->get($this->table)->result();
    }

    /**
     * Get nutritional status summary with optional filtering
     */
    public function get_nutritional_summary($school_name = null, $school_level = 'all', $school_id = null)
    {
        $this->db->select('nutritional_status, COUNT(*) as count');
        
        //  PRIMARY FILTER: Filter by school_id if provided
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        
        // Secondary school filter
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }
        
        // Add school level filtering
        if ($school_level !== 'all') {
            if ($school_level === 'secondary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'secondary')");
            } 
            elseif ($school_level === 'elementary') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'elementary')");
            }
            elseif ($school_level === 'integrated') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE LOWER(school_level) = 'integrated')");
            }
            elseif ($school_level === 'Stand Alone SHS') {
                $this->db->where("school_name IN (SELECT school_name FROM users WHERE school_level = 'Stand Alone SHS' OR LOWER(school_level) IN ('shs', 'senior high school', 'senior high'))");
            }
        }
        
        $this->db->group_by('nutritional_status');
        return $this->db->get($this->table)->result();
    }
}