<!-- 20260923000002_update_emr_patient_and_vital_fields -->
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update_emr_patient_and_vital_fields extends CI_Migration {

    public function up()
    {
        // ==========================================================
        // 1) emr_patients : replace `dob` with `age`
        // ==========================================================
        if ($this->db->field_exists('dob', 'emr_patients')) {

            // Add age column first
            if (!$this->db->field_exists('age', 'emr_patients')) {
                $this->dbforge->add_column('emr_patients', [
                    'age' => [
                        'type'       => 'TINYINT',
                        'constraint' => 3,
                        'unsigned'   => TRUE,
                        'null'       => TRUE,
                        'after'      => 'gender',
                    ],
                ]);
            }

            // Populate age from the existing DOB before removing it
            $this->db->query("
                UPDATE `emr_patients`
                SET `age` = TIMESTAMPDIFF(YEAR, `dob`, CURDATE())
                WHERE `dob` IS NOT NULL
                  AND `dob` <> '0000-00-00'
            ");

            // Drop dob — age has already been preserved above
            $this->dbforge->drop_column('emr_patients', 'dob');

        } elseif (!$this->db->field_exists('age', 'emr_patients')) {
            // No dob to migrate, just ensure age exists
            $this->dbforge->add_column('emr_patients', [
                'age' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                    'after'      => 'gender',
                ],
            ]);
        }

        // ==========================================================
        // 2) emr_followups : add BP / HR / SpO2 / RR / Temp
        // ==========================================================
        $newVitals = [];

        if (!$this->db->field_exists('bp', 'emr_followups')) {
            $newVitals['bp'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => TRUE,
                'after'      => 'followup_date',
            ];
        }
        if (!$this->db->field_exists('hr', 'emr_followups')) {
            $newVitals['hr'] = [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => TRUE,
                'null'       => TRUE,
                'after'      => 'bp',
            ];
        }
        if (!$this->db->field_exists('spo2', 'emr_followups')) {
            $newVitals['spo2'] = [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'unsigned'   => TRUE,
                'null'       => TRUE,
                'after'      => 'hr',
            ];
        }
        if (!$this->db->field_exists('rr', 'emr_followups')) {
            $newVitals['rr'] = [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => TRUE,
                'null'       => TRUE,
                'after'      => 'spo2',
            ];
        }
        if (!$this->db->field_exists('temp', 'emr_followups')) {
            $newVitals['temp'] = [
                'type'       => 'DECIMAL',
                'constraint' => '4,1',
                'null'       => TRUE,
                'after'      => 'rr',
            ];
        }

        if (!empty($newVitals)) {
            $this->dbforge->add_column('emr_followups', $newVitals);
        }

        // ==========================================================
        // 3) emr_followups : drop old height / weight / bmi
        //    (run AFTER the new vitals were added successfully)
        // ==========================================================
        $oldCols = [];
        foreach (['height', 'weight', 'bmi', 'bmi_category'] as $col) {
            if ($this->db->field_exists($col, 'emr_followups')) {
                $oldCols[] = $col;
            }
        }
        if (!empty($oldCols)) {
            $this->dbforge->drop_column('emr_followups', $oldCols);
        }

        // ==========================================================
        // 4) Lab JSON column — NO schema change required.
        //    `lab` is already JSON, so it can store the new shape
        //    ({cbc:[{parameter,result}], blood_chemistry:{analyses:[]},
        //      urinalysis:[{parameter,result}]}) with no DDL change.
        // ==========================================================
    }

    public function down()
    {
        // ---- Restore dob on emr_patients ----
        if (!$this->db->field_exists('dob', 'emr_patients')) {
            $this->dbforge->add_column('emr_patients', [
                'dob' => [
                    'type'  => 'DATE',
                    'null'  => TRUE,
                    'after' => 'gender',
                ],
            ]);
        }
        if ($this->db->field_exists('age', 'emr_patients')) {
            $this->dbforge->drop_column('emr_patients', 'age');
        }
        // WARNING: exact DOB cannot be reconstructed from age.

        // ---- Restore height / weight / bmi on emr_followups ----
        $restore = [];
        if (!$this->db->field_exists('height', 'emr_followups')) {
            $restore['height'] = ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => TRUE];
        }
        if (!$this->db->field_exists('weight', 'emr_followups')) {
            $restore['weight'] = ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => TRUE];
        }
        if (!$this->db->field_exists('bmi', 'emr_followups')) {
            $restore['bmi'] = ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => TRUE];
        }
        if (!$this->db->field_exists('bmi_category', 'emr_followups')) {
            $restore['bmi_category'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => TRUE];
        }
        if (!empty($restore)) {
            $this->dbforge->add_column('emr_followups', $restore);
        }
        // WARNING: previous height/weight/bmi values cannot be recovered.

        // ---- Drop new vitals ----
        $dropCols = [];
        foreach (['bp', 'hr', 'spo2', 'rr', 'temp'] as $col) {
            if ($this->db->field_exists($col, 'emr_followups')) {
                $dropCols[] = $col;
            }
        }
        if (!empty($dropCols)) {
            $this->dbforge->drop_column('emr_followups', $dropCols);
        }
    }
}