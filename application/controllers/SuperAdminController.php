<?php
class Superadmincontroller extends CI_Controller {

    private $hidden_assessment_table_fields = [
        'is_locked',
        'classification_of_beneficiary',
        'pregnant',
        'with_0_1_year_old_child',
        'dewormed',
        'parent_consent_milk',
        'participation_4ps',
        'previous_sbfp'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('user_model');
        $this->load->library('session');
        $this->load->helper('url');
        
        // Require users to be logged in for access to this controller
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }

        // NOTE: allow any logged-in role to access the super admin dashboard
    }
    
    public function index() {
        // Get user counts by role
        $userCounts = $this->user_model->get_user_counts_by_role();
        
        // Get all users for management
        $users = $this->user_model->get_all_users();
        
        // Define available roles
        $availableRoles = ['super_admin', 'admin', 'district', 'division', 'doctor', 'user'];
        
        // Also load districts and school districts so the view can use authoritative values
        $this->load->model('legislative_district_model');
        $legislative_districts = $this->legislative_district_model->get_districts_with_school_districts();

        // Get ordered school districts for selection (includes legislative linkage)
        $school_districts = $this->db
            ->select('sd.*, ld.name as legislative_name')
            ->from('school_districts sd')
            ->join('legislative_districts ld', 'sd.legislative_district_id = ld.id', 'left')
            ->order_by('ld.name', 'ASC')
            ->order_by('sd.name', 'ASC')
            ->get()
            ->result();

        // Fetch schools with related district and legislative district names
        $this->load->model('school_model');
        $schools = $this->db
            ->select('s.*, sd.name as school_district_name, ld.name as legislative_name')
            ->from('schools s')
            ->join('school_districts sd', 's.school_district_id = sd.id', 'left')
            ->join('legislative_districts ld', 'sd.legislative_district_id = ld.id', 'left')
            ->order_by('ld.name', 'ASC')
            ->order_by('sd.name', 'ASC')
            ->order_by('s.name', 'ASC')
            ->get()
            ->result();

        // Also fetch users' school-related info directly from users table
        $users_school_info = $this->db
            ->select('id, name, legislative_district, school_district, school_id, school_address, school_level, school_head_name, school_info_completed')
            ->from('users')
            ->order_by('legislative_district', 'ASC')
            ->order_by('school_district', 'ASC')
            ->order_by('name', 'ASC')
            ->get()
            ->result();

        $data = [
            'title' => 'Super Admin Dashboard',
            'userCounts' => $userCounts,
            'users' => $users,
            'availableRoles' => $availableRoles,
            'currentUser' => $this->session->userdata('email'),
            'legislative_districts' => $legislative_districts,
            'school_districts' => $school_districts,
            'schools' => $schools,
            'users_school_info' => $users_school_info
        ];
        
        $this->load->view('templates/header', $data);
        // Load the new standard PHP view
        $this->load->view('superadmindashboard', $data);
        $this->load->view('templates/footer');
    }

    public function school_section()
    {
        redirect('nutritional-assessment-table');
    }

    public function nutritional_assessment_table()
    {
        if ($this->session->userdata('role') !== 'super_admin' && $this->session->userdata('role') !== 'admin') {
            show_error('You do not have permission to access this page.', 403);
            return;
        }

        $tables = [];
        foreach ([
            'nutritional_assessments' => 'Nutritional Assessment Table',
            'grade_sections' => 'Grade Sections Table'
        ] as $table => $title) {
            $exists = $this->db->table_exists($table);
            $fields = $exists ? $this->db->field_data($table) : [];
            $fields = array_values(array_filter($fields, function ($field) {
                return !in_array($field->name, $this->hidden_assessment_table_fields, TRUE);
            }));
            $rows = [];

                if ($exists) {
                    if (
                        $table === 'nutritional_assessments'
                        && in_array('is_deleted', $this->db->list_fields($table), TRUE)
                    ) {
                        $this->db->where('is_deleted', 0);
                    }
                    // HARD CAP: only ever load 10 rows per table.
                    $query = $this->db->order_by('id', 'DESC')->limit(10)->get($table);
                    if ($query === false) {
                        show_error('Could not load the ' . html_escape($title) . '.', 500);
                        return;
                    }
                    $rows = $query->result_array();
                    foreach ($rows as &$row) {
                        foreach ($this->hidden_assessment_table_fields as $hidden_field) {
                            unset($row[$hidden_field]);
                        }
                    }
                    unset($row);
                }

                $total = 0;
                if ($exists) {
                    if (
                        $table === 'nutritional_assessments'
                        && in_array('is_deleted', $this->db->list_fields($table), TRUE)
                    ) {
                        $this->db->where('is_deleted', 0);
                    }
                    $total = $this->db->count_all_results($table);
                }

                $tables[$table] = [
                    'title'  => $title,
                    'exists' => $exists,
                    'fields' => $fields,
                    'rows'   => $rows,
                    'total'  => $total,
                ];
        }

        $data = [
            'title' => 'Nutritional Assessment Table',
            'tables' => $tables
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('nutritional_assessment_table', $data);
        $this->load->view('templates/footer');
    }

    public function nutritional_assessment_table_data()
    {
        if ($this->session->userdata('role') !== 'super_admin' && $this->session->userdata('role') !== 'admin') {
            show_error('You do not have permission to access this page.', 403);
            return;
        }

        $table = $this->input->get('table', TRUE);
        if (!in_array($table, ['nutritional_assessments', 'grade_sections'], TRUE)) {
            show_error('Invalid table.', 400);
            return;
        }
        if (!$this->db->table_exists($table)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'draw' => (int) $this->input->get('draw'),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                ]));
        }

        // DataTables params
        $draw   = (int) $this->input->get('draw');
        $start  = max(0, (int) $this->input->get('start'));
        $length = (int) $this->input->get('length');
        $length = ($length > 0 && $length <= 100) ? $length : 10;   // cap page size

        $search = $this->input->get('search');
        $search = is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '';

        // Visible columns only (hide the same fields as the page view)
        $all_fields = $this->db->field_data($table);
        $visible_fields = [];
        foreach ($all_fields as $f) {
            if (!in_array($f->name, $this->hidden_assessment_table_fields, TRUE)) {
                $visible_fields[] = $f->name;
            }
        }

        $has_is_deleted = in_array('is_deleted', $this->db->list_fields($table), TRUE);
        $filter_deleted = ($table === 'nutritional_assessments' && $has_is_deleted);

        // Total (unfiltered) count
        if ($filter_deleted) {
            $this->db->where('is_deleted', 0);
        }
        $records_total = (int) $this->db->count_all_results($table);

        // Filtered count
        if ($filter_deleted) {
            $this->db->where('is_deleted', 0);
        }
        if ($search !== '' && !empty($visible_fields)) {
            $this->db->group_start();
            foreach ($visible_fields as $i => $name) {
                $i === 0 ? $this->db->like($name, $search) : $this->db->or_like($name, $search);
            }
            $this->db->group_end();
        }
        $records_filtered = (int) $this->db->count_all_results($table);

        // Rows for this page
        if ($filter_deleted) {
            $this->db->where('is_deleted', 0);
        }
        if ($search !== '' && !empty($visible_fields)) {
            $this->db->group_start();
            foreach ($visible_fields as $i => $name) {
                $i === 0 ? $this->db->like($name, $search) : $this->db->or_like($name, $search);
            }
            $this->db->group_end();
        }
        $rows = $this->db->order_by('id', 'DESC')
                        ->limit($length, $start)
                        ->get($table)
                        ->result_array();

        foreach ($rows as &$row) {
            foreach ($this->hidden_assessment_table_fields as $h) {
                unset($row[$h]);
            }
        }
        unset($row);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => $draw,
                'recordsTotal' => $records_total,
                'recordsFiltered' => $records_filtered,
                'data' => $rows,
            ]));
    }

   public function update_table_record()
    {
        if ($this->session->userdata('role') !== 'super_admin' && $this->session->userdata('role') !== 'admin') {
            show_error('You do not have permission to access this page.', 403);
            return;
        }
 
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }
 
        $table = $this->input->post('table', TRUE);
        if (!in_array($table, ['nutritional_assessments', 'grade_sections'], TRUE)) {
            show_error('Invalid table.', 400);
            return;
        }
 
        if (!$this->db->table_exists($table)) {
            show_error('The requested table is not available.', 404);
            return;
        }
 
        $id = $this->input->post('record_id', TRUE);
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            $this->session->set_flashdata('error', 'Invalid record ID.');
            redirect('nutritional-assessment-table');
            return;
        }
        $record_id = (int) $id;
 
        // FALSE: do not XSS-filter. The view escapes output, and the filter silently alters stored values.
        $posted_values = $this->input->post('values', FALSE);
        if (!is_array($posted_values)) {
            $this->session->set_flashdata('error', 'No record changes were submitted.');
            redirect('nutritional-assessment-table');
            return;
        }
 
        // The record must exist, and must not be deleted or locked.
        $existing = $this->db->get_where($table, ['id' => $record_id])->row_array();
        if (!$existing) {
            $this->session->set_flashdata('error', 'The record was not found.');
            redirect('nutritional-assessment-table');
            return;
        }
        if ($table === 'nutritional_assessments') {
            if (!empty($existing['is_deleted'])) {
                $this->session->set_flashdata('error', 'This assessment has been deleted and cannot be edited.');
                redirect('nutritional-assessment-table');
                return;
            }
            if (!empty($existing['is_locked'])) {
                $this->session->set_flashdata('error', 'This assessment is locked and cannot be edited.');
                redirect('nutritional-assessment-table');
                return;
            }
        }
 
        // Real column metadata, including nullability (CI's field_data() does not provide it).
        // $table is whitelisted above, so it is safe to place in the query.
        $columns_query = $this->db->query('SHOW COLUMNS FROM `' . $table . '`');
        if ($columns_query === FALSE) {
            $this->session->set_flashdata('error', 'Could not read the table structure.');
            redirect('nutritional-assessment-table');
            return;
        }
 
        $protected = ['id', 'created_at', 'updated_at', 'is_deleted', 'deleted_at'];
        $updates = [];
        foreach ($columns_query->result_array() as $column) {
            $name = $column['Field'];
            if (
                in_array($name, $protected, TRUE)
                || in_array($name, $this->hidden_assessment_table_fields, TRUE)
                || !array_key_exists($name, $posted_values)
                || !is_scalar($posted_values[$name])
            ) {
                continue;
            }
 
            $value = trim((string) $posted_values[$name]);
 
            if ($value === '') {
                if ($column['Null'] === 'YES') {
                    $value = NULL;
                } elseif (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|date|datetime|timestamp|time|year)/i', $column['Type'])) {
                    // Blank is not valid for a NOT NULL number/date column: leave it unchanged.
                    continue;
                }
            }
 
            // Only write fields that actually changed.
            $current = array_key_exists($name, $existing) ? $existing[$name] : NULL;
            if ($value === NULL ? $current === NULL : ($current !== NULL && (string) $current === $value)) {
                continue;
            }
 
            $updates[$name] = $value;
        }
 
        if (empty($updates)) {
            $this->session->set_flashdata('success', 'No changes were made.');
            redirect('nutritional-assessment-table');
            return;
        }
 
        if (in_array('updated_at', $this->db->list_fields($table), TRUE)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
        }
 
        $this->db->where('id', $record_id);
        $updated = $this->db->update($table, $updates);
        if (!$updated) {
            $db_error = $this->db->error();
            log_message('error', 'Failed updating ' . $table . ' record ' . $record_id . ': ' . $db_error['message']);
            $this->session->set_flashdata('error', 'The record could not be updated.');
        } else {
            $this->session->set_flashdata('success', 'Record updated successfully.');
        }
 
        redirect('nutritional-assessment-table');
    }

    public function delete_table_record()
    {
        if ($this->session->userdata('role') !== 'super_admin' && $this->session->userdata('role') !== 'admin') {
            show_error('You do not have permission to access this page.', 403);
            return;
        }

        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $table = $this->input->post('table', TRUE);
        if (!in_array($table, ['nutritional_assessments', 'grade_sections'], TRUE)) {
            show_error('Invalid table.', 400);
            return;
        }

        $id = $this->input->post('record_id', TRUE);
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            $this->session->set_flashdata('error', 'Invalid record ID.');
            redirect('nutritional-assessment-table');
            return;
        }

        if (!$this->db->table_exists($table)) {
            $this->session->set_flashdata('error', 'The requested table is not available.');
            redirect('nutritional-assessment-table');
            return;
        }

        $record_id = (int) $id;
        $record = $this->db->get_where($table, ['id' => $record_id])->row_array();
        if (!$record) {
            $this->session->set_flashdata('error', 'The record was not found.');
            redirect('nutritional-assessment-table');
            return;
        }

        if ($table === 'nutritional_assessments') {
            $fields = $this->db->list_fields($table);
            if (!in_array('is_deleted', $fields, TRUE)) {
                show_error('Soft deletion is not supported by this assessment table.', 500);
                return;
            }
            if (!empty($record['is_deleted'])) {
                $this->session->set_flashdata('error', 'This assessment has already been deleted.');
                redirect('nutritional-assessment-table');
                return;
            }

            $delete_data = ['is_deleted' => 1];
            if (in_array('deleted_at', $fields, TRUE)) {
                $delete_data['deleted_at'] = date('Y-m-d H:i:s');
            }

            $this->db->where('id', $record_id);
            $this->db->where('is_deleted', 0);
            $deleted = $this->db->update($table, $delete_data);
        } else {
            $has_assessments = $this->grade_section_has_assessments($record);
            if ($has_assessments === NULL) {
                $this->session->set_flashdata(
                    'error',
                    'Could not verify assessment links for this section; it was not deleted.'
                );
                redirect('nutritional-assessment-table');
                return;
            }
            if ($has_assessments) {
                $this->session->set_flashdata(
                    'error',
                    'This grade section is linked to nutritional assessments and cannot be deleted.'
                );
                redirect('nutritional-assessment-table');
                return;
            }

            $this->db->where('id', $record_id);
            $deleted = $this->db->delete($table);
        }

        if (!$deleted) {
            $db_error = $this->db->error();
            log_message('error', 'Failed deleting ' . $table . ' record ' . $record_id . ': ' . $db_error['message']);
            $this->session->set_flashdata('error', 'The record could not be deleted.');
        } else {
            $this->session->set_flashdata('success', 'Record deleted successfully.');
        }

        redirect('nutritional-assessment-table');
    }

    private function grade_section_has_assessments($section)
    {
        if (!$this->db->table_exists('nutritional_assessments')) {
            return NULL;
        }

        $assessment_fields = $this->db->list_fields('nutritional_assessments');
        if (in_array('section_id', $assessment_fields, TRUE)) {
            $linked_query = $this->db
                ->where('section_id', $section['id'])
                ->get('nutritional_assessments');
            if ($linked_query === false) {
                return NULL;
            }
            if ($linked_query->num_rows() > 0) {
                return TRUE;
            }
        }

        $field_mapping = [
            'grade' => 'grade_level',
            'section' => 'section',
            'year' => 'year',
            'legislative_district' => 'legislative_district',
            'school_district' => 'school_district'
        ];
        foreach ($field_mapping as $section_field => $assessment_field) {
            if (
                !array_key_exists($section_field, $section)
                || !in_array($assessment_field, $assessment_fields, TRUE)
            ) {
                return NULL;
            }
        }

        foreach ($field_mapping as $section_field => $assessment_field) {
            $this->db->where($assessment_field, $section[$section_field]);
        }

        $matching_query = $this->db->get('nutritional_assessments');
        if ($matching_query === false) {
            return NULL;
        }

        return $matching_query->num_rows() > 0;
    }

    public function add_user()
    {
        $this->load->library('form_validation');

        // Prepare available roles for the form
        $availableRoles = ['super_admin', 'admin', 'district', 'division', 'doctor', 'user'];

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Full Name', 'required|trim');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
            $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');
            $this->form_validation->set_rules('role', 'Role', 'required');

            if ($this->form_validation->run() === TRUE) {
                $userData = [
                    'name' => $this->input->post('name'),
                    'email' => $this->input->post('email'),
                    'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                    'legislative_district' => $this->input->post('legislative_district'),
                    'school_district' => $this->input->post('school_district'),
                    'school_id' => $this->input->post('school_id'),
                    'role' => $this->input->post('role'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($this->user_model->create_user($userData)) {
                    $this->session->set_flashdata('success', 'User created successfully.');
                    redirect('superadmin');
                    return;
                } else {
                    $this->session->set_flashdata('error', 'Failed to create user.');
                }
            }
        }

        // Show form (on GET or validation fail)
        $data = [
            'title' => 'Add User',
            'availableRoles' => $availableRoles
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('add_user', $data);
        $this->load->view('templates/footer');
    }
    
    public function update_user_role($user_id) {
        if ($this->input->method() != 'post') {
            show_404();
        }
        
        $new_role = $this->input->post('role');
        
        if ($this->user_model->update_user_role($user_id, $new_role)) {
            $this->session->set_flashdata('success', 'User role updated successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to update user role.');
        }
        
        redirect('dashboard');
    }
    
    public function update_all_roles() {
        if ($this->input->method() != 'post') {
            show_404();
        }

        // If client provided a users payload (JSON) use it, otherwise fall back to server-driven update
        $users_json = $this->input->post('users');

        if ($users_json) {
            $users_data = json_decode($users_json, true);
            if ($users_data === null) {
                $this->session->set_flashdata('error', 'Invalid users payload.');
                redirect('dashboard');
                return;
            }

            if ($this->user_model->update_multiple_user_roles($users_data)) {
                $this->session->set_flashdata('success', 'All user roles updated successfully (payload)!');
            } else {
                $this->session->set_flashdata('error', 'Failed to update user roles (payload).');
            }
        } else {
            // Server-driven update: call model method that performs default logic
            if (method_exists($this->user_model, 'update_all_roles')) {
                if ($this->user_model->update_all_roles()) {
                    $this->session->set_flashdata('success', 'All user roles updated successfully (server-driven)!');
                } else {
                    $this->session->set_flashdata('error', 'Failed to update user roles (server-driven).');
                }
            } else {
                $this->session->set_flashdata('error', 'No users payload provided and server-driven update not implemented in user_model.');
            }
        }

        redirect('dashboard');
    }

    public function delete_user($user_id)
    {
        if ($this->input->method() != 'post') {
            show_404();
        }

        if ($this->user_model->delete_user($user_id)) {
            $this->session->set_flashdata('success', 'User deleted successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete user.');
        }

        redirect('superadmin');
    }

    public function edit_user($user_id)
    {
        $this->load->library('form_validation');

        $user = $this->user_model->get_user_by_id($user_id);
        if (!$user) {
            show_404();
            return;
        }

        $availableRoles = ['super_admin', 'admin', 'district', 'division', 'doctor', 'user'];

        if ($this->input->method() === 'post') {
            // Existing rules
            $this->form_validation->set_rules('name', 'Full Name', 'required|trim');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('role', 'Role', 'required');
            $this->form_validation->set_rules('school_id', 'School ID', 'trim');
            $this->form_validation->set_rules('legislative_district', 'Legislative District', 'trim');
            $this->form_validation->set_rules('school_district', 'School District', 'trim');
            
            // NEW: Add school_level rule (optional, change to 'required' if needed)
            $this->form_validation->set_rules('school_level', 'School Level', 'trim');

            // Password fields optional...
            $password = $this->input->post('password');
            $confirm_password = $this->input->post('confirm_password');
            if (!empty($password)) {
                $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
                $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');
            }

            if ($this->form_validation->run() === TRUE) {
                $updateData = [
                    'name' => $this->input->post('name'),
                    'email' => $this->input->post('email'),
                    'role' => $this->input->post('role'),
                    'school_id' => $this->input->post('school_id'),
                    'legislative_district' => $this->input->post('legislative_district'),
                    'school_district' => $this->input->post('school_district'),
                    'school_level' => $this->input->post('school_level')   // NEW
                ];

                if (!empty($password)) {
                    $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                if ($this->user_model->update_user($user_id, $updateData)) {
                    $this->session->set_flashdata('success', 'User updated successfully.');
                    redirect('superadmin');
                    return;
                } else {
                    $this->session->set_flashdata('error', 'Failed to update user.');
                }
            }
        }

        $data = [
            'title' => 'Edit User',
            'user' => $user,
            'availableRoles' => $availableRoles
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('edit_user', $data);
        $this->load->view('templates/footer');
    }
}