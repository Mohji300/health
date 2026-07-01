<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_performance_indexes extends CI_Migration {

    public function up() {
        // Define indexes per table: [table] => [index_name => [column1, column2, ...]]
        $indexes = [
            'nutritional_assessments' => [
                'idx_assessment_deleted' => ['assessment_type', 'is_deleted'],
                'idx_school_id'          => ['school_id'],
                // 'grade_level' already has a key (MUL), but we could add a composite with sex if needed:
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
            foreach ($indices as $index_name => $columns) {
                // Check if index already exists
                $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index_name}'")->num_rows();
                if ($exists > 0) {
                    echo "Index '{$index_name}' already exists on table '{$table}'. Skipping.\n";
                    continue;
                }

                // Build ALTER TABLE statement
                $column_list = implode('`, `', $columns);
                $sql = "ALTER TABLE `{$table}` ADD INDEX `{$index_name}` (`{$column_list}`)";
                $this->db->query($sql);
                echo "Added index '{$index_name}' on table '{$table}'.\n";
            }
        }
    }

    public function down() {
        // Drop the indexes (optional rollback)
        $indexes = [
            'nutritional_assessments' => ['idx_assessment_deleted', 'idx_school_id', 'idx_grade_sex'],
            'schools'                 => ['idx_district'],
            'school_districts'        => ['idx_legislative'],
        ];

        foreach ($indexes as $table => $indices) {
            foreach ($indices as $index_name) {
                $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index_name}'")->num_rows();
                if ($exists == 0) {
                    echo "Index '{$index_name}' does not exist on table '{$table}'. Skipping.\n";
                    continue;
                }
                $sql = "ALTER TABLE `{$table}` DROP INDEX `{$index_name}`";
                $this->db->query($sql);
                echo "Dropped index '{$index_name}' from table '{$table}'.\n";
            }
        }
    }
}