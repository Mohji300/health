<?php
// application/controllers/SuperAdminController.php
class SuperAdminController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
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
        $userCounts = $this->User_model->get_user_counts_by_role();
        
        // Get all users for management
        $users = $this->User_model->get_all_users();
        
        // Define available roles
        $availableRoles = ['super_admin', 'admin', 'district', 'division', 'user'];
        
        $data = [
            'title' => 'Super Admin Dashboard',
            'userCounts' => $userCounts,
            'users' => $users,
            'availableRoles' => $availableRoles,
            'currentUser' => $this->session->userdata('email')
        ];
        
        $this->load->view('templates/header', $data);
        // Load the new standard PHP view
        $this->load->view('SuperAdminDashboard', $data);
        $this->load->view('templates/footer');
    }

    public function add_user()
    {
        $this->load->library('form_validation');

        // Prepare available roles for the form
        $availableRoles = ['super_admin', 'admin', 'district', 'division', 'user'];

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

                if ($this->User_model->create_user($userData)) {
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
        $this->load->view('Add_User', $data);
        $this->load->view('templates/footer');
    }
    
    public function update_user_role($user_id) {
        if ($this->input->method() != 'post') {
            show_404();
        }
        
        $new_role = $this->input->post('role');
        
        if ($this->User_model->update_user_role($user_id, $new_role)) {
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

            if ($this->User_model->update_multiple_user_roles($users_data)) {
                $this->session->set_flashdata('success', 'All user roles updated successfully (payload)!');
            } else {
                $this->session->set_flashdata('error', 'Failed to update user roles (payload).');
            }
        } else {
            // Server-driven update: call model method that performs default logic
            if (method_exists($this->User_model, 'update_all_roles')) {
                if ($this->User_model->update_all_roles()) {
                    $this->session->set_flashdata('success', 'All user roles updated successfully (server-driven)!');
                } else {
                    $this->session->set_flashdata('error', 'Failed to update user roles (server-driven).');
                }
            } else {
                $this->session->set_flashdata('error', 'No users payload provided and server-driven update not implemented in User_model.');
            }
        }

        redirect('dashboard');
    }

    public function delete_user($user_id)
    {
        if ($this->input->method() != 'post') {
            show_404();
        }

        if ($this->User_model->delete_user($user_id)) {
            $this->session->set_flashdata('success', 'User deleted successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete user.');
        }

        redirect('superadmin');
    }

    public function edit_user($user_id)
    {
        $this->load->library('form_validation');

        // Get the user to edit
        $user = $this->User_model->get_user_by_id($user_id);
        if (!$user) {
            show_404();
            return;
        }

        $availableRoles = ['super_admin', 'admin', 'district', 'division', 'user'];

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Full Name', 'required|trim');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('role', 'Role', 'required');
            $this->form_validation->set_rules('school_id', 'School ID', 'trim');
            $this->form_validation->set_rules('legislative_district', 'Legislative District', 'trim');
            $this->form_validation->set_rules('school_district', 'School District', 'trim');
            
            // Password fields are optional - only validate if one is provided
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
                    'school_district' => $this->input->post('school_district')
                ];

                // Only update password if provided
                if (!empty($password)) {
                    $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                if ($this->User_model->update_user($user_id, $updateData)) {
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
        $this->load->view('Edit_User', $data);
        $this->load->view('templates/footer');
    }
}