<?php

namespace Models;

use Config\Database;
use Interfaces\DatabaseInterface;

class User
{
    private $id;
    private $username;
    private $email;
    private $password;
    private $createdAt;
    private $roles = [];
    private $permissions = [];
    private $db;

    public function __construct(DatabaseInterface $db, $id = null) {
        $this->db = $db;

        if ($id !== null) {
            $this->loadById($id);
        }
    }

    public function loadById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $userData = $stmt->fetch();

        if ($userData) {
            $this->id = $userData['id'];
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->password = $userData['password'];
            $this->createdAt = $userData['created_at'];

            $this->loadRoles();
            $this->loadPermissions();

            return true;
        }

        return false;
    }

    public function loadByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userData = $stmt->fetch();

        if ($userData) {
            $this->id = $userData['id'];
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->password = $userData['password'];
            $this->createdAt = $userData['created_at'];

            $this->loadRoles();
            $this->loadPermissions();

            return true;
        }

        return false;
    }

    private function loadRoles() {
        $stmt = $this->db->prepare("
            SELECT r.name 
            FROM roles r
            JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$this->id]);

        $this->roles = [];
        while ($role = $stmt->fetch()) {
            $this->roles[] = $role['name'];
        }
    }

    private function loadPermissions() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.name 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$this->id]);

        $this->permissions = [];
        while ($permission = $stmt->fetch()) {
            $this->permissions[] = $permission['name'];
        }
    }

    public function create($username, $password, $email) {
        // Check if username or email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        $exists = $stmt->fetch();

        if ($exists) {
            return false;
        }

        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $success = $stmt->execute([$username, $hashedPassword, $email]);

        if ($success) {
            $this->id = $this->db->lastInsertId();
            $this->username = $username;
            $this->email = $email;
            $this->password = $hashedPassword;

            // Assign default 'user' role
            $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = 'user' LIMIT 1");
            $stmt->execute();
            $userRole = $stmt->fetch();
            $userRoleId = $userRole['id'];

            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$this->id, $userRoleId]);

            $this->loadRoles();
            $this->loadPermissions();

            return true;
        }

        return false;
    }

    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }

    public function hasRole($role) {
        return in_array($role, $this->roles);
    }

    public function hasPermission($permission) {
        return in_array($permission, $this->permissions) || in_array('admin', $this->roles);
    }

    public function addRole($roleName) {
        $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
        $stmt->execute([$roleName]);
        $role = $stmt->fetch();

        if (!$role) {
            return false;
        }

        $roleId = $role['id'];

        // Check if user already has this role
        $stmt = $this->db->prepare("SELECT * FROM user_roles WHERE user_id = ? AND role_id = ? LIMIT 1");
        $stmt->execute([$this->id, $roleId]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$this->id, $roleId]);

            $this->loadRoles();
            $this->loadPermissions();

            return true;
        }

        return false;
    }

    public function removeRole($roleName) {
        $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
        $stmt->execute([$roleName]);
        $role = $stmt->fetch();

        if (!$role) {
            return false;
        }

        $roleId = $role['id'];

        $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
        $stmt->execute([$this->id, $roleId]);

        $this->loadRoles();
        $this->loadPermissions();

        return true;
    }

    public function delete() {
        if (!$this->id) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public static function getAllUsers(DatabaseInterface $db) {
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email, u.created_at, 
                   GROUP_CONCAT(r.name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getRoles() {
        return $this->roles;
    }

    public function getPermissions() {
        return $this->permissions;
    }
}