<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthController extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
    }

    /**
     * Show login form and handle login POST.
     */
    public function login()
    {
        $this->load->library('form_validation');

        // If already logged in, redirect to the appropriate dashboard by role
        if ($this->session->userdata('logged_in')) {
            $this->redirect_based_on_role_and_school_info();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run() === TRUE) {
                $email = $this->input->post('email');
                $password = $this->input->post('password');

                $user = $this->User_model->get_by_email($email);

                if (!$user) {
                    $this->session->set_flashdata('error', 'Invalid email or password.');
                    redirect('login');
                    return;
                }

                if (!password_verify($password, $user->password)) {
                    $this->session->set_flashdata('error', 'Invalid email or password.');
                    redirect('login');
                    return;
                }

                // Login successful — set session with all necessary data
                $this->session->set_userdata([
                    'logged_in' => true,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'district' => $user->district,
                    'school_id' => $user->school_id,
                    'school_name' => $user->name,
                    'school_info_completed' => $user->school_info_completed,
                    'legislative_district' => $user->legislative_district,
                    'school_district' => $user->school_district
                ]);

                // Redirect based on role and school info completion
                $this->redirect_based_on_role_and_school_info($user);
                return;
            }
        }

        // Show login form
        $data = [
            'title' => 'Login',
            'no_sidebar' => true // hide global sidebar on login page
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('auth/login', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Redirect user based on role and school info completion status
     */
private function redirect_based_on_role_and_school_info($user = null) {
    if (!$user) {
        $user_id = $this->session->userdata('user_id');
        $user = $this->User_model->get_user_by_id($user_id);
    }

    if (!$user) {
        redirect('login');
        return;
    }

    $role = $user->role;
    
    // For ALL roles, check if school info is completed
    $needs_school_info = empty($user->school_id) || !$user->school_info_completed;
    
    if ($needs_school_info) {
        // Redirect ALL roles to school info form if not completed
        redirect('school-info/form');
    } else {
        // If school info is complete, redirect to appropriate dashboard
        switch ($role) {
            case 'division':
                redirect('division_dashboard');
                break;
            case 'district':
                redirect('district_dashboard');
                break;
            case 'super_admin':
            case 'admin':
                redirect('superadmin');
                break;
            case 'user':
                redirect('user');
                break;
            default:
                redirect('superadmin');
                break;
        }
    }
}

    public function logout()
    {
        // Clear all session data
        $session_data = [
            'logged_in', 'user_id', 'username', 'email', 'role', 
            'district', 'school_id', 'school_info_completed',
            'legislative_district', 'school_district'
        ];
        
        $this->session->unset_userdata($session_data);
        $this->session->sess_destroy();
        
        redirect('login');
    }
    
    /**
     * Register function (if needed)
     */
    public function register()
    {
        // Your registration logic here
        redirect('login');
    }
}