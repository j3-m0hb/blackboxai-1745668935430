<?php
/**
 * Database Class
 * 
 * Provides an object-oriented interface for database operations
 */
class Database {
    private static $instance = null;
    private $pdo;
    private $inTransaction = false;
    private $queryCount = 0;
    private $queryLog = [];
    
    /**
     * Constructor
     */
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->pdo->beginTransaction();
            $this->inTransaction = true;
        }
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        if ($this->inTransaction) {
            $this->pdo->commit();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->pdo->rollBack();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Execute query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $start = microtime(true);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->logQuery($sql, $params, microtime(true) - $start);
            
            return $stmt;
        } catch (PDOException $e) {
            $this->logQuery($sql, $params, 0, $e->getMessage());
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update records and return affected rows
     */
    public function update($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Delete records and return affected rows
     */
    public function delete($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Execute query and return affected rows
     */
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Quote string
     */
    public function quote($string) {
        return $this->pdo->quote($string);
    }
    
    /**
     * Get query count
     */
    public function getQueryCount() {
        return $this->queryCount;
    }
    
    /**
     * Get query log
     */
    public function getQueryLog() {
        return $this->queryLog;
    }
    
    /**
     * Clear query log
     */
    public function clearQueryLog() {
        $this->queryLog = [];
        $this->queryCount = 0;
    }
    
    /**
     * Log query
     */
    private function logQuery($sql, $params, $duration, $error = null) {
        $this->queryCount++;
        
        if (isDevelopmentMode()) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'duration' => $duration,
                'error' => $error,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
            ];
        }
    }
    
    /**
     * Table helper methods
     */
    
    /**
     * Insert record into table
     */
    public function insertInto($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            $placeholders
        );
        
        return $this->insert($sql, $values);
    }
    
    /**
     * Update table records
     */
    public function updateTable($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $values = array_values($data);
        $set = implode('=?, ', $fields) . '=?';
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            $set,
            $where
        );
        
        return $this->update($sql, array_merge($values, $whereParams));
    }
    
    /**
     * Delete from table
     */
    public function deleteFrom($table, $where, $params = []) {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        return $this->delete($sql, $params);
    }
    
    /**
     * Soft delete from table
     */
    public function softDelete($table, $where, $params = []) {
        return $this->updateTable(
            $table,
            ['deleted_at' => date('Y-m-d H:i:s')],
            $where,
            $params
        );
    }
    
    /**
     * Select from table
     */
    public function select($table, $fields = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        $sql = sprintf("SELECT %s FROM %s", $fields, $table);
        
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY " . $orderBy;
        }
        
        if ($limit) {
            $sql .= " LIMIT " . $limit;
        }
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Count records in table
     */
    public function count($table, $where = '', $params = []) {
        $sql = sprintf("SELECT COUNT(*) as count FROM %s", $table);
        
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        
        $result = $this->fetchOne($sql, $params);
        return (int)$result['count'];
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Get table columns
     */
    public function getColumns($table) {
        $sql = "SHOW COLUMNS FROM " . $table;
        return $this->fetchAll($sql);
    }
    
    /**
     * Get table primary key
     */
    public function getPrimaryKey($table) {
        $columns = $this->getColumns($table);
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return null;
    }
}
