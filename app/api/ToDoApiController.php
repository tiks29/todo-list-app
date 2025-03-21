<?php

namespace Api;

use Interfaces\DatabaseInterface;
use Services\AuthService;

class ToDoApiController
{
    private $db;
    private $authService;
    private $todoFactory;

    public function __construct(
        DatabaseInterface $db,
        AuthService $authService,
        callable $todoFactory
    ) {
        $this->db = $db;
        $this->authService = $authService;
        $this->todoFactory = $todoFactory;
    }

    public function handleRequest() {
        if (!$this->authService->isLoggedIn()) {
            $this->sendJsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->handleGet($userId);
                break;

            case 'POST':
                $this->handlePost($userId);
                break;

            case 'PUT':
                $this->handlePut($userId);
                break;

            case 'DELETE':
                $this->handleDelete($userId);
                break;

            default:
                $this->sendJsonResponse(['error' => 'Method not allowed'], 405);
                break;
        }
    }

    private function handleGet($userId) {
        $filter = $_GET['filter'] ?? 'all';
        $todos = call_user_func_array([$this->todoFactory, 'getAllForUser'], [$this->db, $userId, $filter]);
        $this->sendJsonResponse($todos);
    }

    private function handlePost($userId) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['title']) || empty($data['title'])) {
            $this->sendJsonResponse(['error' => 'Title is required'], 400);
            return;
        }

        $title = $data['title'];
        $description = $data['description'] ?? '';

        $todo = call_user_func($this->todoFactory);
        if ($todo->create($userId, $title, $description)) {
            $this->sendJsonResponse($todo->toArray());
        } else {
            $this->sendJsonResponse(['error' => 'Failed to create todo'], 500);
        }
    }

    private function handlePut($userId) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id']) || !isset($data['title'])) {
            $this->sendJsonResponse(['error' => 'ID and title are required'], 400);
            return;
        }

        $todoId = $data['id'];
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $completed = isset($data['completed']) ? (int)$data['completed'] : 0;

        $todo = call_user_func($this->todoFactory, $todoId);

        if (!$todo->getId() || !$todo->belongsToUser($userId)) {
            $this->sendJsonResponse(['error' => 'Todo not found or not authorized'], 404);
            return;
        }

        if ($todo->update($title, $description, $completed)) {
            $this->sendJsonResponse($todo->toArray());
        } else {
            $this->sendJsonResponse(['error' => 'Failed to update todo'], 500);
        }
    }

    private function handleDelete($userId) {
        $todoId = $_GET['id'] ?? null;

        if (!$todoId) {
            $this->sendJsonResponse(['error' => 'Todo ID is required'], 400);
            return;
        }

        $todo = call_user_func($this->todoFactory, $todoId);

        if (!$todo->getId() || !$todo->belongsToUser($userId)) {
            $this->sendJsonResponse(['error' => 'Todo not found or not authorized'], 404);
            return;
        }

        if ($todo->delete()) {
            $this->sendJsonResponse(['success' => true, 'message' => 'Todo deleted successfully']);
        } else {
            $this->sendJsonResponse(['error' => 'Failed to delete todo'], 500);
        }
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}