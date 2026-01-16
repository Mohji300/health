<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// debugging controller to help diagnose issues with district dashboard data retrieval
class Debug_dashboard extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('district_dashboard_model');
        $this->load->helper('url');
        $this->load->library('session');
        
        // Require login
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }
    }
    
    public function index() {
        $user_district = $this->session->userdata('district') ?? 'Unknown District';
        $parsed_district = $user_district ? preg_replace('/\s+(District|Division)$/', '', $user_district) : 'Unknown';
        
        echo "<h1>Dashboard Debug Information</h1>";
        echo "<h3>User District: $user_district</h3>";
        echo "<h3>Parsed District: $parsed_district</h3>";
        
        // Check data consistency
        echo "<h2>Data Consistency Check</h2>";
        $debug_info = $this->district_dashboard_model->debug_data_consistency($parsed_district);
        echo "<pre>" . print_r($debug_info, true) . "</pre>";
        
        // Test the reports method
        echo "<h2>Test get_district_reports_summary</h2>";
        $reports = $this->district_dashboard_model->get_district_reports_summary($parsed_district);
        echo "<pre>" . print_r($reports, true) . "</pre>";
        
        // Test the schools method
        echo "<h2>Test get_district_schools</h2>";
        $schools = $this->district_dashboard_model->get_district_schools($parsed_district);
        echo "<pre>" . print_r($schools, true) . "</pre>";
        
        // Check table structures
        echo "<h2>Table Structures</h2>";
        $tables = ['schools', 'school_districts', 'nutritional_assessments', 'nutritional_status'];
        
        foreach ($tables as $table) {
            echo "<h3>$table</h3>";
            if ($this->db->table_exists($table)) {
                $fields = $this->db->list_fields($table);
                echo "Columns: <pre>" . print_r($fields, true) . "</pre>";
                
                // Show sample data
                $sample = $this->db->select('*')->from($table)->limit(3)->get()->result_array();
                echo "Sample Data: <pre>" . print_r($sample, true) . "</pre>";
            } else {
                echo "<p style='color: red;'>Table does not exist</p>";
            }
        }
        
        // Run a direct SQL check
        echo "<h2>Direct SQL Check</h2>";
        
        // Check if nutritional_assessments has data
        if ($this->db->table_exists('nutritional_assessments')) {
            $sql = "SELECT COUNT(*) as total FROM nutritional_assessments";
            $result = $this->db->query($sql)->row();
            echo "<p>Total assessments: " . $result->total . "</p>";
            
            $sql = "SELECT COUNT(DISTINCT school_id) as schools_with_data FROM nutritional_assessments WHERE school_id IS NOT NULL";
            $result = $this->db->query($sql)->row();
            echo "<p>Schools with data: " . $result->schools_with_data . "</p>";
            
            // Check for your specific district
            $sql = "SELECT 
                    sd.name as district_name,
                    COUNT(DISTINCT s.id) as total_schools,
                    COUNT(DISTINCT na.school_id) as schools_with_assessments
                FROM school_districts sd
                LEFT JOIN schools s ON sd.id = s.school_district_id
                LEFT JOIN nutritional_assessments na ON s.id = na.school_id
                WHERE sd.name LIKE '%" . $this->db->escape_like_str($parsed_district) . "%'
                GROUP BY sd.id, sd.name";
            
            $result = $this->db->query($sql)->result();
            echo "<p>District check SQL result:</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        }
    }
}