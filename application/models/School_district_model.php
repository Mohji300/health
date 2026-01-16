<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class School_district_model extends CI_Model {
    public function get_by_name_and_ld($name, $legislative_district_id){
        return $this->db->get_where('school_districts', [
            'name' => $name,
            'legislative_district_id' => $legislative_district_id
        ])->row();
    }

    public function create($name, $legislative_district_id){
        $now = date('Y-m-d H:i:s');
        $this->db->insert('school_districts', [
            'name' => $name,
            'legislative_district_id' => $legislative_district_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->db->insert_id();
    }
}
