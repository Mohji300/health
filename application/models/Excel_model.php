<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class excel_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Load database
        $this->load->database();
    }

    public function check_tables() {
        // Check if required tables exist, if not create them
        if (!$this->db->table_exists('legislative_districts')) {
            $this->create_legislative_districts_table();
        }
        
        if (!$this->db->table_exists('school_districts')) {
            $this->create_school_districts_table();
        }
        
        if (!$this->db->table_exists('schools')) {
            $this->create_schools_table();
        } else {
            // Check if new columns exist, if not add them
            $this->add_missing_columns();
        }
        
        // Check if users table has the additional columns needed
        $this->check_users_table_columns();
    }
    
    /**
     * Check if users table has the required columns for school data
     */
    private function check_users_table_columns() {
        if ($this->db->table_exists('users')) {
            // Check for school_id column
            if (!$this->db->field_exists('school_id', 'users')) {
                $this->db->query("ALTER TABLE users ADD COLUMN school_id VARCHAR(100) NULL DEFAULT NULL");
            }
            
            // Check for legislative_district column
            if (!$this->db->field_exists('legislative_district', 'users')) {
                $this->db->query("ALTER TABLE users ADD COLUMN legislative_district VARCHAR(255) NULL DEFAULT NULL");
            }
            
            // Check for school_district column
            if (!$this->db->field_exists('school_district', 'users')) {
                $this->db->query("ALTER TABLE users ADD COLUMN school_district VARCHAR(255) NULL DEFAULT NULL");
            }
            
            // Check for school_level column
            if (!$this->db->field_exists('school_level', 'users')) {
                $this->db->query("ALTER TABLE users ADD COLUMN school_level VARCHAR(50) NULL DEFAULT NULL");
            }
        }
    }

    private function create_legislative_districts_table() {
        $this->load->dbforge();
        
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => FALSE
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP'
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            )
        );
        
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('legislative_districts', TRUE);
    }

    private function create_school_districts_table() {
        $this->load->dbforge();
        
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => FALSE
            ),
            'legislative_district_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP'
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            )
        );
        
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('school_districts', TRUE);
        
        // Add foreign key constraint (check if it doesn't exist first)
        $constraint_check = $this->db->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_school_districts_legislative' AND TABLE_NAME = 'school_districts'");
        if ($constraint_check->num_rows() == 0) {
            $this->db->query('ALTER TABLE school_districts ADD CONSTRAINT fk_school_districts_legislative FOREIGN KEY (legislative_district_id) REFERENCES legislative_districts(id) ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    private function create_schools_table() {
        $this->load->dbforge();
        
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'school_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => FALSE
            ),
            'school_district_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE
            ),
            'school_level' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'default' => NULL,
                'comment' => 'Elementary, Secondary, Private, Integrated'
            ),
            'school_size' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'default' => NULL,
                'comment' => 'Number of students (to be updated later)'
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP'
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            )
        );
        
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('schools', TRUE);
        
        // Add foreign key constraint (check if it doesn't exist first)
        $constraint_check = $this->db->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_schools_school_district' AND TABLE_NAME = 'schools'");
        if ($constraint_check->num_rows() == 0) {
            $this->db->query('ALTER TABLE schools ADD CONSTRAINT fk_schools_school_district FOREIGN KEY (school_district_id) REFERENCES school_districts(id) ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }
    
    private function add_missing_columns() {
        // Check if school_level column exists
        if (!$this->db->field_exists('school_level', 'schools')) {
            $this->db->query("ALTER TABLE schools ADD COLUMN school_level VARCHAR(50) NULL DEFAULT NULL COMMENT 'Elementary, Secondary, Private, Integrated'");
        }
        
        // Check if school_size column exists
        if (!$this->db->field_exists('school_size', 'schools')) {
            $this->db->query("ALTER TABLE schools ADD COLUMN school_size INT NULL DEFAULT NULL COMMENT 'Number of students (to be updated later)'");
        }
    }

    /**
     * Insert data WITHOUT clearing existing data (for appending)
     * This method allows uploading multiple files without losing previous data
     */
    public function insert_excel_data_append($legislative_districts, $school_districts, $schools_data) {
        $this->db->trans_start();

        try {
            // Step 1: Get existing legislative districts to avoid duplicates
            $existing_legislative = array();
            $query = $this->db->select('name, id')->get('legislative_districts');
            foreach ($query->result() as $row) {
                $existing_legislative[$row->name] = $row->id;
            }
            
            // Step 2: Insert ONLY NEW Legislative Districts
            $legislative_district_ids = $existing_legislative; // Start with existing ones
            foreach ($legislative_districts as $district) {
                if (empty($district)) continue;
                
                // Check if district already exists
                if (!isset($legislative_district_ids[$district])) {
                    $this->db->insert('legislative_districts', array(
                        'name' => $district,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ));
                    $legislative_district_ids[$district] = $this->db->insert_id();
                    log_message('info', "Added new legislative district: $district");
                }
            }

            // Step 3: Get existing school districts to avoid duplicates
            $existing_school_districts = array();
            $query = $this->db->select('name, legislative_district_id, id')->get('school_districts');
            foreach ($query->result() as $row) {
                $key = $row->name . '_' . $row->legislative_district_id;
                $existing_school_districts[$key] = $row->id;
            }
            
            // Step 4: Insert ONLY NEW School Districts
            $school_district_ids = array();
            foreach ($school_districts as $key => $district) {
                if (empty($district['school_district'])) continue;
                
                $legislative_id = isset($legislative_district_ids[$district['legislative_district']]) ? 
                    $legislative_district_ids[$district['legislative_district']] : null;
                
                if ($legislative_id) {
                    $check_key = $district['school_district'] . '_' . $legislative_id;
                    
                    // Check if school district already exists
                    if (!isset($existing_school_districts[$check_key])) {
                        $this->db->insert('school_districts', array(
                            'name' => $district['school_district'],
                            'legislative_district_id' => $legislative_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ));
                        $school_district_ids[$key] = $this->db->insert_id();
                        log_message('info', "Added new school district: " . $district['school_district']);
                    } else {
                        $school_district_ids[$key] = $existing_school_districts[$check_key];
                    }
                }
            }

            // Step 5: Get existing schools to avoid duplicates (by school_id)
            $existing_schools = array();
            $query = $this->db->select('school_id, id')->get('schools');
            foreach ($query->result() as $row) {
                if (!empty($row->school_id)) {
                    $existing_schools[$row->school_id] = $row->id;
                }
            }
            
            // Step 6: Insert ONLY NEW Schools
            $schools_inserted = 0;
            $schools_skipped = 0;
            $schools_by_level = array(
                'Elementary' => 0,
                'Secondary' => 0,
                'Private' => 0,
                'Integrated' => 0,
                'Unknown' => 0
            );
            
            foreach ($schools_data as $school) {
                // Check if school already exists (by school_id)
                if (!empty($school['school_id']) && isset($existing_schools[$school['school_id']])) {
                    $schools_skipped++;
                    log_message('debug', "Skipping existing school: " . $school['school_id'] . " - " . $school['school_name']);
                    continue;
                }
                
                $district_key = $school['legislative_district'] . '_' . $school['school_district'];
                $school_district_id = isset($school_district_ids[$district_key]) ? $school_district_ids[$district_key] : null;
                
                if ($school_district_id) {
                    // Insert school with new fields
                    $insert_data = array(
                        'school_id' => $school['school_id'],
                        'name' => $school['school_name'],
                        'school_district_id' => $school_district_id,
                        'school_level' => isset($school['school_level']) ? $school['school_level'] : null,
                        'school_size' => isset($school['school_size']) ? $school['school_size'] : null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    );
                    
                    $this->db->insert('schools', $insert_data);
                    $schools_inserted++;
                    
                    // Count by level for logging
                    $level = isset($school['school_level']) ? $school['school_level'] : 'Unknown';
                    if (isset($schools_by_level[$level])) {
                        $schools_by_level[$level]++;
                    } else {
                        $schools_by_level['Unknown']++;
                    }
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                log_message('error', 'Database transaction failed in insert_excel_data_append');
                return false;
            }

            log_message('info', "Appended data: " . count($legislative_districts) . " legislative districts, " . count($school_districts) . " school districts, " . $schools_inserted . " new schools, " . $schools_skipped . " skipped (duplicates)");
            log_message('info', "Schools by level: " . json_encode($schools_by_level));
            
            return true;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Excel Model Error (Append): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total schools count
     */
    public function get_total_schools_count() {
        return $this->db->count_all('schools');
    }

    public function clear_all_data() {
        $this->db->trans_start();
        
        // Delete in correct order due to foreign key constraints
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->truncate('schools');
        $this->db->truncate('school_districts');
        $this->db->truncate('legislative_districts');
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get_data_summary() {
        $summary = array(
            'legislative_districts' => 0,
            'school_districts' => 0,
            'schools' => 0,
            'school_levels' => array(
                'Elementary' => 0,
                'Secondary' => 0,
                'Private' => 0,
                'Integrated' => 0,
                'Unknown' => 0
            )
        );
        
        try {
            // Check if database connection is working
            if (!$this->db->conn_id) {
                log_message('error', 'Database connection not available in excel_model');
                return $summary;
            }
            
            // Get legislative districts count
            $result = $this->db->query("SELECT COUNT(*) as count FROM legislative_districts");
            if ($result) {
                $summary['legislative_districts'] = $result->row()->count;
            }
            
            // Get school districts count
            $result = $this->db->query("SELECT COUNT(*) as count FROM school_districts");
            if ($result) {
                $summary['school_districts'] = $result->row()->count;
            }
            
            // Get schools count and breakdown by level
            $result = $this->db->query("SELECT COUNT(*) as count FROM schools");
            if ($result) {
                $summary['schools'] = $result->row()->count;
            }
            
            // Get school levels breakdown
            $result = $this->db->query("SELECT school_level, COUNT(*) as count FROM schools GROUP BY school_level");
            if ($result) {
                foreach ($result->result() as $row) {
                    $level = $row->school_level ? $row->school_level : 'Unknown';
                    if (isset($summary['school_levels'][$level])) {
                        $summary['school_levels'][$level] = $row->count;
                    } else {
                        $summary['school_levels']['Unknown'] += $row->count;
                    }
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error in get_data_summary: ' . $e->getMessage());
        }
        
        return $summary;
    }

    /**
     * Get ordered data for verification
     */
    public function get_ordered_data() {
        $data = array();
        
        // Get legislative districts in order
        $data['legislative_districts'] = $this->db
            ->order_by('id', 'ASC')
            ->get('legislative_districts')
            ->result();
            
        // Get school districts ordered by legislative district and name
        $data['school_districts'] = $this->db
            ->select('sd.*, ld.name as legislative_name')
            ->from('school_districts sd')
            ->join('legislative_districts ld', 'sd.legislative_district_id = ld.id')
            ->order_by('sd.legislative_district_id', 'ASC')
            ->order_by('sd.name', 'ASC')
            ->get()
            ->result();
            
        // Get schools ordered by school district and name with new fields
        $data['schools'] = $this->db
            ->select('s.*, sd.name as school_district_name, ld.name as legislative_name')
            ->from('schools s')
            ->join('school_districts sd', 's.school_district_id = sd.id')
            ->join('legislative_districts ld', 'sd.legislative_district_id = ld.id')
            ->order_by('s.school_district_id', 'ASC')
            ->order_by('s.name', 'ASC')
            ->get()
            ->result();
            
        return $data;
    }
    
    /**
     * Update school size for a specific school
     */
    public function update_school_size($school_id, $size) {
        $this->db->where('id', $school_id);
        return $this->db->update('schools', array(
            'school_size' => $size,
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }
    
    /**
     * Get schools by level
     */
    public function get_schools_by_level($level = null) {
        $this->db->select('s.*, sd.name as school_district_name, ld.name as legislative_name')
            ->from('schools s')
            ->join('school_districts sd', 's.school_district_id = sd.id')
            ->join('legislative_districts ld', 'sd.legislative_district_id = ld.id');
        
        if ($level) {
            $this->db->where('s.school_level', $level);
        }
        
        return $this->db->order_by('s.school_district_id', 'ASC')
            ->order_by('s.name', 'ASC')
            ->get()
            ->result();
    }
    
    /**
     * Get school by ID
     */
    public function get_school_by_id($school_id) {
        $this->db->where('school_id', $school_id);
        return $this->db->get('schools')->row();
    }
    
    /**
     * Check if school exists
     */
    public function school_exists($school_id) {
        $this->db->where('school_id', $school_id);
        $query = $this->db->get('schools');
        return $query->num_rows() > 0;
    }
}
?>