<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Legislative_district_model extends CI_Model {
    
    public function get_by_name($name){
        return $this->db->get_where('legislative_districts', ['name' => $name])->row();
    }

    public function create($name){
        $now = date('Y-m-d H:i:s');
        $this->db->insert('legislative_districts', [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->db->insert_id();
    }

    /**
     * Get all legislative districts
     */
    public function get_districts_with_school_districts() {
        $this->db->select('*');
        $this->db->from('legislative_districts');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

/**
 * Get school districts by legislative district name
 */
public function get_school_districts_by_legislative($legislative_district) {
    try {
        // First, we need to get the legislative district ID from the name
        $this->db->select('id');
        $this->db->from('legislative_districts');
        $this->db->where('name', $legislative_district);
        $legislative_query = $this->db->get();
        $legislative_row = $legislative_query->row();
        
        if (!$legislative_row) {
            return []; // No legislative district found with that name
        }
        
        $legislative_district_id = $legislative_row->id;
        
        // Now get school districts using the legislative_district_id
        $this->db->select('*');
        $this->db->from('school_districts');
        $this->db->where('legislative_district_id', $legislative_district_id);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get();
        
        return $query->result();
        
    } catch (Exception $e) {
        log_message('error', 'Model error in get_school_districts_by_legislative: ' . $e->getMessage());
        return [];
    }
}

    /**
     * Get schools by school district name
     */
    public function get_schools_by_district($school_district) {
        $this->db->select('*');
        $this->db->from('schools');
        $this->db->where('school_district', $school_district);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get();
        return $query->result();
    }
}