<?php

/**
 * OLFU Queue Management System - Database Connection
 */

class Database
{
    private $conn;

    public function __construct()
    {
        $this->conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );

        if ($this->conn->connect_error) {
            die('Database Connection Failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function query($sql)
    {
        return $this->conn->query($sql);
    }

    public function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    public function escape($data)
    {
        return $this->conn->real_escape_string($data);
    }

    public function close()
    {
        $this->conn->close();
    }
}

// Create global database instance
$db = new Database();
