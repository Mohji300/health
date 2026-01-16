<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NutritionalAssessment extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nutritional_assessment_model');
        $this->load->model('School_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
        $this->load->database();
    }

    /**
     * Show the nutritional assessment form
     */
    public function index()
    {
        $data = [];
        $data['legislative_district'] = $this->input->get('legislative_district', TRUE);
        $data['school_district'] = $this->input->get('school_district', TRUE);
        $data['grade'] = $this->input->get('grade', TRUE);
        $data['section'] = $this->input->get('section', TRUE);
        $data['school_id'] = $this->input->get('school_id', TRUE) ?: 'N/A'; 
        $data['school_name'] = $this->input->get('school_name', TRUE) ?: 'Unknown School';
        $data['assessment_type'] = $this->input->get('assessment_type', TRUE) ?: 'baseline';
        
        // Check if assessment already exists for this section and type
        $data['has_existing_assessment'] = $this->Nutritional_assessment_model->assessment_exists(
            $data['legislative_district'],
            $data['school_district'],
            $data['grade'],
            $data['section'],
            $data['assessment_type']
        );
        
        // Get existing assessments for this section to show types
        $data['existing_assessments'] = $this->Nutritional_assessment_model->get_assessment_types(
            $data['legislative_district'],
            $data['school_district'],
            $data['grade'],
            $data['section']
        );
        
        // If this is endline assessment but baseline doesn't exist, show warning
        if ($data['assessment_type'] == 'endline' && 
            !$this->has_baseline_assessment($data['existing_assessments'])) {
            $this->session->set_flashdata('warning', 
                'You are creating an endline assessment without a baseline. '
                . 'It is recommended to create a baseline assessment first.');
        }

        // Load view with form
        $this->load->view('nutritional_assessment', $data);
    }

    /**
     * Store a single assessment
     */
    public function store()
    {
        // Validation rules
        $this->form_validation->set_rules('name', 'Name', 'required|trim');
        $this->form_validation->set_rules('birthday', 'Birthday', 'required|valid_date[Y-m-d]');
        $this->form_validation->set_rules('weight', 'Weight', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('height', 'Height', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('sex', 'Sex', 'required|in_list[M,F]');
        $this->form_validation->set_rules('grade', 'Grade', 'required|trim');
        $this->form_validation->set_rules('date_of_weighing', 'Date of Weighing', 'required|valid_date[Y-m-d]');
        $this->form_validation->set_rules('assessment_type', 'Assessment Type', 'required|in_list[baseline,endline]');

        if ($this->form_validation->run() == FALSE) {
            // Return validation errors as JSON
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false, 
                    'errors' => $this->form_validation->error_array()
                ]));
        }

        $assessment_data = [
            'name' => $this->input->post('name'),
            'birthday' => $this->input->post('birthday'),
            'weight' => $this->input->post('weight'),
            'height' => $this->input->post('height'),
            'sex' => $this->input->post('sex'),
            'grade_level' => $this->input->post('grade'),
            'section' => $this->input->post('section'),
            'date_of_weighing' => $this->input->post('date_of_weighing'),
            'legislative_district' => $this->input->post('legislative_district'),
            'school_district' => $this->input->post('school_district'),
            'school_id' => $this->input->post('school_id'),
            'school_name' => $this->input->post('school_name'),
            'height_squared' => $this->input->post('height_squared'),
            'age' => $this->input->post('age'),
            'bmi' => $this->input->post('bmi'),
            'nutritional_status' => $this->input->post('nutritional_status'),
            'sbfp_beneficiary' => $this->input->post('sbfp_beneficiary'),
            'height_for_age' => $this->input->post('height_for_age') ?: 'Normal',
            'assessment_type' => $this->input->post('assessment_type'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Check for duplicate entry
        $is_duplicate = $this->check_duplicate_assessment($assessment_data);
        
        if ($is_duplicate) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'Duplicate entry: This student already has a ' . $assessment_data['assessment_type'] . ' assessment for this section.'
                ]));
        }

        if ($this->Nutritional_assessment_model->create($assessment_data)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true, 
                    'message' => ucfirst($assessment_data['assessment_type']) . ' assessment saved successfully!'
                ]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'Error saving assessment'
                ]));
        }
    }

 /**
 * Bulk store multiple assessments (via AJAX/form submission from localStorage)
 */
public function bulk_store()
{
    // Get the raw POST data
    $students_json = $this->input->post('students');
    $assessment_type = $this->input->post('assessment_type', TRUE) ?: 'baseline';
    
    if (!$students_json) {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false, 
                'message' => 'No student records provided'
            ]));
    }

    // Decode the JSON data
    $students = json_decode($students_json, true);
    
    if (!is_array($students) || empty($students)) {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false, 
                'message' => 'Invalid student data format'
            ]));
    }

    $created_count = 0;
    $updated_count = 0;
    $duplicate_count = 0;
    $errors = [];

    // Get school data from first student for logging
    $first_student = $students[0] ?? [];
    $school_id = $first_student['school_id'] ?? '';
    $grade = $first_student['grade'] ?? '';
    $section = $first_student['section'] ?? '';

    log_message('info', "Bulk store attempt: {$school_id}, {$grade}, {$section}, Type: {$assessment_type}, Count: " . count($students));

    foreach ($students as $idx => $student) {
        try {
            // Map the JavaScript object properties to your database columns
            $assessment_data = [
                'school_district' => isset($student['school_district']) ? $student['school_district'] : '',
                'legislative_district' => isset($student['legislative_district']) ? $student['legislative_district'] : '',
                'school_id' => isset($student['school_id']) ? $student['school_id'] : '',
                'school_name' => isset($student['school_name']) ? $student['school_name'] : '',
                'grade_level' => isset($student['grade']) ? $student['grade'] : '',
                'section' => isset($student['section']) ? $student['section'] : '',
                'name' => isset($student['name']) ? trim($student['name']) : '',
                'birthday' => isset($student['birthday']) ? $student['birthday'] : '',
                'weight' => isset($student['weight']) ? floatval($student['weight']) : 0,
                'height' => isset($student['height']) ? floatval($student['height']) : 0,
                'sex' => isset($student['sex']) ? strtoupper($student['sex']) : '',
                'height_squared' => isset($student['heightSquared']) ? floatval($student['heightSquared']) : 0,
                'age' => isset($student['age']) ? $student['age'] : (isset($student['ageDisplay']) ? $student['ageDisplay'] : (isset($student['ageYears']) && isset($student['ageMonths']) ? $student['ageYears'] . '|' . $student['ageMonths'] : '0|0')),
                'bmi' => isset($student['bmi']) ? floatval($student['bmi']) : 0,
                'nutritional_status' => isset($student['nutritionalStatus']) ? $student['nutritionalStatus'] : 'Normal',
                'height_for_age' => isset($student['heightForAge']) ? $student['heightForAge'] : 'Normal',
                'sbfp_beneficiary' => isset($student['sbfpBeneficiary']) ? $student['sbfpBeneficiary'] : 'No',
                'date_of_weighing' => isset($student['date']) ? $student['date'] : date('Y-m-d'),
                'assessment_type' => $assessment_type, // Use the POST assessment_type
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Validate required fields
            if (empty($assessment_data['name']) || empty($assessment_data['birthday']) || 
                empty($assessment_data['grade_level']) || empty($assessment_data['section'])) {
                $errors[] = "Row " . ($idx + 1) . ": Missing required fields (name, birthday, grade, or section)";
                continue;
            }

            // Check if this student already has an assessment of this type
            $existing_assessment = $this->get_existing_assessment($assessment_data);
            
            if ($existing_assessment) {
                // Update existing record instead of skipping
                $this->db->where('id', $existing_assessment->id);
                if ($this->db->update('nutritional_assessments', $assessment_data)) {
                    $updated_count++;
                } else {
                    $errors[] = "Row " . ($idx + 1) . ": " . $student['name'] . " - Failed to update existing record";
                }
            } else {
                // Insert new record
                if ($this->Nutritional_assessment_model->create($assessment_data)) {
                    $created_count++;
                } else {
                    $db_error = $this->db->error();
                    $errors[] = "Row " . ($idx + 1) . ": " . $student['name'] . " - Database error: " . ($db_error['message'] ?? 'Unknown error');
                }
            }
        } catch (Exception $e) {
            $errors[] = "Row " . ($idx + 1) . ": " . ($student['name'] ?? 'Unknown') . " - Exception: " . $e->getMessage();
        }
    }

    $response = [
        'success' => ($created_count > 0 || $updated_count > 0),
        'created_count' => $created_count,
        'updated_count' => $updated_count,
        'duplicate_count' => $duplicate_count,
        'total_count' => count($students),
        'assessment_type' => $assessment_type,
        'errors' => $errors,
        'message' => "Successfully processed {$created_count} new and {$updated_count} updated " . 
                     ucfirst($assessment_type) . " assessment(s). " .
                     (!empty($errors) ? " Encountered " . count($errors) . " error(s)." : "")
    ];

    log_message('info', "Bulk store result: " . json_encode($response));

    return $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
}

/**
 * Get existing assessment for update
 */
private function get_existing_assessment($assessment_data)
{
    $this->db->select('id');
    $this->db->where('name', $assessment_data['name']);
    $this->db->where('birthday', $assessment_data['birthday']);
    $this->db->where('grade_level', $assessment_data['grade_level']);
    $this->db->where('section', $assessment_data['section']);
    $this->db->where('school_id', $assessment_data['school_id']);
    $this->db->where('assessment_type', $assessment_data['assessment_type']);
    $this->db->where('is_deleted', FALSE);
    
    $query = $this->db->get('nutritional_assessments', 1);
    
    return $query->row();
}

/**
 * Check for duplicate assessment
 */
private function check_duplicate_assessment($assessment_data)
{
    $this->db->select('id');
    $this->db->where('name', $assessment_data['name']);
    $this->db->where('birthday', $assessment_data['birthday']);
    $this->db->where('grade_level', $assessment_data['grade_level']);
    $this->db->where('section', $assessment_data['section']);
    $this->db->where('school_id', $assessment_data['school_id']);
    $this->db->where('assessment_type', $assessment_data['assessment_type']); 
    $this->db->where('is_deleted', FALSE);
    
    $query = $this->db->get('nutritional_assessments');
    
    return $query->num_rows() > 0;
}

    /**
     * Check if baseline assessment exists
     */
    private function has_baseline_assessment($existing_assessments)
    {
        if (!$existing_assessments) {
            return false;
        }
        
        foreach ($existing_assessments as $assessment) {
            if ($assessment->assessment_type == 'baseline') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * View all assessments for a specific section and type
     */
    public function view_all()
    {
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';
        
        if ($legislative_district && $school_district && $grade && $section) {
            // View specific section assessments
            $data['assessments'] = $this->Nutritional_assessment_model->get_by_section(
                $legislative_district, 
                $school_district, 
                $grade, 
                $section,
                $assessment_type
            );
            $data['filtered'] = true;
            $data['current_filter'] = [
                'legislative_district' => $legislative_district,
                'school_district' => $school_district,
                'grade' => $grade,
                'section' => $section,
                'assessment_type' => $assessment_type
            ];
        } else {
            // View all assessments
            $data['assessments'] = $this->Nutritional_assessment_model->get_all();
            $data['filtered'] = false;
        }
        
        $this->load->view('nutritional_assessment_view', $data);
    }

    /**
     * Debug method to check what's being received
     */
    public function debug_bulk_store()
    {
        $students_json = $this->input->post('students');
        $students = json_decode($students_json, true);
        
        echo "<pre>";
        echo "Raw POST data:\n";
        print_r($students_json);
        echo "\n\nDecoded data:\n";
        print_r($students);
        echo "\n\nFirst student structure:\n";
        if (!empty($students[0])) {
            print_r($students[0]);
        }
        echo "</pre>";
    }

    /**
     * Statistics with assessment type filtering
     */
    public function statistics() {
        // Get filter values from GET request
        $filters = [
            'legislative_district' => $this->input->get('legislative_district'),
            'school_district' => $this->input->get('school_district'),
            'school_id' => $this->input->get('school_id'),
            'school_name' => $this->input->get('school_name'),
            'grade_level' => $this->input->get('grade_level'),
            'assessment_type' => $this->input->get('assessment_type'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];

        // Get statistics data based on filters
        $data['nutritional_stats'] = $this->Nutritional_assessment_model->get_nutritional_statistics($filters);
        
        // Calculate totals
        $data['total_severely_wasted'] = 0;
        $data['total_wasted'] = 0;
        $data['total_normal'] = 0;
        $data['total_students'] = 0;
        
        foreach ($data['nutritional_stats'] as $stat) {
            $data['total_severely_wasted'] += $stat->severely_wasted ?? 0;
            $data['total_wasted'] += $stat->wasted ?? 0;
            $data['total_normal'] += $stat->normal_bmi ?? 0;
            $data['total_students'] += $stat->total_students ?? 0;
        }

        // Get filter dropdown options
        $data['legislative_districts'] = $this->School_model->get_legislative_districts();
        $data['school_districts'] = $this->School_model->get_school_districts();
        $data['school_names'] = $this->School_model->get_school_names();
        $data['grade_levels'] = $this->Nutritional_assessment_model->get_grade_levels();
        
        // Get assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'endline' => 'Endline'
        ];
        
        // Store current filters for form persistence
        $data['current_filters'] = $filters;

        $this->load->view('statistics', $data);
    }
    
    /**
     * Get assessment comparison data (for baseline vs endline)
     */
    public function get_comparison_data()
    {
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        
        if (!$legislative_district || !$school_district || !$grade || !$section) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ]));
        }
        
        // Get baseline assessments
        $baseline = $this->Nutritional_assessment_model->get_by_section(
            $legislative_district, 
            $school_district, 
            $grade, 
            $section,
            'baseline'
        );
        
        // Get endline assessments
        $endline = $this->Nutritional_assessment_model->get_by_section(
            $legislative_district, 
            $school_district, 
            $grade, 
            $section,
            'endline'
        );
        
        $response = [
            'success' => true,
            'baseline' => [
                'count' => count($baseline),
                'data' => $baseline
            ],
            'endline' => [
                'count' => count($endline),
                'data' => $endline
            ],
            'comparison_available' => !empty($baseline) && !empty($endline)
        ];
        
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}