<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Phpspreadsheet_lib {
    
    public function __construct() {
        // Load Composer's autoloader
        require_once FCPATH . 'vendor/autoload.php';
    }
    
    /**
     * Load a template file
     */
    public function loadTemplate($templatePath) {
        if (!file_exists($templatePath)) {
            throw new Exception('Template file not found: ' . $templatePath);
        }
        return \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
    }
    
    /**
     * Create a new spreadsheet
     */
    public function createSpreadsheet() {
        return new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }
    
    /**
     * Save spreadsheet to output
     */
    public function saveToOutput($spreadsheet, $filename) {
        // Clear output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // Save to output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Get active sheet
     */
    public function getActiveSheet($spreadsheet) {
        return $spreadsheet->getActiveSheet();
    }
}