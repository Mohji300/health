<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shd_reports_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('shd_reports_model');
        $this->load->model('user_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');

        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
        }
    }

    public function index()
    {
        $data = [];

        // Basic filters (extend as needed)
        $data['date_from'] = $this->input->get('date_from', TRUE);
        $data['date_to'] = $this->input->get('date_to', TRUE);

        // Fetch reports via model
        $data['reports'] = $this->shd_reports_model->get_reports_with_filters($data['date_from'], $data['date_to']);

        // Simple stats
        $data['total_reports'] = is_array($data['reports']) ? count($data['reports']) : 0;

        $this->load->view('shd_reports', $data);
    }
}
