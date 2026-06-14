<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shd_reports_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Create a new school report (empty record)
     */
    public function create_school_report($data)
    {
        $this->db->insert('school_reports', [
            'school_name' => $data['school_name'],
            'school_year' => $data['school_year'],
            'report_data' => null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->insert_id();
    }

    /**
     * Get one report by ID
     */
    public function get_report($id)
    {
        return $this->db->get_where('school_reports', ['id' => $id])->row();
    }

    /**
     * Update the entire report_data JSON
     */
    public function update_report_data($id, $json_data)
    {
        $this->db->where('id', $id);
        return $this->db->update('school_reports', [
            'report_data' => $json_data,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * List all reports (for main listing)
     */
    public function list_all_reports()
    {
        return $this->db->order_by('created_at', 'DESC')->get('school_reports')->result();
    }
}