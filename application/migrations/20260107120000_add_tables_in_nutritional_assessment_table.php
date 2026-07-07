<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_beneficiary_fields extends CI_Migration {

    public function up()
    {
        // Fields to add
        $fields = [
            'classification_of_beneficiary' => [
                'type' => 'ENUM',
                'constraint' => ['Primary', 'Secondary'],
                'null' => TRUE,
                'default' => NULL,
            ],
            'pregnant' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => TRUE,
                'default' => NULL,
            ],
            'with_0_1_year_old_child' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => TRUE,
                'default' => NULL,
            ],
            'dewormed' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => TRUE,
                'default' => NULL,
            ],
            'parent_consent_milk' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => TRUE,
                'default' => NULL,
            ],
            'participation_4ps' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => TRUE,
                'default' => NULL,
            ],
            'previous_sbfp' => [
                'type' => 'ENUM',
                'constraint' => ['Yes', 'No'],
                'null' => TRUE,
                'default' => NULL,
            ],
        ];

        // Check if table exists (just in case)
        if ($this->db->table_exists('nutritional_assessments')) {
            // Add each column only if it doesn't already exist
            foreach ($fields as $column => $def) {
                if (!$this->db->field_exists($column, 'nutritional_assessments')) {
                    $this->dbforge->add_column('nutritional_assessments', [$column => $def]);
                }
            }
        }
    }

    public function down()
    {
        // Remove columns (optional – rollback)
        $columns = [
            'classification_of_beneficiary',
            'pregnant',
            'with_0_1_year_old_child',
            'dewormed',
            'parent_consent_milk',
            'participation_4ps',
            'previous_sbfp'
        ];
        if ($this->db->table_exists('nutritional_assessments')) {
            foreach ($columns as $col) {
                if ($this->db->field_exists($col, 'nutritional_assessments')) {
                    $this->dbforge->drop_column('nutritional_assessments', $col);
                }
            }
        }
    }
}