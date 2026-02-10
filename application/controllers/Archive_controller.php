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
    }
    
    public function index() {
        try {
            // Load pagination library
            $this->load->library('pagination');
            
            // Configure pagination
            $config['base_url'] = site_url('archive');
            $config['total_rows'] = $this->Archive_model->count_all_archived_records();
            $config['per_page'] = 20;
            $config['uri_segment'] = 2;
            $config['full_tag_open'] = '<ul class="pagination">';
            $config['full_tag_close'] = '</ul>';
            $config['first_tag_open'] = '<li class="page-item">';
            $config['first_tag_close'] = '</li>';
            $config['last_tag_open'] = '<li class="page-item">';
            $config['last_tag_close'] = '</li>';
            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_tag_close'] = '</li>';
            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_tag_close'] = '</li>';
            $config['num_tag_open'] = '<li class="page-item">';
            $config['num_tag_close'] = '</li>';
            $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
            $config['cur_tag_close'] = '</span></li>';
            $config['attributes'] = ['class' => 'page-link'];
            
            $this->pagination->initialize($config);
            
            $page = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
            
            // Get archived records
            $data['archived_records'] = $this->Archive_model->get_all_archived_records(
                $config['per_page'], 
                $page
            );
            
            // Get school years from nutritional_assessments table
            $data['school_years'] = $this->Archive_model->get_distinct_school_years();
            
            $data['pagination'] = $this->pagination->create_links();
            $data['title'] = 'Nutritional Assessment Archive';
            
            $this->load->view('archive', $data);
            
        } catch (Exception $e) {
            // Log the error
            log_message('error', 'Archive controller error: ' . $e->getMessage());
            
            // Show error page
            show_error('An error occurred while loading the archive page. Please try again later.', 500);
        }
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