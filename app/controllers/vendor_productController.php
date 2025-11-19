<?php
// app/controllers/ProductController.php
require_once __DIR__ . '/../models/vendor_productModels.php';
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';
require_once __DIR__ . '/../models/storeModels.php'; 

class ProductController {
    private $productModel;
    private $storeModel;
    private $db_connection;

    public function __construct() {
        // Assume session_start() is called once by the front controller
        // However, since it's missing in some methods, we'll keep the call in the constructor 
        // to ensure it runs early, but it's better practice in the main entry file.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $database = new Database();
        $this->db_connection = $database->connect();
        
        // 🛑 FIX 1: Initialize $this->storeModel
        $this->productModel = new ProductModel($this->db_connection);
        $this->storeModel = new StoreModel($this->db_connection);
    }
    public function getVendorProductList() {
        // 🛑 FIX 2: Remove redundant session_start() call
        header('Content-Type: application/json');

        // Check if user is logged in and is a vendor
        if (!isset($_SESSION['user']) || ($_SESSION['user']['user_type'] !== 'Vendor' && $_SESSION['user']['user_type'] !== 'vendor')) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Please log in as a vendor."]);
            return;
        }
        
        // 1. Get vendor_id from the session
        // This is where the original 'unknown method' error was pointing to
        $store = $this->storeModel->getStoreDetailsByUserId($_SESSION['user']['user_id']);

        if (!$store) {
            http_response_code(404);
            echo json_encode(["error" => "Vendor store not found or not approved."]);
            return;
        }
        
        $vendorId = $store['vendor_id'];

        // 2. Fetch all products using the new StoreModel method
        $products = $this->storeModel->getProductsByVendorId($vendorId);
        
        http_response_code(200);
        echo json_encode($products);
        // 🛑 FIX 3: Removed $this->db_connection->close() in favor of connection persistence
    }

    public function viewProduct() {
        // 🛑 FIX 2: Remove redundant session_start() call
        
        if (!isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Please log in."]);
            return;
        }

        header('Content-Type: application/json');

        if (!isset($_GET['product_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Product ID is missing."]);
            return;
        }

        $productId = $_GET['product_id'];
        $product = $this->productModel->getProductById($productId);

        if ($product) {
            http_response_code(200);
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Product not found."]);
        }
        // 🛑 FIX 3: Removed $this->db_connection->close()
    }

    /**
     * Vendor-specific method to view a product for editing.
     * Includes a security check to ensure the vendor can only view their own products.
     */
    public function viewVendorProduct() {
        // 🛑 FIX 2: Remove redundant session_start() call
        header('Content-Type: application/json');

        // Check if user is logged in and is a vendor
        if (!isset($_SESSION['user']) || ($_SESSION['user']['user_type'] !== 'Vendor' && $_SESSION['user']['user_type'] !== 'vendor')) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Please log in as a vendor."]);
            return;
        }

        if (!isset($_GET['product_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Product ID is missing."]);
            return;
        }

        $productId = $_GET['product_id'];
        $product = $this->productModel->getProductById($productId);
        $userId = $_SESSION['user']['user_id'];

        if ($product) {
            // Security check: Verify if the product's vendor_id matches the logged-in vendor's user_id.
            $vendorId = $this->productModel->getVendorIdByUserId($userId);
            
            if ((int)$product['vendor_id'] === (int)$vendorId) {
                http_response_code(200);
                echo json_encode($product);
            } else {
                http_response_code(403);
                echo json_encode(["error" => "Forbidden. You do not have access to this product."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Product not found."]);
        }
        // 🛑 FIX 3: Removed $this->db_connection->close()
    }

    public function viewVendorProductsByCategory() {
        // 🛑 FIX 2: Remove redundant session_start() call
        header('Content-Type: application/json');

        // Check if user is logged in and is a vendor
        if (!isset($_SESSION['user']) || ($_SESSION['user']['user_type'] !== 'Vendor' && $_SESSION['user']['user_type'] !== 'vendor')) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Please log in as a vendor."]);
            return;
        }

        // Get vendor_id from the session
        $vendorId = $this->productModel->getVendorIdByUserId($_SESSION['user']['user_id']);

        if (!$vendorId) {
            http_response_code(404);
            echo json_encode(["error" => "Vendor not found."]);
            return;
        }

        // Get category_id from the GET request
        $categoryId = $_GET['category_id'] ?? null;
        
        $products = $this->productModel->getProductsByStoreAndCategory($vendorId, $categoryId);
        
        http_response_code(200);
        echo json_encode($products);
        // 🛑 FIX 3: Removed $this->db_connection->close()
    }
    
    /**
     * Handles the creation of a new product via a POST request.
     */
    public function createProduct() {
        // 🛑 FIX 2: Remove redundant session_start() call
        header('Content-Type: application/json');

        if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'Vendor') {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Please log in as a vendor."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed."]);
            return;
        }

        // Validate and sanitize input
        $vendorId = $this->productModel->getVendorIdByUserId($_SESSION['user']['user_id']);
        if (!$vendorId) {
            http_response_code(404);
            echo json_encode(["error" => "Vendor not found."]);
            return;
        }
        
        // 🛑 FIX 4: Used null coalescing for POST variables to avoid warnings
        $name = filter_var($_POST['name'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var($_POST['description'] ?? null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $stockQuantity = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $imageUrl = '';

        if (!$name || $price === false || $stockQuantity === false || $categoryId === false) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or missing required product data."]);
            return;
        }

        // --- Start of Corrected Image Upload Logic ---
        $imageFile = $_FILES['image'] ?? null;
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/products/';

            // Create the directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid() . '_' . basename($imageFile['name']);
            $targetFilePath = $uploadDir . $fileName;

            // Verify the file type to prevent malicious uploads
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($imageFile['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid file type. Only JPG, PNG, and GIF are allowed."]);
                return;
            }

            if (move_uploaded_file($imageFile['tmp_name'], $targetFilePath)) {
                // This is the URL path for the database, which is relative to the web root
                $imageUrl = 'uploads/products/' . $fileName;
            } else {
                // Improved error handling
                http_response_code(500);
                echo json_encode(["error" => "Failed to move uploaded file. Check directory permissions."]);
                return;
            }
        } else {
            // This block will now only be reached if no file was uploaded or there was a system error
            http_response_code(400);
            echo json_encode(["error" => "No image uploaded or an upload error occurred."]);
            return;
        }
        // --- End of Corrected Image Upload Logic ---

        $productData = [
            'vendor_id' => $vendorId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock_quantity' => $stockQuantity,
            'category_id' => $categoryId,
            'image_url' => $imageUrl
        ];

        if ($this->productModel->createProduct($productData)) {
            http_response_code(201); // 201 Created
            echo json_encode(["message" => "Product added successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add product to the database."]);
        }
        // 🛑 FIX 3: Removed $this->db_connection->close()
    }
  public function updateProduct() {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'Vendor') {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized. Please log in as a vendor."]);
        return;
    }

    // 1. Get product ID from GET (URL) or POST (Form Data, which contains product_id input)
    $productId = $_GET['product_id'] ?? $_POST['product_id'] ?? null;

    // 2. Validate request method and ensure we found a product ID
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($productId)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid request. (Missing product ID)"]);
        return;
    }

    // --- Data Sanitization ---
    $name = filter_var($_POST['name'] ?? null, FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'] ?? null, FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $stockQuantity = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
    
    // Status handling removed as requested.
    
    // --- VALIDATION (Status requirement REMOVED) ---
    if (!$name || $price === false || $stockQuantity === false) {
        http_response_code(400);
        // Updated error message to reflect remaining required fields
        echo json_encode(["error" => "Invalid or missing required product data (Name, Price, or Stock)."]);
        return;
    }
    
    // Get the product data first for security and to retrieve the current image URL
    $product = $this->productModel->getProductById($productId);
    $vendorId = $this->productModel->getVendorIdByUserId($_SESSION['user']['user_id']); 

    if (!$product || (int)$product['vendor_id'] !== (int)$vendorId) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden. You do not have access to this product."]);
        return;
    }
    
    // Build the product data array, retaining the EXISTING image URL
    $productData = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'stock_quantity' => $stockQuantity,
        // Status is removed from the data array
        'image_url' => $product['image_url'] // Use the existing URL from the database
    ];
    
    // Add category_id to the update data ONLY if it was actually passed
    if (isset($_POST['category_id'])) {
        $categoryId = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
        if ($categoryId !== false) {
             $productData['category_id'] = $categoryId;
        } else {
             http_response_code(400);
             echo json_encode(["error" => "Invalid category ID format."]);
             return;
        }
    }

    // Security check passed (already done above), now attempt update
    if ($this->productModel->updateProduct($productId, $productData)) {
        http_response_code(200);
        echo json_encode(["message" => "Product updated successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update product. No changes or database error."]);
    }
}
public function deleteProduct() {
        // 🛑 FIX 2: Remove redundant session_start() call
        header('Content-Type: application/json');

        if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'Vendor') {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Please log in as a vendor."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['product_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid request."]);
            return;
        }

        $productId = $_GET['product_id'];
        
        // Security check: ensure the product belongs to the logged-in vendor
        $product = $this->productModel->getProductById($productId);
        $vendorId = $this->productModel->getVendorIdByUserId($_SESSION['user']['user_id']);

        if ($product && (int)$product['vendor_id'] === (int)$vendorId) {
            if ($this->productModel->deleteProduct($productId)) {
                http_response_code(200);
                echo json_encode(["message" => "Product deleted successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete product."]);
            }
        } else {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden. You do not have access to this product."]);
        }
        // 🛑 FIX 3: Removed $this->db_connection->close()
    }
}