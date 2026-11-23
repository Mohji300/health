<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_school_id_to_nutritional_assessments extends CI_Migration
{
    public function up()
    {
        if (
            $this->db->table_exists('nutritional_assessments')
            && !$this->db->field_exists('school_id', 'nutritional_assessments')
        ) {
            $this->dbforge->add_column('nutritional_assessments', [
                'school_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                    'default'    => NULL,
                    'after'      => 'id'
                ]
            ]);
        }
    }

    public function down()
    {
        if (
            $this->db->table_exists('nutritional_assessments')
            && $this->db->field_exists('school_id', 'nutritional_assessments')
        ) {
            $this->dbforge->drop_column('nutritional_assessments', 'school_id');
        }
    }
}