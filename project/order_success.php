<?php
session_start();
include('db.php');

$order_id = intval($_GET['order_id'] ?? 0);
$method = htmlspecialchars($_GET['method'] ?? '');

if (!$order_id) {
    die("Invalid Order ID");
}

// fetch order items (multiple products)
$stmt = $conn->prepare("
    SELECT o.order_id, o.total_amount, o.status, o.payment_method, 
           o.shipping_address, o.created_at,
           p.name AS product_name, p.image_url_main, oi.quantity, oi.unit_price_at_sale
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$order_items)
    die("Order not found.");

// decode address
$address = json_decode($order_items[0]['shipping_address'], true);
$total_amount = $order_items[0]['total_amount'];
$order_status = $order_items[0]['status'];
$created_at = $order_items[0]['created_at'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SuvarnaKart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fef6e4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #a67c00;
            text-align: center;
            margin-bottom: 10px;
        }

        .status {
            text-align: center;
            font-weight: 700;
            margin: 10px 0;
            font-size: 1.2em;
        }

        .success {
            color: green;
        }

        .pending {
            color: orange;
        }

        .products {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .product-item {
            display: flex;
            gap: 20px;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .product-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-info {
            flex: 1;
        }

        .product-info h4 {
            margin: 0;
            color: #333;
        }

        .product-info p {
            margin: 3px 0;
            color: #555;
            font-size: 0.9em;
        }

        .summary {
            margin-top: 25px;
            padding: 15px;
            background: #fff7e0;
            border-radius: 12px;
        }

        .summary h3 {
            margin-top: 0;
            color: #6a4b00;
        }

        .summary p {
            margin: 5px 0;
            color: #444;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            background: #a67c00;
            color: #fff;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        @media(max-width:600px) {
            .product-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎉 Order Placed Successfully!</h1>

        <div class="status <?= ($method == 'Razorpay') ? 'success' : 'pending' ?>">
            <?php if ($method == 'Razorpay'): ?>
                ✅ Payment Successful (<?= htmlspecialchars($method) ?>)
            <?php else: ?>
                🕓 Payment Pending (COD)
            <?php endif; ?>
        </div>

        <div class="products">
            <?php foreach ($order_items as $item): ?>
                <div class="product-item">
                    <img src="<?= htmlspecialchars($item['image_url_main']) ?>"
                        alt="<?= htmlspecialchars($item['product_name']) ?>">
                    <div class="product-info">
                        <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                        <p>Quantity: <?= htmlspecialchars($item['quantity']) ?></p>
                        <p>Unit Price: ₹<?= number_format($item['unit_price_at_sale'], 2) ?></p>
                        <p>Subtotal: ₹<?= number_format($item['quantity'] * $item['unit_price_at_sale'], 2) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <h3>📦 Shipping Details</h3>
            <p><b>Name:</b> <?= htmlspecialchars($address['name'] ?? '') ?></p>
            <p><b>Phone:</b> <?= htmlspecialchars($address['phone'] ?? '') ?></p>
            <p><b>Address:</b> <?= htmlspecialchars($address['address'] ?? '') ?></p>
            <p><b>Order Placed On:</b> <?= date('d M Y, h:i A', strtotime($created_at)) ?></p>
            <p><b>Estimated Delivery:</b> <?= date('d M Y', strtotime('+5 days')) ?></p>
            <p><b>Total Amount:</b> ₹<?= number_format($total_amount, 2) ?></p>
            <p><b>Order Status:</b> <?= htmlspecialchars($order_status) ?></p>
        </div>

        <div style="text-align:center;">
            <a href="index.php" class="btn">🛍 Continue Shopping</a>
            <a href="profile.php" class="btn" style="background:#6a4b00;">📄 View My Orders</a>
        </div>
    </div>
</body>

</html>