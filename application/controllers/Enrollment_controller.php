<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Enrollment_controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Enrollment_model');
        $this->load->library('form_validation');
        $this->load->helper(array('form', 'url', 'html'));
    }

    public function index() {
        $data['enrollments'] = array();
        $this->load->view('enrollment', $data);
    }

    public function store() {
        $this->form_validation->set_rules('school_year', 'School Year', 'required|regex_match[/^\d{4}-\d{4}$/]|is_unique[enrollments.school_year]');
        $this->form_validation->set_rules('kindergarten', 'Kindergarten Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_1', 'Grade 1 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_2', 'Grade 2 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_3', 'Grade 3 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_4', 'Grade 4 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_5', 'Grade 5 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_6', 'Grade 6 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('sped', 'SPED Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_7', 'Grade 7 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_8', 'Grade 8 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_9', 'Grade 9 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_10', 'Grade 10 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_11', 'Grade 11 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_12', 'Grade 12 Students', 'required|integer|greater_than_equal_to[0]');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', validation_errors());
            $this->session->set_flashdata('old_input', $this->input->post());
            redirect('enrollment');
        } else {
            $data = array(
                'school_year' => $this->input->post('school_year'),
                'kindergarten' => $this->input->post('kindergarten'),
                'grade_1' => $this->input->post('grade_1'),
                'grade_2' => $this->input->post('grade_2'),
                'grade_3' => $this->input->post('grade_3'),
                'grade_4' => $this->input->post('grade_4'),
                'grade_5' => $this->input->post('grade_5'),
                'grade_6' => $this->input->post('grade_6'),
                'sped' => $this->input->post('sped'),
                'grade_7' => $this->input->post('grade_7'),
                'grade_8' => $this->input->post('grade_8'),
                'grade_9' => $this->input->post('grade_9'),
                'grade_10' => $this->input->post('grade_10'),
                'grade_11' => $this->input->post('grade_11'),
                'grade_12' => $this->input->post('grade_12')
            );
            
            if ($this->Enrollment_model->insert_enrollment($data)) {
                $this->session->set_flashdata('success', 'Enrollment data saved successfully!');
            } else {
                $this->session->set_flashdata('error', 'Failed to save enrollment data!');
            }
            redirect('enrollment');
        }
    }

    public function edit($id) {
        $data['enrollment'] = $this->Enrollment_model->get_enrollment_by_id($id);
        if (empty($data['enrollment'])) {
            show_404();
        }
        $this->load->view('enrollment/edit', $data);
    }

    public function update($id) {
        $this->form_validation->set_rules('school_year', 'School Year', 'required|regex_match[/^\d{4}-\d{4}$/]');
        $this->form_validation->set_rules('kindergarten', 'Kindergarten Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_1', 'Grade 1 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_2', 'Grade 2 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_3', 'Grade 3 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_4', 'Grade 4 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_5', 'Grade 5 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_6', 'Grade 6 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('sped', 'SPED Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_7', 'Grade 7 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_8', 'Grade 8 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_9', 'Grade 9 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_10', 'Grade 10 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_11', 'Grade 11 Students', 'required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('grade_12', 'Grade 12 Students', 'required|integer|greater_than_equal_to[0]');

        // Check if school_year is unique except current record
        $existing = $this->Enrollment_model->get_enrollment_by_school_year($this->input->post('school_year'));
        if ($existing && $existing['id'] != $id) {
            $this->session->set_flashdata('error', 'School year already exists!');
            redirect('enrollment/edit/'.$id);
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', validation_errors());
            redirect('enrollment/edit/'.$id);
        } else {
            $data = array(
                'school_year' => $this->input->post('school_year'),
                'kindergarten' => $this->input->post('kindergarten'),
                'grade_1' => $this->input->post('grade_1'),
                'grade_2' => $this->input->post('grade_2'),
                'grade_3' => $this->input->post('grade_3'),
                'grade_4' => $this->input->post('grade_4'),
                'grade_5' => $this->input->post('grade_5'),
                'grade_6' => $this->input->post('grade_6'),
                'sped' => $this->input->post('sped'),
                'grade_7' => $this->input->post('grade_7'),
                'grade_8' => $this->input->post('grade_8'),
                'grade_9' => $this->input->post('grade_9'),
                'grade_10' => $this->input->post('grade_10'),
                'grade_11' => $this->input->post('grade_11'),
                'grade_12' => $this->input->post('grade_12')
            );
            
            if ($this->Enrollment_model->update_enrollment($id, $data)) {
                $this->session->set_flashdata('success', 'Enrollment data updated successfully!');
            } else {
                $this->session->set_flashdata('error', 'Failed to update enrollment data!');
            }
            redirect('enrollment');
        }
    }

    public function delete($id) {
        if ($this->Enrollment_model->delete_enrollment($id)) {
            $this->session->set_flashdata('success', 'Enrollment data deleted successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete enrollment data!');
        }
        redirect('enrollment');
    }
}
?>