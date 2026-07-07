<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class sbfp_beneficiaries_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get beneficiaries with role‑based filtering – only those with sbfp_beneficiary = 'Yes'
     */
    public function get_beneficiaries($assessment_type = 'baseline', $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '', $section_id = '') {
        
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');  // Only explicit beneficiaries
        
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        $this->apply_school_level_filter($school_level);
        
        // Custom grade order
        $this->db->order_by("CASE
            WHEN n.grade_level = 'Kindergarten' THEN 0
            WHEN n.grade_level = 'Grade 1' THEN 1
            WHEN n.grade_level = 'Grade 2' THEN 2
            WHEN n.grade_level = 'Grade 3' THEN 3
            WHEN n.grade_level = 'Grade 4' THEN 4
            WHEN n.grade_level = 'Grade 5' THEN 5
            WHEN n.grade_level = 'Grade 6' THEN 6
            WHEN n.grade_level = 'Grade 7' THEN 7
            WHEN n.grade_level = 'Grade 8' THEN 8
            WHEN n.grade_level = 'Grade 9' THEN 9
            WHEN n.grade_level = 'Grade 10' THEN 10
            WHEN n.grade_level = 'Grade 11' THEN 11
            WHEN n.grade_level = 'Grade 12' THEN 12
            ELSE 99 END", '', FALSE);
        $this->db->order_by('n.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Apply additional filters (grade, school name, district)
     */
    private function apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter) {
        if (!empty($grade_level_filter)) {
            $this->db->where('n.grade_level', $grade_level_filter);
        }
        if (!empty($school_name_filter) && in_array($user_role, ['district', 'division', 'admin'])) {
            $this->db->where('n.school_name', $school_name_filter);
        }
        if (!empty($district_filter) && in_array($user_role, ['division', 'admin'])) {
            $this->db->where('n.school_district', $district_filter);
        }
    }
    
    /**
     * Apply role‑based filter – fixed for district and division users
     */
    private function apply_role_filter($user_role, $school_id, $district, $school_name = '', $selected_school = '') {
        $user_role_lower = strtolower($user_role);
        switch($user_role_lower) {
            case 'school':
            case 'user':
                if (!empty($school_id)) {
                    $this->db->where('n.school_id', $school_id);
                } elseif (!empty($school_name)) {
                    $this->db->where('n.school_name', $school_name);
                } else {
                    $this->db->where('1=0'); // no valid school info
                }
                break;
                
            case 'district':
                if (!empty($district)) {
                    // Join with schools and school_districts to filter by district name
                    $this->db->join('schools s', 'n.school_id = s.school_id', 'left');
                    $this->db->join('school_districts sd', 's.school_district_id = sd.id', 'left');
                    $this->db->where('sd.name', $district);
                    // Also allow filtering by the legacy school_district column (fallback)
                    $this->db->or_where('n.school_district', $district);
                } else {
                    $this->db->where('1=0');
                }
                break;
                
            case 'division':
            case 'admin':
                // NO role‑based filter – division/admin see all schools.
                // School name filtering is handled via apply_additional_filters().
                // Do NOT add any WHERE clause here.
                break;
                
            default:
                $this->db->where('1=0');
                break;
        }
    }
    
    /**
     * Apply school level grade restrictions
     */
    private function apply_school_level_filter($school_level) {
        if ($school_level === 'all') return;
        
        $grade_map = [
            'elementary' => ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'SPED'],
            'secondary'  => ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
            'integrated_elementary' => ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
            'integrated_secondary'  => ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
            'Stand Alone SHS' => ['Grade 11', 'Grade 12'],
        ];
        if (isset($grade_map[$school_level])) {
            $this->db->where_in('n.grade_level', $grade_map[$school_level]);
        }
        // 'integrated' has no grade restriction
    }
    
    // ----- Other methods (all updated to use sbfp_beneficiary = 'Yes') -----
    
    public function get_sections($assessment_type, $school_id, $grade_level = '', $school_name = '') {
        $this->db->distinct();
        $this->db->select('section_id as id, section');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        }
        if (!empty($school_name)) {
            $this->db->where('school_name', $school_name);
        }
        if (!empty($grade_level)) {
            $this->db->where('grade_level', $grade_level);
        }
        $this->db->where('section_id IS NOT NULL');
        $this->db->where('section !=', '');
        $this->db->order_by('section', 'ASC');
        $query = $this->db->get();
        return $query->result();
    }
    
    public function count_by_assessment_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '', $section_id = '') {
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        $this->apply_school_level_filter($school_level);
        return $this->db->count_all_results();
    }
    
    public function get_nutritional_stats_with_filter($assessment_type, $school_name = '', $school_level = 'all', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $grade_level_filter = '', $school_name_filter = '', $district_filter = '', $section_id = '') {
        $this->db->select('n.nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        $this->apply_additional_filters($user_role, $grade_level_filter, $school_name_filter, $district_filter);
        $this->apply_school_level_filter($school_level);
        $this->db->group_by('n.nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_schools_by_role($user_role, $school_id, $district) {
        $this->db->select('DISTINCT(n.school_name)');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        
        if ($user_role === 'school' && !empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        } elseif ($user_role === 'district' && !empty($district)) {
            // Fix: join with school_districts
            $this->db->join('schools s', 'n.school_id = s.school_id', 'left');
            $this->db->join('school_districts sd', 's.school_district_id = sd.id', 'left');
            $this->db->where('sd.name', $district);
        }
        // For division/admin: no extra filter – they see all schools with beneficiaries
        
        $this->db->order_by('n.school_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_count_by_school($assessment_type = 'baseline', $school_name = '', $school_id = '') {
        $this->db->select('COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        $query = $this->db->get();
        $result = $query->row();
        return $result ? $result->count : 0;
    }
    
    // DataTables methods
    public function count_beneficiaries_filtered($assessment_type, $filters) {
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        $this->apply_role_filter(
            $filters['user_role'],
            $filters['school_id'],
            $filters['school_district'],
            $filters['school_name'],
            $filters['selected_school']
        );
        $this->apply_additional_filters(
            $filters['user_role'],
            $filters['grade_level'],
            $filters['school_name'],
            $filters['district']
        );
        $this->apply_school_level_filter($filters['school_level']);
        if (!empty($filters['section_id'])) {
            $this->db->where('n.section_id', $filters['section_id']);
        }
        return $this->db->count_all_results();
    }
    
    public function get_beneficiaries_datatable($assessment_type, $filters, $limit, $offset, $order_by, $order_dir, $search) {
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        $this->apply_role_filter(
            $filters['user_role'],
            $filters['school_id'],
            $filters['school_district'],
            $filters['school_name'],
            $filters['selected_school']
        );
        $this->apply_additional_filters(
            $filters['user_role'],
            $filters['grade_level'],
            $filters['school_name'],
            $filters['district']
        );
        $this->apply_school_level_filter($filters['school_level']);
        if (!empty($filters['section_id'])) {
            $this->db->where('n.section_id', $filters['section_id']);
        }
        // Search
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('n.name', $search);
            $this->db->or_like('n.grade_level', $search);
            $this->db->or_like('n.section', $search);
            $this->db->or_like('n.school_name', $search);
            $this->db->or_like('n.nutritional_status', $search);
            $this->db->or_like('n.sex', $search);
            $this->db->group_end();
        }
        // Ordering
        $allowed = ['name','sex','grade_level','birthday','date_of_weighing','age','weight','height','bmi','nutritional_status','height_for_age'];
        if (in_array($order_by, $allowed)) {
            $this->db->order_by($order_by, $order_dir);
        } else {
            $this->db->order_by("CASE
                WHEN n.grade_level = 'Kindergarten' THEN 0
                WHEN n.grade_level = 'Grade 1' THEN 1
                WHEN n.grade_level = 'Grade 2' THEN 2
                WHEN n.grade_level = 'Grade 3' THEN 3
                WHEN n.grade_level = 'Grade 4' THEN 4
                WHEN n.grade_level = 'Grade 5' THEN 5
                WHEN n.grade_level = 'Grade 6' THEN 6
                WHEN n.grade_level = 'Grade 7' THEN 7
                WHEN n.grade_level = 'Grade 8' THEN 8
                WHEN n.grade_level = 'Grade 9' THEN 9
                WHEN n.grade_level = 'Grade 10' THEN 10
                WHEN n.grade_level = 'Grade 11' THEN 11
                WHEN n.grade_level = 'Grade 12' THEN 12
                ELSE 99 END", '', FALSE);
            $this->db->order_by('n.name', 'ASC');
        }
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get();
        return $query->result_array();
    }
    
    // Other methods remain similar
    public function get_beneficiaries_by_status($nutritional_status, $assessment_type = 'baseline', $school_name = '', $school_id = '', $section_id = '') {
        $this->db->select('n.*');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        $this->db->where('LOWER(n.nutritional_status)', strtolower($nutritional_status));
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        // ... order by ...
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_summary_stats($assessment_type = 'baseline', $school_name = '', $school_id = '') {
        $this->db->select('
            COUNT(*) as total_beneficiaries,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "severely wasted" THEN 1 ELSE 0 END) as severely_wasted,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "wasted" THEN 1 ELSE 0 END) as wasted,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "normal" THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "overweight" THEN 1 ELSE 0 END) as overweight,
            SUM(CASE WHEN LOWER(n.nutritional_status) = "obese" THEN 1 ELSE 0 END) as obese,
            SUM(CASE WHEN LOWER(n.height_for_age) = "tall" THEN 1 ELSE 0 END) as tall,
            SUM(CASE WHEN n.sex = "M" THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN n.sex = "F" THEN 1 ELSE 0 END) as female_count
        ');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        $query = $this->db->get();
        return $query->row();
    }
    
    public function get_grade_distribution($assessment_type = 'baseline', $school_name = '', $school_id = '') {
        $this->db->select('n.grade_level, COUNT(*) as count');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        if (!empty($school_name)) {
            $this->db->where('n.school_name', $school_name);
        }
        if (!empty($school_id)) {
            $this->db->where('n.school_id', $school_id);
        }
        $this->db->group_by('n.grade_level');
        // ... order by custom ...
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function export_beneficiaries($assessment_type = 'baseline', $school_name = '', $user_role = 'school', $school_id = '', $district = '', $selected_school = '', $section_id = '') {
        $this->db->select('
            n.school_name,
            n.school_id,
            n.legislative_district,
            n.school_district,
            n.grade_level,
            n.section,
            n.name as student_name,
            n.nutritional_status,
            n.sex,
            n.age,
            n.weight,
            n.height,
            n.bmi,
            n.height_for_age,
            n.date_of_weighing,
            n.sbfp_beneficiary,
            n.assessment_type,
            n.year as school_year
        ');
        $this->db->from('nutritional_assessments n');
        $this->db->where('n.assessment_type', $assessment_type);
        $this->db->where('n.is_deleted', 0);
        $this->db->where('n.sbfp_beneficiary', 'Yes');
        if (!empty($section_id)) {
            $this->db->where('n.section_id', $section_id);
        }
        $this->apply_role_filter($user_role, $school_id, $district, $school_name, $selected_school);
        $this->db->order_by('n.school_name', 'ASC');
        $this->db->order_by("CASE ... END", '', FALSE);
        $this->db->order_by('n.section', 'ASC');
        $this->db->order_by('n.name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    // Backward compatibility (also updated)
    public function count_by_assessment($assessment_type) {
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        return $this->db->count_all_results('nutritional_assessments');
    }
    
    public function get_schools() {
        $this->db->select('DISTINCT(school_name)');
        $this->db->from('nutritional_assessments');
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        $this->db->order_by('school_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function get_nutritional_stats($assessment_type = 'baseline') {
        $this->db->select('nutritional_status, COUNT(*) as count');
        $this->db->from('nutritional_assessments');
        $this->db->where('assessment_type', $assessment_type);
        $this->db->where('is_deleted', 0);
        $this->db->where('sbfp_beneficiary', 'Yes');
        $this->db->group_by('nutritional_status');
        $query = $this->db->get();
        return $query->result_array();
    }
}
?>