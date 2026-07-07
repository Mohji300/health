<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function index()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        echo "<h2>Manual Migration – No Library</h2>";

        // // ---------- 1. Add 'section_id' column ----------
        // if (!$this->db->field_exists('section_id', 'nutritional_assessments')) {
        //     echo "Adding column 'section_id'...<br>";
        //     if ($this->db->query("ALTER TABLE nutritional_assessments ADD section_id INT(11) NULL AFTER section")) {
        //         echo "Column 'section_id' added.<br>";
        //     } else {
        //         echo "Failed: " . $this->db->error()['message'] . "<br>";
        //         return;
        //     }
        // } else {
        //     echo "Column 'section_id' already exists.<br>";
        // }

        // ---------- 2. Add beneficiary flag columns ----------
        $beneficiary_fields = [
            'classification_of_beneficiary' => "ENUM('Primary','Secondary') NULL DEFAULT NULL",
            'pregnant'                      => "ENUM('Yes','No') NULL DEFAULT NULL",
            'with_0_1_year_old_child'       => "ENUM('Yes','No') NULL DEFAULT NULL",
            'dewormed'                      => "ENUM('Yes','No') NULL DEFAULT NULL",
            'parent_consent_milk'           => "ENUM('Yes','No') NULL DEFAULT NULL",
            'participation_4ps'             => "ENUM('Yes','No') NULL DEFAULT NULL",
            'previous_sbfp'                 => "ENUM('Yes','No') NULL DEFAULT NULL",
        ];

        foreach ($beneficiary_fields as $column => $definition) {
            if (!$this->db->field_exists($column, 'nutritional_assessments')) {
                echo "Adding column '$column'...<br>";
                $sql = "ALTER TABLE nutritional_assessments ADD $column $definition";
                if ($this->db->query($sql)) {
                    echo "Column '$column' added.<br>";
                } else {
                    echo "Failed to add '$column': " . $this->db->error()['message'] . "<br>";
                }
            } else {
                echo "Column '$column' already exists.<br>";
            }
        }

        // // ---------- 3. Backfill section_id (safe to re-run) ----------
        // echo "Backfilling 'section_id'...<br>";
        // $this->db->query("
        //     UPDATE nutritional_assessments na
        //     JOIN grade_sections gs 
        //         ON na.grade_level = gs.grade 
        //         AND na.section = gs.section 
        //         AND na.year = gs.year 
        //         AND na.legislative_district = gs.legislative_district
        //         AND na.school_district = gs.school_district
        //     SET na.section_id = gs.id
        //     WHERE na.section_id IS NULL
        // ");
        // echo "Backfill completed.<br>";

        // // ---------- 4. Add indexes if missing ----------
        // $indexes = [
        //     'nutritional_assessments' => [
        //         'idx_section_id'        => ['section_id'],
        //         'idx_assessment_deleted'=> ['assessment_type', 'is_deleted'],
        //         'idx_school'            => ['school_id'],
        //         'idx_grade_sex'         => ['grade_level', 'sex'],
        //         // Optional – you may add indexes on beneficiary columns if needed
        //         // 'idx_classification' => ['classification_of_beneficiary'],
        //     ],
        //     'schools' => [
        //         'idx_district' => ['school_district_id'],
        //     ],
        //     'school_districts' => [
        //         'idx_legislative' => ['legislative_district_id'],
        //     ],
        // ];

        // foreach ($indexes as $table => $indices) {
        //     foreach ($indices as $idx_name => $columns) {
        //         $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$idx_name}'")->num_rows();
        //         if ($exists > 0) {
        //             echo "Index '{$idx_name}' already exists on {$table}. Skipping.<br>";
        //             continue;
        //         }
        //         $col_list = implode('`, `', $columns);
        //         $sql = "ALTER TABLE `{$table}` ADD INDEX `{$idx_name}` (`{$col_list}`)";
        //         $this->db->query($sql);
        //         echo "Added index '{$idx_name}' on {$table}.<br>";
        //     }
        // }

        // ---------- 5. Record migration version ----------
        $this->ensure_version_inserted();
    }

    private function ensure_version_inserted()
    {
        $version = 20260107120000; // Update this timestamp if you want a new version
        $query = $this->db->query("SELECT * FROM migrations WHERE version = $version");
        if ($query->num_rows() == 0) {
            $this->db->query("INSERT INTO migrations (version) VALUES ($version)");
            echo "Version $version recorded.<br>";
        } else {
            echo "Version already recorded.<br>";
        }
    }
}