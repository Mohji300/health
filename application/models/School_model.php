<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class School_model extends CI_Model {
    public function get_by_name_and_sd($name, $school_district_id){
        return $this->db->get_where('schools', [
            'name' => $name,
            'school_district_id' => $school_district_id
        ])->row();
    }

    public function get_by_name($name){
        return $this->db->get_where('schools', [
            'name' => $name
        ])->row();
    }

    public function create($name, $school_district_id, $school_id = null){
        $now = date('Y-m-d H:i:s');
        $this->db->insert('schools', [
            'name' => $name,
            'school_district_id' => $school_district_id,
            'school_id' => $school_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->db->insert_id();
    }

    public function update_school_id($id, $school_id){
        $this->db->where('id', $id)->update('schools', [
            'school_id' => (string)$school_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->affected_rows();
    }
}
