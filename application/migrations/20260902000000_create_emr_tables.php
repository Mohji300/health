<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_emr_tables extends CI_Migration {

    public function up()
    {
        // ---- emr_patients ----
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `emr_patients` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `school_id` INT NULL DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `dob` DATE NULL DEFAULT NULL,
                `gender` ENUM('Male','Female','Other') NULL DEFAULT NULL,
                `phone` VARCHAR(50) NULL DEFAULT NULL,
                `address` TEXT NULL,
                `consent` ENUM('Yes','No') NULL DEFAULT NULL,
                `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_emr_patients_school_id` (`school_id`),
                KEY `idx_emr_patients_name` (`name`),
                CONSTRAINT `fk_emr_patients_school`
                    FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ---- emr_followups (with prescription) ----
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `emr_followups` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `patient_id` BIGINT UNSIGNED NOT NULL,
                `followup_date` DATE NOT NULL,
                `impression` TEXT NULL,
                `diagnoses` VARCHAR(255) NULL DEFAULT NULL,
                `maintenance` TEXT NULL,
                `doctor_notes` TEXT NULL,
                `height` DECIMAL(5,2) NULL DEFAULT NULL,
                `weight` DECIMAL(5,2) NULL DEFAULT NULL,
                `bmi` DECIMAL(5,2) NULL DEFAULT NULL,
                `bmi_category` VARCHAR(50) NULL DEFAULT NULL,
                `vision_left` VARCHAR(20) NULL DEFAULT NULL,
                `vision_right` VARCHAR(20) NULL DEFAULT NULL,
                `hearing_left` VARCHAR(20) NULL DEFAULT NULL,
                `hearing_right` VARCHAR(20) NULL DEFAULT NULL,
                `dental_findings` TEXT NULL,
                `xray_results` TEXT NULL,
                `immunizations` TEXT NULL,
                `referral_notes` TEXT NULL,
                `referral_status` VARCHAR(20) NULL DEFAULT NULL,
                `lab` JSON NULL DEFAULT NULL,
                `prescription` JSON NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_emr_followups_patient_date` (`patient_id`, `followup_date`),
                CONSTRAINT `fk_emr_followups_patient`
                    FOREIGN KEY (`patient_id`) REFERENCES `emr_patients` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ---- emr_announcements ----
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `emr_announcements` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `content` TEXT NULL,
                `announcement_date` DATE NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_emr_announcements_date` (`announcement_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ----- ADD ADDITIONAL INDEXES FOR PERFORMANCE -----
        $this->db->query("ALTER TABLE `emr_patients` ADD INDEX IF NOT EXISTS `idx_status` (`status`)");
        $this->db->query("ALTER TABLE `emr_followups` ADD INDEX IF NOT EXISTS `idx_followup_date` (`followup_date`)");
        $this->db->query("ALTER TABLE `schools` ADD INDEX IF NOT EXISTS `idx_school_district_id` (`school_district_id`)");
        $this->db->query("ALTER TABLE `school_districts` ADD INDEX IF NOT EXISTS `idx_legislative_district_id` (`legislative_district_id`)");
        $this->db->query("ALTER TABLE `school_districts` ADD INDEX IF NOT EXISTS `idx_name` (`name`)");
        $this->db->query("ALTER TABLE `legislative_districts` ADD INDEX IF NOT EXISTS `idx_name` (`name`)");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `emr_followups`');
        $this->db->query('DROP TABLE IF EXISTS `emr_patients`');
        $this->db->query('DROP TABLE IF EXISTS `emr_announcements`');
    }
}