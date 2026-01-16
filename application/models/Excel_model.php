<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Excel_model extends CI_Model {

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
        
        // Add foreign key constraint
        $this->db->query('ALTER TABLE school_districts ADD CONSTRAINT fk_school_districts_legislative FOREIGN KEY (legislative_district_id) REFERENCES legislative_districts(id) ON DELETE CASCADE ON UPDATE CASCADE');
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
        
        // Add foreign key constraint
        $this->db->query('ALTER TABLE schools ADD CONSTRAINT fk_schools_school_district FOREIGN KEY (school_district_id) REFERENCES school_districts(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function insert_excel_data($legislative_districts, $school_districts, $schools_data) {
        $this->db->trans_start();

        try {
            // Step 1: Clear existing data
            $this->clear_all_data();

            // Step 2: Sort legislative districts in order (1ST, 2ND, 3RD)
            $sorted_legislative_districts = $this->sort_legislative_districts($legislative_districts);
            
            // Step 3: Insert Legislative Districts in sorted order
            $legislative_district_ids = [];
            foreach ($sorted_legislative_districts as $district) {
                if (empty($district)) continue;
                
                $this->db->insert('legislative_districts', [
                    'name' => $district,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $legislative_district_ids[$district] = $this->db->insert_id();
            }

            // Step 4: Sort school districts by legislative district order
            $sorted_school_districts = $this->sort_school_districts($school_districts, $legislative_district_ids);
            
            // Step 5: Insert School Districts in sorted order
            $school_district_ids = [];
            foreach ($sorted_school_districts as $key => $district) {
                if (empty($district['school_district'])) continue;
                
                $legislative_id = isset($legislative_district_ids[$district['legislative_district']]) ? 
                    $legislative_district_ids[$district['legislative_district']] : null;
                
                if ($legislative_id) {
                    $this->db->insert('school_districts', [
                        'name' => $district['school_district'],
                        'legislative_district_id' => $legislative_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $school_district_ids[$key] = $this->db->insert_id();
                }
            }

            // Step 6: Sort schools by school district order
            $sorted_schools_data = $this->sort_schools_data($schools_data, $school_district_ids);
            
            // Step 7: Insert Schools in sorted order
            $schools_inserted = 0;
            foreach ($sorted_schools_data as $school) {
                $district_key = $school['legislative_district'] . '_' . $school['school_district'];
                $school_district_id = isset($school_district_ids[$district_key]) ? $school_district_ids[$district_key] : null;
                
                if ($school_district_id) {
                    $this->db->insert('schools', [
                        'school_id' => $school['school_id'],
                        'name' => $school['school_name'],
                        'school_district_id' => $school_district_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $schools_inserted++;
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                log_message('error', 'Database transaction failed in insert_excel_data');
                return false;
            }

            log_message('info', "Successfully inserted: " . count($legislative_districts) . " legislative districts, " . count($school_districts) . " school districts, " . $schools_inserted . " schools");
            return true;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Excel Model Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sort legislative districts in order: 1ST DISTRICT, 2ND DISTRICT, 3RD DISTRICT
     */
    private function sort_legislative_districts($districts) {
        $sorted = [];
        
        // Look for 1ST DISTRICT
        foreach ($districts as $district) {
            if (strpos($district, '1ST DISTRICT') !== false) {
                $sorted[] = $district;
                break;
            }
        }
        
        // Look for 2ND DISTRICT
        foreach ($districts as $district) {
            if (strpos($district, '2ND DISTRICT') !== false) {
                $sorted[] = $district;
                break;
            }
        }
        
        // Look for 3RD DISTRICT
        foreach ($districts as $district) {
            if (strpos($district, '3RD DISTRICT') !== false) {
                $sorted[] = $district;
                break;
            }
        }
        
        return $sorted;
    }

    /**
     * Sort school districts by legislative district order and then alphabetically
     */
    private function sort_school_districts($school_districts, $legislative_district_ids) {
        $sorted = [];
        
        // Group school districts by legislative district
        $grouped_districts = [];
        foreach ($school_districts as $key => $district) {
            $legislative_name = $district['legislative_district'];
            $grouped_districts[$legislative_name][] = $district;
        }
        
        // Sort each group alphabetically
        foreach ($grouped_districts as &$districts) {
            usort($districts, function($a, $b) {
                return strcmp($a['school_district'], $b['school_district']);
            });
        }
        
        // Build sorted array based on legislative district order
        foreach ($legislative_district_ids as $legislative_name => $id) {
            if (isset($grouped_districts[$legislative_name])) {
                foreach ($grouped_districts[$legislative_name] as $district) {
                    $key = $district['legislative_district'] . '_' . $district['school_district'];
                    $sorted[$key] = $district;
                }
            }
        }
        
        return $sorted;
    }

    /**
     * Sort schools by school district order and then by school name
     */
    private function sort_schools_data($schools_data, $school_district_ids) {
        // Group schools by school district
        $grouped_schools = [];
        foreach ($schools_data as $school) {
            $district_key = $school['legislative_district'] . '_' . $school['school_district'];
            $grouped_schools[$district_key][] = $school;
        }
        
        // Sort each group by school name
        foreach ($grouped_schools as &$schools) {
            usort($schools, function($a, $b) {
                return strcmp($a['school_name'], $b['school_name']);
            });
        }
        
        // Build sorted array based on school district order
        $sorted = [];
        foreach ($school_district_ids as $district_key => $id) {
            if (isset($grouped_schools[$district_key])) {
                foreach ($grouped_schools[$district_key] as $school) {
                    $sorted[] = $school;
                }
            }
        }
        
        return $sorted;
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
        $summary = [
            'legislative_districts' => 0,
            'school_districts' => 0,
            'schools' => 0
        ];
        
        try {
            // Check if database connection is working
            if (!$this->db->conn_id) {
                log_message('error', 'Database connection not available in Excel_model');
                return $summary;
            }
            
            // Use simple queries to avoid any issues
            $result = $this->db->query("SELECT COUNT(*) as count FROM legislative_districts");
            if ($result) {
                $summary['legislative_districts'] = $result->row()->count;
            }
            
            $result = $this->db->query("SELECT COUNT(*) as count FROM school_districts");
            if ($result) {
                $summary['school_districts'] = $result->row()->count;
            }
            
            $result = $this->db->query("SELECT COUNT(*) as count FROM schools");
            if ($result) {
                $summary['schools'] = $result->row()->count;
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
        $data = [];
        
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
            
        // Get schools ordered by school district and name
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
}
?>