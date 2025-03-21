<?php

namespace Models;

use Interfaces\DatabaseInterface;

class ToDo
{
    private $id;
    private $userId;
    private $title;
    private $description;
    private $completed;
    private $createdAt;
    private $db;

    public function __construct(DatabaseInterface $db, $id = null) {
        $this->db = $db;

        if ($id !== null) {
            $this->loadById($id);
        }
    }

    public function loadById($id) {
        $stmt = $this->db->prepare("SELECT * FROM todos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $todoData = $stmt->fetch();

        if ($todoData) {
            $this->id = $todoData['id'];
            $this->userId = $todoData['user_id'];
            $this->title = $todoData['title'];
            $this->description = $todoData['description'];
            $this->completed = $todoData['completed'];
            $this->createdAt = $todoData['created_at'];

            return true;
        }

        return false;
    }

    public function create($userId, $title, $description = '') {
        $stmt = $this->db->prepare("INSERT INTO todos (user_id, title, description) VALUES (?, ?, ?)");
        $success = $stmt->execute([$userId, $title, $description]);

        if ($success) {
            $this->id = $this->db->lastInsertId();
            $this->userId = $userId;
            $this->title = $title;
            $this->description = $description;
            $this->completed = 0;
            $this->createdAt = date('Y-m-d H:i:s');

            return true;
        }

        return false;
    }

    public function update($title, $description, $completed) {
        if (!$this->id) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE todos SET title = ?, description = ?, completed = ? WHERE id = ?");
        $success = $stmt->execute([$title, $description, $completed, $this->id]);

        if ($success) {
            $this->title = $title;
            $this->description = $description;
            $this->completed = $completed;

            return true;
        }

        return false;
    }

    public function delete() {
        if (!$this->id) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM todos WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    public function toggleCompletion() {
        $newStatus = $this->completed ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE todos SET completed = ? WHERE id = ?");
        $success = $stmt->execute([$newStatus, $this->id]);

        if ($success) {
            $this->completed = $newStatus;
            return true;
        }

        return false;
    }

    public function belongsToUser($userId) {
        return $this->userId == $userId;
    }

    public static function getAllForUser(DatabaseInterface $db, $userId, $filter = 'all') {
        $sql = "SELECT * FROM todos WHERE user_id = ?";
        $params = [$userId];

        if ($filter === 'active') {
            $sql .= " AND completed = 0";
        } elseif ($filter === 'completed') {
            $sql .= " AND completed = 1";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function isCompleted() {
        return $this->completed;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'description' => $this->description,
            'completed' => $this->completed,
            'created_at' => $this->createdAt
        ];
    }
}