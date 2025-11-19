<?php
require_once __DIR__ . '/../models/searchModels.php';

class SearchController {
    private $searchModel;

    public function __construct($pdo) {
        $this->searchModel = new SearchModel($pdo);
    }

    /**
     * Handle search suggestions via AJAX.
     * Expects GET parameter: query
     * Returns JSON: { "stores": [...], "products": [...] }
     */
    public function getSuggestions() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['stores' => [], 'products' => []]);
            exit;
        }

        // Get and validate query
        $query = trim($_GET['query'] ?? '');
        if (empty($query)) {
            header('Content-Type: application/json');
            echo json_encode(['stores' => [], 'products' => []]);
            exit;
        }

        try {
            // Fetch data using the model
            $stores = $this->searchModel->getStoresByQuery($query);
            $products = $this->searchModel->getProductsByQuery($query);

            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode(['stores' => $stores, 'products' => $products]);
        } catch (Throwable $e) {
            error_log("SearchController error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['stores' => [], 'products' => []]);
        }
        exit;
    }
}