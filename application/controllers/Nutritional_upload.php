<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritional_upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Nutritional_model');
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
        // Set content type to JSON
        header('Content-Type: application/json');
        
        try {
            // Check if file was uploaded
            if (empty($_FILES['excel_file']['name'])) {
                throw new Exception('No file uploaded.');
            }

            $this->load->library('upload');
            
            $config['upload_path'] = './uploads/temp/';
            $config['allowed_types'] = 'xlsx|xls|csv';
            $config['max_size'] = 5120; // 5MB
            $config['encrypt_name'] = TRUE;

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, TRUE);
            }

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('excel_file')) {
                throw new Exception($this->upload->display_errors());
            }

            $upload_data = $this->upload->data();
            $file_path = $upload_data['full_path'];

            // Load PhpSpreadsheet
            require_once FCPATH . 'vendor/autoload.php';
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray(null, true, true, true);
            
            $extractedStudents = $this->processExcelData($data);
            
            // Clean up uploaded file
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $response = array(
                'success' => true,
                'message' => 'Successfully extracted ' . count($extractedStudents) . ' student record(s).',
                'students' => $extractedStudents
            );
            
            echo json_encode($response);
            return;
            
        } catch (Exception $e) {
            // Clean up uploaded file on error
            if (isset($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
            
            $response = array(
                'success' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage()
            );
            
            echo json_encode($response);
            return;
        }
    }

    private function processExcelData($data) {
        $extractedStudents = array();
        $dataStartRow = $this->findDataStartRow($data);

        if ($dataStartRow === -1) {
            $dataStartRow = 8;
        }

        $columnIndices = array(
            'nameIndex' => 'C',        // Column C
            'birthdayIndex' => 'D',    // Column D
            'weightIndex' => 'E',      // Column E
            'heightIndex' => 'F',      // Column F
            'sexIndex' => 'G',         // Column G
            'bmiIndex' => 'L',         // Column L
            'nutritionalStatusIndex' => 'M', // Column M
            'heightForAgeIndex' => 'N' // Column N
        );

        foreach ($data as $rowNumber => $row) {
            if ($rowNumber < $dataStartRow) continue;
            
            if ($this->isReferenceRow($row)) continue;

            $name = $this->getCellValue($row, $columnIndices['nameIndex']);
            $rawBirthday = $this->getCellValue($row, $columnIndices['birthdayIndex']);
            $rawWeight = $this->getCellValue($row, $columnIndices['weightIndex']);
            $rawHeight = $this->getCellValue($row, $columnIndices['heightIndex']);
            $rawSex = $this->getCellValue($row, $columnIndices['sexIndex']);
            $rawBmi = $this->getCellValue($row, $columnIndices['bmiIndex']);
            $rawNutritionalStatus = $this->getCellValue($row, $columnIndices['nutritionalStatusIndex']);
            $rawHeightForAge = $this->getCellValue($row, $columnIndices['heightForAgeIndex']);

            $birthday = $this->formatExcelDate($rawBirthday);
            $weight = $this->parseNumericValue($rawWeight);
            $height = $this->parseNumericValue($rawHeight);
            $sex = $this->parseSex($rawSex);
            $bmi = $this->parseNumericValue($rawBmi);
            $nutritionalStatus = $this->cleanText($rawNutritionalStatus);
            $heightForAge = $this->cleanText($rawHeightForAge);

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
                !str_starts_with($nameCell, '=IF') && 
                !str_contains($nameCell, 'Names') &&
                !str_contains($nameCell, 'NUTRITIONAL') &&
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
        if (str_starts_with($firstCell, '=IF') || 
            str_contains($firstCell, 'Year-Month') ||
            str_contains($firstCell, 'Severely') ||
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
        
        return trim(strval($value));
    }

    private function parseNumericValue($value) {
        if (empty($value)) return null;
        $numericString = preg_replace('/[^\d.-]/', '', $value);
        $num = floatval($numericString);
        return is_nan($num) ? null : $num;
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

    private function createStudentData($name, $birthday, $weight, $height, $sex, $bmi, $nutritionalStatus, $heightForAge) {
        $ageDisplay = '';
        if (!empty($birthday)) {
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
        }
        
        $heightSquared = $height ? number_format(($height * $height), 4) : null;
        $sbfpBeneficiary = ($nutritionalStatus === 'Severely Wasted' || $nutritionalStatus === 'Wasted') ? 'Yes' : 'No';

        return array(
            'name' => trim($name),
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
            '/(\d{1,2})-(\w{3})-(\d{2,4})/', // 5-Dec-17 format
            '/(\d{4})-(\d{1,2})-(\d{1,2})/',
            '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
            '/(\d{1,2})-(\d{1,2})-(\d{4})/',
        );
        
        foreach ($formats as $format) {
            if (preg_match($format, $excelDate, $match)) {
                if ($format === $formats[0]) {
                    // Handle "5-Dec-17" format
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

    private function getGradeLevels() {
        return array(
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
        );
    }
}