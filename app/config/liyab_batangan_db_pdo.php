<?php
class Database {
    private $host = "localhost";
    private $db_name = "u290721747_liyag_batangan";
    private $username = "u290721747_LIyag_Batangan";
    private $password = "liyag_Batangan_001";
    public $conn;

    /**
     * Get the database connection (PDO).
     * @return PDO The PDO connection object.
     */
    public function connect() {
        $this->conn = null;

        // Data Source Name (DSN) for MySQL
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
        
        // PDO connection options
        $options = [
            // Throw exceptions on errors (recommended for robust error handling)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            // Default fetch style to associative array
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
            // Ensure real prepared statements are used
            PDO::ATTR_EMULATE_PREPARES   => false,                  
        ];

        try {
            // Establish the PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (\PDOException $e) {
            // Halt execution and display a simple error on connection failure
            error_log("Database Connection Error (PDO): " . $e->getMessage());
            die("Database connection failed.");
        }
    }
}
$database = new Database();
$pdo = $database->connect();