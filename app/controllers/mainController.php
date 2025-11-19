<?php

require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';
require_once __DIR__ . '/../models/productModels.php';

class ProductController {
    private $productModel;

    public function __construct(ProductModel $productModel) {
        $this->productModel = $productModel;
    }

    public function handleRequest($action = 'list', $id = null, $categoryId = null) {
        // Simple routing based on action
        switch ($action) {
            case 'view':
                $this->viewProduct($id);
                break;
            case 'category':
                $this->listProductsByCategory($categoryId);
                break;
            case 'list':
            default:
                $this->listAllProducts();
                break;
        }
    }

    private function listAllProducts() {
        // Fetch data from the model
        $products = $this->productModel->getActiveProductsInStock();

        // Data for the view
        $data = [
            'page_title' => 'All Active Products',
            'products' => $products,
            'is_filtered' => false
        ];

        // Load the list view template
        $this->render('product_list_view.php', $data);
    }

    private function listProductsByCategory($categoryId) {
        if (empty($categoryId)) {
            // Redirect or show error if no category is provided
            header('Location: index.php?action=list');
            exit;
        }

        // Fetch data from the model
        $products = $this->productModel->getProductsByCategoryId($categoryId);

        // Data for the view (Assuming category name lookup would happen here too)
        $data = [
            'page_title' => "Products in Category ID: " . htmlspecialchars($categoryId),
            'products' => $products,
            'is_filtered' => true
        ];

        // Load the list view template
        $this->render('product_list_view.php', $data);
    }

    private function viewProduct($productId) {
        if (empty($productId)) {
            // Redirect or show error if no product ID is provided
            header('Location: index.php?action=list');
            exit;
        }

        // 1. Get the single product details
        $product = $this->productModel->readSingle($productId);

        if (!$product) {
            // Handle Product Not Found (e.g., show a 404 view)
            $this->render('404_view.php', ['message' => 'Product not found.']);
            return;
        }

        // 2. Get the product's reviews
        $reviews = $this->productModel->getReviewsByProductId($productId);

        // Data for the view
        $data = [
            'page_title' => $product['name'],
            'product' => $product,
            'reviews' => $reviews
        ];

        // Load the single product view template
        $this->render('product_single_view.php', $data);
    }

    // A simple method to load the view and pass data
    private function render($viewFile, $data = []) {
        // Extract the data array to make variables available in the view file
        extract($data);
        
        // Ensure this path is correct relative to where you execute the code
        require "views/{$viewFile}";
    }
}