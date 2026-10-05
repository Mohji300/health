<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emr_controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index()
    {
        // Require login to view EMR UI
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        // The view is a self-contained UI page
        $this->load->view('emr_view');
    }

    public function patients()
    {
        // Require login – already handled in __construct
        // Load the patients view
        $this->load->view('emr_patients_view');
    }

}
