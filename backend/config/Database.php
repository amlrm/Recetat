<?php
/**
 * Database Connection Class
 * Handles all database operations
 */

class Database {
    private $conn;

    public function __construct() {
        try {
            $this->conn = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );

            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception('Database connection failed: ' . $this->conn->connect_error);
            }

            // Set charset to utf8mb4
            $this->conn->set_charset('utf8mb4');
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection error']));
        }
    }

    /**
     * Prepare and execute a query
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @param string $types Data types (s=string, i=int, d=double, b=blob)
     * @return mysqli_stmt
     */
    public function prepare($query, $params = [], $types = '') {
        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }

        if (!empty($params)) {
            // Auto-detect types if not provided
            if (empty($types)) {
                $types = $this->getTypes($params);
            }

            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        return $stmt;
    }

    /**
     * Auto-detect parameter types
     * @param array $params
     * @return string
     */
    private function getTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /**
     * Fetch single row
     * @param string $query
     * @param array $params
     * @return array|null
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Fetch multiple rows
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Insert a record
     * @param string $table
     * @param array $data
     * @return int Last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));

        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->prepare($query, array_values($data));

        return $this->conn->insert_id;
    }

    /**
     * Update a record
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int Number of affected rows
     */
    public function update($table, $data, $where) {
        $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
        $where_clause = implode(' AND ', array_map(fn($k) => "$k=?", array_keys($where)));

        $query = "UPDATE $table SET $set WHERE $where_clause";
        $params = array_merge(array_values($data), array_values($where));

        $stmt = $this->prepare($query, $params);
        return $this->conn->affected_rows;
    }

    /**
     * Delete a record
     * @param string $table
     * @param array $where
     * @return int Number of affected rows
     */
    public function delete($table, $where) {
        $where_clause = implode(' AND ', array_map(fn($k) => "$k=?", array_keys($where)));
        $query = "DELETE FROM $table WHERE $where_clause";

        $stmt = $this->prepare($query, array_values($where));
        return $this->conn->affected_rows;
    }

    /**
     * Close connection
     */
    public function close() {
        $this->conn->close();
    }
}
?>
