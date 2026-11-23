<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Nutritionalassessment extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('nutritional_assessment_model');
        $this->load->model('school_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
        $this->load->database();

        require_once APPPATH . 'helpers/WHO_Standards_helper.php';
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
        $data['section_id'] = $this->input->get('section_id', TRUE);
        $data['school_year'] = $this->input->get('school_year', TRUE);
        $data['school_id'] = $this->input->get('school_id', TRUE) ?: 'N/A'; 
        $data['school_name'] = $this->input->get('school_name', TRUE) ?: 'Unknown School';
        $data['assessment_type'] = $this->input->get('assessment_type', TRUE) ?: 'baseline';
        
        // Validate assessment type
        $valid_types = ['baseline', 'midline', 'endline'];
        if (!in_array($data['assessment_type'], $valid_types)) {
            $data['assessment_type'] = 'baseline';
        }
        
        // Check if assessment already exists for this section and type
        $data['has_existing_assessment'] = $this->nutritional_assessment_model->assessment_exists(
            $data['legislative_district'],
            $data['school_district'],
            $data['grade'],
            $data['section'],
            $data['school_year'],
            $data['assessment_type']
        );
        
        // Get existing assessments for this section to show types
        $data['existing_assessments'] = $this->nutritional_assessment_model->get_assessment_types(
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
        
        // If this is midline assessment but baseline doesn't exist, show warning
        if ($data['assessment_type'] == 'midline' && 
            !$this->has_baseline_assessment($data['existing_assessments'])) {
            $this->session->set_flashdata('warning', 
                'You are creating a midline assessment without a baseline. '
                . 'It is recommended to create a baseline assessment first.');
        }

        $existing_weighing_date = null;
        if ($data['has_existing_assessment']) {
            $this->db->select('date_of_weighing');
            $this->db->where('legislative_district', $data['legislative_district']);
            $this->db->where('school_district', $data['school_district']);
            $this->db->where('grade_level', $data['grade']);
            $this->db->where('section', $data['section']);
            $this->db->where('assessment_type', $data['assessment_type']);
            $this->db->where('is_deleted', 0);
            $this->db->order_by('date_of_weighing', 'DESC');
            $this->db->limit(1);
            $query = $this->db->get('nutritional_assessments');
            if ($query->num_rows() > 0) {
                $existing_weighing_date = $query->row()->date_of_weighing;
            }
        }
        $data['existing_weighing_date'] = $existing_weighing_date;

        $mode = $this->input->get('mode', TRUE);
        $data['mode'] = ($mode === 'beneficiary') ? 'beneficiary' : 'default';

        if ($data['mode'] === 'beneficiary') {
        // Get all students for this section and assessment type
        $data['students'] = $this->nutritional_assessment_model->get_by_section(
            $data['legislative_district'],
            $data['school_district'],
            $data['grade'],
            $data['section'],
            $data['school_year'],
            $data['assessment_type'],
            $data['section_id'],
            ($data['school_id'] === 'N/A' ? null : $data['school_id'])
        );
        // Also pass a flag that we are in beneficiary mode
        $data['is_beneficiary_mode'] = true;
    } else {
        $data['is_beneficiary_mode'] = false;
    }

        // Load view with form
        $this->load->view('nutritional_assessment', $data);
    }

    /**
     * Store a single assessment
     */
    public function store()
    {
        // Set JSON response header
        $this->output->set_content_type('application/json');

        $last_name = trim($this->input->post('last_name', TRUE));
        $first_name = trim($this->input->post('first_name', TRUE));
        $middle_initial = trim($this->input->post('middle_initial', TRUE));
        

        $full_name = $last_name;
        if (!empty($middle_initial)) {
            $full_name .= ' ' . $middle_initial . '.';
        }
        $full_name .= ' ' . $first_name;
        
        // Validation rules
        $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
        $this->form_validation->set_rules('birthday', 'Birthday', 'required|valid_date[Y-m-d]');
        $this->form_validation->set_rules('weight', 'Weight', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('height', 'Height', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('sex', 'Sex', 'required|in_list[M,F]');
        $this->form_validation->set_rules('grade', 'Grade', 'required|trim');
        $this->form_validation->set_rules('date', 'Date of Weighing', 'required|valid_date[Y-m-d]');
        $this->form_validation->set_rules('assessment_type', 'Assessment Type', 'required|in_list[baseline,midline,endline]');

        if ($this->form_validation->run() == FALSE) {
            return $this->output
                ->set_output(json_encode([
                    'success' => false, 
                    'errors' => $this->form_validation->error_array()
                ]));
        }

        // Get school_level from users table based on current user
        $current_user_id = $this->session->userdata('user_id');
        $school_level = '';
        
        if ($current_user_id) {
            $this->db->select('school_level');
            $this->db->where('id', $current_user_id);
            $user = $this->db->get('users')->row();
            
            if ($user && isset($user->school_level)) {
                $school_level = $user->school_level;
            }
        }

        // Get input values
        $birthday = $this->input->post('birthday');
        $weight = floatval($this->input->post('weight'));
        $height = floatval($this->input->post('height'));
        $sex = $this->input->post('sex');
        $date_of_weighing = $this->input->post('date');
        
        // Calculate derived values using WHO standards
        $heightSquared = number_format(($height * $height), 4);
        $bmi = round($weight / ($height * $height), 2);
        
        // Calculate age in months using weighing date
        $ageInMonths = $this->calculateAgeInMonths($birthday, $date_of_weighing);
        
        // Use WHO standards helper for classifications
        $nutritional_status = getWHO_BMIClassification($bmi, $ageInMonths, $sex, $weight);
        $heightForAge = $this->calculateHeightForAge($height, $ageInMonths, $sex);
        
        // Calculate age display
        $ageDisplay = $this->calculateAgeDisplay($birthday, $date_of_weighing);
        
        $sbfpBeneficiary = ($nutritional_status === 'Severely Wasted' || $nutritional_status === 'Wasted') ? 'Yes' : 'No';

        $assessment_data = [
            'name' => $full_name,
            'first_name' => $first_name,
            'middle_initial' => $middle_initial,
            'last_name' => $last_name,
            'birthday' => $birthday,
            'weight' => $weight,
            'height' => $height,
            'sex' => $sex,
            'grade_level' => $this->input->post('grade'),
            'section' => $this->input->post('section'),
            'date_of_weighing' => $date_of_weighing,
            'legislative_district' => $this->input->post('legislative_district'),
            'school_district' => $this->input->post('school_district'),
            'school_id' => $this->input->post('school_id'),
            'school_name' => $this->input->post('school_name'),
            'year' => $this->input->post('school_year'),
            'school_level' => $school_level,
            'height_squared' => $heightSquared,
            'age' => $ageDisplay,
            'bmi' => $bmi,
            'nutritional_status' => $nutritional_status,
            'sbfp_beneficiary' => $sbfpBeneficiary,
            'height_for_age' => $heightForAge,
            'assessment_type' => $this->input->post('assessment_type'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->load->model('section_model');
        $section_id = $this->section_model->get_section_id(
            $this->input->post('grade'),
            $this->input->post('section'),
            $this->input->post('school_year'),
            $this->input->post('legislative_district'),
            $this->input->post('school_district'),
            $current_user_id
        );
        $assessment_data['section_id'] = $section_id;

        // Check for duplicate entry
        $is_duplicate = $this->check_duplicate_assessment($assessment_data);
        
        if ($is_duplicate) {
            return $this->output
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'Duplicate entry: This student already has a ' . $assessment_data['assessment_type'] . ' assessment for this section.'
                ]));
        }

        if ($this->nutritional_assessment_model->create($assessment_data)) {
            // Fetch inserted record to return authoritative values (BMI, nutritional_status, etc.)
            $insertId = $this->db->insert_id();
            $this->db->where('id', $insertId);
            $record = $this->db->get('nutritional_assessments')->row();

            return $this->output
                ->set_output(json_encode([
                    'success' => true,
                    'message' => ucfirst($assessment_data['assessment_type']) . ' assessment saved successfully!',
                    'record' => $record
                ]));
        } else {
            return $this->output
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
        $this->output->set_content_type('application/json');

        $students_json = $this->input->post('students');
        $assessment_type = $this->input->post('assessment_type', TRUE) ?: 'baseline';
        
        // Validate assessment type
        $valid_types = ['baseline', 'midline', 'endline'];
        if (!in_array($assessment_type, $valid_types)) {
            $assessment_type = 'baseline';
        }
        
        if (!$students_json) {
            return $this->output
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'No student records provided'
                ]));
        }

        // Decode the JSON data
        $students = json_decode($students_json, true);
        
        if (!is_array($students) || empty($students)) {
            return $this->output
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'Invalid student data format'
                ]));
        }

        // Get school_level from users table based on current user
        $current_user_id = $this->session->userdata('user_id');
        $school_level = '';
        
        if ($current_user_id) {
            $this->db->select('school_level');
            $this->db->where('id', $current_user_id);
            $user = $this->db->get('users')->row();
            
            if ($user && isset($user->school_level)) {
                $school_level = $user->school_level;
            }
        }

        $created_count = 0;
        $updated_count = 0;
        $duplicate_count = 0;
        $errors = [];
        $created_records = [];
        $updated_records = [];

        // Get school data from first student for logging
        $first_student = $students[0] ?? [];
        $school_id = $first_student['school_id'] ?? '';
        $grade = $first_student['grade'] ?? '';
        $section = $first_student['section'] ?? '';

        foreach ($students as $idx => $student) {
            try {
                $full_name = isset($student['name']) ? trim($student['name']) : '';

                if (empty($full_name) && isset($student['first_name']) && isset($student['last_name'])) {
                    $full_name = trim($student['last_name']);
                    if (!empty($student['middle_initial'])) {
                        $full_name .= ' ' . $student['middle_initial'] . '.';
                    }
                    $full_name .= ' ' . trim($student['first_name']);
                }

                // Get input values
                $birthday = isset($student['birthday']) ? $student['birthday'] : '';
                $weight = isset($student['weight']) ? floatval($student['weight']) : 0;
                $height = isset($student['height']) ? floatval($student['height']) : 0;
                $sex = isset($student['sex']) ? strtoupper($student['sex']) : '';
                $date_of_weighing = isset($student['date']) ? $student['date'] : date('Y-m-d');
                
                // Calculate derived values using WHO standards
                $heightSquared = number_format(($height * $height), 4);
                $bmi = round($weight / ($height * $height), 2);
                
                // Calculate age in months using weighing date
                $ageInMonths = $this->calculateAgeInMonths($birthday, $date_of_weighing);
                
                // Use WHO standards helper for classifications
                $nutritional_status = getWHO_BMIClassification($bmi, $ageInMonths, $sex, $weight);
                $heightForAge = $this->calculateHeightForAge($height, $ageInMonths, $sex);
                
                // Calculate age display
                $ageDisplay = $this->calculateAgeDisplay($birthday, $date_of_weighing);
                
                $sbfpBeneficiary = ($nutritional_status === 'Severely Wasted' || $nutritional_status === 'Wasted') ? 'Yes' : 'No';

                $assessment_data = [
                    'school_district' => isset($student['school_district']) ? $student['school_district'] : '',
                    'legislative_district' => isset($student['legislative_district']) ? $student['legislative_district'] : '',
                    'school_id' => isset($student['school_id']) ? $student['school_id'] : '',
                    'school_name' => isset($student['school_name']) ? $student['school_name'] : '',
                    'school_level' => $school_level, 
                    'grade_level' => isset($student['grade']) ? $student['grade'] : '',
                    'section' => isset($student['section']) ? $student['section'] : '',
                    'year' => isset($student['school_year']) ? $student['school_year'] : (isset($student['year']) ? $student['year'] : ''),
                    'name' => $full_name,
                    'birthday' => $birthday,
                    'weight' => $weight,
                    'height' => $height,
                    'sex' => $sex,
                    'height_squared' => $heightSquared,
                    'age' => $ageDisplay,
                    'bmi' => $bmi,
                    'nutritional_status' => $nutritional_status,
                    'height_for_age' => $heightForAge,
                    'sbfp_beneficiary' => $sbfpBeneficiary,
                    'date_of_weighing' => $date_of_weighing,
                    'assessment_type' => $assessment_type, 
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->load->model('section_model');
                $section_id = $this->section_model->get_section_id(
                    $assessment_data['grade_level'],
                    $assessment_data['section'],
                    $assessment_data['year'],
                    $assessment_data['legislative_district'],
                    $assessment_data['school_district'],
                    $current_user_id
                );
                $assessment_data['section_id'] = $section_id;

                if (empty($assessment_data['name']) || empty($assessment_data['birthday']) || 
                    empty($assessment_data['grade_level']) || empty($assessment_data['section'])) {
                    $errors[] = "Row " . ($idx + 1) . ": Missing required fields (name, birthday, grade, or section)";
                    continue;
                }

                $existing_assessment = $this->get_existing_assessment($assessment_data);
                
                if ($existing_assessment) {
                    $this->db->where('id', $existing_assessment->id);
                    if ($this->db->update('nutritional_assessments', $assessment_data)) {
                        $updated_count++;
                        // fetch updated record
                        $this->db->where('id', $existing_assessment->id);
                        $rec = $this->db->get('nutritional_assessments')->row();
                        if ($rec) $updated_records[] = $rec;
                    } else {
                        $db_error = $this->db->error();
                        $errors[] = "Row " . ($idx + 1) . ": " . $student['name'] . " - Failed to update existing record: " . $db_error['message'];
                    }
                } else {
                    if ($this->nutritional_assessment_model->create($assessment_data)) {
                        $created_count++;
                        $insertId = $this->db->insert_id();
                        $this->db->where('id', $insertId);
                        $rec = $this->db->get('nutritional_assessments')->row();
                        if ($rec) $created_records[] = $rec;
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
            'created_records' => $created_records,
            'updated_records' => $updated_records,
            'duplicate_count' => $duplicate_count,
            'total_count' => count($students),
            'assessment_type' => $assessment_type,
            'errors' => $errors,
            'message' => "Successfully processed {$created_count} new and {$updated_count} updated " . 
                        ucfirst($assessment_type) . " assessment(s). " .
                        (!empty($errors) ? " Encountered " . count($errors) . " error(s)." : "")
        ];

        return $this->output
            ->set_output(json_encode($response));
    }

    private function calculateAgeInMonths($birthday, $weighing_date)
    {
        if (empty($birthday) || empty($weighing_date)) {
            return 0;
        }
        try {
            $birth = new DateTime($birthday, new DateTimeZone('UTC'));
            $weigh = new DateTime($weighing_date, new DateTimeZone('UTC'));
            $diff = $birth->diff($weigh);
            return ($diff->y * 12) + $diff->m;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function calculateAgeDisplay($birthday, $weighing_date)
    {
        if (empty($birthday) || empty($weighing_date)) {
            return '0|0';
        }
        try {
            $birth = new DateTime($birthday, new DateTimeZone('UTC'));
            $weigh = new DateTime($weighing_date, new DateTimeZone('UTC'));
            $diff = $birth->diff($weigh);
            return $diff->y . '|' . $diff->m;
        } catch (Exception $e) {
            return '0|0';
        }
    }
    
    /**
     * Calculate Height-for-Age classification
     */
    private function calculateHeightForAge($height, $ageInMonths, $sex)
    {
        if ($height === null || $ageInMonths === null || empty($sex)) {
            return 'Normal';
        }
        
        // Convert height from meters to cm
        $heightCm = $height * 100;
        
        // Use WHO standards helper
        return getWHO_HeightForAgeClassification($heightCm, $ageInMonths, $sex);
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
        $this->db->where('year', $assessment_data['year'] ?? '');
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
        $this->db->where('year', $assessment_data['year'] ?? '');
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

    public function view_all()
    {
        $legislative_district = $this->input->get('legislative_district', TRUE);
        $school_district = $this->input->get('school_district', TRUE);
        $grade = $this->input->get('grade', TRUE);
        $section = $this->input->get('section', TRUE);
        $assessment_type = $this->input->get('assessment_type', TRUE) ?: 'baseline';
        
        if ($legislative_district && $school_district && $grade && $section) {
            $data['assessments'] = $this->nutritional_assessment_model->get_by_section(
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
            $data['assessments'] = $this->nutritional_assessment_model->get_all();
            $data['filtered'] = false;
        }
        
        $this->load->view('nutritional_assessment_view', $data);
    }

    /**
     * Classify a student (calculate BMI and WHO nutritional status) without saving
     * Expects JSON: birthday, weight, height, sex, date (weighing date)
     */
    public function classify()
    {
        // Disable CSRF for this endpoint (AJAX without token)
        $this->config->set_item('csrf_protection', FALSE);

        $this->output->set_content_type('application/json');

        // Read JSON input (or fallback to standard POST)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $this->input->post();
        }

        // Extract and validate
        $birthday = $input['birthday'] ?? null;
        $weight = isset($input['weight']) ? floatval($input['weight']) : null;
        $height = isset($input['height']) ? floatval($input['height']) : null;
        $sex = $input['sex'] ?? null;
        $date_of_weighing = $input['date'] ?? null;

        if (empty($birthday) || $weight <= 0 || $height <= 0 || empty($sex) || empty($date_of_weighing)) {
            return $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Missing or invalid parameters',
                'received' => $input
            ]));
        }

        // Calculate
        $bmi = round($weight / ($height * $height), 2);
        $ageInMonths = $this->calculateAgeInMonths($birthday, $date_of_weighing);
        $nutritional_status = getWHO_BMIClassification($bmi, $ageInMonths, $sex, $weight);
        $heightForAge = $this->calculateHeightForAge($height, $ageInMonths, $sex);

        return $this->output->set_output(json_encode([
            'success' => true,
            'bmi' => $bmi,
            'nutritional_status' => $nutritional_status,
            'height_for_age' => $heightForAge,
            'ageInMonths' => $ageInMonths
        ]));
    }

    /**
     * Toggle beneficiary status for a single student (AJAX)
     */
    public function toggle_beneficiary()
    {
        $this->output->set_content_type('application/json');
        $id = $this->input->post('id');
        $beneficiary = $this->input->post('beneficiary'); // 'Yes' or 'No'

        if (empty($id) || !in_array($beneficiary, ['Yes', 'No'])) {
            return $this->output->set_output(json_encode(['success' => false, 'message' => 'Invalid input']));
        }

        $updated = $this->nutritional_assessment_model->update_beneficiary_status($id, $beneficiary);
        if ($updated) {
            return $this->output->set_output(json_encode(['success' => true]));
        } else {
            return $this->output->set_output(json_encode(['success' => false, 'message' => 'Update failed']));
        }
    }

    /**
     * Toggle beneficiary status for all students in a section (AJAX)
     */
    public function toggle_all_beneficiaries()
    {
        $this->output->set_content_type('application/json');
        $legislative_district = $this->input->post('legislative_district');
        $school_district = $this->input->post('school_district');
        $school_id = $this->input->post('school_id');
        $grade = $this->input->post('grade');
        $section = $this->input->post('section');
        $school_year = $this->input->post('school_year');
        $assessment_type = $this->input->post('assessment_type');
        $beneficiary = $this->input->post('beneficiary'); // 'Yes' or 'No'

        $updated = $this->nutritional_assessment_model->update_all_beneficiaries(
            $legislative_district, $school_district, $school_id, $grade, $section,
            $school_year, $assessment_type, $beneficiary
        );

        if ($updated !== false) {
            return $this->output->set_output(json_encode(['success' => true, 'updated' => $updated]));
        } else {
            return $this->output->set_output(json_encode(['success' => false]));
        }
    }

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
        $data['nutritional_stats'] = $this->nutritional_assessment_model->get_nutritional_statistics($filters);
        
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
        $data['legislative_districts'] = $this->school_model->get_legislative_districts();
        $data['school_districts'] = $this->school_model->get_school_districts();
        $data['school_names'] = $this->school_model->get_school_names();
        $data['grade_levels'] = $this->nutritional_assessment_model->get_grade_levels();
        
        // Get assessment types for filter
        $data['assessment_types'] = [
            '' => 'All Types',
            'baseline' => 'Baseline',
            'midline' => 'Midline',
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
        $baseline = $this->nutritional_assessment_model->get_by_section(
            $legislative_district, 
            $school_district, 
            $grade, 
            $section,
            'baseline'
        );
        
        // Get endline assessments
        $endline = $this->nutritional_assessment_model->get_by_section(
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


/**
 * READ-ONLY diagnostic endpoint.
 * Identifies possible grade_sections rows for one assessment.
 *
 * Does NOT update or delete any records.
 *
 * Usage:
 * nutritionalassessment/debug_section_relationship/918
 *
 * If the assessment does not contain user_id, you can supply it:
 * nutritionalassessment/debug_section_relationship/918?user_id=123
 */
public function debug_section_relationship($assessment_id = NULL)
{
    $this->output->set_content_type('application/json');

    // Require login.
    if (!$this->session->userdata('user_id')) {
        $this->output->set_status_header(403);

        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in first.'
        ], JSON_PRETTY_PRINT));
    }

    if (empty($assessment_id) || !is_numeric($assessment_id)) {
        $this->output->set_status_header(400);

        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'A valid assessment ID is required.'
        ], JSON_PRETTY_PRINT));
    }

    // 1. Retrieve the assessment.
    $assessment = $this->db
        ->where('id', (int) $assessment_id)
        ->get('nutritional_assessments')
        ->row();

    if (!$assessment) {
        $this->output->set_status_header(404);

        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'Assessment not found.'
        ], JSON_PRETTY_PRINT));
    }

    // 2. Resolve user_id.
    // Prefer the assessment's own user_id if that column exists.
    // Otherwise, accept a user_id supplied in the URL.
    $provided_user_id = trim(
        (string) $this->input->get('user_id', TRUE)
    );

    $assessment_has_user_id = isset($assessment->user_id)
        && $assessment->user_id !== ''
        && $assessment->user_id !== NULL;

    $resolved_user_id = $assessment_has_user_id
        ? (string) $assessment->user_id
        : $provided_user_id;

    $user_id_source = $assessment_has_user_id
        ? 'nutritional_assessments.user_id'
        : ($provided_user_id !== ''
            ? 'URL query parameter'
            : 'UNAVAILABLE');

    // 3. Build common matching conditions.
    // Do not use user_id in this first query, so we can see
    // all possible matches and detect ambiguity.
    $apply_common_filters = function () use ($assessment) {
        $this->db->where(
            'grade',
            $assessment->grade_level
        );

        $this->db->where(
            'year',
            $assessment->year
        );

        $this->db->where(
            'UPPER(TRIM(section)) = ' .
            $this->db->escape(
                strtoupper(trim((string) $assessment->section))
            ),
            NULL,
            FALSE
        );

        if ($assessment->legislative_district !== NULL) {
            $this->db->where(
                'legislative_district',
                $assessment->legislative_district
            );
        }

        if ($assessment->school_district !== NULL) {
            $this->db->where(
                'school_district',
                $assessment->school_district
            );
        }
    };

    // 4. Find all candidates based on grade, section,
    // year, and district.
    $this->db->select(
        'id, user_id, grade, section, year, ' .
        'legislative_district, school_district, created_at'
    );
    $this->db->from('grade_sections');
    $apply_common_filters();
    $this->db->order_by('user_id', 'ASC');
    $this->db->order_by('id', 'ASC');

    $candidate_query = $this->db->get();

    if ($candidate_query === FALSE) {
        $this->output->set_status_header(500);

        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'Could not query candidate sections.',
            'database_error' => $this->db->error()
        ], JSON_PRETTY_PRINT));
    }

    $all_candidates = $candidate_query->result_array();

    // 5. Narrow candidates to the resolved user_id.
    $user_candidates = [];

    if ($resolved_user_id !== '') {
        $this->db->select(
            'id, user_id, grade, section, year, ' .
            'legislative_district, school_district, created_at'
        );
        $this->db->from('grade_sections');
        $apply_common_filters();
        $this->db->where('user_id', $resolved_user_id);
        $this->db->order_by('id', 'ASC');

        $user_query = $this->db->get();

        if ($user_query === FALSE) {
            $this->output->set_status_header(500);

            return $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Could not query user-specific sections.',
                'database_error' => $this->db->error()
            ], JSON_PRETTY_PRINT));
        }

        $user_candidates = $user_query->result_array();
    }

    // 6. Check whether the current section_id points to
    // an existing grade_sections row.
    $current_section = NULL;

    if ($assessment->section_id !== NULL
        && $assessment->section_id !== '') {

        $current_section = $this->db
            ->where('id', $assessment->section_id)
            ->get('grade_sections')
            ->row_array();
    }

    // 7. Provide a cautious diagnostic conclusion.
    if ($resolved_user_id === '') {
        $diagnosis =
            'USER_ID_UNKNOWN: verify which user_id belongs to ' .
            'this school before choosing a section.';
    } elseif (count($user_candidates) === 1) {
        $diagnosis =
            'ONE_USER_MATCH: exactly one section matches the ' .
            'assessment details and user_id. Verify the school-to-user ' .
            'mapping before using this as a confirmed match.';
    } elseif (count($user_candidates) > 1) {
        $diagnosis =
            'AMBIGUOUS: multiple sections match, even with user_id. ' .
            'Do not assign a section automatically.';
    } else {
        $diagnosis =
            'NO_USER_MATCH: no section matches all supplied criteria ' .
            'for this user_id. Check spelling, school year, districts, ' .
            'and the school-to-user mapping.';
    }

    // 8. Return read-only diagnostic data.
    return $this->output->set_output(json_encode([
        'success' => true,
        'mode' => 'READ_ONLY',
        'assessment' => [
            'id' => $assessment->id,
            'school_id' => $assessment->school_id,
            'school_name' => $assessment->school_name,
            'grade' => $assessment->grade_level,
            'section' => $assessment->section,
            'year' => $assessment->year,
            'legislative_district' =>
                $assessment->legislative_district,
            'school_district' => $assessment->school_district,
            'is_deleted' => $assessment->is_deleted,
            'current_section_id' => $assessment->section_id
        ],
        'user_id_check' => [
            'assessment_has_user_id' => $assessment_has_user_id,
            'resolved_user_id' =>
                $resolved_user_id !== '' ? $resolved_user_id : NULL,
            'user_id_source' => $user_id_source,
            'note' =>
                'Confirm that this user_id belongs to the assessment school.'
        ],
        'current_section_check' => [
            'section_id_is_null' => $assessment->section_id === NULL,
            'referenced_section_exists' => $current_section !== NULL,
            'referenced_section' => $current_section
        ],
        'all_candidates_without_user_filter' => [
            'count' => count($all_candidates),
            'sections' => $all_candidates
        ],
        'candidates_matching_user_id' => [
            'count' => count($user_candidates),
            'sections' => $user_candidates
        ],
        'diagnosis' => $diagnosis
    ], JSON_PRETTY_PRINT));
}


public function debug_all_sections()
{
    $this->output->set_content_type('application/json');

    // Require login.
    if (!$this->session->userdata('user_id')) {
        $this->output->set_status_header(403);

        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in first.'
        ], JSON_PRETTY_PRINT));
    }

    // Optional filters.
    $user_id = trim((string) $this->input->get('user_id', TRUE));
    $grade = trim((string) $this->input->get('grade', TRUE));
    $section = trim((string) $this->input->get('section', TRUE));
    $school_year = trim((string) $this->input->get('school_year', TRUE));
    $school_district = trim((string) $this->input->get('school_district', TRUE));
    $legislative_district = trim(
        (string) $this->input->get('legislative_district', TRUE)
    );

    $limit = 100;
    $page = max(1, (int) $this->input->get('page'));
    $offset = ($page - 1) * $limit;

    // Build a reusable filter for grade_sections.
    $apply_filters = function () use (
        $user_id,
        $grade,
        $section,
        $school_year,
        $school_district,
        $legislative_district
    ) {
        if ($user_id !== '') {
            $this->db->where('user_id', $user_id);
        }

        if ($grade !== '') {
            $this->db->where('grade', $grade);
        }

        if ($section !== '') {
            $this->db->where(
                'UPPER(TRIM(section)) = ' .
                $this->db->escape(strtoupper($section)),
                NULL,
                FALSE
            );
        }

        if ($school_year !== '') {
            $this->db->where('year', $school_year);
        }

        if ($school_district !== '') {
            $this->db->where('school_district', $school_district);
        }

        if ($legislative_district !== '') {
            $this->db->where(
                'legislative_district',
                $legislative_district
            );
        }
    };

    // Count all matching section rows.
    $this->db->from('grade_sections');
    $apply_filters();
    $total_sections = $this->db->count_all_results();

    // Retrieve matching sections.
    $this->db->select(
        'id, user_id, grade, section, year, ' .
        'legislative_district, school_district, created_at'
    );
    $this->db->from('grade_sections');
    $apply_filters();
    $this->db->order_by('user_id', 'ASC');
    $this->db->order_by('year', 'DESC');
    $this->db->order_by('grade', 'ASC');
    $this->db->order_by('section', 'ASC');
    $this->db->order_by('id', 'ASC');
    $this->db->limit($limit, $offset);

    $query = $this->db->get();

    if ($query === FALSE) {
        $this->output->set_status_header(500);

        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'Could not retrieve sections.',
            'database_error' => $this->db->error()
        ], JSON_PRETTY_PRINT));
    }

    $sections = $query->result_array();
    $section_ids = [];

    foreach ($sections as $row) {
        $section_ids[] = (int) $row['id'];
    }

    // Count linked assessments for the sections on this page.
    $assessment_counts = [];

    if (!empty($section_ids)) {
        $this->db->select(
            'section_id, COUNT(*) AS total_assessments, ' .
            'SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) AS active_assessments, ' .
            'SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) AS deleted_assessments'
        );
        $this->db->from('nutritional_assessments');
        $this->db->where_in('section_id', $section_ids);
        $this->db->group_by('section_id');

        $count_query = $this->db->get();

        if ($count_query === FALSE) {
            $this->output->set_status_header(500);

            return $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Could not count linked assessments.',
                'database_error' => $this->db->error()
            ], JSON_PRETTY_PRINT));
        }

        foreach ($count_query->result_array() as $count_row) {
            $assessment_counts[(string) $count_row['section_id']] = [
                'total' => (int) $count_row['total_assessments'],
                'active' => (int) $count_row['active_assessments'],
                'deleted' => (int) $count_row['deleted_assessments']
            ];
        }
    }

    // Attach counts and a section-level matching summary.
    foreach ($sections as &$row) {
        $key = (string) $row['id'];

        $row['linked_assessments'] = isset($assessment_counts[$key])
            ? $assessment_counts[$key]
            : [
                'total' => 0,
                'active' => 0,
                'deleted' => 0
            ];

        // Count active assessments with matching section details
        // but section_id still NULL. This is diagnostic only.
        $this->db->from('nutritional_assessments');
        $this->db->where('section_id IS NULL', NULL, FALSE);
        $this->db->where('grade_level', $row['grade']);
        $this->db->where('year', $row['year']);
        $this->db->where(
            'UPPER(TRIM(section)) = ' .
            $this->db->escape(strtoupper(trim($row['section']))),
            NULL,
            FALSE
        );
        $this->db->where('is_deleted', 0);

        if ($row['school_district'] !== NULL) {
            $this->db->where(
                'school_district',
                $row['school_district']
            );
        }

        if ($row['legislative_district'] !== NULL) {
            $this->db->where(
                'legislative_district',
                $row['legislative_district']
            );
        }

        $row['unlinked_assessments_same_details'] =
            $this->db->count_all_results();
    }
    unset($row);

    return $this->output->set_output(json_encode([
        'success' => true,
        'mode' => 'READ_ONLY',
        'description' =>
            'Lists grade_sections and counts linked assessments. ' .
            'Unlinked counts use matching grade, section, year, and ' .
            'district only; they do not prove the school matches.',
        'applied_filters' => [
            'user_id' => $user_id ?: NULL,
            'grade' => $grade ?: NULL,
            'section' => $section ?: NULL,
            'school_year' => $school_year ?: NULL,
            'school_district' => $school_district ?: NULL,
            'legislative_district' => $legislative_district ?: NULL
        ],
        'pagination' => [
            'page' => $page,
            'per_page' => $limit,
            'total_sections' => $total_sections,
            'total_pages' => (int) ceil($total_sections / $limit),
            'returned_sections' => count($sections)
        ],
        'sections' => $sections
    ], JSON_PRETTY_PRINT));
}

    /**
     * READ-ONLY diagnostic endpoint.
     * Lists individual ACTIVE assessments whose section_id is NULL (all schools),
     * with the grade_sections rows that could match by grade/section/year/district.
     * Nothing is updated or deleted. Student names are intentionally not returned.
     *
     * Usage:
     *   nutritionalassessment/debug_unlinked_assessments
     *   nutritionalassessment/debug_unlinked_assessments?page=2
     *   Optional filters: school_id, school_district, legislative_district,
     *                     grade, section, year, assessment_type
     */
public function debug_unlinked_assessments()
{
    $this->output->set_content_type('application/json');

    // Require login only.
    if (!$this->session->userdata('user_id')) {
        $this->output->set_status_header(403);
        return $this->output->set_output(json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in first.'
        ], JSON_PRETTY_PRINT));
    }
 
        $limit  = 100;
        $page   = max(1, (int) $this->input->get('page'));
        $offset = ($page - 1) * $limit;
 
        $filters = [
            'school_id'            => trim((string) $this->input->get('school_id', TRUE)),
            'school_district'      => trim((string) $this->input->get('school_district', TRUE)),
            'legislative_district' => trim((string) $this->input->get('legislative_district', TRUE)),
            'grade_level'          => trim((string) $this->input->get('grade', TRUE)),
            'year'                 => trim((string) $this->input->get('year', TRUE)),
            'assessment_type'      => trim((string) $this->input->get('assessment_type', TRUE)),
        ];
        $section_filter = trim((string) $this->input->get('section', TRUE));
 
        $apply_filters = function () use ($filters, $section_filter) {
            // Core condition: every active assessment with no section.
            $this->db->where('section_id IS NULL', NULL, FALSE);
            $this->db->where('is_deleted', 0);
 
            foreach ($filters as $column => $value) {
                if ($value !== '') {
                    $this->db->where($column, $value);
                }
            }
            if ($section_filter !== '') {
                $this->db->where(
                    'UPPER(TRIM(section)) = ' .
                    $this->db->escape(strtoupper($section_filter)),
                    NULL,
                    FALSE
                );
            }
        };
 
        // Total count.
        $this->db->from('nutritional_assessments');
        $apply_filters();
        $total = $this->db->count_all_results();
 
        // Page of records.
        $this->db->select(
            'id, school_id, school_name, grade_level, section, year, ' .
            'legislative_district, school_district, assessment_type, ' .
            'created_at, updated_at'
        );
        $this->db->from('nutritional_assessments');
        $apply_filters();
        $this->db->order_by('school_id', 'ASC');
        $this->db->order_by('year', 'DESC');
        $this->db->order_by('grade_level', 'ASC');
        $this->db->order_by('section', 'ASC');
        $this->db->order_by('id', 'ASC');
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
 
        if ($query === FALSE) {
            $this->output->set_status_header(500);
            return $this->output->set_output(json_encode([
                'success' => FALSE,
                'message' => 'Could not retrieve unlinked assessments.',
                'database_error' => $this->db->error()
            ], JSON_PRETTY_PRINT));
        }
 
        $rows = $query->result_array();
 
        // Attach candidate sections (cache per unique detail combination).
        $cache = [];
        $summary = ['NO_CANDIDATE' => 0, 'ONE_CANDIDATE' => 0, 'MULTIPLE_CANDIDATES' => 0];
 
        foreach ($rows as &$row) {
            $key = md5(json_encode([
                $row['grade_level'], strtoupper(trim((string) $row['section'])),
                $row['year'], $row['legislative_district'], $row['school_district']
            ]));
 
            if (!isset($cache[$key])) {
                $this->db->select('id, user_id');
                $this->db->from('grade_sections');
                $this->db->where('grade', $row['grade_level']);
                $this->db->where('year', $row['year']);
                $this->db->where(
                    'UPPER(TRIM(section)) = ' .
                    $this->db->escape(strtoupper(trim((string) $row['section']))),
                    NULL,
                    FALSE
                );
                if ($row['legislative_district'] !== NULL) {
                    $this->db->where('legislative_district', $row['legislative_district']);
                }
                if ($row['school_district'] !== NULL) {
                    $this->db->where('school_district', $row['school_district']);
                }
                $cq = $this->db->get();
                $cache[$key] = ($cq === FALSE) ? [] : $cq->result_array();
            }
 
            $count = count($cache[$key]);
            $row['candidate_sections'] = $cache[$key];
            $row['candidate_status'] = $count === 0
                ? 'NO_CANDIDATE'
                : ($count === 1 ? 'ONE_CANDIDATE' : 'MULTIPLE_CANDIDATES');
            $summary[$row['candidate_status']]++;
        }
        unset($row);
 
        return $this->output->set_output(json_encode([
            'success' => TRUE,
            'mode' => 'READ_ONLY',
            'description' =>
                'Active assessments with section_id IS NULL, for all schools. ' .
                'Candidates match only on grade, section, year and district; ' .
                'they do NOT prove the school matches. Verify ownership before linking.',
            'applied_filters' => array_filter($filters + ['section' => $section_filter]),
            'pagination' => [
                'page' => $page,
                'per_page' => $limit,
                'total_unlinked' => $total,
                'total_pages' => (int) ceil($total / $limit),
                'returned' => count($rows)
            ],
            'candidate_summary_this_page' => $summary,
            'assessments' => $rows
        ], JSON_PRETTY_PRINT));
    }

}