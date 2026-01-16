<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritional_assessment_model extends CI_Model {

    protected $table = 'nutritional_assessments';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get submitted assessments summary
     */
    public function get_submitted_summary($legislative_district, $school_district)
    {
        $this->db->select('grade_level as grade, section, assessment_type, COUNT(*) as total_students, MAX(date_of_weighing) as last_updated');
        $this->db->from('nutritional_assessments');
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('is_deleted', FALSE); // Only show non-deleted
        $this->db->group_by(['grade_level', 'section', 'assessment_type']);
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        $this->db->order_by('assessment_type', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Delete assessment (soft delete)
     */
    public function delete_assessment($legislative_district, $school_district, $grade, $section, $assessment_type = null)
    {
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('grade_level', $grade);
        $this->db->where('section', $section);
        $this->db->where('is_deleted', FALSE);
        
        if ($assessment_type && $assessment_type != 'both') {
            $this->db->where('assessment_type', $assessment_type);
        }
        
        $data = [
            'is_deleted' => TRUE,
            'deleted_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('nutritional_assessments', $data);
    }

    /**
     * Get assessment types for a section
     */
    public function get_assessment_types($legislative_district, $school_district, $grade, $section)
    {
        $this->db->select('assessment_type, COUNT(*) as count, MAX(date_of_weighing) as last_updated');
        $this->db->from('nutritional_assessments');
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('grade_level', $grade);
        $this->db->where('section', $section);
        $this->db->where('is_deleted', FALSE);
        $this->db->group_by('assessment_type');
        
        return $this->db->get()->result();
    }

    /**
     * Check if assessment exists for a section
     */
    public function assessment_exists($legislative_district, $school_district, $grade, $section, $assessment_type = 'baseline')
    {
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('grade_level', $grade);
        $this->db->where('section', $section);
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', FALSE);
        
        return $this->db->count_all_results('nutritional_assessments') > 0;
    }

    /**
     * Get assessments by section
     */
    public function get_by_section($legislative_district, $school_district, $grade_level, $section, $assessment_type = null)
    {
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('grade_level', $grade_level);
        $this->db->where('section', $section);
        $this->db->where('is_deleted', FALSE);
        
        if ($assessment_type) {
            $this->db->where('assessment_type', $assessment_type);
        }
        
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Create a new assessment
     */
    public function create($data)
    {
        return $this->db->insert('nutritional_assessments', $data);
    }

    /**
     * Get all assessments
     */
    public function get_all()
    {
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Get processed data for dashboard (aggregated counts)
     */
    public function get_processed_data()
    {
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

        return ['nutritionalData' => $data, 'grandTotal' => $grandTotal];
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
 * Get reports with filters for Nutritional Reports page
 */
public function get_reports_with_filters($legislative_district = null, $school_district = null, $school_name = null, $grade_level = null, $date_from = null, $date_to = null, $assessment_type = null)
{
    $this->db->select('
        school_id,
        school_name, 
        legislative_district, 
        school_district, 
        grade_level, 
        section,
        assessment_type,
        COUNT(*) as student_count, 
        MIN(created_at) as first_submission, 
        MAX(created_at) as last_submission
    ');
    $this->db->from($this->table);
    $this->db->where('is_deleted', FALSE);
    $this->db->group_by('school_id, school_name, legislative_district, school_district, grade_level, section, assessment_type');

    // Apply filters
    if (!empty($legislative_district)) {
        $this->db->where('legislative_district', $legislative_district);
    }
    if (!empty($school_district)) {
        $this->db->where('school_district', $school_district);
    }
    if (!empty($school_name)) {
        $this->db->where('school_name', $school_name);
    }
    if (!empty($grade_level)) {
        $this->db->where('grade_level', $grade_level);
    }
    if (!empty($date_from)) {
        $this->db->where('DATE(created_at) >=', $date_from);
    }
    if (!empty($date_to)) {
        $this->db->where('DATE(created_at) <=', $date_to);
    }
    if (!empty($assessment_type)) {
        $this->db->where('assessment_type', $assessment_type);
    }

    $this->db->order_by('school_name', 'ASC');
    $this->db->order_by('grade_level', 'ASC');
    $this->db->order_by('section', 'ASC');
    $this->db->order_by('assessment_type', 'ASC');

    return $this->db->get()->result();
}

/**
 * Get SBFP beneficiaries with filters
 */
public function get_sbfp_beneficiaries($filters = [])
{
    $this->db->select('*');
    $this->db->from($this->table);
    $this->db->where('is_deleted', FALSE);
    
    // Filter for SBFP beneficiaries (Yes) - make sure the column name is correct
    $this->db->where('sbfp_beneficiary', 'Yes');
    
    // Apply other filters EXCEPT nutritional_status (since we're already filtering by SBFP)
    if (!empty($filters['legislative_district'])) {
        $this->db->where('legislative_district', $filters['legislative_district']);
    }
    if (!empty($filters['school_district'])) {
        $this->db->where('school_district', $filters['school_district']);
    }
    if (!empty($filters['school_name'])) {
        $this->db->where('school_name', $filters['school_name']);
    }
    if (!empty($filters['grade_level'])) {
        $this->db->where('grade_level', $filters['grade_level']);
    }
    if (!empty($filters['assessment_type'])) {
        $this->db->where('assessment_type', $filters['assessment_type']);
    }
    if (!empty($filters['date_from'])) {
        $this->db->where('DATE(created_at) >=', $filters['date_from']);
    }
    if (!empty($filters['date_to'])) {
        $this->db->where('DATE(created_at) <=', $filters['date_to']);
    }
    
    // DO NOT apply nutritional_status filter here since we're getting SBFP beneficiaries
    
    $this->db->order_by('school_name', 'ASC');
    $this->db->order_by('grade_level', 'ASC');
    $this->db->order_by('section', 'ASC');
    $this->db->order_by('name', 'ASC');
    
    $query = $this->db->get();
    return $query->result();
}
    
    /**
     * Get unique legislative districts
     */
    public function get_unique_legislative_districts()
    {
        $this->db->distinct();
        $this->db->select('legislative_district');
        $this->db->from($this->table);
        $this->db->where('legislative_district IS NOT NULL');
        $this->db->where('legislative_district !=', '');
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('legislative_district', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get unique school districts
     */
    public function get_unique_school_districts()
    {
        $this->db->distinct();
        $this->db->select('school_district');
        $this->db->from($this->table);
        $this->db->where('school_district IS NOT NULL');
        $this->db->where('school_district !=', '');
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('school_district', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get unique school names
     */
    public function get_unique_school_names()
    {
        $this->db->distinct();
        $this->db->select('school_name');
        $this->db->from($this->table);
        $this->db->where('school_name IS NOT NULL');
        $this->db->where('school_name !=', '');
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('school_name', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get unique grade levels
     */
    public function get_unique_grade_levels()
    {
        $this->db->distinct();
        $this->db->select('grade_level');
        $this->db->from($this->table);
        $this->db->where('grade_level IS NOT NULL');
        $this->db->where('grade_level !=', '');
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('grade_level', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get total assessments count
     */
    public function get_total_assessments_count()
    {
        $this->db->where('is_deleted', FALSE);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Get assessment type count
     */
    public function get_assessment_type_count($assessment_type = null)
    {
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        if ($assessment_type) {
            $this->db->where('assessment_type', $assessment_type);
        }
        
        return $this->db->count_all_results();
    }

    /**
     * Get total schools count
     */
    public function get_total_schools_count()
    {
        $this->db->distinct();
        $this->db->select('school_name');
        $this->db->from($this->table);
        $this->db->where('school_name IS NOT NULL');
        $this->db->where('school_name !=', '');
        $this->db->where('is_deleted', FALSE);
        return $this->db->count_all_results();
    }

    /**
     * Get total students count (distinct names within schools)
     */
    public function get_total_students_count()
    {
        $this->db->distinct();
        $this->db->select('name, birthday, school_name');
        $this->db->from($this->table);
        $this->db->where('name IS NOT NULL');
        $this->db->where('name !=', '');
        $this->db->where('is_deleted', FALSE);
        return $this->db->count_all_results();
    }

    /**
     * Get nutritional statistics
     */
    public function get_nutritional_statistics($filters = [])
    {
        $this->db->select('
            grade_level, 
            section,
            SUM(CASE WHEN LOWER(nutritional_status) = "severely wasted" THEN 1 ELSE 0 END) as severely_wasted,
            SUM(CASE WHEN LOWER(nutritional_status) = "wasted" THEN 1 ELSE 0 END) as wasted,
            SUM(CASE WHEN LOWER(nutritional_status) = "normal" THEN 1 ELSE 0 END) as normal_bmi,
            COUNT(*) as total_students
        ');
        
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        // Apply filters
        if (!empty($filters['legislative_district'])) {
            $this->db->where('legislative_district', $filters['legislative_district']);
        }
        if (!empty($filters['school_district'])) {
            $this->db->where('school_district', $filters['school_district']);
        }
        if (!empty($filters['school_name'])) {
            $this->db->where('school_name', $filters['school_name']);
        }
        if (!empty($filters['grade_level'])) {
            $this->db->where('grade_level', $filters['grade_level']);
        }
        if (!empty($filters['assessment_type'])) {
            $this->db->where('assessment_type', $filters['assessment_type']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('date_of_weighing >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('date_of_weighing <=', $filters['date_to']);
        }
        
        $this->db->group_by(['grade_level', 'section']);
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Get recent submissions (last 30 days)
     */
    public function get_recent_submissions($limit = 10)
    {
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result();
    }

    /**
     * Get submissions by date range
     */
    public function get_submissions_by_date_range($start_date, $end_date)
    {
        $this->db->where('DATE(created_at) >=', $start_date);
        $this->db->where('DATE(created_at) <=', $end_date);
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Check if section has submitted assessments
     */
    public function has_submitted_assessments($legislative_district, $school_district, $grade, $section)
    {
        $this->db->where('legislative_district', $legislative_district);
        $this->db->where('school_district', $school_district);
        $this->db->where('grade_level', $grade);
        $this->db->where('section', $section);
        $this->db->where('is_deleted', FALSE);
        return $this->db->count_all_results($this->table) > 0;
    }


    /**
     * Get detailed nutritional statistics by school/grade/section with aggregation
     */
    public function get_detailed_nutritional_statistics($filters = [])
    {
        $this->db->select('
            school_name,
            legislative_district,
            school_district,
            grade_level,
            section,
            COUNT(*) as total_students,
            SUM(CASE WHEN LOWER(nutritional_status) = "severely wasted" THEN 1 ELSE 0 END) as severely_wasted,
            SUM(CASE WHEN LOWER(nutritional_status) = "wasted" THEN 1 ELSE 0 END) as wasted,
            SUM(CASE WHEN LOWER(nutritional_status) = "normal" THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN LOWER(nutritional_status) = "overweight" THEN 1 ELSE 0 END) as overweight,
            SUM(CASE WHEN LOWER(nutritional_status) = "obese" THEN 1 ELSE 0 END) as obese,
            MAX(created_at) as last_submission
        ');
        
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        $this->db->group_by('school_name, legislative_district, school_district, grade_level, section');
        
        // Apply filters
        $this->apply_filters($filters);
        
        $this->db->order_by('school_name', 'ASC');
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get individual wasted and severely wasted students with all details
     */
    public function get_wasted_students_detailed($filters = [])
    {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        // Filter for wasted and severely wasted
        $this->db->group_start();
        $this->db->where('LOWER(nutritional_status)', 'wasted');
        $this->db->or_where('LOWER(nutritional_status)', 'severely wasted');
        $this->db->group_end();
        
        // Apply other filters
        $this->apply_filters($filters);
        
        $this->db->order_by('school_name', 'ASC');
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get all students with nutritional details for export
     */
    public function get_all_students_for_export($filters = [])
    {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        // Apply filters
        $this->apply_filters($filters);
        
        $this->db->order_by('school_name', 'ASC');
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get nutritional statistics summary (for statistics page cards)
     */
    public function get_nutritional_statistics_summary($filters = [])
    {
        $this->db->select('
            COUNT(*) as total_students,
            SUM(CASE WHEN LOWER(nutritional_status) = "severely wasted" THEN 1 ELSE 0 END) as severely_wasted,
            SUM(CASE WHEN LOWER(nutritional_status) = "wasted" THEN 1 ELSE 0 END) as wasted,
            SUM(CASE WHEN LOWER(nutritional_status) = "normal" THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN LOWER(nutritional_status) = "overweight" THEN 1 ELSE 0 END) as overweight,
            SUM(CASE WHEN LOWER(nutritional_status) = "obese" THEN 1 ELSE 0 END) as obese
        ');
        
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        // Apply filters
        $this->apply_filters($filters);
        
        return $this->db->get()->row();
    }

/**
 * Helper method to apply filters
 */
private function apply_filters($filters = [])
{
    if (!empty($filters['legislative_district'])) {
        $this->db->where('legislative_district', $filters['legislative_district']);
    }
    
    if (!empty($filters['school_district'])) {
        $this->db->where('school_district', $filters['school_district']);
    }
    
    if (!empty($filters['school_name'])) {
        $this->db->where('school_name', $filters['school_name']);
    }
    
    if (!empty($filters['grade_level'])) {
        $this->db->where('grade_level', $filters['grade_level']);
    }
    
    if (!empty($filters['assessment_type'])) {
        $this->db->where('assessment_type', $filters['assessment_type']);
    }
    
    // Add nutritional_status filter if specified
    if (!empty($filters['nutritional_status'])) {
        $this->db->where('LOWER(nutritional_status)', strtolower($filters['nutritional_status']));
    }
    
    if (!empty($filters['date_from'])) {
        $this->db->where('DATE(created_at) >=', $filters['date_from']);
    }
    
    if (!empty($filters['date_to'])) {
        $this->db->where('DATE(created_at) <=', $filters['date_to']);
    }
}

    /**
     * Get school ID (assuming there's a school_id field in the table)
     */
    public function get_school_info($school_name)
    {
        $this->db->select('school_id, school_name, school_district, legislative_district');
        $this->db->from($this->table);
        $this->db->where('school_name', $school_name);
        $this->db->where('is_deleted', FALSE);
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    /**
     * Get all wasted students for statistics page (individual records)
     */
    public function get_all_wasted_students($filters = [])
    {
        $this->db->select('
            id,
            school_id,
            school_name,
            legislative_district,
            school_district,
            grade_level,
            section,
            name,
            birthday,
            age,
            sex,
            weight,
            height,
            bmi,
            nutritional_status,
            sbfp_beneficiary,
            height_for_age,
            date_of_weighing,
            created_at
        ');
        
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        // Filter for wasted and severely wasted
        $this->db->group_start();
        $this->db->where('LOWER(nutritional_status)', 'wasted');
        $this->db->or_where('LOWER(nutritional_status)', 'severely wasted');
        $this->db->group_end();
        
        // Apply other filters
        $this->apply_filters($filters);
        
        $this->db->order_by('nutritional_status', 'DESC'); // Severely wasted first
        $this->db->order_by('school_name', 'ASC');
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get students by specific nutritional status with optional filters
     */
    public function get_students_by_nutritional_status($status, $filters = [])
    {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->where('is_deleted', FALSE);
        
        // Filter by nutritional status (case insensitive)
        $this->db->where('LOWER(nutritional_status)', strtolower($status));
        
        // Apply other filters using the existing helper method
        $this->apply_filters($filters);
        
        $this->db->order_by('school_name', 'ASC');
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Get grade levels for statistics filter
     */
    public function get_grade_levels()
    {
        $this->db->distinct();
        $this->db->select('grade_level');
        $this->db->from($this->table);
        $this->db->where('grade_level IS NOT NULL');
        $this->db->where('grade_level !=', '');
        $this->db->where('is_deleted', FALSE);
        $this->db->order_by('grade_level', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
 * Get export data with filters
 */
public function get_export_data_with_filters($legislative_district = null, $school_district = null, $school_name = null, $grade_level = null, $date_from = null, $date_to = null, $assessment_type = null)
{
    $this->db->select('*');
    $this->db->from($this->table);
    $this->db->where('is_deleted', FALSE);

    // Apply filters
    if (!empty($legislative_district)) {
        $this->db->where('legislative_district', $legislative_district);
    }
    if (!empty($school_district)) {
        $this->db->where('school_district', $school_district);
    }
    if (!empty($school_name)) {
        $this->db->where('school_name', $school_name);
    }
    if (!empty($grade_level)) {
        $this->db->where('grade_level', $grade_level);
    }
    if (!empty($date_from)) {
        $this->db->where('DATE(created_at) >=', $date_from);
    }
    if (!empty($date_to)) {
        $this->db->where('DATE(created_at) <=', $date_to);
    }
    if (!empty($assessment_type)) {
        $this->db->where('assessment_type', $assessment_type);
    }

    $this->db->order_by('school_name', 'ASC');
    $this->db->order_by('grade_level', 'ASC');
    $this->db->order_by('section', 'ASC');
    $this->db->order_by('name', 'ASC');

    return $this->db->get()->result();
}

}