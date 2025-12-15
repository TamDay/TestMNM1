<?php
/**
 * Database Configuration and Connection
 * Sử dụng PDO để kết nối MySQL với singleton pattern
 */

class Database {
    private static $instance = null;
    private $conn;
    
    // Thông tin kết nối database
    private $host = 'sql112.infinityfree.com';
    private $db_name = 'if0_40684890_meetingroom';
    private $username = 'if0_40684890';
    private $password = 'WwqgguTFEz';
    private $charset = 'utf8mb4';
    
    // Private constructor để implement Singleton
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }
    
    // Lấy instance duy nhất của Database
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Lấy connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserializing
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}


// Helper function để lấy database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

