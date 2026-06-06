<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Enrollment_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // $this->load->model('Enrollment_model');   // <-- comment for now
        $this->load->library('form_validation');
        $this->load->helper(array('form', 'url', 'html'));
    }

    //  public function get_all_enrollments() {
    //        $this->db->order_by('school_year', 'DESC');
    //        return $this->db->get($this->table)->result_array();
    //    }

    //    public function get_enrollment_by_id($id) {
    //        return $this->db->get_where($this->table, array('id' => $id))->row_array();
    //    }

    //    public function get_enrollment_by_school_year($school_year) {
    //        return $this->db->get_where($this->table, array('school_year' => $school_year))->row_array();
    //    }

    //    public function insert_enrollment($data) {
    //        return $this->db->insert($this->table, $data);
    //    }

    //    public function update_enrollment($id, $data) {
    //        $this->db->where('id', $id);
    //        return $this->db->update($this->table, $data);
    //    }

    //    public function delete_enrollment($id) {
    //        $this->db->where('id', $id);
    //        return $this->db->delete($this->table);
    //    }
}
?>