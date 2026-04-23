<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritional_upload extends CI_Controller {

    private $weighing_date = null;
    
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('nutritional_model');

        require_once FCPATH . 'vendor/autoload.php';
        
        // Load the WHO standards helper
        require_once APPPATH . 'helpers/WHO_Standards_helper.php';
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

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp_file);
            } catch (Exception $e) {
                log_message('error', 'Failed to load Excel file: ' . $e->getMessage());
                throw new Exception('Failed to load Excel file: ' . $e->getMessage());
            }

            $worksheet = $spreadsheet->getActiveSheet();
            
            // Extract weighing date from cell C3
            $this->extractWeighingDate($worksheet);
            
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

    /**
     * Extract weighing date from cell C3
     */
    private function extractWeighingDate($worksheet) {
        try {
            $weighingDateCell = $worksheet->getCell('C3');
            $weighingDateValue = $weighingDateCell->getValue();
            
            if (is_numeric($weighingDateValue)) {
                // Excel serial date
                $this->weighing_date = $this->formatExcelSerialDate($weighingDateValue);
            } else {
                $this->weighing_date = $this->formatExcelDate($weighingDateValue);
            }
            
            // Validate the date is reasonable (not the placeholder 2002 date)
            if (!empty($this->weighing_date)) {
                $dateObj = new DateTime($this->weighing_date);
                $minDate = new DateTime('2020-01-01'); // No assessments before 2020
                $maxDate = new DateTime('+1 year');
                
                if ($dateObj < $minDate || $dateObj > $maxDate) {
                    // Date is invalid (like the 2002 placeholder)
                    log_message('info', 'Invalid weighing date found: ' . $this->weighing_date . '. Using current date instead.');
                    $this->weighing_date = date('Y-m-d');
                }
            }
            
            if (empty($this->weighing_date)) {
                // Default to today if no date found
                $this->weighing_date = date('Y-m-d');
            }
        } catch (Exception $e) {
            log_message('error', 'Failed to extract weighing date: ' . $e->getMessage());
            $this->weighing_date = date('Y-m-d');
        }
    }

    /**
     * Process Excel data with new column mapping
     */
    private function processExcelData($data) {
        $extractedStudents = array();
        
        // Data starts from row 7 (header at row 6)
        $dataStartRow = 7;
        
        // Column mapping for new template
        $columnIndices = array(
            'lastNameIndex' => 'B',
            'firstNameIndex' => 'C', 
            'middleInitialIndex' => 'D',
            'birthdayIndex' => 'E',
            'weightIndex' => 'F',
            'heightIndex' => 'G',
            'sexIndex' => 'H'
        );
        
        // Find the last row with actual student data
        $lastDataRow = $this->findLastDataRowNew($data, $dataStartRow);
        
        for ($rowNumber = $dataStartRow; $rowNumber <= $lastDataRow; $rowNumber++) {
            if (!isset($data[$rowNumber])) continue;
            
            $row = $data[$rowNumber];
            
            // Check if row is empty or contains formula/placeholder
            if ($this->isEmptyRow($row) || $this->isPlaceholderRow($row)) {
                continue;
            }
            
            // Get name components
            $lastName = $this->sanitizeName($this->getCellValue($row, $columnIndices['lastNameIndex']));
            $firstName = $this->sanitizeName($this->getCellValue($row, $columnIndices['firstNameIndex']));
            $middleInitial = $this->sanitizeName($this->getCellValue($row, $columnIndices['middleInitialIndex']));
            
            // Combine name: "Last, First M.I"
            $name = $this->combineName($lastName, $firstName, $middleInitial);
            
            if (empty($name)) {
                continue;
            }
            
            // Get other data
            $rawBirthday = $this->getCellValue($row, $columnIndices['birthdayIndex']);
            $rawWeight = $this->getCellValue($row, $columnIndices['weightIndex']);
            $rawHeight = $this->getCellValue($row, $columnIndices['heightIndex']);
            $rawSex = $this->getCellValue($row, $columnIndices['sexIndex']);
            
            $birthday = $this->formatExcelDate($rawBirthday);
            $birthday = $this->sanitizeForJson($birthday);
            
            $weight = $this->sanitizeNumeric($rawWeight);
            $height = $this->sanitizeNumeric($rawHeight);
            $sex = $this->sanitizeForJson($this->parseSex($rawSex));
            
            if ($this->isValidStudentData($name, $weight, $height, $sex)) {
                // Calculate nutritional values
                $bmi = $this->calculateBMI($weight, $height);
                $ageInMonths = $this->calculateAgeInMonths($birthday);
                $nutritionalStatus = $this->getBMIClassification($bmi, $ageInMonths, $sex);
                $heightForAge = $this->getHeightForAgeClassification($height, $ageInMonths, $sex);
                
                $studentData = $this->createStudentData(
                    $name,
                    $birthday,
                    $weight,
                    $height,
                    $sex,
                    $bmi,
                    $nutritionalStatus,
                    $heightForAge,
                    $ageInMonths
                );
                
                $extractedStudents[] = $studentData;
            }
        }
        
        return $extractedStudents;
    }
    
    /**
     * Combine name components: "Last, First M.I"
     */
    private function combineName($lastName, $firstName, $middleInitial) {
        $nameParts = array();
        
        if (!empty($lastName)) {
            $nameParts[] = $lastName;
        }
        
        $firstAndMiddle = array();
        if (!empty($firstName)) {
            $firstAndMiddle[] = $firstName;
        }
        if (!empty($middleInitial)) {
            $mi = rtrim($middleInitial, '.');
            $firstAndMiddle[] = $mi . '.';
        }
        
        if (!empty($firstAndMiddle)) {
            $nameParts[] = implode(' ', $firstAndMiddle);
        }
        
        return implode(', ', $nameParts);
    }
    
    /**
     * Calculate age in months using weighing date
     */
    private function calculateAgeInMonths($birthday) {
        if (empty($birthday) || empty($this->weighing_date)) {
            return 0;
        }
        
        try {
            $birthDate = new DateTime($birthday);
            $weighingDate = new DateTime($this->weighing_date);
            $interval = $birthDate->diff($weighingDate);
            
            $totalMonths = ($interval->y * 12) + $interval->m;

            if ($interval->invert == 1 || $interval->days < 0) {
                return 0;
            }
            
            return $totalMonths;
        } catch (Exception $e) {
            log_message('error', 'Age calculation error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate BMI from weight (kg) and height (meters)
     */
    private function calculateBMI($weight, $height) {
        if ($weight === null || $height === null || $weight <= 0 || $height <= 0) {
            return null;
        }

        $bmi = $weight / ($height * $height);
        
        return round($bmi, 2);
    }
    
    /**
     * Get BMI classification based on age and sex
     */
    private function getBMIClassification($bmi, $ageInMonths, $sex) {
        if ($bmi === null || $ageInMonths === null || empty($sex)) {
            return 'Normal';
        }
        
        if ($ageInMonths < 72) {
            return $this->getSimpleBMIClassification($bmi);
        }
        
        $cutoffs = getWHO_BMICutoffs($ageInMonths, $sex);
        
        if (!$cutoffs) {
            return 'Normal';
        }
        
        if ($bmi <= $cutoffs['severe_wasted']) {
            return 'Severely Wasted';
        } elseif ($bmi >= $cutoffs['wasted_from'] && $bmi <= $cutoffs['wasted_to']) {
            return 'Wasted';
        } elseif ($bmi >= $cutoffs['normal_from'] && $bmi <= $cutoffs['normal_to']) {
            return 'Normal';
        } elseif ($bmi >= $cutoffs['overweight_from'] && $bmi <= $cutoffs['overweight_to']) {
            return 'Overweight';
        } elseif ($bmi >= $cutoffs['obese']) {
            return 'Obese';
        } else {
            return 'Normal';
        }
    }
    
    /**
     * Simple BMI classification for children under 6
     */
    private function getSimpleBMIClassification($bmi) {
        if ($bmi < 14) return 'Severely Wasted';
        if ($bmi < 16) return 'Wasted';
        if ($bmi < 19) return 'Normal';
        if ($bmi < 22) return 'Overweight';
        return 'Obese';
    }
    
    /**
     * Get Height-for-Age classification
     */
    private function getHeightForAgeClassification($height, $ageInMonths, $sex) {
        if ($height === null || $ageInMonths === null || empty($sex)) {
            return 'Normal';
        }
        
        // Convert height from meters to cm for WHO standards
        $heightCm = $height * 100;

        $cutoffs = getWHO_HeightCutoffs($ageInMonths, $sex);
        
        if (!$cutoffs) {
            return 'Normal';
        }
        
        // Classify based on height value
        if ($heightCm <= $cutoffs['severe_stunted']) {
            return 'Severely Stunted';
        } elseif ($heightCm >= $cutoffs['stunted_from'] && $heightCm <= $cutoffs['stunted_to']) {
            return 'Stunted';
        } elseif ($heightCm >= $cutoffs['normal_from'] && $heightCm <= $cutoffs['normal_to']) {
            return 'Normal';
        } elseif ($heightCm >= $cutoffs['tall']) {
            return 'Tall';
        } else {
            return 'Normal';
        }
    }
    
    /**
     * Find last data row for new template
     */
    private function findLastDataRowNew($data, $startRow) {
        $lastRow = $startRow;
        $consecutiveEmptyRows = 0;
        $maxEmptyRows = 5;
        
        for ($rowNumber = $startRow; $rowNumber <= $startRow + 500; $rowNumber++) {
            if (!isset($data[$rowNumber])) break;
            
            $row = $data[$rowNumber];
            
            // Check if this row has student data
            $lastName = $this->getCellValue($row, 'B');
            $firstName = $this->getCellValue($row, 'C');
            $hasData = !empty($lastName) || !empty($firstName);

            $firstCell = isset($row['A']) ? trim($row['A']) : '';
            $isEndMarker = (strpos($firstCell, 'Body Mass Index') !== false || 
                           strpos($firstCell, 'No. of Cases') !== false ||
                           strpos($firstCell, 'Prepared by:') !== false);
            
            if ($isEndMarker) {
                break;
            }
            
            if ($hasData && !$this->isPlaceholderRow($row)) {
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
     * Check if row is empty
     */
    private function isEmptyRow($row) {
        if (empty($row)) return true;
        
        $hasData = false;
        foreach ($row as $cell) {
            if (!empty($cell) && trim($cell) !== '') {
                $hasData = true;
                break;
            }
        }
        return !$hasData;
    }
    
    /**
     * Check if row contains placeholder data (like the example row)
     */
    private function isPlaceholderRow($row) {
        $lastName = $this->getCellValue($row, 'B');
        $firstName = $this->getCellValue($row, 'C');
        
        // Check for placeholder values like "Last Name", "First Name"
        if ($lastName === 'Last Name' || $firstName === 'First Name') {
            return true;
        }
        
        // Check for the example row with weight=1, height=1
        $weight = $this->getCellValue($row, 'F');
        $height = $this->getCellValue($row, 'G');
        if (($weight == '1' || $weight == 1) && ($height == '1' || $height == 1)) {
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
    
    private function isValidStudentData($name, $weight, $height, $sex) {
        if (empty($name)) return false;
        $hasValidWeight = $weight !== null && $weight > 0 && $weight < 200;
        $hasValidHeight = $height !== null && $height > 0 && $height < 3;
        $hasValidSex = !empty($sex);
        return ($hasValidWeight || $hasValidHeight) && $hasValidSex;
    }
    
    private function sanitizeName($name) {
        if (empty($name)) {
            return '';
        }
        
        $name = (string)$name;
        $name = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $name);
        $name = preg_replace('/[^\p{L}\p{M}\s\'\-\.,()]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        // Skip placeholder text
        if (in_array($name, ['Last Name', 'First Name', 'M.I', 'M.I.', 'M.I', 'MI'])) {
            return '';
        }
        
        if (empty($name) || preg_match('/^[\s\'\-\.,]+$/', $name)) {
            return '';
        }
        
        return $name;
    }
    
    private function createStudentData($name, $birthday, $weight, $height, $sex, $bmi, $nutritionalStatus, $heightForAge, $ageInMonths = null) {
        // Calculate age display from weighing date
        $ageDisplay = '';
        if (!empty($birthday) && !empty($this->weighing_date)) {
            try {
                $birthdayDate = new DateTime($birthday);
                $weighingDate = new DateTime($this->weighing_date);
                $interval = $birthdayDate->diff($weighingDate);
                $ageYears = $interval->y;
                $ageMonths = $interval->m;
                
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
            'date' => $this->weighing_date,
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
            'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
        );
    }
}