<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class user_model extends CI_Model {

    /**
     * Table name used by this model.
     */
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct();
        // Ensure the database library is loaded
        $this->load->database();
    }

    public function get_user_counts_by_role()
    {
        $this->db->select('role, COUNT(*) as count');
        $this->db->from($this->table);
        $this->db->group_by('role');
        $query = $this->db->get();
        $result = $query->result();

        $counts = array(
            'total' => 0,
            'super_admins' => 0,
            'admins' => 0,
            'district' => 0,
            'division' => 0,
            'users' => 0
        );

        foreach ($result as $row) {
            $counts['total'] += (int) $row->count;

            switch ($row->role) {
                case 'super_admin':
                    $counts['super_admins'] = (int) $row->count;
                    break;
                case 'admin':
                    $counts['admins'] = (int) $row->count;
                    break;
                case 'district':
                    $counts['district'] = (int) $row->count;
                    break;
                case 'division':
                    $counts['division'] = (int) $row->count;
                    break;
                case 'user':
                    $counts['users'] = (int) $row->count;
                    break;
            }
        }

        return $counts;
    }

    public function get_all_users()
    {
        $this->db->select('id, name, email, role, created_at, school_id, legislative_district, school_district, school_level');
        $this->db->from($this->table);
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    public function update_user_role($user_id, $new_role)
    {
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, array(
            'role' => $new_role,
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }

    public function update_multiple_user_roles($users_data)
    {
        if (empty($users_data) || !is_array($users_data)) {
            return false;
        }

        $this->db->trans_start();

        foreach ($users_data as $user) {
            if (!isset($user['id']) || !isset($user['role'])) {
                continue;
            }

            $this->db->where('id', $user['id']);
            $this->db->update($this->table, array(
                'role' => $user['role'],
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Create a new user record.
     * Expects an associative array of column => value.
     * Also hashes the password if 'password' key exists.
     */
    public function create_user($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        // Hash the password if it exists and is not already hashed
        if (isset($data['password']) && !empty($data['password'])) {
            // Check if password is not already hashed (simple check)
            if (strlen($data['password']) < 60 || strpos($data['password'], '$2y$') !== 0) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }

        // Add created_at if not set
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Add updated_at if not set
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Get a single user row by email.
     * Returns the user object or null if not found.
     */
    public function get_by_email($email)
    {
        if (empty($email)) {
            return null;
        }

        $this->db->where('email', $email);
        $query = $this->db->get($this->table);
        $row = $query->row();
        return $row ? $row : null;
    }
    
    /**
     * Alias for get_by_email - for compatibility with Excel controller
     */
    public function get_user_by_email($email)
    {
        return $this->get_by_email($email);
    }

    /**
     * Get a single user by school ID
     */
    public function get_user_by_school_id($school_id)
    {
        if (empty($school_id)) {
            return null;
        }

        $this->db->where('school_id', $school_id);
        $query = $this->db->get($this->table);
        $row = $query->row();
        return $row ? $row : null;
    }

    /**
     * Delete a user by ID.
     */
    public function delete_user($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        $this->db->where('id', $user_id);
        return $this->db->delete($this->table);
    }

    /**
     * Get a single user by ID.
     */
    public function get_user_by_id($user_id)
    {
        if (empty($user_id)) {
            return null;
        }

        $this->db->where('id', $user_id);
        $query = $this->db->get($this->table);
        $row = $query->row();
        return $row ? $row : null;
    }

    /**
     * Get a single user by ID (alias for get_user_by_id for compatibility)
     */
    public function get_user($user_id) {
        return $this->get_user_by_id($user_id);
    }

    /**
     * Update user information.
     */
    public function update_user($user_id, $data)
    {
        if (empty($user_id) || empty($data)) {
            return false;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Optional server-driven bulk update.
     * Example implementation: ensure users with empty/null role are set to 'user'.
     */
    public function update_all_roles()
    {
        // Set empty or NULL roles to 'user' as a safe default.
        $this->db->where("(role IS NULL OR role = '')", null, false);
        $this->db->update($this->table, array(
            'role' => 'user',
            'updated_at' => date('Y-m-d H:i:s')
        ));

        // Return true if query ran (even if 0 rows affected).
        return ($this->db->affected_rows() >= 0);
    }

    /**
     * Get users by role
     */
    public function get_users_by_role($role)
    {
        if (empty($role)) {
            return array();
        }

        $this->db->where('role', $role);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Get users by legislative district
     */
    public function get_users_by_legislative_district($legislative_district)
    {
        if (empty($legislative_district)) {
            return array();
        }

        $this->db->where('legislative_district', $legislative_district);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Get users by school district
     */
    public function get_users_by_school_district($school_district)
    {
        if (empty($school_district)) {
            return array();
        }

        $this->db->where('school_district', $school_district);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Get users by school level
     */
    public function get_users_by_school_level($school_level)
    {
        if (empty($school_level)) {
            return array();
        }

        $this->db->where('school_level', $school_level);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Check if a user exists by email
     */
    public function user_exists_by_email($email)
    {
        $user = $this->get_by_email($email);
        return !is_null($user);
    }

    /**
     * Check if a user exists by school ID
     */
    public function user_exists_by_school_id($school_id)
    {
        $user = $this->get_user_by_school_id($school_id);
        return !is_null($user);
    }

    /**
     * Get total count of users
     */
    public function get_total_users_count()
    {
        return $this->db->count_all($this->table);
    }

    /**
     * Get users created within a date range
     */
    public function get_users_by_date_range($start_date, $end_date)
    {
        if (empty($start_date) || empty($end_date)) {
            return array();
        }

        $this->db->where('created_at >=', $start_date);
        $this->db->where('created_at <=', $end_date . ' 23:59:59');
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get($this->table);
        return $query->result();
    }

    /**
     * Verify user password
     */
    public function verify_password($plain_password, $hashed_password)
    {
        return password_verify($plain_password, $hashed_password);
    }

    /**
     * Update user password
     */
    public function update_password($user_id, $new_password)
    {
        if (empty($user_id) || empty($new_password)) {
            return false;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, array(
            'password' => $hashed_password,
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }
}