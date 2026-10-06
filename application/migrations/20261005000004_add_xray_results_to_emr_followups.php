<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_xray_results_to_emr_followups extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('emr_followups')
            && !$this->db->field_exists('xray_results', 'emr_followups')) {
            $this->dbforge->add_column('emr_followups', [
                'xray_results' => [
                    'type'  => 'TEXT',
                    'null'  => TRUE,
                    'after' => 'dental_findings',
                ],
            ]);
        }
    }

    public function down()
    {
        // Keep recorded clinical results during rollback.
    }
}
