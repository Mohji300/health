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
            $data[$grade . '_m']     = $this->create_empty_grade_data();
            $data[$grade . '_f']     = $this->create_empty_grade_data();
            $data[$grade . '_total'] = $this->create_empty_grade_data();
        }

        $this->db->select("CASE
                WHEN LOWER(TRIM(n.grade_level)) IN ('kindergarten', 'kinder') THEN 'Kinder'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 1'  THEN 'Grade 1'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 2'  THEN 'Grade 2'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 3'  THEN 'Grade 3'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 4'  THEN 'Grade 4'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 5'  THEN 'Grade 5'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 6'  THEN 'Grade 6'
                WHEN LOWER(TRIM(n.grade_level)) = 'sped'     THEN 'SPED'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 7'  THEN 'Grade 7'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 8'  THEN 'Grade 8'
                WHEN LOWER(TRIM(n.grade_level)) = 'grade 9'  THEN 'Grade 9'
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
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'wasted'          THEN 1 ELSE 0 END) AS wasted,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'normal'          THEN 1 ELSE 0 END) AS normal_bmi,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'overweight'      THEN 1 ELSE 0 END) AS overweight,
            SUM(CASE WHEN LOWER(TRIM(n.nutritional_status)) = 'obese'           THEN 1 ELSE 0 END) AS obese,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) = 'severely stunted' THEN 1 ELSE 0 END) AS severely_stunted,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) = 'stunted'          THEN 1 ELSE 0 END) AS stunted,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) = 'normal'           THEN 1 ELSE 0 END) AS normal_hfa,
            SUM(CASE WHEN LOWER(TRIM(n.height_for_age)) IN ('tall', 'above normal') THEN 1 ELSE 0 END) AS tall")
                 ->from('nutritional_assessments n')
                 ->join('schools s',           'n.school_id = s.school_id',      'inner')
                 ->join('school_districts sd', 's.school_district_id = sd.id',   'inner')
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
        $rows  = $query->result();

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

            $data[$gradeKey]['enrolment']        = (int)($row->enrolment        ?? 0);
            $data[$gradeKey]['pupils_weighed']   = (int)($row->pupils_weighed   ?? 0);
            $data[$gradeKey]['pupils_height']    = (int)($row->pupils_height    ?? 0);
            $data[$gradeKey]['severely_wasted']  = (int)($row->severely_wasted  ?? 0);
            $data[$gradeKey]['wasted']           = (int)($row->wasted           ?? 0);
            $data[$gradeKey]['normal_bmi']       = (int)($row->normal_bmi       ?? 0);
            $data[$gradeKey]['overweight']       = (int)($row->overweight       ?? 0);
            $data[$gradeKey]['obese']            = (int)($row->obese            ?? 0);
            $data[$gradeKey]['severely_stunted'] = (int)($row->severely_stunted ?? 0);
            $data[$gradeKey]['stunted']          = (int)($row->stunted          ?? 0);
            $data[$gradeKey]['normal_hfa']       = (int)($row->normal_hfa       ?? 0);
            $data[$gradeKey]['tall']             = (int)($row->tall             ?? 0);
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
            $maleKey   = $grade . '_m';
            $femaleKey = $grade . '_f';
            $totalKey  = $grade . '_total';

            if (!isset($data[$maleKey], $data[$femaleKey], $data[$totalKey])) {
                continue;
            }

            foreach ($numericFields as $field) {
                $data[$totalKey][$field] = (int)($data[$maleKey][$field] ?? 0)
                                         + (int)($data[$femaleKey][$field] ?? 0);
            }
        }

        return $data;
    }

    /**
     * Create an empty grade data structure.
     */
    private function create_empty_grade_data() {
        return [
            'enrolment'        => 0,
            'pupils_weighed'   => 0,
            'severely_wasted'  => 0,
            'wasted'           => 0,
            'normal_bmi'       => 0,
            'overweight'       => 0,
            'obese'            => 0,
            'severely_stunted' => 0,
            'stunted'          => 0,
            'normal_hfa'       => 0,
            'tall'             => 0,
            'pupils_height'    => 0,
        ];
    }

    /**
     * Apply school level filter using the `school_level` column.
     * Assumes `s` is the alias for the schools table.
     */
    private function apply_school_level_filter($school_level) {
        if ($school_level === 'all') return;

        $level_map = [
            'elementary'            => 'elementary',
            'secondary'             => 'secondary',
            'integrated'            => 'integrated',
            'shs_only'              => 'shs_only',
            'integrated_elementary' => 'integrated',
            'integrated_secondary'  => 'integrated',
        ];

        if (isset($level_map[$school_level])) {
            $this->db->where('s.school_level', $level_map[$school_level]);
        }
    }

    /**
     * Get grand total for ONE assessment type.
     */
    public function get_division_grand_total($assessment_type = 'baseline', $school_level = 'all', $legislative_district_id = null) {
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) return 0;

        $this->db->select('COUNT(*) AS total')
            ->from('nutritional_assessments n')
            ->join('schools s',           'n.school_id = s.school_id',     'inner')
            ->join('school_districts sd', 's.school_district_id = sd.id',  'inner')
            ->where('n.is_deleted', 0)
            ->where('n.assessment_type', $assessment_type);

        if ($legislative_district_id) {
            $this->db->where('sd.legislative_district_id', (int) $legislative_district_id);
        }
        $this->apply_school_level_filter($school_level);
        if ($school_level === 'shs_only') {
            $this->db->where_in('n.grade_level', ['Grade 11', 'Grade 12']);
        }
        $q = $this->db->get();
        return (int) ($q->row()->total ?? 0);
    }

    /**
     * Get assessment count for ONE assessment type (single query).
     */
    public function get_assessment_count_division($assessment_type = 'baseline', $school_level = 'all', $legislative_district_id = null) {
        if (!$this->db->table_exists('nutritional_assessments')) return 0;
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'])) return 0;

        $this->db->select('COUNT(DISTINCT n.school_id) AS total')
            ->from('nutritional_assessments n')
            ->join('schools s',           'n.school_id = s.school_id',     'inner')
            ->join('school_districts sd', 's.school_district_id = sd.id',  'inner')
            ->where('n.assessment_type', $assessment_type)
            ->where('n.is_deleted', 0);

        if ($legislative_district_id) {
            $this->db->where('sd.legislative_district_id', (int) $legislative_district_id);
        }
        $this->apply_school_level_filter($school_level);
        if ($school_level === 'shs_only') {
            $this->db->where_in('n.grade_level', ['Grade 11', 'Grade 12']);
        }
        $q = $this->db->get();
        return (int) ($q->row()->total ?? 0);
    }

    /**
     * Get schools for a district with assessment status.
     *
     * The outer query fetches only the schools.
     * Assessment status is derived from get_school_assessment_status(),
     * which uses the school CODE (not the numeric PK).
     */
    public function get_schools_with_status($district_id, $assessment_type = null) {
        // ---------------------------------------------------------------
        // 1. Get all schools in the district (1 query)
        // ---------------------------------------------------------------
        $schools = $this->db
            ->select('s.id, s.name, s.school_id AS code, s.school_id')
            ->from('schools s')
            ->where('s.school_district_id', $district_id)
            ->order_by('s.name')
            ->get()
            ->result_array();

        if (empty($schools)) {
            return [];
        }

        $codes = array_column($schools, 'code');

        // ---------------------------------------------------------------
        // 2. Section counts per school (1 query)
        // ---------------------------------------------------------------
        $section_counts = [];   // code => int
        $rows = $this->db
            ->select('u.school_id AS school_code, COUNT(DISTINCT gs.id) AS section_count', false)
            ->from('grade_sections gs')
            ->join('users u', 'u.id = gs.user_id', 'inner')
            ->where_in('u.school_id', $codes)
            ->group_by('u.school_id')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $section_counts[$row['school_code']] = (int) $row['section_count'];
        }

        // ---------------------------------------------------------------
        // 3. Assessment stats per (school, type) (1 query)
        // ---------------------------------------------------------------
        $assess_stats = [];   // code => [type => ['rows' => int, 'distinct' => int]]
        $rows = $this->db
            ->select("
                school_id AS school_code,
                assessment_type,
                COUNT(*)                  AS row_count,
                COUNT(DISTINCT section_id) AS distinct_sections
            ", false)
            ->from('nutritional_assessments')
            ->where_in('school_id', $codes)
            ->where('is_deleted', 0)
            ->where_in('assessment_type', ['baseline', 'midline', 'endline'])
            ->group_by('school_id, assessment_type')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $assess_stats[$row['school_code']][$row['assessment_type']] = [
                'rows'     => (int) $row['row_count'],
                'distinct' => (int) $row['distinct_sections'],
            ];
        }

        // ---------------------------------------------------------------
        // 4. Build the status in PHP — no more DB hits
        // ---------------------------------------------------------------
        foreach ($schools as &$school) {
            $code           = $school['code'];
            $total_sections = $section_counts[$code] ?? 0;
            $any            = isset($assess_stats[$code]);

            $school['has_any_assessment'] = $any ? 1 : 0;

            foreach (['baseline', 'midline', 'endline'] as $type) {
                $stats = $assess_stats[$code][$type] ?? null;

                if (!$stats) {
                    $school['has_' . $type]     = false;
                    $school['partial_' . $type] = false;
                    continue;
                }

                if ($total_sections > 0) {
                    $school['has_' . $type] = ($stats['distinct'] === $total_sections);
                } else {
                    $school['has_' . $type] = ($stats['rows'] > 0);
                }

                $school['partial_' . $type] = ($stats['rows'] > 0 && !$school['has_' . $type]);
            }

            $school['has_submitted'] = $assessment_type
                ? ($school['has_' . $assessment_type] ? 1 : 0)
                : (($school['has_baseline'] && $school['has_midline'] && $school['has_endline']) ? 1 : 0);

            $school['has_partial_assessment'] = (
                $school['has_any_assessment'] &&
                !($school['has_baseline'] && $school['has_midline'] && $school['has_endline'])
            ) ? 1 : 0;

            $school['assessments'] = [
                'baseline' => (bool) $school['has_baseline'],
                'midline'  => (bool) $school['has_midline'],
                'endline'  => (bool) $school['has_endline'],
                'any'      => (bool) ($school['has_baseline'] || $school['has_midline'] || $school['has_endline']),
            ];
        }
        unset($school);

        return $schools;
    }

    /**
     * An assessment is complete for a school only when every saved section has
     * at least one active row for that assessment type.
     *
     * Uses explicit COUNT(DISTINCT section_id) via select(..., false) so the
     * result is not dependent on CI3's handling of ->distinct() + count_all_results().
     */
    public function get_school_assessment_status($school_code) {
        $sections = $this->db->select('gs.id')
            ->from('grade_sections gs')
            ->join('users u', 'u.id = gs.user_id')
            ->where('u.school_id', $school_code)
            ->get()
            ->result_array();

        $section_ids = array_column($sections, 'id');

        $status = [];

        $status['has_any_assessment'] = $this->db
            ->where('school_id', $school_code)
            ->where('is_deleted', 0)
            ->count_all_results('nutritional_assessments') > 0;

        foreach (['baseline', 'midline', 'endline'] as $type) {
            if (!empty($section_ids)) {
                // Total active rows for this type/school/sections
                $row_count = $this->db
                    ->where('school_id', $school_code)
                    ->where('is_deleted', 0)
                    ->where('assessment_type', $type)
                    ->where_in('section_id', $section_ids)
                    ->count_all_results('nutritional_assessments');

                // Distinct sections that have at least one row for this type
                $row = $this->db
                    ->select('COUNT(DISTINCT section_id) AS c', false)
                    ->from('nutritional_assessments')
                    ->where('school_id', $school_code)
                    ->where('is_deleted', 0)
                    ->where('assessment_type', $type)
                    ->where_in('section_id', $section_ids)
                    ->get()
                    ->row();

                $submitted = (int) ($row->c ?? 0);

                $status['has_' . $type]     = ($submitted === count($section_ids));
                $status['partial_' . $type] = ($row_count > 0 && !$status['has_' . $type]);
            } else {
                $row_count = $this->db
                    ->where('school_id', $school_code)
                    ->where('is_deleted', 0)
                    ->where('assessment_type', $type)
                    ->count_all_results('nutritional_assessments');

                $status['has_' . $type]     = ($row_count > 0);
                $status['partial_' . $type] = false;
            }
        }

        $status['partial_any'] = $status['has_any_assessment'] && !(
            $status['has_baseline'] && $status['has_midline'] && $status['has_endline']
        );

        return $status;
    }

    /**
     * Get district summary (total and fully submitted schools)
     * for the given assessment type and school level.
     *
     * A school is considered submitted/completed only when
     * all of its required sections have at least one active
     * assessment record for the selected assessment type.
     */
    public function get_district_summary(
        $assessment_type,
        $school_level = 'all',
        $legislative_district_id = null
    ) {
        if (!in_array($assessment_type, ['baseline', 'midline', 'endline'], true)) {
            $assessment_type = 'baseline';
        }

        $safe_type = $this->db->escape($assessment_type);

        /*
        * Build the school-level completion query.
        *
        * A school is complete when the number of distinct
        * submitted sections equals the number of sections
        * assigned/saved for that school.
        */
        $this->db->select("
            sd.id,
            sd.name AS district_name,

            COUNT(DISTINCT s.id) AS total_schools,

            COUNT(DISTINCT CASE
                WHEN (
                    SELECT COUNT(DISTINCT gs.id)
                    FROM grade_sections gs
                    INNER JOIN users u
                        ON u.id = gs.user_id
                    WHERE u.school_id = s.school_id
                ) = (
                    SELECT COUNT(DISTINCT na2.section_id)
                    FROM nutritional_assessments na2
                    WHERE na2.school_id = s.school_id
                    AND na2.assessment_type = {$safe_type}
                    AND na2.is_deleted = 0
                )
                AND (
                    SELECT COUNT(DISTINCT na3.section_id)
                    FROM nutritional_assessments na3
                    WHERE na3.school_id = s.school_id
                    AND na3.assessment_type = {$safe_type}
                    AND na3.is_deleted = 0
                ) > 0
                THEN s.id
            END) AS submitted_schools
        ", false);

        $this->db->from('school_districts sd');

        $this->db->join(
            'schools s',
            's.school_district_id = sd.id',
            'left'
        );

        if ($legislative_district_id) {
            $this->db->where(
                'sd.legislative_district_id',
                (int) $legislative_district_id
            );
        }

        /*
        * Apply the same school-level filtering used
        * by the dashboard.
        */
        $this->apply_school_level_filter($school_level);

        $this->db->group_by(['sd.id', 'sd.name']);
        $this->db->order_by('sd.name');

        return $this->db->get()->result_array();
    }

    public function get_district_consolidated_records($district_name) {
        return $this->db->select('na.section_id, na.name, na.grade_level, na.section,
                na.year as school_year, na.assessment_type, na.age, na.sex, na.weight,
                na.height, na.bmi, na.nutritional_status, na.height_for_age,
                na.sbfp_beneficiary, na.date_of_weighing')
            ->from('nutritional_assessments na')
            ->join('schools s',           's.school_id = na.school_id')
            ->join('school_districts sd', 'sd.id = s.school_district_id')
            ->where('sd.name', $district_name)
            ->where('na.is_deleted', 0)
            ->order_by('na.grade_level', 'ASC')
            ->order_by('na.section', 'ASC')
            ->order_by('na.name', 'ASC')
            ->get()
            ->result_array();
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

    /**
     * Get legislative districts for dropdown
     */
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
        // 1 query: districts + school counts
        $districts = $this->db
            ->select('sd.id, sd.name, COUNT(DISTINCT s.id) AS total_schools', false)
            ->from('school_districts sd')
            ->join('schools s', 's.school_district_id = sd.id', 'left')
            ->group_by('sd.id, sd.name')
            ->order_by('sd.name')
            ->get()
            ->result_array();

        if (empty($districts)) return [];

        // 1 query: submitted schools per district
        $district_ids = array_column($districts, 'id');
        $submitted = [];
        $rows = $this->db
            ->select('sd.id AS district_id, COUNT(DISTINCT na.school_id) AS submitted', false)
            ->from('school_districts sd')
            ->join('schools s', 's.school_district_id = sd.id', 'inner')
            ->join('nutritional_assessments na',
                'na.school_id = s.school_id AND na.is_deleted = 0', 'inner')
            ->where_in('sd.id', $district_ids)
            ->group_by('sd.id')
            ->get()
            ->result_array();
        foreach ($rows as $row) {
            $submitted[$row['district_id']] = (int) $row['submitted'];
        }

        // Build the legacy nested shape the caller expects
        $reports = [];
        foreach ($districts as $d) {
            $legislative = 'District ' . substr($d['name'], 0, 3);
            if (!isset($reports[$legislative])) $reports[$legislative] = [];
            $reports[$legislative][$d['name']] = [
                'total'     => (int) $d['total_schools'],
                'submitted' => $submitted[$d['id']] ?? 0,
            ];
        }
        return $reports;
    }

    /**
     * Get school details by ID or name
     */
    public function get_school_details($identifier) {
        $this->db->select('s.id, s.name, s.school_id as code, s.school_level as level, sd.name as district')
                ->from('schools s')
                ->join('school_districts sd', 's.school_district_id = sd.id', 'left');

        if (is_numeric($identifier)) {
            $this->db->where('s.id', $identifier);
        } else {
            $this->db->where('s.name', $identifier);
        }

        $query = $this->db->limit(1)->get();
        if ($query === false) {
            log_message('error', 'Unable to load school details: ' . $this->db->error()['message']);
            return null;
        }
        $school = $query->row_array();

        if ($school) {
            $school += [
                'address'        => null,
                'contact_person' => null,
                'contact_number' => null,
                'email'          => null,
                'region'         => null,
                'division'       => null,
                'type'           => null,
            ];

            // Assessment status is complete only when every saved section has rows.
            $assessment_status = $this->get_school_assessment_status($school['code']);
            $school['assessments'] = [
                'has_baseline'         => $assessment_status['has_baseline'],
                'has_midline'          => $assessment_status['has_midline'],
                'has_endline'          => $assessment_status['has_endline'],
                'last_assessment_date' => $this->get_last_assessment_date($school['code']),
            ];

            $sections_query = $this->db->select('
                    gs.id as section_id,
                    gs.grade,
                    gs.section,
                    gs.year as school_year,
                    gs.school_district,
                    gs.legislative_district
                ')
                ->from('grade_sections gs')
                ->join('users u', 'u.id = gs.user_id')
                ->where('u.school_id', $school['code'])
                ->order_by('gs.grade', 'ASC')
                ->order_by('gs.section', 'ASC')
                ->get();
            $school['sections'] = $sections_query === false ? [] : $sections_query->result_array();

            $consolidated_query = $this->db->select('
                    section_id,
                    name,
                    grade_level,
                    section,
                    year as school_year,
                    school_district,
                    legislative_district,
                    assessment_type,
                    age,
                    sex,
                    weight,
                    height,
                    bmi,
                    nutritional_status,
                    height_for_age,
                    sbfp_beneficiary,
                    date_of_weighing
                ')
                ->from('nutritional_assessments')
                ->where('school_id', $school['code'])
                ->where('is_deleted', 0)
                ->order_by('grade_level', 'ASC')
                ->order_by('section', 'ASC')
                ->order_by('name', 'ASC')
                ->order_by('assessment_type', 'ASC')
                ->get();

            if ($consolidated_query === false) {
                log_message('error', 'Unable to load consolidated school data: ' . $this->db->error()['message']);
                $school['consolidated'] = [];
            } else {
                $school['consolidated'] = $consolidated_query->result_array();
            }
        }

        return $school;
    }

    /**
     * Helper method to get last assessment date for a school using school_id (code)
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
     * Get user schools based on user type and district.
     *
     * IMPORTANT: assessments.school_id stores the school CODE, not the
     * numeric schools.id. Both branches below filter on $school['code'].
     */
    public function get_user_schools($user_id, $user_type, $user_district) {
        // Division-level: all schools across all districts
        if ($user_type === 'division') {
            $districts = $this->get_all_districts();

            $all_schools = [];

            foreach ($districts as $district) {
                $district_schools = $this->db->select('id, name, school_id as code')
                                            ->from('schools')
                                            ->where('school_district_id', $district['id'])
                                            ->order_by('name')
                                            ->get()
                                            ->result_array();

                foreach ($district_schools as &$school) {
                    $school['district']    = $district['name'];
                    $school['district_id'] = $district['id'];

                    $school['has_baseline'] = $this->db->from('nutritional_assessments')
                                                    ->where('school_id', $school['code'])
                                                    ->where('is_deleted', 0)
                                                    ->where('assessment_type', 'baseline')
                                                    ->count_all_results() > 0;

                    $school['has_midline'] = $this->db->from('nutritional_assessments')
                                                    ->where('school_id', $school['code'])
                                                    ->where('is_deleted', 0)
                                                    ->where('assessment_type', 'midline')
                                                    ->count_all_results() > 0;

                    $school['has_endline'] = $this->db->from('nutritional_assessments')
                                                    ->where('school_id', $school['code'])
                                                    ->where('is_deleted', 0)
                                                    ->where('assessment_type', 'endline')
                                                    ->count_all_results() > 0;

                    $school['has_submitted'] = $school['has_baseline']
                                            || $school['has_midline']
                                            || $school['has_endline'];
                }
                unset($school);

                $all_schools = array_merge($all_schools, $district_schools);
            }

            return $all_schools;
        }

        // District-level: only schools in the user's district
        if ($user_type === 'district' && !empty($user_district)) {
            $district = $this->db->select('id')
                                ->from('school_districts')
                                ->where('name', $user_district)
                                ->limit(1)
                                ->get()
                                ->row();

            if (!$district) {
                return [];
            }

            $schools = $this->db->select('id, name, school_id as code')
                            ->from('schools')
                            ->where('school_district_id', $district->id)
                            ->order_by('name')
                            ->get()
                            ->result_array();

            foreach ($schools as &$school) {
                $school['district']    = $user_district;
                $school['district_id'] = $district->id;

                $school['has_baseline'] = $this->db->from('nutritional_assessments')
                                                ->where('school_id', $school['code'])
                                                ->where('is_deleted', 0)
                                                ->where('assessment_type', 'baseline')
                                                ->count_all_results() > 0;

                $school['has_midline'] = $this->db->from('nutritional_assessments')
                                                ->where('school_id', $school['code'])
                                                ->where('is_deleted', 0)
                                                ->where('assessment_type', 'midline')
                                                ->count_all_results() > 0;

                $school['has_endline'] = $this->db->from('nutritional_assessments')
                                                ->where('school_id', $school['code'])
                                                ->where('is_deleted', 0)
                                                ->where('assessment_type', 'endline')
                                                ->count_all_results() > 0;

                $school['has_submitted'] = $school['has_baseline']
                                        || $school['has_midline']
                                        || $school['has_endline'];
            }
            unset($school);

            return $schools;
        }

        return [];
    }

    /**
     * Get division summary
     */
    public function get_division_summary() {
        $total_schools   = $this->db->from('schools')->count_all_results();
        $total_districts = $this->db->from('school_districts')->count_all_results();

        $total_assessments = 0;
        if ($this->db->table_exists('nutritional_assessments')) {
            $total_assessments = $this->db->from('nutritional_assessments')
                                        ->where('is_deleted', 0)
                                        ->count_all_results();
        }

        $schools_with_assessments = 0;
        if ($this->db->table_exists('nutritional_assessments')) {
            $schools_with_assessments = $this->db->select('COUNT(DISTINCT school_id) as count')
                                                ->from('nutritional_assessments')
                                                ->where('is_deleted', 0)
                                                ->get()
                                                ->row()->count ?? 0;
        }

        return [
            'total_schools'            => $total_schools,
            'total_districts'          => $total_districts,
            'total_assessments'        => $total_assessments,
            'schools_with_assessments' => $schools_with_assessments,
            'coverage_rate'            => $total_schools > 0
                ? round(($schools_with_assessments / $total_schools) * 100)
                : 0,
        ];
    }
}