<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritional_upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('nutritional_model');

        require_once FCPATH . 'vendor/autoload.php';
    }

    public function index() {
        $data = array(
            'title' => 'Nutritional Status Upload',
            'gradeLevels' => $this->getGradeLevels()
        );
        
        $this->load->view('templates/header', $data);
        $this->load->view('nutritional_upload', $data);
        $this->load->view('templates/footer');
    }

    public function process_excel() {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            log_message('error', "PHP Error: $errstr in $errfile on line $errline");
            return true;
        });

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');

        if (!$this->session->userdata('user_id')) {
            log_message('error', 'Session expired - no user_id');
            echo json_encode([
                'success' => false, 
                'message' => 'Session expired. Please login again.'
            ]);
            exit;
        }
        
        try {
            // Check file upload
            if (empty($_FILES['excel_file']['name'])) {
                throw new Exception('No file uploaded.');
            }

            $file_ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, ['xlsx', 'xls', 'csv'])) {
                throw new Exception('Invalid file type. Only XLSX, XLS, and CSV files are allowed.');
            }

            if ($_FILES['excel_file']['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size exceeds 5MB limit.');
            }

            if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];
                $error_msg = $upload_errors[$_FILES['excel_file']['error']] ?? 'Unknown upload error';
                throw new Exception('Upload error: ' . $error_msg);
            }

            $tmp_file = $_FILES['excel_file']['tmp_name'];

            if (!file_exists($tmp_file) || !is_readable($tmp_file)) {
                throw new Exception('Temporary file is not accessible.');
            }

            $required_extensions = ['zip', 'xml', 'dom', 'gd', 'mbstring'];
            foreach ($required_extensions as $ext) {
                if (!extension_loaded($ext)) {
                    throw new Exception("Required PHP extension '{$ext}' is not installed.");
                }
            }

            require_once FCPATH . 'vendor/autoload.php';

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp_file);
            } catch (Exception $e) {
                log_message('error', 'Failed to load Excel file: ' . $e->getMessage());
                throw new Exception('Failed to load Excel file: ' . $e->getMessage());
            }

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

            $extractedStudents = $this->processExcelData($data);

            unset($spreadsheet, $worksheet, $data);
            gc_collect_cycles();

            while (ob_get_level()) {
                ob_end_clean();
            }

            $response = [
                'success' => true,
                'message' => 'Successfully extracted ' . count($extractedStudents) . ' student record(s).',
                'students' => $extractedStudents
            ];
            
            // Encode to JSON with error handling
            $json = json_encode($response);
            
            if ($json === false) {
                $jsonError = json_last_error_msg();
                log_message('error', 'JSON encode error: ' . $jsonError);

                $cleanStudents = [];
                foreach ($extractedStudents as $index => $student) {
                    $cleanStudent = [];
                    foreach ($student as $key => $value) {
                        if (is_string($value)) {
                            $clean = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                            $clean = preg_replace('/[^\p{L}\p{M}\p{N}\p{P}\p{Z}]/u', '', $clean);
                            $cleanStudent[$key] = $clean;
                        } else {
                            $cleanStudent[$key] = $value;
                        }
                    }
                    $cleanStudents[] = $cleanStudent;
                }
                
                $response['students'] = $cleanStudents;
                $response['message'] = 'Successfully extracted ' . count($extractedStudents) . ' student records.';
                
                $json = json_encode($response);
                
                if ($json === false) {
                    // Last resort - minimal response
                    $response = [
                        'success' => true,
                        'message' => 'Successfully extracted ' . count($extractedStudents) . ' student records.',
                        'students' => array_map(function($student) {
                            return [
                                'name' => mb_convert_encoding($student['name'] ?? '', 'UTF-8', 'UTF-8'),
                                'birthday' => $student['birthday'] ?? '',
                                'weight' => $student['weight'] ?? 0,
                                'height' => $student['height'] ?? 0,
                                'sex' => $student['sex'] ?? '',
                                'nutritional_status' => $student['nutritional_status'] ?? 'Normal',
                                'height_for_age' => $student['height_for_age'] ?? 'Normal'
                            ];
                        }, $extractedStudents)
                    ];
                    
                    $json = json_encode($response);
                }
            }

            header('Content-Length: ' . strlen($json));
            echo $json;
            exit;

        } catch (Exception $e) {
            log_message('error', 'Exception in process_excel: ' . $e->getMessage());

            while (ob_get_level()) {
                ob_end_clean();
            }

            $response = [
                'success' => false, 
                'message' => 'Error processing file: ' . $e->getMessage()
            ];
            
            $json = json_encode($response);
            header('Content-Length: ' . strlen($json));
            echo $json;
            exit;
        }
    }

    private function processExcelData($data) {
        $extractedStudents = array();
        $dataStartRow = $this->findDataStartRow($data);

        if ($dataStartRow === -1) {
            $dataStartRow = 8;
        }

        $columnIndices = array(
            'nameIndex' => 'C',        
            'birthdayIndex' => 'D',    
            'weightIndex' => 'E',     
            'heightIndex' => 'F',      
            'sexIndex' => 'G',         
            'bmiIndex' => 'L',         
            'nutritionalStatusIndex' => 'M', 
            'heightForAgeIndex' => 'N'
        );

        // Find the last row with actual student data
        $lastDataRow = $this->findLastDataRow($data, $dataStartRow);
        
        // Only process up to the last data row
        for ($rowNumber = $dataStartRow; $rowNumber <= $lastDataRow; $rowNumber++) {
            if (!isset($data[$rowNumber])) continue;
            
            $row = $data[$rowNumber];
            
            if ($this->isReferenceRow($row)) continue;

            $name = $this->sanitizeName($this->getCellValue($row, $columnIndices['nameIndex']));

            if (empty($name) || $name === 'f' || $name === 'm') {
                continue;
            }

            if (strpos($name, '=') === 0 || strpos($name, 'IF(') !== false) {
                continue;
            }

            $rawBirthday = $this->getCellValue($row, $columnIndices['birthdayIndex']);
            $rawWeight = $this->getCellValue($row, $columnIndices['weightIndex']);
            $rawHeight = $this->getCellValue($row, $columnIndices['heightIndex']);
            $rawSex = $this->getCellValue($row, $columnIndices['sexIndex']);
            $rawBmi = $this->getCellValue($row, $columnIndices['bmiIndex']);
            $rawNutritionalStatus = $this->getCellValue($row, $columnIndices['nutritionalStatusIndex']);
            $rawHeightForAge = $this->getCellValue($row, $columnIndices['heightForAgeIndex']);

            $birthday = $this->formatExcelDate($rawBirthday);
            $birthday = $this->sanitizeForJson($birthday);
            
            $weight = $this->sanitizeNumeric($rawWeight);
            $height = $this->sanitizeNumeric($rawHeight);
            $sex = $this->sanitizeForJson($this->parseSex($rawSex));
            $bmi = $this->sanitizeNumeric($rawBmi);
            $nutritionalStatus = $this->sanitizeForJson($this->cleanText($rawNutritionalStatus));
            $heightForAge = $this->sanitizeForJson($this->cleanText($rawHeightForAge));

            if ($this->isValidStudentData($name, $weight, $height, $sex)) {
                $studentData = $this->createStudentData(
                    $name,
                    $birthday,
                    $weight,
                    $height,
                    $sex,
                    $bmi,
                    $nutritionalStatus,
                    $heightForAge
                );
                
                $extractedStudents[] = $studentData;
            }
        }

        return $extractedStudents;
    }

    private function findDataStartRow($data) {
        foreach ($data as $rowNumber => $row) {
            if ($rowNumber > 20) break;
            
            $nameCell = isset($row['C']) ? trim($row['C']) : '';
            if (!empty($nameCell) && 
                strpos($nameCell, '=IF') !== 0 && 
                strpos($nameCell, 'Names') === false &&
                strpos($nameCell, 'NUTRITIONAL') === false &&
                strlen($nameCell) > 1 &&
                !preg_match('/^[0-9\.\-\s]+$/', $nameCell) &&
                $nameCell !== 'f' &&
                $nameCell !== 'm') {
                return $rowNumber;
            }
        }
        return -1;
    }

    private function isReferenceRow($row) {
        $firstCell = isset($row['A']) ? $row['A'] : '';
        if (strpos($firstCell, '=IF') === 0 || 
            strpos($firstCell, 'Year-Month') !== false ||
            strpos($firstCell, 'Severely') !== false ||
            (empty($firstCell) && count($row) < 3)) {
            return true;
        }
        return false;
    }

    private function getCellValue($row, $column) {
        if (!isset($row[$column]) || $row[$column] === null) {
            return '';
        }
        
        $value = $row[$column];
        if (is_numeric($value) && $value > 25569 && $value < 50000) {
            return $this->formatExcelSerialDate($value);
        }

        $value = strval($value);

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        
        return trim($value);
    }

    private function parseSex($sexValue) {
        if (empty($sexValue)) return '';
        $sexStr = strtoupper(trim($sexValue));
        if (in_array($sexStr, ['M', 'MALE', 'L', 'LAKI', 'BOY'])) return 'M';
        if (in_array($sexStr, ['F', 'FEMALE', 'P', 'PEREMPUAN', 'GIRL'])) return 'F';
        return '';
    }

    private function cleanText($text) {
        if (empty($text)) return '';
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function isValidStudentData($name, $weight, $height, $sex) {
        if (empty($name) || $name === 'f' || $name === 'm') return false;
        $hasValidWeight = $weight !== null && $weight > 0 && $weight < 200;
        $hasValidHeight = $height !== null && $height > 0 && $height < 3;
        $hasValidSex = !empty($sex);
        return ($hasValidWeight || $hasValidHeight) && $hasValidSex;
    }

    /**
     * Find the last row that contains actual data
     */
    private function findLastDataRow($data, $startRow) {
        $lastRow = $startRow;
        $consecutiveEmptyRows = 0;
        $maxEmptyRows = 5;
        
        for ($rowNumber = $startRow; $rowNumber <= $startRow + 500; $rowNumber++) {
            if (!isset($data[$rowNumber])) break;
            
            $row = $data[$rowNumber];
            $name = $this->getCellValue($row, 'C');
            $firstCell = isset($row['A']) ? trim($row['A']) : '';

            if (strpos($firstCell, 'Body Mass Index') !== false || 
                strpos($firstCell, 'No. of Cases') !== false ||
                strpos($firstCell, 'Severely Wasted') !== false ||
                strpos($firstCell, 'Prepared by:') !== false) {
                break;
            }

            if (!empty($name) && $name !== 'f' && $name !== 'm' && 
                strpos($name, '=IF') !== 0 && 
                strpos($name, 'Year-Month') === false &&
                strlen($name) > 1) {
                $lastRow = $rowNumber;
                $consecutiveEmptyRows = 0;
            } else {
                $consecutiveEmptyRows++;
                if ($consecutiveEmptyRows > $maxEmptyRows) {
                    break;
                }
            }
        }
        
        return $lastRow;
    }

    /**
     * Special sanitization for names
     */
    private function sanitizeName($name) {
        if (empty($name)) {
            return '';
        }

        $name = (string)$name;

        $name = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $name);

        $name = preg_replace('/[^\p{L}\p{M}\s\'\-\.,()]/u', '', $name);

        $name = preg_replace('/\s+/', ' ', $name);

        $name = trim($name);

        if (empty($name) || preg_match('/^[\s\'\-\.,]+$/', $name)) {
            return '';
        }
        
        return $name;
    }

    private function createStudentData($name, $birthday, $weight, $height, $sex, $bmi, $nutritionalStatus, $heightForAge) {
        $ageDisplay = '';
        if (!empty($birthday)) {
            try {
                $birthdayDate = new DateTime($birthday);
                $today = new DateTime();
                $interval = $today->diff($birthdayDate);
                $ageYears = $interval->y;
                $ageMonths = $interval->m;
                
                if ($interval->d < 0) {
                    $ageMonths--;
                    if ($ageMonths < 0) {
                        $ageYears--;
                        $ageMonths = 11;
                    }
                }
                
                $ageDisplay = $ageYears . '|' . $ageMonths;
            } catch (Exception $e) {
                $ageDisplay = '0|0';
            }
        }
        
        $heightSquared = $height ? number_format(($height * $height), 4) : null;
        $sbfpBeneficiary = ($nutritionalStatus === 'Severely Wasted' || $nutritionalStatus === 'Wasted') ? 'Yes' : 'No';

        return array(
            'name' => $name,
            'birthday' => $birthday,
            'weight' => $weight,
            'height' => $height,
            'sex' => $sex,
            'height_squared' => $heightSquared,
            'age_display' => $ageDisplay,
            'bmi' => $bmi,
            'nutritional_status' => $nutritionalStatus,
            'height_for_age' => $heightForAge,
            'sbfp_beneficiary' => $sbfpBeneficiary
        );
    }

    private function formatExcelSerialDate($serial) {
        $utc_days = floor($serial - 25569);
        $utc_value = $utc_days * 86400;
        $date_info = new DateTime('@' . $utc_value);
        return $date_info->format('Y-m-d');
    }

    private function formatExcelDate($excelDate) {
        if (empty($excelDate)) return '';
        
        if (is_numeric($excelDate)) {
            return $this->formatExcelSerialDate($excelDate);
        }
        
        $formats = array(
            '/(\d{1,2})-(\w{3})-(\d{2,4})/', 
            '/(\d{4})-(\d{1,2})-(\d{1,2})/',
            '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
            '/(\d{1,2})-(\d{1,2})-(\d{4})/',
        );
        
        foreach ($formats as $format) {
            if (preg_match($format, $excelDate, $match)) {
                if ($format === $formats[0]) {
                    $day = intval($match[1]);
                    $monthNames = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
                    $month = array_search($match[2], $monthNames) + 1;
                    $year = intval($match[3]);
                    if ($year < 100) $year += 2000;
                } else if ($format === $formats[1]) {
                    $year = intval($match[1]);
                    $month = intval($match[2]);
                    $day = intval($match[3]);
                } else {
                    $month = intval($match[1]);
                    $day = intval($match[2]);
                    $year = intval($match[3]);
                }
                
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }
        
        try {
            $date = new DateTime($excelDate);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Sanitize string data for JSON
     */
    private function sanitizeForJson($value) {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string)$value;

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }

        $value = trim($value);
        
        return $value;
    }

    /**
     * Sanitize numeric values
     */
    private function sanitizeNumeric($value) {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove any non-numeric characters except decimal point and minus sign
        $clean = preg_replace('/[^\d.-]/', '', $value);
        
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return null;
        }
        
        return is_numeric($clean) ? floatval($clean) : null;
    }

    private function getGradeLevels() {
        return array(
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
        );
    }
}