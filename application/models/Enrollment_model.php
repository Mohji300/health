<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Enrollment_model extends CI_Model {

    private $table = 'enrollments';
    private $assessments_table = 'nutritional_assessments';
    private $users_table       = 'users';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_enrollments() {
        $this->db->order_by('school_year', 'DESC');
        return $this->db->get($this->table)->result_array();
    }

    public function get_enrollment_by_id($id) {
        return $this->db->get_where($this->table, array('id' => $id))->row_array();
    }

    public function get_enrollment_by_school_year($school_year) {
        return $this->db->get_where($this->table, array('school_year' => $school_year))->row_array();
    }

    public function insert_enrollment($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update_enrollment($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function delete_enrollment($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Return unique active uploaded student counts by grade level.
     */
    public function get_nutritional_assessment_counts($school_year = null, $school_id = null, $school_name = null) {
        $summary = $this->get_uploaded_student_summary($school_year, $school_id, $school_name);
        $counts = array();
        foreach ($summary['grades'] as $grade => $grade_counts) {
            $counts[$grade] = $grade_counts['total'];
        }
        return $counts;
    }

    /**
     * Count unique students in active uploads for the selected school and year.
     */
    public function get_uploaded_student_summary($school_year = null, $school_id = null, $school_name = null) {
        $grades = array(
            'kindergarten', 'sped', 'grade_1', 'grade_2', 'grade_3', 'grade_4',
            'grade_5', 'grade_6', 'grade_7', 'grade_8', 'grade_9', 'grade_10',
            'grade_11', 'grade_12'
        );
        $summary = array(
            'total' => 0,
            'male' => 0,
            'female' => 0,
            'grades' => array()
        );
        foreach ($grades as $grade) {
            $summary['grades'][$grade] = array('total' => 0, 'male' => 0, 'female' => 0);
        }

        if (empty($school_id) && empty($school_name)) {
            return $summary;
        }

        $this->db->select('name, birthday, school_name, grade_level, sex');
        $this->db->from($this->assessments_table);
        $this->db->where('is_deleted', 0);
        $this->db->where('name IS NOT NULL', null, false);
        $this->db->where('name !=', '');
        if (!empty($school_year)) {
            $this->db->where('year', $school_year);
        }
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        } else {
            $this->db->where('school_name', $school_name);
        }
        $this->db->order_by('date_of_weighing', 'DESC');
        $this->db->order_by('id', 'DESC');

        $seen_students = array();
        foreach ($this->db->get()->result_array() as $row) {
            $identity = serialize(array(
                strtolower(trim((string) $row['name'])),
                isset($row['birthday']) ? (string) $row['birthday'] : '',
                strtolower(trim((string) $row['school_name']))
            ));
            if (isset($seen_students[$identity])) {
                continue;
            }
            $seen_students[$identity] = true;
            $summary['total']++;

            $grade = $this->normalize_grade_key($row['grade_level']);
            if ($grade === null || !isset($summary['grades'][$grade])) {
                continue;
            }
            $summary['grades'][$grade]['total']++;

            $sex = strtoupper(trim((string) $row['sex']));
            if (in_array($sex, array('M', 'MALE', 'L', 'LAKI', 'BOY'), true)) {
                $summary['male']++;
                $summary['grades'][$grade]['male']++;
            } elseif (in_array($sex, array('F', 'FEMALE', 'P', 'PEREMPUAN', 'GIRL'), true)) {
                $summary['female']++;
                $summary['grades'][$grade]['female']++;
            }
        }
        return $summary;
    }

    public function get_latest_uploaded_school_year($school_id = null, $school_name = null) {
        if (empty($school_id) && empty($school_name)) {
            return null;
        }

        $this->db->select('year');
        $this->db->from($this->assessments_table);
        $this->db->where('is_deleted', 0);
        $this->db->where('year IS NOT NULL', null, false);
        $this->db->where('year !=', '');
        if (!empty($school_id)) {
            $this->db->where('school_id', $school_id);
        } else {
            $this->db->where('school_name', $school_name);
        }
        $this->db->order_by('year', 'DESC');
        $this->db->limit(1);

        $row = $this->db->get()->row_array();
        return !empty($row['year']) ? $row['year'] : null;
    }

    public function get_user_school_info($user_id) {
        if (empty($user_id)) {
            return null;
        }

        return $this->db
            ->select('school_id, name')
            ->where('id', $user_id)
            ->get($this->users_table)
            ->row_array();
    }

    /**
     * Returns the school_level value stored on the users table
     * for the given user id (e.g. "Elementary", "Junior High School",
     * "Senior High School", "Integrated", "Stand-Alone", ...).
     *
     * The value is returned as-is so whatever the school head
     * picked during profile setup is what ends up on the page.
     */
    public function get_user_school_level($user_id) {
        if (empty($user_id)) {
            return null;
        }

        $row = $this->db
            ->select('school_level')
            ->where('id', $user_id)
            ->get($this->users_table)
            ->row_array();

        return !empty($row['school_level']) ? $row['school_level'] : null;
    }

    /**
     * Normalize whatever value is stored in `grade_level` to a
     * key that matches the front-end grade ids
     * (kindergarten, sped, grade_1 ... grade_12).
     */
    private function normalize_grade_key($value) {
        $value = strtolower(trim((string) $value));
        $map = array(
            'kinder'         => 'kindergarten',
            'kindergarten'   => 'kindergarten',
            'k'              => 'kindergarten',
            'sped'           => 'sped',
            'sn'             => 'sped',
            'grade 1'        => 'grade_1',
            'grade1'         => 'grade_1',
            'g1'             => 'grade_1',
            'grade 2'        => 'grade_2',
            'grade2'         => 'grade_2',
            'g2'             => 'grade_2',
            'grade 3'        => 'grade_3',
            'grade3'         => 'grade_3',
            'g3'             => 'grade_3',
            'grade 4'        => 'grade_4',
            'grade4'         => 'grade_4',
            'g4'             => 'grade_4',
            'grade 5'        => 'grade_5',
            'grade5'         => 'grade_5',
            'g5'             => 'grade_5',
            'grade 6'        => 'grade_6',
            'grade6'         => 'grade_6',
            'g6'             => 'grade_6',
            'grade 7'        => 'grade_7',
            'grade7'         => 'grade_7',
            'g7'             => 'grade_7',
            'grade 8'        => 'grade_8',
            'grade8'         => 'grade_8',
            'g8'             => 'grade_8',
            'grade 9'        => 'grade_9',
            'grade9'         => 'grade_9',
            'g9'             => 'grade_9',
            'grade 10'       => 'grade_10',
            'grade10'        => 'grade_10',
            'g10'            => 'grade_10',
            'grade 11'       => 'grade_11',
            'grade11'        => 'grade_11',
            'g11'            => 'grade_11',
            'grade 12'       => 'grade_12',
            'grade12'        => 'grade_12',
            'g12'            => 'grade_12',
        );
        if (isset($map[$value])) return $map[$value];
        // Already normalized?
        if (preg_match('/^(kindergarten|sped|grade_\d{1,2})$/', $value)) return $value;
        return null;
    }
}