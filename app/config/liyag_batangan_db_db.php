<?php
// app/config/liyag_batangan_db.php (Now using PDO)

class Database {
    private $host = "localhost";
    private $db_name = "liyag_batangan";
    private $username = "root";
    private $password = "";
    public $conn; // Will now hold the PDO object

    /**
     * Get the database connection (PDO).
     * @return PDO The PDO connection object.
     */
    public function connect() {
        $this->conn = null;
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      
            PDO::ATTR_EMULATE_PREPARES   => false,                 
        ];

        try {
            // Establish the PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (\PDOException $e) {
            // Log the error and halt execution
            error_log("Database Connection Error (PDO): " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }
}