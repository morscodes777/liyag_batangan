<?php
// Ensure order data exists before rendering
if (!isset($orderDetails) || !isset($orderItems) || empty($orderDetails)) {
    // Fallback or error handling if data is missing
    echo "<div style='text-align:center; padding: 50px;'><h1>Error</h1><p>Order details could not be loaded.</p></div>";
    exit;
}

$orderId = $orderDetails['order_id'];
$orderTotal = $orderDetails['order_total'];
$shippingFee = $orderDetails['shipping_fee'] ?? 0.00;
$paymentMethod = $orderDetails['payment_method'];
$status = $orderDetails['order_status'];
$address = $orderDetails['full_address'] ?? 'Address not available';
$subTotal = $orderTotal - $shippingFee;
$date = date('F d, Y H:i A', strtotime($orderDetails['created_at'] ?? 'now'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $orderId ?> Receipt</title>
    <link rel="stylesheet" href="public/assets/css/receipt.css">
    <link rel="icon" type="image/png" href="public/assets/default/icon/logo.png">

</head>
<body>

<div class="receipt-container">
    
    <div class="header">
        <h1>SUCCESS! 🎉</h1>
        <p>Order Placed Successfully</p>
    </div>

    <div class="info-section">
        <span>Order ID:</span>
        <span>#<?= htmlspecialchars($orderId) ?></span>
    </div>

    <div class="info-section">
        <span>Date:</span>
        <span><?= htmlspecialchars($date) ?></span>
    </div>

    <div class="info-section">
        <span>Payment Method:</span>
        <span><?= htmlspecialchars($paymentMethod) ?></span>
    </div>
    
    <div class="info-section">
        <span>Status:</span>
        <span style="font-weight: bold; color: green;"><?= htmlspecialchars($status) ?></span>
    </div>

    <div class="info-section" style="flex-direction: column; border-top: 1px solid #eee; padding-top: 10px;">
        <span style="margin-bottom: 5px;">Delivery Address:</span>
        <span style="font-weight: normal; font-size: 0.85em; color: #555;"><?= htmlspecialchars($address) ?></span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= htmlspecialchars($item['quantity']) ?></td>
                <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                <td>₱<?= number_format($item['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; padding-top: 10px;">Subtotal (Items):</td>
                <td style="text-align: right; padding-top: 10px;">₱<?= number_format($subTotal, 2) ?></td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right;">Shipping Fee:</td>
                <td style="text-align: right;">₱<?= number_format($shippingFee, 2) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; font-size: 1.2em;">GRAND TOTAL:</td>
                <td class="grand-total">₱<?= number_format($orderTotal, 2) ?></td>
            </tr>
        </tfoot>
    </table>
    <a href="index.php?action=track_orders&status=Pending" class="receipt-btn">
        <i class="bi bi-person-circle"></i> View Order History / Account
    </a>

    <div class="footer">
        Thank you for shopping with us!
        <div style="margin-top: 5px;">Please check your email for a detailed confirmation.</div>
    </div>
</div>

<script src="app/scripts/receipt.js"></script>

</body>
</html>