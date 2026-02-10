<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Archive_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get distinct school years from nutritional_assessmentss table
     */
    public function get_distinct_school_years() {
        $this->db->select('year');
        $this->db->distinct();
        $this->db->from('nutritional_assessments');
        $this->db->where('year IS NOT NULL');
        $this->db->where("year != ''");
        $this->db->where('is_deleted', 0); // Only active records
        $this->db->order_by('year', 'DESC');
        
        $query = $this->db->get();
        $results = $query->result();
        
        $years = [];
        foreach ($results as $row) {
            if (!empty($row->year)) {
                $years[] = $row->year;
            }
        }
        
        return $years;
    }
    
    /**
     * Check if there are records to archive for a specific school year
     */
    public function check_records_to_archive($school_year) {
        $this->db->where('year', $school_year);
        $this->db->where('is_deleted', 0);
        return $this->db->count_all_results('nutritional_assessments') > 0;
    }
    
    /**
     * Get count of records to archive for a specific school year
     */
    public function count_records_to_archive($school_year) {
        $this->db->where('year', $school_year);
        $this->db->where('is_deleted', 0);
        return $this->db->count_all_results('nutritional_assessments');
    }
    
    /**
     * Archive all records for a specific school year
     */
    public function archive_records($school_year = '2025-2026') {
        try {
            // Start transaction
            $this->db->trans_start();
            
            // 1. Check if archive table exists, create if not
            if (!$this->db->table_exists('nutritional_assessment_archive')) {
                $this->create_archive_table();
            }
            
            // 2. Check if we have records to archive
            $record_count = $this->count_records_to_archive($school_year);
            if ($record_count === 0) {
                return [
                    'success' => false,
                    'message' => "No active records found to archive for school year {$school_year}.",
                    'archived_count' => 0
                ];
            }
            
            // 3. Copy records to archive table
            $this->db->query("
                INSERT INTO nutritional_assessment_archive 
                SELECT *, NOW() as archived_at 
                FROM nutritional_assessments 
                WHERE year = ? AND is_deleted = 0
            ", array($school_year));
            
            $archived_count = $this->db->affected_rows();
            
            // 4. Soft delete archived records from main table (mark as deleted)
            $this->db->where('year', $school_year);
            $this->db->where('is_deleted', 0);
            $this->db->update('nutritional_assessments', [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
            
            $deleted_count = $this->db->affected_rows();
            
            // Commit transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Database transaction failed');
            }
            
            // Log the archive operation
            $this->log_archive_operation($school_year, $archived_count);
            
            return [
                'success' => true,
                'archived_count' => $archived_count,
                'deleted_count' => $deleted_count,
                'message' => "Archived {$archived_count} records for school year {$school_year}"
            ];
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->trans_rollback();
            
            return [
                'success' => false,
                'message' => 'Archive failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create archive table with correct structure
     */
    private function create_archive_table() {
        $sql = "
        CREATE TABLE IF NOT EXISTS nutritional_assessment_archive (
            id INT PRIMARY KEY,
            school_district VARCHAR(255),
            legislative_district VARCHAR(255),
            school_name VARCHAR(255),
            school_id VARCHAR(50),
            school_level VARCHAR(50),
            grade_level VARCHAR(255) NOT NULL,
            section VARCHAR(255) NOT NULL,
            year VARCHAR(20),
            name VARCHAR(255) NOT NULL,
            birthday DATE NOT NULL,
            weight DECIMAL(5,2) NOT NULL,
            height DECIMAL(4,2) NOT NULL,
            sex ENUM('M', 'F') NOT NULL,
            height_squared DECIMAL(6,4) NOT NULL,
            age VARCHAR(20),
            bmi DECIMAL(5,2) NOT NULL,
            nutritional_status VARCHAR(255) NOT NULL,
            height_for_age VARCHAR(255) DEFAULT 'Normal',
            sbfp_beneficiary VARCHAR(255) NOT NULL,
            date_of_weighing DATE NOT NULL,
            created_at DATETIME,
            updated_at DATETIME,
            assessment_type ENUM('baseline', 'midline', 'endline') DEFAULT 'baseline',
            is_deleted TINYINT(1) DEFAULT 0,
            deleted_at TIMESTAMP NULL,
            is_locked TINYINT(1) DEFAULT 0,
            archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_year (year),
            INDEX idx_archived_at (archived_at),
            INDEX idx_school_name (school_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        
        $this->db->query($sql);
    }
    
    /**
     * Get all archived records
     */
    public function get_all_archived_records($limit = 20, $offset = 0) {
        $this->db->select('*');
        $this->db->from('nutritional_assessment_archive');
        $this->db->order_by('archived_at', 'DESC');
        $this->db->order_by('school_name', 'ASC');
        $this->db->order_by('grade_level', 'ASC');
        
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Count all archived records
     */
    public function count_all_archived_records() {
        return $this->db->count_all('nutritional_assessment_archive');
    }
    
    /**
     * Get single archived record
     */
    public function get_archived_record($record_id) {
        $this->db->where('id', $record_id);
        $query = $this->db->get('nutritional_assessment_archive');
        
        return $query->row();
    }
    
    /**
     * Restore a record from archive to main table
     */
    public function restore_record($record_id) {
        try {
            $this->db->trans_start();
            
            // 1. Get the record from archive
            $record = $this->get_archived_record($record_id);
            
            if (!$record) {
                throw new Exception('Record not found in archive');
            }
            
            // Remove archived_at field and convert to array
            $record_data = (array) $record;
            unset($record_data['archived_at']);
            
            // Check if record already exists in main table (by id)
            $this->db->where('id', $record_id);
            $existing = $this->db->get('nutritional_assessments')->row();
            
            if ($existing) {
                // Update existing record - set is_deleted back to 0
                $record_data['is_deleted'] = 0;
                $record_data['deleted_at'] = null;
                $this->db->where('id', $record_id);
                $this->db->update('nutritional_assessments', $record_data);
            } else {
                // Insert as new record
                $this->db->insert('nutritional_assessments', $record_data);
            }
            
            // 3. Delete from archive
            $this->db->where('id', $record_id);
            $this->db->delete('nutritional_assessment_archive');
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Restore transaction failed');
            }
            
            return [
                'success' => true,
                'message' => 'Record restored successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            
            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Log archive operations
     */
    private function log_archive_operation($school_year, $record_count) {
        $user_id = $this->session->userdata('user_id') ?: 0;
        
        $log_data = [
            'school_year' => $school_year,
            'record_count' => $record_count,
            'archived_by' => $user_id,
            'archive_date' => date('Y-m-d H:i:s'),
            'ip_address' => $this->input->ip_address()
        ];
        
        // Create archive_logs table if it doesn't exist
        if (!$this->db->table_exists('archive_logs')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS archive_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    school_year VARCHAR(20) NOT NULL,
                    record_count INT NOT NULL,
                    archived_by INT,
                    archive_date DATETIME NOT NULL,
                    ip_address VARCHAR(45)
                )
            ");
        }
        
        $this->db->insert('archive_logs', $log_data);
    }
}