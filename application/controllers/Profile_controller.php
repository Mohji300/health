<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model');
        $this->load->library('form_validation');
        $this->load->helper('url');

        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }
    }

    public function index()
    {
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);

        $data = [
            'user' => $user,
            'title' => 'My Profile',
            'user_role' => $this->session->userdata('role'),
            'errors' => $this->session->flashdata('errors'),
            'success' => $this->session->flashdata('success'),
            'input_data' => $this->session->flashdata('input_data')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('profile', $data);
        $this->load->view('templates/footer');
    }

    public function update()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[255]');
        $this->form_validation->set_rules('school_id', 'ID Number', 'required|max_length[100]');
        $this->form_validation->set_rules('address', 'Address', 'required|max_length[500]');
        $this->form_validation->set_rules('level', 'Level/Type', 'required|max_length[255]');
        $this->form_validation->set_rules('head_name', 'Head/Officer Name', 'required|max_length[255]');

        $user_id = $this->session->userdata('user_id');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', $this->form_validation->error_array());
            $this->session->set_flashdata('input_data', $this->input->post());
            redirect('profile');
            return;
        }

        $update_data = [
            'name' => $this->input->post('name'),
            'school_id' => $this->input->post('school_id'),
            'school_address' => $this->input->post('address'),
            'school_level' => $this->input->post('level'),
            'school_head_name' => $this->input->post('head_name'),
            'school_district' => $this->input->post('SchoolDistricts') ?: $this->input->post('school_district')
        ];

        $success = $this->User_model->update_user($user_id, $update_data);

        if ($success) {
            $this->session->set_userdata([
                'name' => $update_data['name'],
                'school_id' => $update_data['school_id'],
                'school_info_completed' => true,
                'school_district' => $update_data['school_district'],
                'legislative_district' => $this->input->post('legislativeDistricts') ?: $this->session->userdata('legislative_district')
            ]);

            $this->session->set_flashdata('success', 'Profile updated successfully');
        } else {
            $this->session->set_flashdata('error', 'Failed to update profile');
        }

        redirect('profile');
    }
}
