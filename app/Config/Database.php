<?php

namespace Config;

use Interfaces\DatabaseInterface;

class Database implements DatabaseInterface {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $host = getenv('MYSQL_HOST') ?: 'mysql';
        $user = getenv('MYSQL_USER') ?: 'todo_user';
        $pass = getenv('MYSQL_PASSWORD') ?: 'Welcome234';
        $db = getenv('MYSQL_DATABASE') ?: 'todo_db';

        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): ?Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function execute($sql) {
        return $this->connection->exec($sql);
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function close() {
        $this->connection = null;
    }

    public function quote($string) {
        return $this->connection->quote($string);
    }
}