<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // Load database library
        $this->load->database();
    }

    public function index()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        echo "<h2>Manual Migration – No Library</h2>";

        // 1. Add column if missing
        if (!$this->db->field_exists('section_id', 'nutritional_assessments')) {
            echo "Adding column...<br>";
            if ($this->db->query("ALTER TABLE nutritional_assessments ADD section_id INT(11) NULL AFTER section")) {
                echo "Column added.<br>";
            } else {
                echo "Failed: " . $this->db->error()['message'] . "<br>";
                return;
            }
        } else {
            echo "Column already exists.<br>";
        }

        // 2. Backfill data (safe to re-run)
        echo "Backfilling data...<br>";
        $this->db->query("
            UPDATE nutritional_assessments na
            JOIN grade_sections gs 
                ON na.grade_level = gs.grade 
                AND na.section = gs.section 
                AND na.year = gs.year 
                AND na.legislative_district = gs.legislative_district
                AND na.school_district = gs.school_district
            SET na.section_id = gs.id
            WHERE na.section_id IS NULL
        ");
        echo "Backfill completed.<br>";

        // 3. Add index if missing
        $index_exists = $this->db->query("SHOW INDEX FROM nutritional_assessments WHERE Key_name = 'idx_section_id'")->num_rows() > 0;
        if (!$index_exists) {
            echo "Adding index...<br>";
            $this->db->query("ALTER TABLE nutritional_assessments ADD INDEX idx_section_id (section_id)");
            echo "Index added.<br>";
        } else {
            echo "Index already exists.<br>";
        }

        // 4. Record version in migrations table
        $this->ensure_version_inserted();

                // 5. Add performance indexes
        $indexes = [
            'nutritional_assessments' => [
                'idx_assessment_deleted' => ['assessment_type', 'is_deleted'],
                'idx_school'             => ['school_id'],
                'idx_grade_sex'          => ['grade_level', 'sex'],
            ],
            'schools' => [
                'idx_district' => ['school_district_id'],
            ],
            'school_districts' => [
                'idx_legislative' => ['legislative_district_id'],
            ],
        ];

        foreach ($indexes as $table => $indices) {
            foreach ($indices as $idx_name => $columns) {
                // Check if index already exists
                $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$idx_name}'")->num_rows();
                if ($exists > 0) {
                    echo "Index '{$idx_name}' already exists on {$table}. Skipping.<br>";
                    continue;
                }
                $col_list = implode('`, `', $columns);
                $sql = "ALTER TABLE `{$table}` ADD INDEX `{$idx_name}` (`{$col_list}`)";
                $this->db->query($sql);
                echo "Added index '{$idx_name}' on {$table}.<br>";
            }
        }
    }

    private function ensure_version_inserted()
    {
        $version = 20250630120000;
        $query = $this->db->query("SELECT * FROM migrations WHERE version = $version");
        if ($query->num_rows() == 0) {
            $this->db->query("INSERT INTO migrations (version) VALUES ($version)");
            echo "Version $version recorded.<br>";
        } else {
            echo "Version already recorded.<br>";
        }
    }
}