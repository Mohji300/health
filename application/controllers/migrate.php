<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller
{
    /**
     * Keep versions in ascending order.
     * Each migration should be safe to rerun after a partial failure.
     */
    private $versions = [
        20261001000003 => 'add_impression_norm',
        20261005000004 => 'add_xray_results',
        // This version matches the registered migration key below.
        20261021000005 => 'add_grade_sections_school_id',
        20261021000006 => 'add_nutritional_assessments_user_id',
        20261028000007 => 'add_nutritional_assessments_is_deleted_index',
        20261101000008 => 'create_enrollments_table',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
    }

    public function index()
    {
        // Do not enable display_errors in production.
        echo "<h2>EMR Migration</h2>";
        echo "<pre style='font-family: monospace; line-height: 1.4;'>";

        $applied = $this->applied_versions();

        // false means bookkeeping failed; an empty array means no versions yet.
        if ($applied === false) {
            echo "[fail] Could not read migration history. No migrations were run.\n";
            echo "</pre>";
            return;
        }

        $ran = 0;

        foreach ($this->versions as $version => $method) {
            if (in_array((string) $version, $applied, true)) {
                echo "[skip] {$version} already applied\n";
                continue;
            }

            echo "[run]  {$version} ({$method})\n";

            /*
             * Do not rely on transactions to roll back ALTER TABLE / CREATE TABLE:
             * MySQL may implicitly commit DDL. Each migration checks its own
             * operations and is designed to be rerunnable after partial failure.
             */
            $migration_succeeded = $this->{$method}();

            if ($migration_succeeded !== true) {
                echo "[fail] {$version} was not recorded as applied.\n";
                echo "       Inspect the schema before rerunning this controller.\n";
                echo "</pre>";
                return;
            }

            if (!$this->record_version($version)) {
                echo "[fail] Migration {$version} ran, but its version could not be recorded.\n";
                echo "       Inspect the schema and migrations table before rerunning.\n";
                echo "</pre>";
                return;
            }

            echo "[ok]   {$version} applied\n\n";
            $applied[] = (string) $version;
            $ran++;
        }

        echo "Done. Applied {$ran} migration(s).\n";
        echo "</pre>";
    }

    // --------------------------------------------------------------
    // Version bookkeeping
    // --------------------------------------------------------------
    private function applied_versions()
    {
        if (!$this->db->table_exists('migrations')) {
            $created = $this->db->query("
                CREATE TABLE `migrations` (
                    `version` BIGINT NOT NULL PRIMARY KEY,
                    `applied_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            if ($created === false) {
                echo "[error] Could not create migrations table: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }

            return [];
        }

        $query = $this->db->select('version')->get('migrations');

        if ($query === false) {
            echo "[error] Could not read migrations table: "
                . $this->db->error()['message'] . "\n";
            return false;
        }

        return array_map(
            function ($row) {
                return (string) $row->version;
            },
            $query->result()
        );
    }

    private function record_version($version)
    {
        return $this->db->insert('migrations', ['version' => $version]);
    }

    // --------------------------------------------------------------
    // 20261001000003 — emr_followups.impression_norm
    // --------------------------------------------------------------
    private function add_impression_norm()
    {
        $table = 'emr_followups';

        if (!$this->db->table_exists($table)) {
            echo "  ! {$table} not found\n";
            return false;
        }

        if (!$this->column_exists($table, 'impression')) {
            echo "  ! {$table}.impression not found; cannot position impression_norm\n";
            return false;
        }

        if (!$this->column_exists($table, 'impression_norm')) {
            if (!$this->db->query("
                ALTER TABLE `emr_followups`
                ADD COLUMN `impression_norm` VARCHAR(191) NULL DEFAULT NULL
                AFTER `impression`
            ")) {
                echo "  ! failed to add impression_norm: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }

            echo "  + added column impression_norm\n";
        } else {
            echo "  = column impression_norm already exists\n";
        }

        // REGEXP_REPLACE requires a compatible MySQL/MariaDB version.
        if (!$this->db->query("
            UPDATE `emr_followups`
            SET `impression_norm` = LEFT(
                UPPER(TRIM(REGEXP_REPLACE(`impression`, '[[:space:]]+', ' '))),
                191
            )
            WHERE `impression` IS NOT NULL
              AND `impression` <> ''
              AND (
                  `impression_norm` IS NULL
                  OR `impression_norm` = ''
              )
        ")) {
            echo "  ! failed to backfill impression_norm: "
                . $this->db->error()['message'] . "\n";
            return false;
        }

        echo "  = backfilled " . $this->db->affected_rows() . " row(s)\n";

        if (!$this->index_exists($table, 'idx_emr_followups_impression_norm')) {
            if (!$this->db->query("
                ALTER TABLE `emr_followups`
                ADD INDEX `idx_emr_followups_impression_norm` (`impression_norm`)
            ")) {
                echo "  ! failed to add index: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }

            echo "  + added index idx_emr_followups_impression_norm\n";
        } else {
            echo "  = index idx_emr_followups_impression_norm already exists\n";
        }

        return true;
    }

    // --------------------------------------------------------------
    // 20261005000004 — emr_followups.xray_results
    // --------------------------------------------------------------
    private function add_xray_results()
    {
        $table = 'emr_followups';

        if (!$this->db->table_exists($table)) {
            echo "  ! {$table} not found\n";
            return false;
        }

        if ($this->column_exists($table, 'xray_results')) {
            echo "  = column xray_results already exists\n";
            return true;
        }

        if (!$this->column_exists($table, 'dental_findings')) {
            echo "  ! {$table}.dental_findings not found; cannot position xray_results\n";
            return false;
        }

        if (!$this->db->query("
            ALTER TABLE `emr_followups`
            ADD COLUMN `xray_results` TEXT NULL
            AFTER `dental_findings`
        ")) {
            echo "  ! failed to add xray_results: "
                . $this->db->error()['message'] . "\n";
            return false;
        }

        echo "  + added column xray_results\n";
        return true;
    }

    // --------------------------------------------------------------
    // 20261021000005 — grade_sections.school_id
    // --------------------------------------------------------------
    private function add_grade_sections_school_id()
    {
        $table = 'grade_sections';

        if (!$this->db->table_exists($table)) {
            echo "  ! {$table} not found\n";
            return false;
        }

        if ($this->column_exists($table, 'school_id')) {
            echo "  = column school_id already exists\n";
            return true;
        }

        if (!$this->column_exists($table, 'id')) {
            echo "  ! {$table}.id not found; cannot position school_id\n";
            return false;
        }

        if (!$this->dbforge->add_column($table, [
            'school_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'id',
            ],
        ])) {
            echo "  ! failed to add school_id: "
                . $this->db->error()['message'] . "\n";
            return false;
        }

        echo "  + added column {$table}.school_id\n";
        return true;
    }

    // --------------------------------------------------------------
    // 20261021000006 — nutritional_assessments.user_id
    // --------------------------------------------------------------
    private function add_nutritional_assessments_user_id()
    {
        $table = 'nutritional_assessments';

        if (!$this->db->table_exists($table)) {
            echo "  ! {$table} not found\n";
            return false;
        }

        if (!$this->column_exists($table, 'id')) {
            echo "  ! {$table}.id not found; cannot position user_id\n";
            return false;
        }

        if ($this->column_exists($table, 'user_id')) {
            echo "  = column user_id already exists\n";
        } else {
            if (!$this->dbforge->add_column($table, [
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'id',
                ],
            ])) {
                echo "  ! failed to add user_id: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }

            echo "  + added column {$table}.user_id\n";
        }

        if (!$this->index_exists($table, 'idx_nutritional_assessments_user_id')) {
            if (!$this->db->query("
                ALTER TABLE `nutritional_assessments`
                ADD INDEX `idx_nutritional_assessments_user_id` (`user_id`)
            ")) {
                echo "  ! failed to add index on user_id: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }

            echo "  + added index idx_nutritional_assessments_user_id\n";
        } else {
            echo "  = index idx_nutritional_assessments_user_id already exists\n";
        }

        return true;
    }

    // --------------------------------------------------------------
    // 20261028000007 — index on nutritional_assessments.is_deleted
    // --------------------------------------------------------------
    private function add_nutritional_assessments_is_deleted_index()
    {
        $table = 'nutritional_assessments';

        if (!$this->db->table_exists($table)) {
            echo "  ! {$table} not found\n";
            return false;
        }

        if (!$this->column_exists($table, 'is_deleted')) {
            echo "  ! {$table}.is_deleted not found; cannot add index\n";
            return false;
        }

        if ($this->index_exists($table, 'idx_nutritional_assessments_is_deleted')) {
            echo "  = index idx_nutritional_assessments_is_deleted already exists\n";
            return true;
        }

        if (!$this->db->query("
            ALTER TABLE `nutritional_assessments`
            ADD INDEX `idx_nutritional_assessments_is_deleted` (`is_deleted`, `id`)
        ")) {
            echo "  ! failed to add index on is_deleted: "
                . $this->db->error()['message'] . "\n";
            return false;
        }

        echo "  + added index idx_nutritional_assessments_is_deleted\n";
        return true;
    }

    // --------------------------------------------------------------
    // 20261101000008 — enrollments table
    // --------------------------------------------------------------
    private function create_enrollments_table()
    {
        $table = 'enrollments';

        // ---- 1. Create the table if missing --------------------------
        if (!$this->db->table_exists($table)) {
            $this->dbforge->add_field([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'school_year'  => [
                    'type'       => 'VARCHAR',
                    'constraint' => 9,
                    'null'       => false,
                ],
                'kindergarten' => $this->enrollment_grade_field(),
                'sped'         => $this->enrollment_grade_field(),
                'grade_1'      => $this->enrollment_grade_field(),
                'grade_2'      => $this->enrollment_grade_field(),
                'grade_3'      => $this->enrollment_grade_field(),
                'grade_4'      => $this->enrollment_grade_field(),
                'grade_5'      => $this->enrollment_grade_field(),
                'grade_6'      => $this->enrollment_grade_field(),
                'grade_7'      => $this->enrollment_grade_field(),
                'grade_8'      => $this->enrollment_grade_field(),
                'grade_9'      => $this->enrollment_grade_field(),
                'grade_10'     => $this->enrollment_grade_field(),
                'grade_11'     => $this->enrollment_grade_field(),
                'grade_12'     => $this->enrollment_grade_field(),
                'school_type'  => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'default'    => null,
                ],
                'created_at'   => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at'   => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);

            if (!$this->dbforge->create_table($table, true, [
                'ENGINE'  => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ])) {
                echo "  ! failed to create {$table}: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }

            echo "  + created table {$table}\n";

            // dbforge can't express ON UPDATE CURRENT_TIMESTAMP — do it via ALTER.
            if (!$this->db->query("
                ALTER TABLE `{$table}`
                    MODIFY `created_at` DATETIME NOT NULL
                                          DEFAULT CURRENT_TIMESTAMP,
                    MODIFY `updated_at` DATETIME NOT NULL
                                          DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP
            ")) {
                echo "  ! failed to set timestamp defaults: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }
            echo "  + applied timestamp defaults\n";
        } else {
            echo "  = table {$table} already exists\n";
        }

        // ---- 2. Defensive: add any missing columns -------------------
        // (handles partial creations and older tables that predate school_type)
        $grade_cols = [
            'kindergarten', 'sped',
            'grade_1',  'grade_2',  'grade_3',  'grade_4',
            'grade_5',  'grade_6',  'grade_7',  'grade_8',
            'grade_9',  'grade_10', 'grade_11', 'grade_12',
        ];

        foreach ($grade_cols as $col) {
            if (!$this->column_exists($table, $col)) {
                if (!$this->dbforge->add_column($table, [
                    $col => $this->enrollment_grade_field(),
                ])) {
                    echo "  ! failed to add {$table}.{$col}: "
                        . $this->db->error()['message'] . "\n";
                    return false;
                }
                echo "  + added column {$col}\n";
            }
        }

        if (!$this->column_exists($table, 'school_type')) {
            if (!$this->dbforge->add_column($table, [
                'school_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'default'    => null,
                ],
            ])) {
                echo "  ! failed to add school_type: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }
            echo "  + added column school_type\n";
        }

        // ---- 3. Unique key on school_year ----------------------------
        if (!$this->index_exists($table, 'uk_enrollments_school_year')) {
            if (!$this->db->query("
                ALTER TABLE `{$table}`
                ADD UNIQUE KEY `uk_enrollments_school_year` (`school_year`)
            ")) {
                echo "  ! failed to add unique key on school_year: "
                    . $this->db->error()['message'] . "\n";
                return false;
            }
            echo "  + added unique key uk_enrollments_school_year\n";
        } else {
            echo "  = unique key uk_enrollments_school_year already exists\n";
        }

        return true;
    }

    /**
     * Reusable INT UNSIGNED NOT NULL DEFAULT 0 column definition.
     */
    private function enrollment_grade_field()
    {
        return [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => false,
            'default'    => 0,
        ];
    }

    // --------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------
    private function column_exists($table, $column)
    {
        // Table and column names are internal constants supplied by this file.
        $table = str_replace('`', '``', $table);
        $query = $this->db->query(
            "SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->escape($column)
        );

        return $query !== false && $query->num_rows() > 0;
    }

    private function index_exists($table, $index_name)
    {
        $table = str_replace('`', '``', $table);
        $query = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = "
            . $this->db->escape($index_name)
        );

        return $query !== false && $query->num_rows() > 0;
    }
}
