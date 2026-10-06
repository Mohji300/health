<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {

    /**
     * Order matters: lowest version first.
     * Each method must be idempotent (safe to re-run).
     */
    private $versions = [
        20261001000003 => 'add_impression_norm',
        20261005000004 => 'add_xray_results',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
    }

    public function index()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        echo "<h2>EMR Migration</h2>";
        echo "<pre style='font-family: monospace; line-height: 1.4;'>";

        $applied = $this->applied_versions();
        $ran = 0;

        foreach ($this->versions as $version => $method) {
            if (in_array((string) $version, $applied, true)) {
                echo "[skip] {$version} already applied\n";
                continue;
            }

            echo "[run]  {$version} ({$method})\n";

            $this->db->trans_start();
            $this->{$method}();
            $this->record_version($version);

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                echo "[fail] {$version} rolled back\n";
                echo "</pre>";
                return;
            }

            $this->db->trans_commit();
            echo "[ok]   {$version} applied\n\n";
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
            $this->db->query("
                CREATE TABLE migrations (
                    version BIGINT NOT NULL PRIMARY KEY,
                    applied_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            return [];
        }

        return array_map(
            fn($row) => (string) $row->version,
            $this->db->select('version')->get('migrations')->result()
        );
    }

    private function record_version($version)
    {
        $this->db->insert('migrations', ['version' => $version]);
    }

    // --------------------------------------------------------------
    // 20261001000003 — impression_norm + backfill + index
    // --------------------------------------------------------------
    private function add_impression_norm()
    {
        if (!$this->db->table_exists('emr_followups')) {
            echo "  ! emr_followups not found — aborting this step\n";
            return;
        }

        if (!$this->column_exists('emr_followups', 'impression_norm')) {
            if (!$this->db->query("
                ALTER TABLE emr_followups
                ADD COLUMN impression_norm VARCHAR(191) NULL DEFAULT NULL AFTER impression
            ")) {
                echo "  ! failed to add impression_norm: " . $this->db->error()['message'] . "\n";
                return;
            }
            echo "  + added column impression_norm\n";
        } else {
            echo "  = column impression_norm already exists\n";
        }

        // Backfill. LEFT(...,191) prevents "Data too long" in strict mode.
        if (!$this->db->query("
            UPDATE emr_followups
            SET impression_norm = LEFT(
                UPPER(TRIM(REGEXP_REPLACE(impression, '[[:space:]]+', ' '))),
                191
            )
            WHERE impression IS NOT NULL
              AND impression <> ''
              AND (impression_norm IS NULL OR impression_norm = '')
        ")) {
            echo "  ! backfill skipped: " . $this->db->error()['message'] . "\n";
        } else {
            echo "  = backfilled " . $this->db->affected_rows() . " row(s)\n";
        }

        if (!$this->index_exists('emr_followups', 'idx_emr_followups_impression_norm')) {
            if (!$this->db->query("
                ALTER TABLE emr_followups
                ADD INDEX idx_emr_followups_impression_norm (impression_norm)
            ")) {
                echo "  ! failed to add index: " . $this->db->error()['message'] . "\n";
                return;
            }
            echo "  + added index idx_emr_followups_impression_norm\n";
        } else {
            echo "  = index idx_emr_followups_impression_norm already exists\n";
        }
    }

    // --------------------------------------------------------------
    // 20261005000004 — xray_results
    // --------------------------------------------------------------
    private function add_xray_results()
    {
        if (!$this->db->table_exists('emr_followups')) {
            echo "  ! emr_followups not found — aborting this step\n";
            return;
        }

        if ($this->column_exists('emr_followups', 'xray_results')) {
            echo "  = column xray_results already exists\n";
            return;
        }

        if (!$this->db->query("
            ALTER TABLE emr_followups
            ADD COLUMN xray_results TEXT NULL AFTER dental_findings
        ")) {
            echo "  ! failed to add xray_results: " . $this->db->error()['message'] . "\n";
            return;
        }
        echo "  + added column xray_results\n";
    }

    // --------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------
    private function column_exists($table, $column)
    {
        $q = $this->db->query(
            "SHOW COLUMNS FROM `" . $this->db->escape_str($table) . "` LIKE " . $this->db->escape($column)
        );
        return $q && $q->num_rows() > 0;
    }

    private function index_exists($table, $index_name)
    {
        $q = $this->db->query(
            "SHOW INDEX FROM `" . $this->db->escape_str($table) . "` WHERE Key_name = " . $this->db->escape($index_name)
        );
        return $q && $q->num_rows() > 0;
    }
}