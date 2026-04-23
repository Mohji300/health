<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Excel_upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->load->database();
        $this->load->model('excel_model');
        $this->load->model('User_model'); // Load user model
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['form', 'url', 'string']);
        
        // Load PhpSpreadsheet
        require_once FCPATH . 'vendor/autoload.php';
        
        $this->excel_model->check_tables();
    }

    public function index() {
        $data['title'] = 'Excel/CSV Data Upload';
        $data['summary'] = $this->excel_model->get_data_summary();
        $this->load->view('excel_upload_view', $data);
    }

    public function upload_excel() {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            log_message('error', "PHP Error: $errstr in $errfile on line $errline");
            return true;
        });

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');

        try {
            if (empty($_FILES['excel_file']['name'])) {
                throw new Exception('No file uploaded.');
            }

            $file_ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, array('xlsx', 'xls', 'csv'))) {
                throw new Exception('Invalid file type. Only XLSX, XLS, and CSV files are allowed.');
            }

            if ($_FILES['excel_file']['size'] > 10 * 1024 * 1024) {
                throw new Exception('File size exceeds 10MB limit.');
            }

            if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = array(
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                );
                $error_msg = isset($upload_errors[$_FILES['excel_file']['error']]) ? 
                    $upload_errors[$_FILES['excel_file']['error']] : 'Unknown upload error';
                throw new Exception('Upload error: ' . $error_msg);
            }

            $tmp_file = $_FILES['excel_file']['tmp_name'];

            if (!file_exists($tmp_file) || !is_readable($tmp_file)) {
                throw new Exception('Temporary file is not accessible.');
            }

            if ($file_ext === 'csv') {
                $result = $this->process_csv_file($tmp_file);
            } else {
                $result = $this->process_excel_file($tmp_file);
            }

            // Clean up
            gc_collect_cycles();

            while (ob_get_level()) {
                ob_end_clean();
            }

            $response = array(
                'success' => $result['success'],
                'message' => $result['message']
            );
            
            $json = json_encode($response);
            
            if ($json === false) {
                $response['message'] = $result['message'];
                $json = json_encode($response);
            }

            header('Content-Length: ' . strlen($json));
            echo $json;
            exit;

        } catch (Exception $e) {
            log_message('error', 'Exception in upload_excel: ' . $e->getMessage());

            while (ob_get_level()) {
                ob_end_clean();
            }

            $response = array(
                'success' => false, 
                'message' => 'Error processing file: ' . $e->getMessage()
            );
            
            $json = json_encode($response);
            header('Content-Length: ' . strlen($json));
            echo $json;
            exit;
        }
    }

    private function process_excel_file($file_path) {
        try {
            // Load Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            $data = $worksheet->rangeToArray(
                'A1:' . $highestColumn . $highestRow,
                NULL,
                TRUE,
                TRUE,
                TRUE
            );
            
            // Process the data
            $result = $this->processDataArray($data);
            
            // Clean up
            unset($spreadsheet, $worksheet, $data);
            gc_collect_cycles();
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Excel processing error: ' . $e->getMessage());
            throw new Exception('Failed to process Excel file: ' . $e->getMessage());
        }
    }
    
    private function process_csv_file($file_path) {
        try {
            // Read CSV file
            $rows = array();
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                $rowNumber = 1;
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $rows[$rowNumber] = $data;
                    $rowNumber++;
                }
                fclose($handle);
            }
            
            // Process the data
            $result = $this->processCsvDataArray($rows);
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'CSV processing error: ' . $e->getMessage());
            throw new Exception('Failed to process CSV file: ' . $e->getMessage());
        }
    }
    
    private function processCsvDataArray($rows) {
        // For CSV, convert to column-letter based array
        $data = array();
        foreach ($rows as $rowNum => $rowData) {
            $row = array();
            $col = 'A';
            foreach ($rowData as $value) {
                $value = $this->normalize_special_chars($value);
                $row[$col] = $value;
                $col++;
            }
            $data[$rowNum] = $row;
        }
        
        return $this->processDataArray($data);
    }
    
    private function processDataArray($data) {
        // Find the header row (row 3 in your Excel)
        $dataStartRow = 4; // Data starts at row 4
        
        // Find the title (row 2, column C)
        $title = '';
        if (isset($data[2]['C'])) {
            $title = $this->cleanText($data[2]['C']);
        }
        
        // Determine sheet type from title
        $sheetType = 'Unknown';
        $titleUpper = strtoupper($title);
        if (strpos($titleUpper, 'ELEMENTARY') !== false) {
            $sheetType = 'Elementary';
        } elseif (strpos($titleUpper, 'SECONDARY') !== false) {
            $sheetType = 'Secondary';
        } elseif (strpos($titleUpper, 'PRIVATE') !== false) {
            $sheetType = 'Private';
        }
        
        $processed_data = array();
        $legislative_districts = array();
        $school_districts = array();
        $users_created = array();
        $users_failed = array();
        $users_skipped = array();
        
        $stats = array(
            'total' => 0,
            'elementary' => 0,
            'secondary' => 0,
            'private' => 0,
            'integrated' => 0,
            'users_created' => 0,
            'users_failed' => 0,
            'users_skipped' => 0
        );
        
        // Process rows starting from row 4
        for ($rowNumber = $dataStartRow; $rowNumber <= count($data); $rowNumber++) {
            if (!isset($data[$rowNumber])) continue;
            
            $row = $data[$rowNumber];
            
            // Get values from columns
            $school_id = $this->getCellValue($row, 'B');
            $school_name = $this->getCellValue($row, 'C');
            $school_district = $this->getCellValue($row, 'D');
            $type_column_value = $this->getCellValue($row, 'E');
            $legislative_district = $this->getCellValue($row, 'F');
            
            // Skip empty rows
            if (empty($school_name) && empty($school_id)) {
                continue;
            }
            
            // Clean the data
            $school_name = $this->sanitizeName($school_name);
            $school_district = $this->cleanText($school_district);
            $legislative_district = $this->cleanText($legislative_district);
            
            // Skip if missing required fields
            if (empty($school_name)) {
                continue;
            }
            
            if (empty($school_district)) {
                continue;
            }
            
            if (empty($legislative_district)) {
                continue;
            }
            
            // Skip if no school_id (required for username)
            if (empty($school_id)) {
                log_message('debug', "Skipping row $rowNumber - No School ID provided");
                $users_skipped[] = array(
                    'school' => $school_name,
                    'reason' => 'No School ID provided'
                );
                $stats['users_skipped']++;
                continue;
            }
            
            // Determine school level
            $school_level = '';
            
            if (!empty($type_column_value) && strpos($type_column_value, '=') !== 0) {
                $school_level = $this->cleanText($type_column_value);
            }
            
            if (empty($school_level)) {
                $school_level = $this->determineSchoolLevelFromId($school_id);
            }
            
            if (empty($school_level) || $school_level == 'Unknown') {
                if ($sheetType != 'Unknown') {
                    $school_level = $sheetType;
                } else {
                    $school_level = 'Unknown';
                }
            }
            
            // Count by school level
            $levelLower = strtolower($school_level);
            if (strpos($levelLower, 'elem') !== false) {
                $stats['elementary']++;
            } elseif (strpos($levelLower, 'second') !== false || strpos($levelLower, 'high') !== false) {
                $stats['secondary']++;
            } elseif (strpos($levelLower, 'private') !== false) {
                $stats['private']++;
            } elseif (strpos($levelLower, 'integrated') !== false) {
                $stats['integrated']++;
            }
            
            // Collect unique legislative districts
            if (!in_array($legislative_district, $legislative_districts)) {
                $legislative_districts[] = $legislative_district;
            }
            
            // Collect unique school districts with their legislative district
            $districtKey = $legislative_district . '_' . $school_district;
            if (!isset($school_districts[$districtKey])) {
                $school_districts[$districtKey] = array(
                    'legislative_district' => $legislative_district,
                    'school_district' => $school_district
                );
            }
            
            // Prepare school data
            $school_data = array(
                'legislative_district' => $legislative_district,
                'school_district' => $school_district,
                'school_id' => $school_id,
                'school_name' => $school_name,
                'school_level' => $school_level,
                'school_size' => null
            );
            
            $processed_data[] = $school_data;
            
            // CREATE USER ACCOUNT FOR THIS SCHOOL using School ID as username/email
            $user_result = $this->createUserAccount($school_data);
            if ($user_result['success']) {
                $users_created[] = $user_result['user'];
                $stats['users_created']++;
            } else {
                $users_failed[] = array(
                    'school' => $school_name,
                    'school_id' => $school_id,
                    'error' => $user_result['message']
                );
                $stats['users_failed']++;
            }
            
            $stats['total']++;
        }
        
        // Check if we have data to process
        if (empty($processed_data)) {
            throw new Exception('No valid data found in the file. Please check the format.');
        }
        
        // Insert data into database (append mode)
        $insert_result = $this->excel_model->insert_excel_data_append(
            $legislative_districts, 
            $school_districts, 
            $processed_data
        );
        
        if (!$insert_result) {
            throw new Exception('Failed to insert data into database.');
        }
        
    }
    
    /**
     * Create a user account for a school using School ID as username/email
     */
    private function createUserAccount($school_data) {
        try {
            // Use School ID as the email/username
            $email = $school_data['school_id'] . '@gmail.com';
            
            // Check if user already exists by email
            $this->load->model('User_model');
            $existing_user = $this->User_model->get_user_by_email($email);
            
            if ($existing_user) {
                return array(
                    'success' => false,
                    'message' => 'User already exists with email: ' . $email
                );
            }
            
            // Also check if user exists by school_id
            $existing_by_school_id = $this->User_model->get_user_by_school_id($school_data['school_id']);
            if ($existing_by_school_id) {
                return array(
                    'success' => false,
                    'message' => 'User already exists with School ID: ' . $school_data['school_id']
                );
            }
            
            // Prepare user data
            $user_data = array(
                'name' => $school_data['school_name'],
                'email' => $email,
                'password' => $school_data['school_id'],
                'role' => 'user',
                'school_id' => $school_data['school_id'],
                'legislative_district' => $school_data['legislative_district'],
                'school_district' => $school_data['school_district'],
                'school_level' => $school_data['school_level']
            );
            
            // Create the user
            $user_id = $this->User_model->create_user($user_data);
            
            if ($user_id) {
                return array(
                    'success' => true,
                    'user' => array(
                        'id' => $user_id,
                        'name' => $school_data['school_name'],
                        'email' => $email,
                        'school_id' => $school_data['school_id'],
                        'password' => $school_data['school_id']
                    ),
                    'message' => 'User created successfully'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Failed to create user in database'
                );
            }
            
        } catch (Exception $e) {
            log_message('error', 'User creation error for ' . $school_data['school_name'] . ': ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    private function determineSchoolLevelFromId($school_id) {
        if (empty($school_id)) {
            return 'Unknown';
        }
        
        $school_id = trim($school_id);
        $firstDigit = substr($school_id, 0, 1);
        
        switch ($firstDigit) {
            case '1':
                return 'Elementary';
            case '3':
                return 'Secondary';
            case '4':
                return 'Private';
            case '5':
                return 'Integrated';
            default:
                return 'Unknown';
        }
    }
    
    private function getCellValue($row, $column) {
        if (!isset($row[$column]) || $row[$column] === null || $row[$column] === '') {
            return '';
        }
        
        $value = $row[$column];
        
        // Skip formula strings that start with =
        if (is_string($value) && strpos($value, '=') === 0) {
            return '';
        }
        
        // Handle Excel serial dates
        if (is_numeric($value) && $value > 25569 && $value < 50000) {
            return $this->formatExcelSerialDate($value);
        }
        
        $value = strval($value);
        
        // Remove control characters
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        
        return trim($value);
    }
    
    private function formatExcelSerialDate($serial) {
        $utc_days = floor($serial - 25569);
        $utc_value = $utc_days * 86400;
        $date_info = new DateTime('@' . $utc_value);
        return $date_info->format('Y-m-d');
    }
    
    private function sanitizeName($name) {
        if (empty($name)) {
            return '';
        }
        
        $name = (string)$name;
        
        // Remove control characters
        $name = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $name);
        
        // Allow letters, spaces, hyphens, apostrophes, periods, commas, parentheses
        $name = preg_replace('/[^\p{L}\p{M}\s\'\-\.,()]/u', '', $name);
        
        // Normalize spaces
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim($name);
    }
    
    private function cleanText($text) {
        if (empty($text)) {
            return '';
        }
        
        $text = (string)$text;
        
        // Skip formula strings
        if (strpos($text, '=') === 0) {
            return '';
        }
        
        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Normalize spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    private function normalize_special_chars($string) {
        if (empty($string)) {
            return '';
        }
        
        // Ensure UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }
        
        // Replace common special characters
        $string = str_replace(
            array('Ã‘', 'Ã±', 'â€¢', 'â€“', 'â€”', 'â€™', 'â€˜'),
            array('Ñ', 'ñ', '•', '–', '—', "'", "'"),
            $string
        );
        
        return trim($string);
    }

    public function clear_data() {
        $result = $this->excel_model->clear_all_data();
        
        if ($result) {
            $this->session->set_flashdata('success', 'All data cleared successfully!');
        } else {
            $this->session->set_flashdata('error', 'Error clearing data.');
        }
        
        redirect('excel_upload');
    }
}