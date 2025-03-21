<?php

namespace Api;

use Interfaces\DatabaseInterface;
use Services\AuthService;

class UserApiController
{
    private $db;
    private $authService;
    private $userFactory;

    public function __construct(
        DatabaseInterface $db,
        AuthService $authService,
        callable $userFactory
    ) {
        $this->db = $db;
        $this->authService = $authService;
        $this->userFactory = $userFactory;
    }

    public function handleRequest() {
        // Ensure user is authenticated and has admin role
        if (!$this->authService->isLoggedIn() || !$this->authService->hasRole('admin')) {
            $this->sendJsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->handleGet();
                break;

            case 'POST':
                $this->handlePost();
                break;

            case 'PUT':
                $this->handlePut();
                break;

            case 'DELETE':
                $this->handleDelete();
                break;

            default:
                $this->sendJsonResponse(['error' => 'Method not allowed'], 405);
                break;
        }
    }

    private function handleGet() {
        $userId = $_GET['id'] ?? null;

        if ($userId) {
            $user = call_user_func($this->userFactory, $userId);
            if ($user->getId()) {
                $this->sendJsonResponse([
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]);
            } else {
                $this->sendJsonResponse(['error' => 'User not found'], 404);
            }
        } else {
            $users = call_user_func([$this->userFactory, 'getAllUsers'], $this->db);
            $this->sendJsonResponse($users);
        }
    }

    private function handlePost() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
            $this->sendJsonResponse(['error' => 'Username, password and email are required'], 400);
            return;
        }

        $user = call_user_func($this->userFactory);
        if ($user->create($data['username'], $data['password'], $data['email'])) {

            // Assign role if specified
            if (isset($data['role']) && in_array($data['role'], ['admin', 'user'])) {
                $user->addRole($data['role']);
            }

            $this->sendJsonResponse([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]);
        } else {
            $this->sendJsonResponse(['error' => 'Failed to create user'], 500);
        }
    }

    private function handlePut() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            $this->sendJsonResponse(['error' => 'User ID is required'], 400);
            return;
        }

        $user = call_user_func($this->userFactory, $data['id']);

        if (!$user->getId()) {
            $this->sendJsonResponse(['error' => 'User not found'], 404);
            return;
        }

        // Handle role changes
        if (isset($data['role'])) {
            if ($data['role'] === 'admin') {
                $user->addRole('admin');
            } else {
                $user->removeRole('admin');
            }
        }

        $this->sendJsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }

    private function handleDelete() {
        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            $this->sendJsonResponse(['error' => 'User ID is required'], 400);
            return;
        }

        $user = call_user_func($this->userFactory, $userId);

        if (!$user->getId()) {
            $this->sendJsonResponse(['error' => 'User not found'], 404);
            return;
        }

        if ($user->delete()) {
            $this->sendJsonResponse(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            $this->sendJsonResponse(['error' => 'Failed to delete user'], 500);
        }
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}