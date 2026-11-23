<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('user_model');
        $this->load->helper('url');

        if (!$this->session->userdata('logged_in')) {
            redirect('login');
        }
    }

    public function index()
    {
        $user_id = $this->session->userdata('user_id');
        $user = $this->user_model->get_user_by_id($user_id);

        $data = [
            'user' => $user,
            'title' => 'My Profile',
            'error' => $this->session->flashdata('error')
        ];

        $this->load->view('profile', $data);
    }

    public function update()
    {
        $this->session->set_flashdata('error', 'Profile information is read-only. Contact an administrator to request a change.');
        redirect('profile');
    }
}
