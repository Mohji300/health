<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Seeders extends CI_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->model('Legislative_district_model');
        $this->load->model('School_district_model');
        $this->load->model('School_model');
    }

    private function ensure_cli_or_confirm(){
        if (!$this->input->is_cli_request()){
            $confirm = $this->input->get_post('confirm');
            if ($confirm !== 'yes'){
                echo "Web runs require passing `confirm=yes` (GET or POST). Or run via CLI.\n";
                return false;
            }
        }
        return true;
    }

    /**
     * Insert Masbate districts -> school districts -> schools
     * Usage (CLI): php index.php seeders masbate
     * Usage (web): /seeders/masbate?confirm=yes
     * Requires `application/data/masbate_districts.php` which must set $data = [ ... ];
     */
    public function masbate(){
        if (!$this->ensure_cli_or_confirm()) return;

        // CORRECTED PATH - using APPPATH which points to application/ directory
        $data_file = APPPATH . 'data/masbate_districts.php';
        if (!file_exists($data_file)){
            echo "Data file not found: {$data_file}\n";
            return;
        }

        include $data_file; // expects $data array
        if (!isset($data) || !is_array($data)){
            echo "Data file must define \$data as an array.\n";
            return;
        }

        $this->db->trans_start();

        $counts = ['legislative_districts' => 0, 'school_districts' => 0, 'schools' => 0];

        foreach ($data as $ldName => $schoolDistricts){
            $ld = $this->Legislative_district_model->get_by_name($ldName);
            if (!$ld){
                $ld_id = $this->Legislative_district_model->create($ldName);
                $counts['legislative_districts']++;
            } else {
                $ld_id = $ld->id;
            }

            foreach ($schoolDistricts as $sdName => $schools){
                $sd = $this->School_district_model->get_by_name_and_ld($sdName, $ld_id);
                if (!$sd){
                    $sd_id = $this->School_district_model->create($sdName, $ld_id);
                    $counts['school_districts']++;
                } else {
                    $sd_id = $sd->id;
                }

                foreach ($schools as $schoolName){
                    $s = $this->School_model->get_by_name_and_sd($schoolName, $sd_id);
                    if (!$s){
                        $this->School_model->create($schoolName, $sd_id);
                        $counts['schools']++;
                    }
                }
            }
        }

        $this->db->trans_complete();

        echo "Done. Created: " . json_encode($counts) . "\n";
    }

    /**
     * Update school `school_id` values using mapping
     * Usage (CLI): php index.php seeders update_school_ids
     * Usage (web): /seeders/update_school_ids?confirm=yes
     * Requires `application/data/update_school_ids.php` which must set $schoolMappings = [ 'SCHOOL NAME' => 'SCHOOL_ID', ... ];
     */
    public function update_school_ids(){
        if (!$this->ensure_cli_or_confirm()) return;

        // CORRECTED PATH - using APPPATH which points to application/ directory
        $data_file = APPPATH . 'data/update_school_ids.php';
        if (!file_exists($data_file)){
            echo "Data file not found: {$data_file}\n";
            return;
        }

        include $data_file; // expects $schoolMappings
        if (!isset($schoolMappings) || !is_array($schoolMappings)){
            echo "Data file must define \$schoolMappings as an array.\n";
            return;
        }

        $updated = 0; $notFound = 0;
        foreach ($schoolMappings as $schoolName => $schoolId){
            $school = $this->School_model->get_by_name($schoolName);
            if ($school){
                $this->School_model->update_school_id($school->id, $schoolId);
                $updated++;
            } else {
                $notFound++;
                echo "Not found: {$schoolName}\n";
            }
        }

        echo "Updated: {$updated}. Not found: {$notFound}.\n";
    }
}