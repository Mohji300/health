<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritional_model extends CI_Model {

    protected $table = 'nutritional_assessments';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Return processed nutritional data structured for the table.
     * Now accepts assessment_type parameter
     */
    public function get_processed_data($assessment_type = null, $school_name = null)
    {
        // Build query with optional assessment_type filter
        if ($assessment_type) {
            $this->db->where('assessment_type', $assessment_type);
        }

        // Optional school filter (filter by school_name in nutritional_assessments)
        if ($school_name) {
            $this->db->where('school_name', $school_name);
        }

        $assessments = $this->db->get($this->table)->result();

        $allGrades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                      'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        // Initialize data structure
        $data = [];
        foreach ($allGrades as $grade) {
            $data[$grade . '_m'] = $this->create_empty_grade_data();
            $data[$grade . '_f'] = $this->create_empty_grade_data();
            $data[$grade . '_total'] = $this->create_empty_grade_data();
        }

        // Simple grade mapping (adjust if your DB has different grade names)
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

            // BMI categories (string matching from DB)
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

        // Calculate grand total from all enrolment counts (total rows)
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
     * Get assessment count by type
     */
    public function get_assessment_count_by_type($assessment_type)
    {
        $this->db->where('assessment_type', $assessment_type);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Check if assessment type has data
     */
    public function has_assessment_data($assessment_type)
    {
        $this->db->where('assessment_type', $assessment_type);
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Get all nutritional assessments
     */
    public function get_all_assessments()
    {
        return $this->db->get($this->table)->result();
    }

    /**
     * Get assessments by grade and gender
     */
    public function get_by_grade_gender($grade, $gender = null)
    {
        $this->db->where('grade_level', $grade);
        if ($gender) {
            $this->db->where('sex', $gender);
        }
        return $this->db->get($this->table)->result();
    }

    /**
     * Get nutritional status summary
     */
    public function get_nutritional_summary()
    {
        $this->db->select('nutritional_status, COUNT(*) as count');
        $this->db->group_by('nutritional_status');
        return $this->db->get($this->table)->result();
    }
}