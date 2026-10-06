<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: 20261001000003_update_emr_impression_norm
 *
 * Adds emr_followups.impression_norm (normalized impression text used
 * for grouping/counting on the dashboard), backfills existing rows,
 * and adds an index.
 *
 * The column stores UPPER(TRIM(collapse-whitespace(impression))).
 * The original `impression` column is left untouched.
 */
class Migration_Update_emr_impression_norm extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('emr_followups')) {
            // Nothing to migrate against; abort gracefully.
            return;
        }

        // 1) Add the column if it's not already present.
        if (!$this->db->field_exists('impression_norm', 'emr_followups')) {
            $this->dbforge->add_column('emr_followups', [
                'impression_norm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => TRUE,
                    'after'      => 'impression',
                ],
            ]);
        }

        // 2) Backfill existing rows.
        //    REGEXP_REPLACE requires MySQL 8.0+ / MariaDB 10.0+.
        //    On older servers, skip the backfill — new rows still get filled
        //    by Emr_api::visit().
        $sql = "UPDATE emr_followups
                SET impression_norm = UPPER(TRIM(
                    REGEXP_REPLACE(impression, '[[:space:]]+', ' ')
                ))
                WHERE impression IS NOT NULL
                  AND impression <> ''
                  AND (impression_norm IS NULL OR impression_norm = '')";

        $this->db->query($sql);

        // 3) Index for GROUP BY / aggregation.
        $idx = $this->db->query(
            "SHOW INDEX FROM `emr_followups` WHERE Key_name = 'idx_emr_followups_impression_norm'"
        );
        if ($idx->num_rows() === 0) {
            $this->db->query(
                "ALTER TABLE emr_followups
                 ADD INDEX idx_emr_followups_impression_norm (impression_norm)"
            );
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('emr_followups')) {
            return;
        }

        // Drop the index first (dropping the column usually drops it too,
        // but being explicit keeps this portable).
        $idx = $this->db->query(
            "SHOW INDEX FROM `emr_followups` WHERE Key_name = 'idx_emr_followups_impression_norm'"
        );
        if ($idx->num_rows() > 0) {
            $this->db->query("ALTER TABLE emr_followups DROP INDEX idx_emr_followups_impression_norm");
        }

        if ($this->db->field_exists('impression_norm', 'emr_followups')) {
            $this->dbforge->drop_column('emr_followups', 'impression_norm');
        }
    }
}