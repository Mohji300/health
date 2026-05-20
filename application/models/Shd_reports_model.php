<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shd_reports_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Return reports matching optional date range filters.
     * Currently returns an empty array if no matching table/data exists.
     */
    public function get_reports_with_filters($date_from = null, $date_to = null)
    {
        // Placeholder implementation - adapt to actual DB schema
        // If the assessments table is not present, return an empty array
        // so the frontend can render without database connectivity.
        try {
            if (!$this->db->table_exists('assessments')) {
                return [];
            }

            $this->db->from('assessments');
            if (!empty($date_from)) {
                $this->db->where('date_of_weighing >=', $date_from);
            }
            if (!empty($date_to)) {
                $this->db->where('date_of_weighing <=', $date_to);
            }
            $this->db->limit(1000);
            $query = $this->db->get();
            return $query->result();
        } catch (Exception $e) {
            log_message('error', 'Shd_reports_model:get_reports_with_filters error: '.$e->getMessage());
            return [];
        }
    }
}
