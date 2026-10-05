<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emrmedication_controller extends CI_Controller {

    private $medications = [
        ['id' => 1, 'name' => 'SITAGUPTIN 50mg + METFOUMM 500mg TAB', 'default_notes' => '1 TAB AFTER BREAKFAST'],
        ['id' => 2, 'name' => 'METFOUMM 500mg TAB', 'default_notes' => ''],
        ['id' => 3, 'name' => 'FUBUXOSTAT 40MG TAB', 'default_notes' => '1 TAB AFTER LUNCH'],
        ['id' => 4, 'name' => 'FENOFIBRATE 200MG TAB', 'default_notes' => '1 TAB AFTER DINNER'],
        ['id' => 5, 'name' => 'CERUXOXIME 500MG TAB', 'default_notes' => '1 TAB 2X A DAY FOR 7 DAYS'],
        ['id' => 6, 'name' => 'LOW FAT DIET', 'default_notes' => 'Avoid fried foods, fatty meats'],
        ['id' => 7, 'name' => 'LOW SUGAR DIET', 'default_notes' => 'Reduce sweets, sugary drinks'],
        ['id' => 8, 'name' => 'LOW PROTINE DIET', 'default_notes' => 'LIMIT ON MEAT, BEER, SEAFOODS'],
        ['id' => 9, 'name' => 'REPEAT FBS/HBA1C AFTER 1 MONTH', 'default_notes' => 'Schedule laboratory test'],
        ['id' => 10, 'name' => 'INCREASE ORAL FLUID INTAKE', 'default_notes' => 'FOR GENEXPENT – drink at least 2L water daily']
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');

        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }
    }

    public function index()
    {
        $this->output
            ->set_status_header(404)
            ->set_content_type('application/json')
            ->set_output(json_encode(['error' => 'Not found']));
    }

    /** 🔹 New endpoint: returns the full medication list as JSON */
    public function medications()
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($this->medications));
    }

    public function search()
    {
        $query = $this->input->get('q');
        if (empty($query)) {
            $this->output->set_content_type('application/json')->set_output(json_encode([]));
            return;
        }

        $results = [];
        $q = strtolower($query);
        foreach ($this->medications as $med) {
            if (stripos($med['name'], $q) !== false) {
                $results[] = $med;
            }
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($results));
    }

}