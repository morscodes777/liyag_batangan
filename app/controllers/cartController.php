<?php
require_once __DIR__ . '/../models/cartModels.php'; 
require_once __DIR__ . '/../models/productModels.php'; 
require_once __DIR__ . '/../models/vendorModels.php'; 
// Use the correct PDO file, assuming 'liyab_batangan_db_pdo.php' is correct
// If the DB file is loaded in index.php, you can skip this line, 
// but keeping it here for clarity if this file is tested standalone.
// require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php'; 

class CartController {
    private $cartModel;
    private $user_id;

    // 🟢 ADD CONSTRUCTOR to accept and store the PDO connection
    public function __construct($db_conn = null) {
        // If index.php passes $db_conn (PDO), use it.
        if ($db_conn instanceof PDO) {
            $this->cartModel = new CartModel($db_conn);
        } else {
            // Fallback for viewCart, which still uses the old style connection logic
            // For AJAX actions, this fallback will not be used if index.php is fixed.
            $db = new Database();
            $conn = $db->connect();
            $this->cartModel = new CartModel($conn);
        }
        
        // Ensure user ID is correctly mapped from session
        if (isset($_SESSION['user']['user_id'])) {
            $this->user_id = $_SESSION['user']['user_id'];
        } elseif (isset($_SESSION['user_id'])) {
            // Fallback to the user_id session variable set in index.php
            $this->user_id = $_SESSION['user_id'];
        } else {
            $this->user_id = null;
        }
    }

    public function viewCart() {
        if (!$this->user_id) {
            header("Location: index.php?action=login");
            exit;
        }
        
        $user = $_SESSION['user'];
        $displayUserName = $user['name'] ?? 'User';
        $userProfilePicture = $user['profile_picture'] ?? null;
        
        try {
            // Use the model instantiated in the constructor
            $raw_cart_items = $this->cartModel->readCartItemsWithProductDetails($this->user_id);
            
            $grouped_cart_items = [];
            $cart_total = 0;
            
            foreach ($raw_cart_items as $item) {
                $timestamp = $item['updated_at'] ?? $item['created_at'] ?? date('Y-m-d H:i:s');
                $date_only = date('Y-m-d', strtotime($timestamp));
                
                $grouped_cart_items[$date_only][] = $item;
                
                $cart_total += $item['line_total']; 
            }
            
            $cart_items = $grouped_cart_items; 

            include __DIR__ . '/../views/cart.php';
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Could not load cart due to a server error.";
            header("Location: index.php?action=home");
            exit;
        }
    }

    public function updateQuantity() {
        if (!$this->user_id) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'You must be logged in to update the cart.']);
            exit;
        }
        
        $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if (!$cart_item_id || $quantity === false || $quantity < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid item or quantity value.']);
            exit;
        }

        try {
            $result = $this->cartModel->updateItemQuantity($cart_item_id, $this->user_id, $quantity);

            if ($result) {
                $item_details = $this->cartModel->getCartItemTimestamp($cart_item_id, $this->user_id); 
                
                if ($item_details && isset($item_details['updated_at'])) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Quantity updated successfully.',
                        'updated_at' => $item_details['updated_at']
                    ]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Quantity updated, but time could not be retrieved.']);
                }

            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found or update failed.']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'A server error occurred during the update.']);
        }
        exit;
    }

    public function deleteItem() {
        if (!$this->user_id) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'You must be logged in to remove items.']);
            exit;
        }

        $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);

        if (!$cart_item_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid cart item ID.']);
            exit;
        }

        try {
            $result = $this->cartModel->deleteItem($cart_item_id, $this->user_id);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Item successfully removed from cart.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found or failed to delete.']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'A server error occurred during deletion.']);
        }
        exit;
    }
}
