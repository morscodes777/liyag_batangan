<?php
require_once __DIR__ . '/../config/liyag_batangan_db.php';

class VendorModel {
    private $conn;
    private $table = 'vendor_account';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        
        if ($this->conn->connect_error) {
            error_log("Database Connection Error: " . $this->conn->connect_error);
        }
    }

    public function getVendorsByStatus(string $status): array {
        $allowed_statuses = ['Pending', 'Approved', 'Rejected'];
        if (!in_array($status, $allowed_statuses)) {
            return [];
        }

        $query = "SELECT 
                      va.vendor_id,
                      va.business_name,
                      va.business_address,
                      va.status,
                      va.registration_date,
                      va.verification_document AS docs_link
                  FROM 
                      " . $this->table . " va
                  WHERE 
                      va.status = ?
                  ORDER BY 
                      va.registration_date DESC";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("VendorModel: Failed to prepare getVendorsByStatus statement: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("s", $status);
        $vendors = [];

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            
            foreach ($rows as $row) {
                $row['name'] = $row['business_name'];
                if ($status === 'Pending') {
                    $row['docs'] = empty($row['docs_link']) ? 'Accomplish Docs' : 'View Docs';
                }
                $vendors[] = $row;
            }
        } else {
            error_log("VendorModel: Failed to execute getVendorsByStatus statement: " . $stmt->error);
        }

        $stmt->close();
        return $vendors;
    }

    public function getPendingVendors(): array {
        return $this->getVendorsByStatus('Pending');
    }

    public function getVendorById(int $vendor_id): ?array {
        $query = "SELECT vendor_id, user_id, business_name, business_address, status 
                  FROM " . $this->table . " WHERE vendor_id = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("VendorModel: Failed to prepare getVendorById statement: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $vendor_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $vendor = $result->fetch_assoc();
            $stmt->close();
            return $vendor;
        } else {
            error_log("VendorModel: Failed to execute getVendorById statement: " . $stmt->error);
            $stmt->close();
            return null;
        }
    }

public function getVendorDetails(int $vendor_id): ?array {
    // Assuming $this->conn is your active MySQLi database connection
    $vendorTable = 'vendor_account';
    $query = "SELECT 
                v.vendor_id,
                v.user_id,
                v.business_name,
                v.business_address,
                v.business_description,
                v.logo_url,
                v.registration_date,
                v.status,
                v.verification_document,
                
                -- Owner details from the users table
                u.name AS owner_name,       
                u.email AS email,
                u.phone_number AS phone     
              FROM {$vendorTable} v
              INNER JOIN users u ON v.user_id = u.user_id
              WHERE v.vendor_id = ? LIMIT 1";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) {
        error_log("VendorModel: Failed to prepare getVendorDetails statement: " . $this->conn->error);
        return null;
    }

    $stmt->bind_param("i", $vendor_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $vendor = $result->fetch_assoc();
        $stmt->close();

        if ($vendor) {
            // NOTE: Base URL must be correctly configured to the root of your public folder
            $mainSiteBaseUrl = "http://liyagbatangan.shop/";

            // Build absolute URLs for images
            $vendor['logo_url'] = !empty($vendor['logo_url']) 
                ? $mainSiteBaseUrl . ltrim($vendor['logo_url'], '/') 
                : null;

            $docUrl = !empty($vendor['verification_document']) 
                ? $mainSiteBaseUrl . ltrim($vendor['verification_document'], '/') 
                : null;
                
            // Map the single document column to the expected JS field
            $vendor['business_permit_url'] = $docUrl;
            
            // Map aliased owner name to 'name' for JS consistency (id="ownerName")
            $vendor['name'] = $vendor['owner_name'] ?? 'N/A'; 
            
            // Ensure required owner/business fields exist and have defaults
            $vendor['business_name'] = $vendor['business_name'] ?? 'N/A';
            $vendor['email'] = $vendor['email'] ?? 'N/A';
            $vendor['phone'] = $vendor['phone'] ?? 'N/A';
            $vendor['registration_date'] = $vendor['registration_date'] ?? 'N/A';
            
            unset($vendor['owner_name']);
        }

        return $vendor; // Returns vendor array or null if fetch_assoc fails/no rows
    } else {
        error_log("VendorModel: Failed to execute getVendorDetails: " . $stmt->error);
        $stmt->close();
        return null;
    }
}


    public function updateVendorStatus(int $vendor_id, string $status): bool {
        $allowed = ['Approved', 'Rejected'];
        if (!in_array($status, $allowed)) return false;

        $query = "UPDATE " . $this->table . " SET status = ? WHERE vendor_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("VendorModel: Failed to prepare updateVendorStatus: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("si", $status, $vendor_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getVendorUserId(int $vendor_id): ?int {
        $query = "SELECT user_id FROM " . $this->table . " WHERE vendor_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return null;

        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        return $user_id ?? null;
    }

    /* ✅ NEW METHODS FOR APPROVE/REJECT OPERATIONS */
    public function approveVendor(int $vendor_id): bool {
        return $this->updateVendorStatus($vendor_id, 'Approved');
    }

    public function rejectVendor(int $vendor_id, string $reason): bool {
        // Update status first
        $updated = $this->updateVendorStatus($vendor_id, 'Rejected');

        // Optionally, save rejection reason in a new column (if exists)
        if ($updated) {
            $query = "UPDATE " . $this->table . " SET rejection_reason = ? WHERE vendor_id = ?";
            $stmt = $this->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("si", $reason, $vendor_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        return $updated;
    }

    public function getTotalVendorSales(int $vendor_id): float {
        $query = "SELECT 
                      SUM(o.vendor_payout) AS total_sales
                  FROM 
                      orders o
                  INNER JOIN 
                      order_items oi ON o.order_id = oi.order_id
                  INNER JOIN 
                      products p ON oi.product_id = p.product_id
                  WHERE 
                      p.vendor_id = ? 
                      AND o.order_status = 'Delivered'";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("VendorModel: Failed to prepare getTotalVendorSales statement: " . $this->conn->error);
            return 0.00;
        }

        $stmt->bind_param("i", $vendor_id);
        
        $total_sales = 0.00;

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total_sales = (float) ($row['total_sales'] ?? 0.00);
        } else {
            error_log("VendorModel: Failed to execute getTotalVendorSales statement: " . $stmt->error);
        }

        $stmt->close();
        return $total_sales;
    }
}
