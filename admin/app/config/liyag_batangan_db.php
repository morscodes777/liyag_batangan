<?php
class Database {
    private $host = "localhost";
    private $db_name = "u290721747_liyag_batangan";
    private $username = "u290721747_LIyag_Batangan";
    private $password = "liyag_Batangan_001";
    public $conn;

    public function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        return $this->conn;
    }
}
?>
    