<?php
require_once __DIR__ . '/../config/liyag_batangan_db.php';

class AdminModel {
    private $conn;

    public function __construct() {
        // Assuming the Database class is defined and connects using mysqli
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Retrieves all regular users from the database, including location data.
     * @return array|false Array of user records or false on failure.
     */
    public function getAllUsers() {
        $sql = "SELECT user_id, name, email, phone_number, address, created_at, latitude, longitude 
                FROM users 
                WHERE user_type != 'Admin' 
                ORDER BY created_at DESC";
        
        $result = $this->conn->query($sql);

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return false;
    }

    /**
     * Finds a specific user by their ID.
     * @param int $userId The ID of the user to find.
     * @return array|false User record or false if not found.
     */
    public function getUserById($userId) {
        $sql = "SELECT user_id, name, email, phone_number, address, user_type, latitude, longitude 
                FROM users 
                WHERE user_id = ? AND user_type != 'Admin'";
        
        // Prepared statement is crucial here
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId); // 'i' for integer
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        $stmt->close();
        return false;
    }

    /**
     * Calculates the total platform sales from the total_commission 
     * of all delivered orders.
     * @return float Total sales amount or 0.00 on failure/no sales.
     */
    public function getTotalPlatformSales() {
        $sql = "SELECT SUM(total_commission) AS total_platform_sales 
                FROM orders 
                WHERE order_status = 'Delivered'";
        
        $result = $this->conn->query($sql);

        if ($result && $result->num_rows === 1) {
            $data = $result->fetch_assoc();
            // Coalesce with 0.00 in case the SUM returns NULL (no matching rows)
            return (float)($data['total_platform_sales'] ?? 0.00);
        }
        
        return 0.00;
    }

    /**
     * Fetches the detailed history of commissions for the modal.
     * @return array|false
     */
    public function getCommissionHistory() {
        $sql = "SELECT order_id, order_date, order_total, total_commission 
                FROM orders 
                WHERE order_status = 'Delivered' 
                ORDER BY order_date DESC";
        
        $result = $this->conn->query($sql);

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return false;
    }

    /**
     * Calculates the total vendor payout (sales) for each vendor from delivered orders.
     * This is used for the Sales and Vendor Performance report.
     * @return array|false Array of vendors with their total 'sales' (payout) or false on failure.
     */
    public function getVendorPayoutPerformance() {
        $sql = "
            SELECT 
                va.business_name AS vendor, 
                SUM(o.vendor_payout) AS sales
            FROM 
                orders o
            JOIN 
                order_items oi ON o.order_id = oi.order_id
            JOIN 
                products p ON oi.product_id = p.product_id
            JOIN 
                vendor_account va ON p.vendor_id = va.vendor_id
            WHERE 
                o.order_status = 'Delivered'
            GROUP BY 
                va.vendor_id, va.business_name
            ORDER BY 
                sales DESC
        ";
        
        $result = $this->conn->query($sql);

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return false;
    }

    /**
     * Calculates the total quantity of products sold per vendor from delivered orders.
     * @return array|false Array of vendors with their total 'products_sold' or false on failure.
     */
    public function getVendorProductsSoldPerformance() {
        $sql = "
            SELECT 
                va.business_name AS vendor, 
                SUM(oi.quantity) AS products_sold
            FROM 
                orders o
            JOIN 
                order_items oi ON o.order_id = oi.order_id
            JOIN 
                products p ON oi.product_id = p.product_id
            JOIN 
                vendor_account va ON p.vendor_id = va.vendor_id
            WHERE 
                o.order_status = 'Delivered'
            GROUP BY 
                va.vendor_id, va.business_name
            ORDER BY 
                products_sold DESC
        ";
        
        $result = $this->conn->query($sql);

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return false;
    }

    public function __destruct() {
        if ($this->conn) {
            // Check if connection is alive before trying to close
            if (is_object($this->conn) && method_exists($this->conn, 'close')) {
                 $this->conn->close();
            }
        }
    }
}