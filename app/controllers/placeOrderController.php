<?php
require_once __DIR__ . '/../models/orderModels.php';
require_once __DIR__ . '/../models/addressModels.php';
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

session_start();

header('Content-Type: application/json');

$PAYMONGO_SECRET_KEY = 'sk_test_euy4HQ8B2RXHSyuzZzafz7y1';
$PAYMONGO_API_URL = 'https://api.paymongo.com/v1/checkout_sessions';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_checkout'])) {

    $user_id = $_SESSION['user_id'] ?? null;
    $user_email = $_SESSION['email'] ?? 'guest@liyagbatangan.com';
    $user_name = $_SESSION['name'] ?? 'Customer';

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit;
    }

    $delivery_address_id = $_POST['delivery_address_id'];
    $payment_method = $_POST['payment_method'];
    $order_total = filter_var($_POST['order_total_final'], FILTER_VALIDATE_FLOAT);
    $shipping_fee = filter_var($_POST['shipping_fee'], FILTER_VALIDATE_FLOAT);
    $order_items_json = $_POST['final_selected_items_detailed'] ?? '';

    $orderModel = new OrderModel($pdo);
    $addressModel = new AddressModel($pdo);

    if ($delivery_address_id === 'new_address') {
        $new_label = filter_var($_POST['new_address_label'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $new_full_address = filter_var($_POST['new_full_address'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $new_lat = filter_var($_POST['new_latitude'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $new_long = filter_var($_POST['new_longitude'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $contact_number = $_POST['contact_number'] ?? $_SESSION['phone_number'] ?? null;

        if (empty($new_full_address) || empty($new_label) || $new_lat == 0 || $new_long == 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please select a location on the map and provide a label for your new address.']);
            exit;
        }

        try {
            $newly_created_address_id = $addressModel->saveAddress(
                $user_id, 
                $new_label, 
                $new_full_address, 
                $new_lat, 
                $new_long,
                $contact_number
            );

            if (!$newly_created_address_id) {
                throw new Exception("Failed to save new address to database.");
            }

            $delivery_address_id = $newly_created_address_id;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save new address.']);
            exit;
        }
    }

    $order_items = json_decode(htmlspecialchars_decode($order_items_json), true);

    if (!$order_items || count($order_items) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No items selected for checkout.']);
        exit;
    }

    $processed_items = [];
    foreach ($order_items as $item) {
        $commissionRate = $item['commission_rate'] ?? 0.00;
        
        if ($commissionRate < 10.00) {
            $commissionRate = 10.00; 
        }
        
        $item['commission_rate'] = $commissionRate;
        $processed_items[] = $item;
    }

    $items_to_delete_ids = [];
    foreach ($processed_items as $item) {
        $items_to_delete_ids[] = $item['cart_item_id'];
    }
    $_SESSION['items_to_delete_' . $user_id] = $items_to_delete_ids; 
    $_SESSION['order_items_details_' . $user_id] = $processed_items; 

    try {
        if ($payment_method === 'COD') {
            
            $orderId = $orderModel->processFullOrder(
                $user_id,
                $delivery_address_id,
                $payment_method,
                $order_total,
                $shipping_fee,
                $processed_items,
                'Pending'
            );

            if ($orderId) {
                $notificationTitle = "✅ Order Placed Successfully!";
                $notificationMessage = "Your COD order #{$orderId} has been confirmed. Total amount: ₱" . number_format($order_total, 2) . ". Thank you for shopping with us!";
                $orderModel->createNotification($user_id, $notificationTitle, $notificationMessage);

                echo json_encode([
                    'success' => true,
                    'message' => 'Order placed successfully.',
                    'redirect_url' => 'index.php?action=receipt&order_id=' . $orderId 
                ]);
                exit;
            } else {
                throw new Exception("processFullOrder() failed to return an Order ID.");
            }

        } elseif ($payment_method === 'GCash') {

            $temp_order_id = $orderModel->createTemporaryOrderForPaymongo(
                $user_id, 
                $delivery_address_id, 
                $payment_method, 
                $order_total, 
                $shipping_fee,
                $processed_items 
            );

            if (!$temp_order_id) {
                throw new Exception("Failed to create temporary order for PayMongo.");
            }

            $BASE_URL = 'http://liyagbatangan.shop/index.php';

            $amount_in_cents = (int)($order_total * 100); 
            $payload = [
                'data' => [
                    'attributes' => [
                        'billing' => [
                            'email' => $user_email,
                            'name' => $user_name,
                        ],
                        'send_email' => false,
                        'show_description' => true,
                        'show_line_items' => true,
                        'cancel_url' => $BASE_URL . '?action=checkout&status=cancel',
                        'success_url' => $BASE_URL . '?action=receipt&order_id=' . $temp_order_id,
                        'payment_method_types' => ['gcash'],
                        'line_items' => [
                            [
                                'currency' => 'PHP',
                                'amount' => $amount_in_cents,
                                'name' => 'Liyag Batangan Order #' . $temp_order_id,
                                'quantity' => 1,
                            ]
                        ],
                        'description' => 'E-commerce Purchase on Liyag Batangan',
                        'metadata' => ['order_id' => $temp_order_id],
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $PAYMONGO_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_USERPWD, $PAYMONGO_SECRET_KEY . ':');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $data = json_decode($response, true);

            if ($http_code === 200 && isset($data['data']['attributes']['checkout_url'])) {
                $checkout_session_id = $data['data']['id'];
                $orderModel->updateOrderWithPaymongoSession($temp_order_id, $checkout_session_id);

                echo json_encode([
                    'success' => true, 
                    'message' => 'Redirecting to GCash for payment.',
                    'redirect_url' => $data['data']['attributes']['checkout_url']
                ]);
            } else {
                $orderModel->deleteOrder($temp_order_id); 
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to initiate GCash payment. Error: ' . ($data['errors'][0]['detail'] ?? 'Unknown API error.'),
                ]);
            }

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payment method selected.']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Order processing failed: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing parameters.']);
}
exit;