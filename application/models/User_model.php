<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

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

        $counts = [
            'total' => 0,
            'super_admins' => 0,
            'admins' => 0,
            'district' => 0,
            'division' => 0,
            'users' => 0
        ];

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
        $this->db->select('id, name, email, role, created_at');
        $this->db->from($this->table);
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    public function update_user_role($user_id, $new_role)
    {
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, [
            'role' => $new_role,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
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
            $this->db->update($this->table, [
                'role' => $user['role'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Create a new user record.
     * Expects an associative array of column => value.
     */
    public function create_user($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Optional server-driven bulk update.
     * Example implementation: ensure users with empty/null role are set to 'user'.
     */
    public function update_all_roles()
    {
        // Set empty or NULL roles to 'user' as a safe default.
        $this->db->where("(role IS NULL OR role = '')", null, false);
        $this->db->update($this->table, [
            'role' => 'user',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Return true if query ran (even if 0 rows affected).
        return ($this->db->affected_rows() >= 0);
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
 * Get a single user by ID (alias for get_user_by_id for compatibility)
 */
public function get_user($user_id) {
    return $this->get_user_by_id($user_id);
}
}
