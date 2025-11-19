<?php
require_once __DIR__ . '/../models/vendorModels.php';
require_once __DIR__ . '/../config/liyag_batangan_db.php';

class HomeController {
    private $db_connection;
    private $vendor;
    private $vendorModel;
    private $productModel;

    public function __construct() {
        $database = new Database();
        $this->db_connection = $database->connect();
        $this->vendor = new Vendor($this->db_connection);
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $user = $_SESSION['user'];

        $approved_stores = $this->vendor->readApproved();

        $displayUserName = htmlspecialchars($user['name'] ?? 'Guest');
        $userProfilePicture = $user['profile_picture'] ?? '';

        require_once __DIR__ . '/../views/home.php';

        $this->db_connection->close();
    }
    public function viewStore() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $user = $_SESSION['user'];
        $displayUserName = htmlspecialchars($user['name'] ?? 'User');
        $userProfilePicture = $user['profile_picture'] ?? null;
        
        $vendorId = $_GET['vendor_id'] ?? null;

        if ($vendorId) {
            $store = $this->vendorModel->getStoreDetails($vendorId);
            $products = $this->productModel->getProductsByVendorId($vendorId);

            if ($store) {
                include __DIR__ . '/../views/view_store.php';
            } else {
                echo "Store not found.";
            }
        } else {
            echo "Invalid request. Vendor ID is missing.";
        }
        $this->db_connection->close();
    }
}