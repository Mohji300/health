<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Enrollment_controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Enrollment_model');
        $this->load->model('nutritional_model');
        $this->load->library('form_validation');
        $this->load->helper(array('form', 'url', 'html'));
    }

    public function index() {
        $user_id = $this->get_current_user_id();
        $school = $this->get_current_school_identity($user_id);
        $assessment_type = $this->get_current_assessment_type();
        $school_level = $this->Enrollment_model->get_user_school_level($user_id);
        $dashboard_school_level = $this->get_dashboard_school_level($school_level);
        $school_year = $this->input->get('school_year');
        $enrollment = null;

        if (!empty($school_year)) {
            $enrollment = $this->Enrollment_model->get_enrollment_by_school_year($school_year);
        } else {
            $school_year = $this->Enrollment_model->get_latest_uploaded_school_year(
                $school['school_id'],
                $school['school_name']
            );
            if (empty($school_year)) {
                $all = $this->Enrollment_model->get_all_enrollments();
                if (!empty($all)) {
                    $enrollment = $all[0];
                    $school_year = $enrollment['school_year'];
                }
            } else {
                $enrollment = $this->Enrollment_model->get_enrollment_by_school_year($school_year);
            }
        }

        $uploaded_summary = $this->get_dashboard_uploaded_summary(
            $assessment_type,
            $dashboard_school_level,
            $school['school_id'],
            $school['school_name']
        );
        $data['school_year']        = $school_year;
        $data['assessment_type']    = $assessment_type;
        $data['enrollment']         = $enrollment;
        $data['uploaded_student_summary'] = $uploaded_summary;
        $data['nutritional_counts'] = array();
        foreach ($uploaded_summary['grades'] as $grade => $counts) {
            $data['nutritional_counts'][$grade] = $counts['total'];
        }

        // School level from users.school_level; fallback to inference
        $data['school_type'] = !empty($school_level)
            ? $school_level
            : $this->detect_school_type($enrollment, $data['nutritional_counts']);

        $this->load->view('enrollment', $data);
    }

    public function nutritional_counts() {
        $user_id = $this->get_current_user_id();
        $school = $this->get_current_school_identity($user_id);
        $school_level = $this->Enrollment_model->get_user_school_level($user_id);
        $dashboard_school_level = $this->get_dashboard_school_level($school_level);
        $assessment_type = $this->get_current_assessment_type();
        $uploaded_summary = $this->get_dashboard_uploaded_summary(
            $assessment_type,
            $dashboard_school_level,
            $school['school_id'],
            $school['school_name']
        );
        $counts = array();
        foreach ($uploaded_summary['grades'] as $grade => $grade_counts) {
            $counts[$grade] = $grade_counts['total'];
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'assessment_type' => $assessment_type,
                'counts'      => $counts,
                'uploaded_summary' => $uploaded_summary,
            )));
    }

    private function get_dashboard_uploaded_summary($assessment_type, $school_level, $school_id, $school_name) {
        $result = $this->nutritional_model->get_processed_data(
            $assessment_type,
            $school_name,
            $school_level,
            $school_id
        );

        $grade_map = array(
            'Kinder' => 'kindergarten',
            'SPED' => 'sped',
            'Grade 1' => 'grade_1',
            'Grade 2' => 'grade_2',
            'Grade 3' => 'grade_3',
            'Grade 4' => 'grade_4',
            'Grade 5' => 'grade_5',
            'Grade 6' => 'grade_6',
            'Grade 7' => 'grade_7',
            'Grade 8' => 'grade_8',
            'Grade 9' => 'grade_9',
            'Grade 10' => 'grade_10',
            'Grade 11' => 'grade_11',
            'Grade 12' => 'grade_12'
        );
        $summary = array('total' => 0, 'male' => 0, 'female' => 0, 'grades' => array());

        foreach ($grade_map as $model_grade => $grade_id) {
            $grade_data = $result['nutritionalData'];
            $male = isset($grade_data[$model_grade . '_m']['enrolment'])
                ? (int) $grade_data[$model_grade . '_m']['enrolment']
                : 0;
            $female = isset($grade_data[$model_grade . '_f']['enrolment'])
                ? (int) $grade_data[$model_grade . '_f']['enrolment']
                : 0;
            $total = $male + $female;

            $summary['grades'][$grade_id] = array(
                'total' => $total,
                'male' => $male,
                'female' => $female
            );
            $summary['total'] += $total;
            $summary['male'] += $male;
            $summary['female'] += $female;
        }

        return $summary;
    }

    private function get_current_school_identity($user_id) {
        $user_school = $this->Enrollment_model->get_user_school_info($user_id);
        $school_id = $this->session->userdata('school_id');
        if (empty($school_id) && !empty($user_school['school_id'])) {
            $school_id = $user_school['school_id'];
            $this->session->set_userdata('school_id', $school_id);
        }

        return array(
            'school_id' => $school_id,
            'school_name' => !empty($school_id)
                ? null
                : ($this->session->userdata('school_name') ?: ($user_school['name'] ?? null)),
        );
    }

    private function get_current_assessment_type() {
        $assessment_type = $this->input->get('assessment_type')
            ?: ($this->session->userdata('assessment_type') ?: 'baseline');
        if (!in_array($assessment_type, array('baseline', 'midline', 'endline'), true)) {
            $assessment_type = 'baseline';
        }
        $this->session->set_userdata('assessment_type', $assessment_type);
        return $assessment_type;
    }

    private function get_dashboard_school_level($user_school_level) {
        $requested_level = $this->input->get('school_level');
        if (!empty($requested_level)) {
            $this->session->set_userdata('school_level', $requested_level);
            return $requested_level;
        }

        $session_level = $this->session->userdata('school_level');
        if (!empty($session_level) && $session_level !== 'all') {
            return $session_level;
        }

        $level = strtolower(trim((string) $user_school_level));
        if (!in_array($level, array('elementary', 'secondary', 'integrated'), true)) {
            if (in_array($level, array('stand alone shs', 'standalone_shs', 'shs', 'senior high school'), true)) {
                $level = 'stand alone shs';
            } else {
                $level = 'all';
            }
        }
        $this->session->set_userdata('school_level', $level);
        return $level;
    }

    private function detect_school_type($enrollment, $nutritional_counts = array()) {
        $elementary = array('kindergarten','sped','grade_1','grade_2','grade_3',
                            'grade_4','grade_5','grade_6');
        $secondary  = array('grade_7','grade_8','grade_9','grade_10',
                            'grade_11','grade_12');

        $active = array();
        foreach (array_merge($elementary, $secondary) as $g) {
            $enroll_val = $enrollment && isset($enrollment[$g]) ? (int) $enrollment[$g] : 0;
            $nutri_val  = isset($nutritional_counts[$g]) ? (int) $nutritional_counts[$g] : 0;
            if ($enroll_val > 0 || $nutri_val > 0) $active[] = $g;
        }

        if (empty($active)) return null;
        if (count($active) === 1) return 'Stand-Alone';

        $has_el = count(array_intersect($active, $elementary)) > 0;
        $has_se = count(array_intersect($active, $secondary))  > 0;

        if ($has_el && $has_se) return 'Integrated';
        if ($has_el)             return 'Elementary';
        if ($has_se)             return 'Secondary';
        return null;
    }

    public function store() {
        $this->form_validation->set_rules('school_year', 'School Year',
            'required|regex_match[/^\d{4}-\d{4}$/]|is_unique[enrollments.school_year]');

        $grades = array('kindergarten','sped','grade_1','grade_2','grade_3','grade_4',
                        'grade_5','grade_6','grade_7','grade_8','grade_9','grade_10',
                        'grade_11','grade_12');
        foreach ($grades as $g) {
            $this->form_validation->set_rules($g, $g,
                'required|integer|greater_than_equal_to[0]');
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', validation_errors());
            $this->session->set_flashdata('old_input', $this->input->post());
            redirect('enrollment');
        }

        $data = array('school_year' => $this->input->post('school_year'));
        foreach ($grades as $g) {
            $data[$g] = (int) $this->input->post($g);
        }

        if ($this->Enrollment_model->insert_enrollment($data)) {
            $this->session->set_flashdata('success', 'Enrollment data saved successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to save enrollment data!');
        }
        redirect('enrollment?school_year=' . urlencode($data['school_year']));
    }

    public function edit($id) {
        $data['enrollment'] = $this->Enrollment_model->get_enrollment_by_id($id);
        if (empty($data['enrollment'])) show_404();
        $this->load->view('enrollment/edit', $data);
    }

    public function update($id) {
        $grades = array('kindergarten','sped','grade_1','grade_2','grade_3','grade_4',
                        'grade_5','grade_6','grade_7','grade_8','grade_9','grade_10',
                        'grade_11','grade_12');

        $this->form_validation->set_rules('school_year', 'School Year',
            'required|regex_match[/^\d{4}-\d{4}$/]');
        foreach ($grades as $g) {
            $this->form_validation->set_rules($g, $g,
                'required|integer|greater_than_equal_to[0]');
        }

        $existing = $this->Enrollment_model->get_enrollment_by_school_year(
            $this->input->post('school_year'));
        if ($existing && $existing['id'] != $id) {
            $this->session->set_flashdata('error', 'School year already exists!');
            redirect('enrollment/edit/'.$id);
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', validation_errors());
            redirect('enrollment/edit/'.$id);
        }

        $data = array('school_year' => $this->input->post('school_year'));
        foreach ($grades as $g) $data[$g] = (int) $this->input->post($g);

        if ($this->Enrollment_model->update_enrollment($id, $data)) {
            $this->session->set_flashdata('success', 'Enrollment data updated successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to update enrollment data!');
        }
        redirect('enrollment?school_year=' . urlencode($data['school_year']));
    }

    public function delete($id) {
        if ($this->Enrollment_model->delete_enrollment($id)) {
            $this->session->set_flashdata('success', 'Enrollment data deleted successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete enrollment data!');
        }
        redirect('enrollment');
    }

    /**
     * Resolve the current user's id from the session.
     */
    private function get_current_user_id() {
        foreach (array('user_id', 'id', 'userid', 'auth_user_id') as $key) {
            $val = $this->session->userdata($key);
            if (!empty($val)) {
                return $val;
            }
        }

        $user = $this->session->userdata('user');
        if (is_array($user)  && !empty($user['id'])) return $user['id'];
        if (is_object($user) && !empty($user->id))   return $user->id;

        return null;
    }
}