<?php

namespace Config;

use Interfaces\DatabaseInterface;

class Schema
{
    private $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function initialize() {
        // Create users table
        $this->db->execute("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create roles table
        $this->db->execute("CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT
        )");

        // Create user_roles table
        $this->db->execute("CREATE TABLE IF NOT EXISTS user_roles (
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        )");

        // Create permissions table
        $this->db->execute("CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT
        )");

        // Create role_permissions table
        $this->db->execute("CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        )");

        // Create todos table
        $this->db->execute("CREATE TABLE IF NOT EXISTS todos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $this->seedDefaultData();
    }

    private function seedDefaultData() {
        // Insert default roles if they don't exist
        $this->db->execute("INSERT IGNORE INTO roles (name, description) VALUES 
            ('admin', 'Administrator with full access'),
            ('user', 'Regular user with limited access')");

        // Insert default permissions if they don't exist
        $this->db->execute("INSERT IGNORE INTO permissions (name, description) VALUES 
            ('create_todo', 'Can create new todos'),
            ('read_todo', 'Can read todos'),
            ('update_todo', 'Can update todos'),
            ('delete_todo', 'Can delete todos'),
            ('manage_users', 'Can manage users')");

        // Assign permissions to roles
        $this->db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id FROM roles r, permissions p
            WHERE r.name = 'admin'");

        $this->db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id FROM roles r, permissions p
            WHERE r.name = 'user' AND p.name IN ('create_todo', 'read_todo', 'update_todo', 'delete_todo')");

        // Create default admin user if not exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
        $stmt->execute();
        $adminExists = $stmt->fetch();

        if (!$adminExists) {
            $hashedPassword = password_hash('admin234', PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute(['admin', $hashedPassword, 'admin@example.com']);

            $adminId = $this->db->lastInsertId();

            $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
            $stmt->execute();
            $adminRole = $stmt->fetch();
            $adminRoleId = $adminRole['id'];

            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$adminId, $adminRoleId]);
        }
    }
}