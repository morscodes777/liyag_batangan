<?php
// models/UserModel.php

// Assuming liyab_batangan_db_pdo.php (or similar) contains the Database class definition.
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php'; 

class UserModel {
    private $pdo;

    public function __construct() {
        $database = new Database(); 
        $this->pdo = $database->connect(); 
        
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    }

    public function getUserById($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id, name, email, phone_number, address, latitude, longitude, user_type, profile_picture FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user;

        } catch (PDOException $e) {
            error_log("PDO Error in getUserById: " . $e->getMessage());
            return false;
        }
    }

    public function register($data) {
        try {
            $check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check_stmt->execute([$data['email']]);
            if ($check_stmt->fetchColumn() > 0) return "Email already registered.";

            $password_hash = hash('sha256', $data['password']);

            $sql = "INSERT INTO users (name, email, password, phone_number, address, latitude, longitude) 
                     VALUES (:name, :email, :password_hash, :phone, :address, :latitude, :longitude)";

            $stmt = $this->pdo->prepare($sql);
            
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => $password_hash, 
                'phone' => $data['phone_number'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude']
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("PDO Error in register: " . $e->getMessage());
            return "Registration failed due to a database error.";
        }
    }

    public function login($email, $password) {
        try {
            $hashed_password = hash('sha256', $password);

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $hashed_password === $user['password']) {
                unset($user['password']); 
                return $user;
            }
        } catch (PDOException $e) {
            error_log("PDO Error in login: " . $e->getMessage());
        }

        return false;
    }

    public function isEmailRegistered($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Database Error in isEmailRegistered: " . $e->getMessage());
            return true; 
        }
    }

    public function getUserDetails($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id, name, email, phone_number, address, latitude, longitude, user_type, profile_picture FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user;

        } catch (PDOException $e) {
            error_log("PDO Error in getUserDetails: " . $e->getMessage());
            return false;
        }
    }

    public function isApprovedVendor($user_id) {
        try {
            $sql = "SELECT COUNT(*) FROM vendor_account WHERE user_id = :user_id AND status = 'Approved'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Database Error in isApprovedVendor: " . $e->getMessage());
            return false; 
        }
    }
    
    public function getVendorAccountDetails($user_id) {
        try {
            $sql = "SELECT vendor_id, business_name FROM vendor_account WHERE user_id = :user_id AND status = 'Approved'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getVendorAccountDetails: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches detailed store information from the vendor_account table.
     * This is required by views like track_order_vendor.php for template headers/sidebars.
     * * @param int $vendor_id The vendor_id from the vendor_account table (which may differ from user_id).
     * @return array|false The store details array, or false on failure.
     */
    public function getStoreDetailsByVendorId($vendor_id) {
        try {
            // Select all necessary columns for the store display
            $sql = "SELECT 
                        va.vendor_id, 
                        va.business_name, 
                        va.business_address, 
                        va.logo_url, 
                        va.average_rating, 
                        va.total_reviews 
                    FROM vendor_account va
                    WHERE va.vendor_id = :vendor_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['vendor_id' => $vendor_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getStoreDetailsByVendorId: " . $e->getMessage());
            return false;
        }
    }

    
}