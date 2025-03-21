<?php

namespace Services;

use interfaces\DatabaseInterface;
use Models\User;

class AuthService
{
    private $db;
    private $userFactory;

    public function __construct(DatabaseInterface $db, callable $userFactory) {
        $this->db = $db;
        $this->userFactory = $userFactory;
    }

    public function login($username, $password) {
        $user = call_user_func($this->userFactory);

        if ($user->loadByUsername($username) && $user->verifyPassword($password)) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['roles'] = $user->getRoles();
            $_SESSION['permissions'] = $user->getPermissions();

            return true;
        }

        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function register($username, $password, $email) {
        $user = call_user_func($this->userFactory);
        return $user->create($username, $password, $email);
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $user = call_user_func($this->userFactory, $_SESSION['user_id']);
        return $user;
    }

    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return in_array($permission, $_SESSION['permissions']) || in_array('admin', $_SESSION['roles']);
    }

    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return in_array($role, $_SESSION['roles']);
    }
}