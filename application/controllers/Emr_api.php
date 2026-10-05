<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emr_api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');

        if (!$this->session->userdata('logged_in')) {
            $this->json_out(401, ['error' => 'Unauthorized']);
        }

        // Writes must be application/json so cross-site form posts
        // can't reach us. GETs stay open.
        if ($this->request_method() !== 'GET') {
            $ct = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($ct, 'application/json') !== 0) {
                $this->json_out(415, ['error' => 'JSON required']);
            }
        }
    }

    // --------------------------------------------------------------
    // OUTPUT / AUTH HELPERS
    // --------------------------------------------------------------

    private function json_out($status, array $body)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_header('Cache-Control: no-store')
            ->set_output(json_encode($body))
            ->_display();
        exit;
    }

    private function json_ok($data)
    {
        if (ob_get_length()) {
            ob_clean();
        }
        $this->output
            ->set_content_type('application/json')
            ->set_header('Cache-Control: no-store')
            ->set_output(json_encode($data))
            ->_display();
        exit;
    }

    /**
     * Case-insensitive enum canonicaliser.
     *   returns the canonical allowed value
     *   returns $default (null by default) if the input is empty
     *   returns false if the input isn't in the allow-list
     */
    private function canon($value, array $allowed, $default = null)
    {
        $v = strtolower(trim((string) $value));
        if ($v === '') return $default;
        foreach ($allowed as $a) {
            if (strtolower($a) === $v) return $a;
        }
        return false;
    }

    private function get_user_role()
    {
        $role = $this->session->userdata('role');
        return $role ? strtolower(trim((string) $role)) : null;
    }

    private function get_school_id()
    {
        return $this->session->userdata('school_id');
    }

    private function require_role(array $allowed_roles)
    {
        $role = $this->get_user_role();
        if (!$role || !in_array($role, $allowed_roles, true)) {
            $this->json_out(403, ['error' => 'Forbidden']);
        }
        return $role;
    }

    /**
     * Returns:
     *   null  -> doctor, sees all schools
     *   int   -> nurse, sees only their own school
     * Exits 403 if the user is neither doctor nor nurse, or a nurse
     * has no school_id in the session.
     */
    private function scope_school_id()
    {
        $role = $this->require_role(['doctor', 'nurse']);
        if ($role === 'doctor') return null;

        $sid = $this->get_school_id();
        if (!$sid) $this->json_out(403, ['error' => 'Forbidden']);

        return (int) $sid;
    }

    /**
     * Ensures the given patient exists and is visible to the current
     * user. Returns 404 (not 403) so we don't reveal whether an ID
     * exists outside the user's scope.
     */
    private function assert_patient_in_scope($patient_id)
    {
        $scope = $this->scope_school_id();

        $row = $this->db
            ->select('school_id')
            ->where('id', (int) $patient_id)
            ->get('emr_patients')
            ->row();

        if (!$row || ($scope !== null && (int) $row->school_id !== $scope)) {
            $this->json_out(404, ['error' => 'Not found']);
        }
    }

    private function require_method(array $methods)
    {
        if (!in_array($this->request_method(), $methods, true)) {
            $this->output->set_header('Allow: ' . implode(', ', $methods));
            $this->json_out(405, ['error' => 'Method not allowed']);
        }
    }

    private function request_method()
    {
        return strtoupper(trim((string) ($_SERVER['REQUEST_METHOD'] ?? '')));
    }

    private function close_session_read_lock()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * True if the given column is a generated column.
     * Cached per-request since SHOW COLUMNS is cheap but not free.
     */
    private function is_generated_column($table, $column)
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) return $cache[$key];

        $table  = $this->db->escape_str($table);
        $column = $this->db->escape_str($column);
        $q = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->escape($column));
        $row = $q && $q->num_rows() ? $q->row_array() : null;
        $isGen = $row && isset($row['Extra']) && stripos($row['Extra'], 'GENERATED') !== false;

        $cache[$key] = $isGen;
        return $isGen;
    }

    // --------------------------------------------------------------
    // SCHOOLS
    // --------------------------------------------------------------

    public function schools()
    {
        $this->require_method(['GET']);
        $scope = $this->scope_school_id();
        $this->close_session_read_lock();

        $this->db->select('s.*, sd.name as district, ld.name as legislative_district');
        $this->db->from('schools s');
        $this->db->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->db->join('legislative_districts ld', 'ld.id = sd.legislative_district_id', 'left');

        // Nurses only see their own school here too.
        if ($scope !== null) {
            $this->db->where('s.id', $scope);
        }

        $schools = $this->db->get()->result();
        $this->json_ok($schools);
    }

    // --------------------------------------------------------------
    // PATIENTS
    // --------------------------------------------------------------

    public function patients()
    {
        $this->require_method(['GET']);
        $scope = $this->scope_school_id();
        $this->close_session_read_lock();

        $page = max(1, (int) $this->input->get('page'));
        $perPage = (int) $this->input->get('per_page');
        if ($perPage < 1) $perPage = 25;
        $perPage = min(100, $perPage);
        $queryText = trim((string) $this->input->get('q'));
        $schoolFilter = (int) $this->input->get('school');
        $statusFilter = (string) $this->input->get('status');

        $this->db->reset_query();
        $this->db->from('emr_patients p');
        $this->apply_patient_filters($scope, $schoolFilter, $statusFilter, $queryText);
        $totalQuery = $this->db->select('COUNT(*) AS total', false)->get();
        if ($totalQuery === false) {
            log_message('error', 'EMR patient count query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load patients']);
        }
        $total = (int) $totalQuery->row()->total;

        $this->db->reset_query();
        $this->db->select('
            p.id,
            p.school_id,
            p.name,
            p.age,
            p.gender,
            p.phone,
            p.address,
            p.consent,
            p.status,
            p.created_at,
            p.updated_at
        ');
        $this->db->from('emr_patients p');
        $this->apply_patient_filters($scope, $schoolFilter, $statusFilter, $queryText);

        $this->db->order_by('p.id', 'DESC');
        $this->db->limit($perPage, ($page - 1) * $perPage);

        $query = $this->db->get();

        if ($query === false) {
            log_message('error', 'EMR patients query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load patients']);
        }

        $this->json_ok([
            'data' => $query->result(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    private function apply_patient_filters($scope, $schoolFilter, $statusFilter, $queryText)
    {
        if ($scope !== null) {
            $this->db->where('p.school_id', $scope);
        } elseif ($schoolFilter > 0) {
            $this->db->where('p.school_id', $schoolFilter);
        }
        if ($statusFilter === 'active' || $statusFilter === 'inactive') {
            $this->db->where('p.status', $statusFilter);
        }
        if ($queryText !== '') {
            $this->db->group_start();
            if (ctype_digit($queryText)) {
                $this->db->where('p.id', (int) $queryText)
                    ->or_like('p.name', $queryText, 'after')
                    ->or_like('p.phone', $queryText, 'both');
            } else {
                $this->db->like('p.name', $queryText, 'after')
                    ->or_like('p.phone', $queryText, 'both');
            }
            $this->db->group_end();
        }
    }

    public function patient($id = null)
    {
        if ($this->request_method() === 'GET') {
            $this->require_method(['GET']);
            if (!$id) $this->json_out(400, ['error' => 'Patient ID required']);
            $this->assert_patient_in_scope($id);
            $this->close_session_read_lock();
            $patientQuery = $this->db->where('id', (int) $id)->get('emr_patients');
            if ($patientQuery === false) {
                log_message('error', 'EMR patient detail query failed: ' . $this->db->error()['message']);
                $this->json_out(500, ['error' => 'Unable to load patient']);
            }
            $patient = $patientQuery->row();
            if (!$patient) $this->json_out(404, ['error' => 'Not found']);
            $this->json_ok($patient);
        }

        $this->require_method(['POST', 'PUT']);
        $scope = $this->scope_school_id();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json_out(400, ['error' => 'Invalid JSON']);
        }

        if ($id !== null) {
            $data['id'] = $id;
        }
        $isUpdate = !empty($data['id']);

        if (!$isUpdate && (empty($data['name']) || empty($data['school_id']))) {
            $this->json_out(400, ['error' => 'Name and school_id required']);
        }

        // ---- Validate enums / ranges / lengths ----
        $gender = null;
        if (array_key_exists('gender', $data)) {
            $gender = $this->canon($data['gender'], ['Male', 'Female', 'Other']);
            if ($gender === false) {
                $this->json_out(422, ['error' => 'Invalid gender']);
            }
        }

        $status = 'active';
        if (array_key_exists('status', $data)) {
            $status = $this->canon($data['status'], ['active', 'inactive'], 'active');
            if ($status === false) {
                $this->json_out(422, ['error' => 'Invalid status']);
            }
        }

        $consent = $this->canon($data['consent'] ?? '', ['Yes', 'No']);
        if ($consent === false) {
            $this->json_out(422, ['error' => 'Invalid consent']);
        }

        $age = null;
        if (array_key_exists('age', $data) && $data['age'] !== '') {
            $age = (int) $data['age'];
            if ($age < 0 || $age > 150) {
                $this->json_out(422, ['error' => 'Age out of range']);
            }
        }

        $name = array_key_exists('name', $data) ? trim((string) $data['name']) : null;
        if ($name !== null && $name === '') {
            $this->json_out(422, ['error' => 'Name is required']);
        }
        if ($name !== null && mb_strlen($name) > 150) {
            $this->json_out(422, ['error' => 'Name too long']);
        }

        $phone = array_key_exists('phone', $data) ? trim((string) $data['phone']) : null;
        if ($phone !== null && mb_strlen($phone) > 50) {
            $this->json_out(422, ['error' => 'Phone too long']);
        }

        $school_id = array_key_exists('school_id', $data) ? (int) $data['school_id'] : null;
        if ($school_id !== null && $school_id < 1) {
            $this->json_out(422, ['error' => 'Invalid school_id']);
        }
        if ($scope !== null && $school_id !== null && $school_id !== $scope) {
            $this->json_out(403, ['error' => 'Forbidden']);
        }

        $patient = $isUpdate ? [] : [
            'school_id' => $school_id,
            'name'      => $name,
            'age'       => $age,
            'gender'    => $gender,
            'phone'     => $phone !== '' ? $phone : null,
            'address'   => isset($data['address']) ? trim((string) $data['address']) : null,
            'status'    => $status,
        ];
        if ($isUpdate) {
            if ($school_id !== null) $patient['school_id'] = $school_id;
            if ($name !== null) $patient['name'] = $name;
            if (array_key_exists('age', $data)) $patient['age'] = $age;
            if (array_key_exists('gender', $data)) $patient['gender'] = $gender;
            if (array_key_exists('phone', $data)) $patient['phone'] = $phone !== '' ? $phone : null;
            if (array_key_exists('address', $data)) {
                $address = trim((string) $data['address']);
                $patient['address'] = $address !== '' ? $address : null;
            }
            if (array_key_exists('status', $data)) $patient['status'] = $status;
        }

        // Only touch consent when the client actually sent it, so an
        // update that doesn't carry the field doesn't wipe it.
        if (array_key_exists('consent', $data)) {
            $patient['consent'] = $consent;
        }

        try {
            if (!$isUpdate) {
                if (!$this->db->insert('emr_patients', $patient)) {
                    throw new Exception($this->db->error()['message']);
                }
                $patient['id'] = $this->db->insert_id();
            } else {
                $this->assert_patient_in_scope($data['id']);

                $this->db->where('id', (int) $data['id']);
                if (!$this->db->update('emr_patients', $patient)) {
                    throw new Exception($this->db->error()['message']);
                }
                $patient['id'] = (int) $data['id'];
            }

            $this->json_ok(['success' => true, 'patient' => $patient]);
        } catch (Exception $e) {
            log_message('error', 'EMR patient save failed: ' . $e->getMessage());
            $this->json_out(500, ['error' => 'Unable to save patient']);
        }
    }

    public function delete_patient($id)
    {
        $this->require_method(['DELETE', 'POST']);
        $this->require_role(['doctor']); // adjust if nurses should delete

        if (!$id) {
            $this->json_out(400, ['error' => 'Patient ID required']);
        }

        $this->assert_patient_in_scope($id);

        try {
            $this->db->trans_start();
            $this->db->where('patient_id', (int) $id)->delete('emr_followups');
            $this->db->where('id', (int) $id)->delete('emr_patients');
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                throw new Exception('Database transaction failed');
            }
            $this->json_ok(['success' => true]);
        } catch (Exception $e) {
            log_message('error', 'EMR delete_patient failed: ' . $e->getMessage());
            $this->json_out(500, ['error' => 'Unable to delete patient']);
        }
    }

    // --------------------------------------------------------------
    // FOLLOWUPS
    // --------------------------------------------------------------

    public function visits()
    {
        $this->require_method(['GET']);
        $scope = $this->scope_school_id();
        $patientFilter = (int) $this->input->get('patient_id');
        if ($patientFilter > 0) $this->assert_patient_in_scope($patientFilter);
        $this->close_session_read_lock();

        $district  = $this->input->get('district');
        $school_id = $this->input->get('school');
        $year      = $this->input->get('year');
        $hasImpNorm = $this->db->field_exists('impression_norm', 'emr_followups');

        $select = [
            'f.id',
            'f.patient_id',
            'f.followup_date',
            'f.impression',
            'f.maintenance',
            'p.name as patient_name',
            'p.school_id',
            's.name as school_name',
            'sd.name as district',
            'ld.name as legislative_district',
        ];
        if ($hasImpNorm) $select[] = 'f.impression_norm';
        $this->db->select(implode(",\n", $select));
        $this->db->from('emr_followups f');
        $this->db->join('emr_patients p', 'p.id = f.patient_id');
        $this->db->join('schools s', 's.id = p.school_id', 'left');
        $this->db->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->db->join('legislative_districts ld', 'ld.id = sd.legislative_district_id', 'left');

        if ($scope !== null) {
            // Nurse: only their own school.
            $this->db->where('p.school_id', $scope);
        } else {
            // Doctor: optional filters.
            if ($district)  $this->db->where('sd.name', $district);
            if ($school_id) $this->db->where('p.school_id', (int) $school_id);
            if ($year) {
                $y = (int) $year;
                // sargable range, not YEAR()
                $this->db
                    ->where('f.followup_date >=', "$y-01-01")
                    ->where('f.followup_date <',  ($y + 1) . '-01-01');
            }
        }
        if ($patientFilter > 0) $this->db->where('f.patient_id', $patientFilter);

        $this->db->order_by('f.followup_date', 'DESC')->order_by('f.id', 'DESC');

        $query = $this->db->get();
        if ($query === false) {
            log_message('error', 'EMR visits query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load followups']);
        }

        $this->json_ok($query->result());
    }

    public function visit($id = null)
    {
        if ($this->request_method() === 'GET') {
            $this->require_method(['GET']);
            if (!$id) $this->json_out(400, ['error' => 'Followup ID required']);

            $visitQuery = $this->db
                ->select('f.*, p.name as patient_name, p.school_id, s.name as school_name, sd.name as district, ld.name as legislative_district')
                ->from('emr_followups f')
                ->join('emr_patients p', 'p.id = f.patient_id')
                ->join('schools s', 's.id = p.school_id', 'left')
                ->join('school_districts sd', 'sd.id = s.school_district_id', 'left')
                ->join('legislative_districts ld', 'ld.id = sd.legislative_district_id', 'left')
                ->where('f.id', (int) $id)
                ->get();
            if ($visitQuery === false) {
                log_message('error', 'EMR visit detail query failed: ' . $this->db->error()['message']);
                $this->json_out(500, ['error' => 'Unable to load followup']);
            }
            $visit = $visitQuery->row();
            if (!$visit) $this->json_out(404, ['error' => 'Not found']);
            $this->assert_patient_in_scope($visit->patient_id);
            $this->close_session_read_lock();
            $visit->lab = $visit->lab === null || $visit->lab === '' ? null : json_decode($visit->lab, true);
            $visit->prescription = $visit->prescription === null || $visit->prescription === ''
                ? [] : json_decode($visit->prescription, true);
            if (!is_array($visit->prescription)) $visit->prescription = [];
            $this->json_ok($visit);
        }

        $this->require_method(['POST', 'PUT']);

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json_out(400, ['error' => 'Invalid JSON']);
        }
        if (empty($data['patient_id']) || empty($data['followup_date'])) {
            $this->json_out(400, ['error' => 'Patient and date required']);
        }

        $this->assert_patient_in_scope($data['patient_id']);

        // ---- Validate ranges ----
        $hr = null;
        if (isset($data['hr']) && $data['hr'] !== '') {
            $hr = (int) $data['hr'];
            if ($hr < 0 || $hr > 400) $this->json_out(422, ['error' => 'HR out of range']);
        }

        $spo2 = null;
        if (isset($data['spo2']) && $data['spo2'] !== '') {
            $spo2 = (float) $data['spo2'];
            if ($spo2 < 0 || $spo2 > 100) $this->json_out(422, ['error' => 'SpO2 out of range']);
        }

        $rr = null;
        if (isset($data['rr']) && $data['rr'] !== '') {
            $rr = (int) $data['rr'];
            if ($rr < 0 || $rr > 200) $this->json_out(422, ['error' => 'RR out of range']);
        }

        $temp = null;
        if (isset($data['temp']) && $data['temp'] !== '') {
            $temp = (float) $data['temp'];
            if ($temp < 20 || $temp > 50) $this->json_out(422, ['error' => 'Temperature out of range']);
        }

        foreach ([
            'bp' => 'BP',
            'vision_left' => 'Vision left',
            'vision_right' => 'Vision right',
            'hearing_left' => 'Hearing left',
            'hearing_right' => 'Hearing right',
        ] as $field => $label) {
            if (isset($data[$field]) && mb_strlen((string) $data[$field]) > 20) {
                $this->json_out(422, ['error' => $label . ' too long']);
            }
        }

        $followupDate = DateTime::createFromFormat('!Y-m-d', (string) $data['followup_date']);
        $dateErrors = DateTime::getLastErrors();
        if (!$followupDate
            || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))
            || $followupDate->format('Y-m-d') !== $data['followup_date']) {
            $this->json_out(422, ['error' => 'Invalid followup_date']);
        }
        $latestAllowedDate = new DateTime('today');
        $latestAllowedDate->modify('+1 year');
        if ($followupDate > $latestAllowedDate) {
            $this->json_out(422, ['error' => 'followup_date is too far in the future']);
        }

        $bp = $data['bp'] ?? null;
        $referral_status = $this->canon($data['referral_status'] ?? '', ['Pending', 'Completed']);
        if ($referral_status === false) {
            $this->json_out(422, ['error' => 'Invalid referral status']);
        }

        $impression = $data['impression'] ?? null;
        $impression_norm = null;
        if ($impression !== null && trim((string) $impression) !== '') {
            $impression_norm = mb_substr(
                mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $impression))),
                0,
                191
            );
        }

        $followup = [
            'patient_id'      => (int) $data['patient_id'],
            'followup_date'   => $data['followup_date'],

            'bp'              => $bp !== '' ? $bp : null,
            'hr'              => $hr,
            'spo2'            => $spo2,
            'rr'              => $rr,
            'temp'            => $temp,

            'impression'      => $impression,
            'maintenance'     => $data['maintenance'] ?? null,
            'doctor_notes'    => $data['doctor_notes'] ?? null,
            'vision_left'     => $data['vision_left'] ?? null,
            'vision_right'    => $data['vision_right'] ?? null,
            'hearing_left'    => $data['hearing_left'] ?? null,
            'hearing_right'   => $data['hearing_right'] ?? null,
            'dental_findings' => $data['dental_findings'] ?? null,
            'xray_results'    => $data['xray_results'] ?? null,
            'immunizations'   => $data['immunizations'] ?? null,
            'referral_notes'  => $data['referral_notes'] ?? null,
            'referral_status' => $referral_status,
            'prescription'    => json_encode($data['prescription'] ?? []),
            'lab'             => json_encode($data['lab'] ?? []),
        ];

        if ($this->db->field_exists('impression_norm', 'emr_followups')
            && !$this->is_generated_column('emr_followups', 'impression_norm')) {
            $followup['impression_norm'] = $impression_norm;
        }

        try {
            if (empty($data['id'])) {
                if (!$this->db->insert('emr_followups', $followup)) {
                    throw new Exception($this->db->error()['message']);
                }
                $followup['id'] = $this->db->insert_id();
            } else {
                // Verify existing follow-up is in scope before updating.
                $existing = $this->db
                    ->select('patient_id')
                    ->where('id', (int) $data['id'])
                    ->get('emr_followups')
                    ->row();
                if (!$existing) {
                    $this->json_out(404, ['error' => 'Not found']);
                }
                $this->assert_patient_in_scope($existing->patient_id);

                $this->db->where('id', (int) $data['id']);
                if (!$this->db->update('emr_followups', $followup)) {
                    throw new Exception($this->db->error()['message']);
                }
                $followup['id'] = (int) $data['id'];
            }

            $followup['lab']          = json_decode($followup['lab'], true);
            $followup['prescription'] = json_decode($followup['prescription'], true);

            $this->json_ok(['success' => true, 'followup' => $followup]);
        } catch (Exception $e) {
            log_message('error', 'EMR visit save failed: ' . $e->getMessage());
            $this->json_out(500, ['error' => 'Unable to save followup']);
        }
    }

    public function dashboard()
    {
        $this->require_method(['GET']);
        $scope = $this->scope_school_id();
        $this->close_session_read_lock();
        $district = trim((string) $this->input->get('district'));
        $school = (int) $this->input->get('school');
        $year = trim((string) $this->input->get('year'));
        $impression = trim((string) $this->input->get('impression'));
        $page = max(1, (int) $this->input->get('page'));
        $perPage = (int) $this->input->get('per_page');
        if ($perPage < 1) $perPage = 20;
        $perPage = min(100, $perPage);
        if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
            $this->json_out(422, ['error' => 'Invalid year']);
        }
        if (mb_strlen($impression) > 191) {
            $this->json_out(422, ['error' => 'Invalid impression']);
        }

        $hasImpNorm = $this->db->field_exists('impression_norm', 'emr_followups');
        $normExpression = $hasImpNorm ? 'f.impression_norm' : 'UPPER(TRIM(f.impression))';

        $this->db->reset_query();
        $this->db->from('emr_patients p')
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        if ($scope !== null) $this->db->where('p.school_id', $scope);
        if ($school > 0 && $scope === null) $this->db->where('p.school_id', $school);
        if ($district !== '') $this->db->where('sd.name', $district);
        $patientCount = (int) $this->db->count_all_results();

        $this->db->reset_query();
        $this->db->select($normExpression . ' AS impression_norm, COUNT(*) AS total', false)
            ->from('emr_followups f')
            ->join('emr_patients p', 'p.id = f.patient_id')
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('school_districts sd', 'sd.id = s.school_district_id', 'left')
            ->where($normExpression . ' IS NOT NULL', null, false)
            ->where($normExpression . " <> ''", null, false)
            ->where($normExpression . " <> 'NONE'", null, false);
        $this->apply_dashboard_visit_filters($scope, $district, $school, $year);
        $topQuery = $this->db->group_by($normExpression, false)->order_by('total', 'DESC')->limit(5)->get();
        if ($topQuery === false) {
            log_message('error', 'EMR dashboard impression query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load dashboard']);
        }
        $topImpressions = $topQuery->result();

        $this->db->reset_query();
        $latestQuery = $this->db->select('f.id, f.patient_id, f.followup_date, f.impression, ' . $normExpression . ' AS impression_norm, p.name AS patient_name, p.school_id, s.name AS school_name, sd.name AS district', false)
            ->from('emr_followups f')
            ->join('emr_patients p', 'p.id = f.patient_id')
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->apply_dashboard_visit_filters($scope, $district, $school, $year);
        $latestQuery = $this->db->order_by('f.followup_date', 'DESC')->order_by('f.id', 'DESC')->limit(200)->get();
        if ($latestQuery === false) {
            log_message('error', 'EMR dashboard latest-visit query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load dashboard']);
        }
        $latestVisits = [];
        $seenPatients = [];
        foreach ($latestQuery->result() as $row) {
            if (isset($seenPatients[$row->patient_id])) continue;
            $seenPatients[$row->patient_id] = true;
            $latestVisits[] = $row;
            if (count($latestVisits) >= 10) break;
        }

        $this->db->reset_query();
        $slideQuery = $this->db->select('f.id, f.patient_id, f.followup_date, f.impression, ' . $normExpression . ' AS impression_norm, f.doctor_notes, p.name AS patient_name, p.school_id, s.name AS school_name', false)
            ->from('emr_followups f')
            ->join('emr_patients p', 'p.id = f.patient_id')
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->apply_dashboard_visit_filters($scope, $district, $school, $year);
        $slideshowQuery = $this->db->order_by('f.followup_date', 'DESC')->order_by('f.id', 'DESC')->limit(20)->get();
        if ($slideshowQuery === false) {
            log_message('error', 'EMR dashboard slideshow query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load dashboard']);
        }
        $slideshow = $slideshowQuery->result();

        $this->db->reset_query();
        $this->db->from('emr_followups f')
            ->join('emr_patients p', 'p.id = f.patient_id')
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->apply_dashboard_visit_filters($scope, $district, $school, $year);
        if ($impression !== '') $this->db->where($normExpression, $impression);
        $total = (int) $this->db->count_all_results();

        $this->db->reset_query();
        $visitQuery = $this->db->select('f.id, f.patient_id, f.followup_date, f.impression, ' . $normExpression . ' AS impression_norm, p.name AS patient_name, p.school_id, s.name AS school_name', false)
            ->from('emr_followups f')
            ->join('emr_patients p', 'p.id = f.patient_id')
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->apply_dashboard_visit_filters($scope, $district, $school, $year);
        if ($impression !== '') $this->db->where($normExpression, $impression);
        $visitRowsQuery = $this->db->order_by('f.followup_date', 'DESC')->order_by('f.id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)->get();
        if ($visitRowsQuery === false) {
            log_message('error', 'EMR dashboard visit-list query failed: ' . $this->db->error()['message']);
            $this->json_out(500, ['error' => 'Unable to load dashboard']);
        }
        $visitRows = $visitRowsQuery->result();

        $this->json_ok([
            'patient_count' => $patientCount,
            'top_impressions' => $topImpressions,
            'latest_visits' => $latestVisits,
            'slideshow' => $slideshow,
            'visits' => ['data' => $visitRows, 'total' => $total, 'page' => $page, 'per_page' => $perPage],
        ]);
    }

    private function apply_dashboard_visit_filters($scope, $district, $school, $year)
    {
        if ($scope !== null) $this->db->where('p.school_id', $scope);
        if ($school > 0 && $scope === null) $this->db->where('p.school_id', $school);
        if ($district !== '') $this->db->where('sd.name', $district);
        if ($year !== '') {
            $yearNumber = (int) $year;
            $this->db->where('f.followup_date >=', $yearNumber . '-01-01')
                ->where('f.followup_date <', ($yearNumber + 1) . '-01-01');
        }
    }

    public function delete_visit($id)
    {
        $this->require_method(['DELETE', 'POST']);
        $this->require_role(['doctor']); // adjust if nurses should delete

        if (!$id) {
            $this->json_out(400, ['error' => 'Followup ID required']);
        }

        $existing = $this->db
            ->select('patient_id')
            ->where('id', (int) $id)
            ->get('emr_followups')
            ->row();
        if (!$existing) {
            $this->json_out(404, ['error' => 'Not found']);
        }
        $this->assert_patient_in_scope($existing->patient_id);

        try {
            $this->db->where('id', (int) $id)->delete('emr_followups');
            $this->json_ok(['success' => true]);
        } catch (Exception $e) {
            log_message('error', 'EMR delete_visit failed: ' . $e->getMessage());
            $this->json_out(500, ['error' => 'Unable to delete followup']);
        }
    }

    // --------------------------------------------------------------
    // PROFILE / SEARCH
    // --------------------------------------------------------------

    public function profile()
    {
        $this->require_method(['GET']);
        $profile = [
            'role'      => $this->get_user_role(),
            'school_id' => $this->get_school_id(),
        ];
        $this->close_session_read_lock();
        $this->json_ok($profile);
    }

    public function search_schools()
    {
        $this->require_method(['GET']);
        $this->require_role(['doctor', 'nurse']);
        $scope = $this->scope_school_id();
        $this->close_session_read_lock();

        $q = trim((string) $this->input->get('q'));
        if ($q === '') {
            $this->json_ok([]);
            return;
        }

        $this->db->select('s.id, s.name as school_name, sd.name as district_name, ld.name as legislative_district_name');
        $this->db->from('schools s');
        $this->db->join('school_districts sd', 'sd.id = s.school_district_id', 'left');
        $this->db->join('legislative_districts ld', 'ld.id = sd.legislative_district_id', 'left');
        $this->db->like('s.name', $q);
        $this->db->limit(10);

        if ($scope !== null) {
            $this->db->where('s.id', $scope);
        }

        $this->json_ok($this->db->get()->result());
    }
}