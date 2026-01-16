<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Excel_upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load database and model
        $this->load->database();
        $this->load->model('Excel_model');
        $this->load->library(['upload', 'session']);
        $this->load->helper(['form', 'url']);
        
        // Check if tables exist, if not create them
        $this->Excel_model->check_tables();
    }

    public function index() {
        $data['title'] = 'CSV Data Upload';
        $data['summary'] = $this->Excel_model->get_data_summary();
        $this->load->view('excel_upload_view', $data);
    }

    public function upload_excel() {
        if ($_FILES['excel_file']['name']) {
            // Create uploads directory if it doesn't exist
            $upload_path = './uploads/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'csv'; // Changed to only accept CSV
            $config['max_size'] = 5120; // 5MB
            $config['encrypt_name'] = TRUE;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('excel_file')) {
                $error = array('error' => $this->upload->display_errors());
                $this->session->set_flashdata('error', $error['error']);
                redirect('excel_upload');
            } else {
                $upload_data = $this->upload->data();
                $file_path = './uploads/' . $upload_data['file_name'];
                
                // Process the CSV file
                $result = $this->process_csv_file($file_path);
                
                // Delete the uploaded file after processing
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                if ($result['success']) {
                    $this->session->set_flashdata('success', $result['message']);
                } else {
                    $this->session->set_flashdata('error', $result['message']);
                }
                
                redirect('excel_upload');
            }
        } else {
            $this->session->set_flashdata('error', 'Please select a CSV file to upload.');
            redirect('excel_upload');
        }
    }

    private function process_csv_file($file_path) {
        try {
            // Check if file exists
            if (!file_exists($file_path)) {
                throw new Exception('Uploaded file not found.');
            }

            // Detect the encoding of the file
            $file_content = file_get_contents($file_path);
            $encoding = mb_detect_encoding($file_content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            
            // If not UTF-8, convert it
            if ($encoding != 'UTF-8') {
                $file_content = mb_convert_encoding($file_content, 'UTF-8', $encoding);
                file_put_contents($file_path, $file_content);
            }

            $rows = [];
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                // Read BOM if present (for UTF-8)
                $bom = "\xef\xbb\xbf";
                if (fgets($handle, 4) !== $bom) {
                    // BOM not found - rewind pointer to start of file
                    rewind($handle);
                }

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Process each field to handle special characters like Ñ
                    foreach ($data as $key => $value) {
                        // Convert to UTF-8 if not already
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                        }
                        // Trim and clean the value
                        $data[$key] = trim($value);
                    }
                    $rows[] = $data;
                }
                fclose($handle);
            }

            // Remove header row if exists
            if (!empty($rows) && isset($rows[0][0]) && $rows[0][0] == 'Legislative District') {
                array_shift($rows);
            }

            $processed_data = [];
            $legislative_districts = [];
            $school_districts = [];

            foreach ($rows as $index => $row) {
                // Skip empty rows or rows missing essential data
                if (empty($row[0]) || empty($row[1]) || empty($row[3])) {
                    continue;
                }

                $legislative_district = trim($row[0]);
                $school_district = trim($row[1]);
                $school_id = isset($row[2]) ? trim($row[2]) : '';
                $school_name = trim($row[3]);

                // Normalize special characters like Ñ
                $legislative_district = $this->normalize_special_chars($legislative_district);
                $school_district = $this->normalize_special_chars($school_district);
                $school_name = $this->normalize_special_chars($school_name);

                // Validate data
                if (empty($legislative_district) || empty($school_district) || empty($school_name)) {
                    continue;
                }

                // Collect unique legislative districts
                if (!in_array($legislative_district, $legislative_districts)) {
                    $legislative_districts[] = $legislative_district;
                }

                // Collect unique school districts with their legislative district
                $district_key = $legislative_district . '_' . $school_district;
                if (!array_key_exists($district_key, $school_districts)) {
                    $school_districts[$district_key] = [
                        'legislative_district' => $legislative_district,
                        'school_district' => $school_district
                    ];
                }

                // Collect school data
                $processed_data[] = [
                    'legislative_district' => $legislative_district,
                    'school_district' => $school_district,
                    'school_id' => $school_id,
                    'school_name' => $school_name
                ];
            }

            // Check if we have data to process
            if (empty($processed_data)) {
                throw new Exception('No valid data found in the CSV file. Please check the format and ensure all required columns are filled.');
            }

            // Insert data into database
            $insert_result = $this->Excel_model->insert_excel_data($legislative_districts, $school_districts, $processed_data);

            if (!$insert_result) {
                throw new Exception('Failed to insert data into database. Please check if the database tables exist.');
            }

            return [
                'success' => true,
                'message' => "Data imported successfully!<br>" .
                            "✓ Legislative Districts: " . count($legislative_districts) . "<br>" .
                            "✓ School Districts: " . count($school_districts) . "<br>" .
                            "✓ Schools: " . count($processed_data)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error processing CSV file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Normalize special characters like Ñ to ensure proper handling
     */
    private function normalize_special_chars($string) {
        // Ensure the string is in UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }
        
        // You can add specific character replacements if needed
        // For example, if you need to replace specific characters:
        // $string = str_replace(['Ã‘', 'Ã±'], ['Ñ', 'ñ'], $string);
        
        return $string;
    }

    public function clear_data() {
        $result = $this->Excel_model->clear_all_data();
        
        if ($result) {
            $this->session->set_flashdata('success', 'All data cleared successfully!');
        } else {
            $this->session->set_flashdata('error', 'Error clearing data.');
        }
        
        redirect('excel_upload');
    }
}
?>