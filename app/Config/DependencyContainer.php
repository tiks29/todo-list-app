<?php

namespace Config;

use Interfaces\DatabaseInterface;
use Models\User;
use Models\Todo;
use Services\AuthService;
use Api\TodoApiController;
use Api\UserApiController;

class DependencyContainer
{
    private $services = [];

    public function __construct() {
        $this->services['database'] = function() {
            return Database::getInstance();
        };

        $this->services['userFactory'] = function($id = null) {
            $db = $this->get('database');
            return new User($db, $id);
        };

        $this->services['todoFactory'] = function($id = null) {
            $db = $this->get('database');
            return new Todo($db, $id);
        };

        $this->services['authService'] = function() {
            $db = $this->get('database');
            $userFactory = $this->services['userFactory'];
            return new AuthService($db, $userFactory);
        };

        $this->services['todoApiController'] = function() {
            $db = $this->get('database');
            $authService = $this->get('authService');
            $todoFactory = $this->services['todoFactory'];
            return new TodoApiController($db, $authService, $todoFactory);
        };

        $this->services['userApiController'] = function() {
            $db = $this->get('database');
            $authService = $this->get('authService');
            $userFactory = $this->services['userFactory'];
            return new UserApiController($db, $authService, $userFactory);
        };
    }

    public function get($id) {
        if (!isset($this->services[$id])) {
            throw new \Exception("Service $id not found");
        }

        if (is_callable($this->services[$id])) {
            $this->services[$id] = $this->services[$id]();
        }

        return $this->services[$id];
    }
}