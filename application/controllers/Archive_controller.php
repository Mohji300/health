<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Archive_controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Archive_model');
        $this->load->helper('url');
        $this->load->library('session');
        
        // Check if user is logged in
        if (!$this->session->userdata('logged_in')) {
            if ($this->input->is_ajax_request()) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Session expired. Please login again.'
                    ]));
                exit;
            } else {
                redirect('login');
            }
        }
        
        // Store user role for easy access
        $this->user_role = $this->session->userdata('role') ?: 'user';
    }
    
    public function index() {
        try {
            // Get archived summary for grouped view
            $data['archived_summary'] = $this->Archive_model->get_archived_summary_by_year_school();
            
            // Get school years from nutritional_assessments table (for archive dropdown)
            $data['school_years'] = $this->Archive_model->get_distinct_school_years();
            
            // Get user role from session
            $data['user_role'] = $this->session->userdata('role') ?: 'user';
            
            $data['title'] = 'Nutritional Assessment Archive';
            
            $this->load->view('archive', $data);
            
        } catch (Exception $e) {
            // Log the error
            log_message('error', 'Archive controller error: ' . $e->getMessage());
            
            // Show error page
            show_error('An error occurred while loading the archive page. Please try again later.', 500);
        }
    }

    public function get_school_details() {
        if (!$this->input->is_ajax_request()) {
            $this->output->set_status_header(400);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid request. Only AJAX requests are allowed.'
                ]));
            return;
        }
        
        try {
            // Get parameters from GET request
            $year = $this->input->get('year', TRUE);
            $school = $this->input->get('school', TRUE);
            $type = $this->input->get('type', TRUE) ?: 'all';
            
            if (empty($year) || empty($school)) {
                throw new Exception('Year and school name are required.');
            }
            
            // Decode URL encoded values
            $year = urldecode($year);
            $school = urldecode($school);
            
            // Get records for this school and year
            $records = $this->Archive_model->get_archived_records_by_year_school($year, $school);
            
            if (empty($records)) {
                $response = [
                    'success' => true,
                    'records' => [],
                    'message' => 'No records found for this school and year.',
                    'year' => $year,
                    'school' => $school,
                    'type' => $type
                ];
            } else {
                // Filter by assessment type if needed
                if ($type !== 'all') {
                    $filtered_records = [];
                    foreach ($records as $record) {
                        if ($record->assessment_type === $type) {
                            $filtered_records[] = $record;
                        }
                    }
                    $records = $filtered_records;
                }
                
                $response = [
                    'success' => true,
                    'records' => $records,
                    'year' => $year,
                    'school' => $school,
                    'type' => $type
                ];
            }
            
        } catch (Exception $e) {
            log_message('error', 'Get school details error: ' . $e->getMessage());
            
            $response = [
                'success' => false,
                'message' => 'Failed to load school details: ' . $e->getMessage()
            ];
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    public function process_archive() {
        // Only allow POST requests
        if (!$this->input->is_ajax_request()) {
            $this->output->set_status_header(400);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid request method. Only AJAX POST requests are allowed.'
                ]));
            return;
        }
        
        try {
            // Check if user has admin role
            if ($this->user_role !== 'admin') {
                throw new Exception('You do not have permission to perform this action. Only administrators can archive records.');
            }
            
            $school_year = $this->input->post('school_year', TRUE);
            
            if (empty($school_year)) {
                throw new Exception('Please select a school year to archive.');
            }
            
            // Check if there are records to archive
            $record_count = $this->Archive_model->count_records_to_archive($school_year);
            
            if ($record_count === 0) {
                $response = [
                    'success' => false,
                    'message' => "No active records found to archive for school year {$school_year}."
                ];
            } else {
                // Process archive
                $result = $this->Archive_model->archive_records($school_year);
                
                if ($result['success']) {
                    $response = [
                        'success' => true,
                        'message' => "Successfully archived {$result['archived_count']} records for school year {$school_year}.",
                        'archived_count' => $result['archived_count']
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => $result['message']
                    ];
                }
            }
            
        } catch (Exception $e) {
            // Log the error
            log_message('error', 'Archive process error: ' . $e->getMessage());
            
            $response = [
                'success' => false,
                'message' => 'An error occurred during the archive process: ' . $e->getMessage()
            ];
        }
        
        // Ensure we're sending proper JSON
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    public function get_record_details($record_id) {
        if (!$this->input->is_ajax_request()) {
            $this->output->set_status_header(400);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid request. Only AJAX requests are allowed.'
                ]));
            return;
        }
        
        try {
            // Validate record ID
            if (!is_numeric($record_id) || $record_id <= 0) {
                throw new Exception('Invalid record ID');
            }
            
            $record = $this->Archive_model->get_archived_record($record_id);
            
            if ($record) {
                $response = [
                    'success' => true,
                    'record' => $record
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Record not found'
                ];
            }
            
        } catch (Exception $e) {
            log_message('error', 'Get record details error: ' . $e->getMessage());
            
            $response = [
                'success' => false,
                'message' => 'Failed to load record details: ' . $e->getMessage()
            ];
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    public function restore_record($record_id) {
        if (!$this->input->is_ajax_request()) {
            $this->output->set_status_header(400);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid request. Only AJAX requests are allowed.'
                ]));
            return;
        }
        
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Method not allowed. Please use POST.'
                ]));
            return;
        }
        
        try {
            // Check if user has admin role
            if ($this->user_role !== 'admin') {
                throw new Exception('You do not have permission to perform this action. Only administrators can restore records.');
            }
            
            // Validate record ID
            if (!is_numeric($record_id) || $record_id <= 0) {
                throw new Exception('Invalid record ID');
            }
            
            $result = $this->Archive_model->restore_record($record_id);
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => 'Record restored successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $result['message']
                ];
            }
            
        } catch (Exception $e) {
            log_message('error', 'Restore record error: ' . $e->getMessage());
            
            $response = [
                'success' => false,
                'message' => 'Failed to restore record: ' . $e->getMessage()
            ];
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}