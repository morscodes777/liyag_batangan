<?php

require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';
require_once __DIR__ . '/../models/productModels.php';

class ProductController {
    private $productModel;

    public function __construct() {
        $database = new Database();
        $db_conn = $database->connect();
        
        $this->productModel = new ProductModel($db_conn);
    }
    
    public function index() {
        $products = $this->productModel->getActiveProductsInStock();

        $view_path = __DIR__ . '/../views/products/index.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo "<h1>Products View Not Found</h1><p>The file 'views/products/index.php' does not exist.</p>";
        }
    }
    
    public function getProductsByCategory($categoryId) {
        if ($categoryId === 'all') {
            return $this->productModel->getActiveProductsInStock();
        } else {
            return $this->productModel->getProductsByCategoryId(intval($categoryId));
        }
    }

    public function view() {
        if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
            header("Location: index.php?action=products");
            exit;
        }

        $product_id = intval($_GET['product_id']);
        $product = $this->productModel->getProductById($product_id);

        if (!$product) {
            echo "<h1>Product Not Found</h1><p>The requested product does not exist.</p>";
            exit;
        }

        $view_path = __DIR__ . '/../views/products/view.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo "<h1>Product Details View Not Found</h1>";
        }
    }
}