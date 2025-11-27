<?php

session_start();
include('db.php');


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'orders'; 
$limit = 3; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit; 


if (isset($_GET['remove_fav']) && isset($_GET['product_id'])) {
    $pid = intval($_GET['product_id']);

    try {
        $delete_sql = "DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid";
        $stmt = $conn->prepare($delete_sql);
        $stmt->execute([':uid' => $user_id, ':pid' => $pid]);
    } catch (PDOException $e) {
        
    }

   
    header('Location: profile.php?tab=favorites&page=' . $page);
    exit;
}


$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$default_address = $addresses[0] ?? [];
$address_book = !empty($user['address_book']) 
    ? json_decode($user['address_book'], true) 
    : [];


$favorites = [];
$total_favorites = 0;
$total_fav_pages = 1;

try {
    $stmt = $conn->prepare("SELECT COUNT(product_id) AS total FROM favorites WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $total_favorites = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_fav_pages = max(1, (int) ceil($total_favorites / $limit)); 

    $sql_fav = "
        SELECT p.*, f.added_at
        FROM favorites f
        JOIN products p ON f.product_id = p.product_id
        WHERE f.user_id = :uid
        ORDER BY f.added_at DESC
        LIMIT :limit OFFSET :offset
    ";

    ;


    $stmt = $conn->prepare($sql_fav);
    $stmt->execute([
        ':uid' => $user_id,
        ':limit' => $limit,
        ':offset' => $offset
    ]);

    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    
}



$stmt = $conn->prepare(query: "SELECT COUNT(DISTINCT order_id) as total FROM orders WHERE user_id=:uid");
$stmt->execute([':uid' => $user_id]);
$total_orders = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_order_pages = max(1, (int) ceil($total_orders / $limit));

$sql = "
    SELECT 
        o.order_id, 
        o.order_date, 
        o.total_amount, 
        o.status, 
        o.shipping_address, 
        o.payment_method,
        oi.quantity, 
        oi.unit_price_at_sale, 
        p.name AS product_name, 
        p.image_url_main
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.user_id = :uid
    ORDER BY o.order_date DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':uid' => $user_id,
    ':limit' => $limit,
    ':offset' => $offset
]);

$orders_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);


$orders = [];
foreach ($orders_raw as $o) {
    $oid = $o['order_id'];
    if (!isset($orders[$oid])) {
        $addr = [];
        if (!empty($o['shipping_address'])) {
            $addr = is_string($o['shipping_address']) ? (json_decode($o['shipping_address'], true) ?? []) : $o['shipping_address'];
        }
        $orders[$oid] = [
            'order_id' => $oid,
            'order_date' => $o['order_date'],
            'total_amount' => $o['total_amount'],
            'status' => $o['status'],
            'payment_method' => $o['payment_method'],
            'shipping_address' => $addr,
            'products' => []
        ];
    }
    $orders[$oid]['products'][] = [
        'name' => $o['product_name'],
        'quantity' => $o['quantity'],
        'unit_price' => $o['unit_price_at_sale'],
        'image' => $o['image_url_main']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile | SuvarnaKart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link rel="stylesheet" href="profile.style">

    <script>
        function switchTab(tab) {
            window.location.href = "?tab=" + tab;
        }
    </script>
</head>

<body>

    <header>
        <div>💎 SuvarnaKart</div>
        <nav>
            <a href="index1.php"> <i class="fa-solid fa-house"></i> Home</a>
            <a href="cart.php" id="cart-nav-link"> <i class="fas fa-shopping-cart"></i> Cart </a>
            <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>

        </nav>
    </header>

    <div class="container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($user['profile_photo'] ?? 'uploads\default_profile.png') ?>" alt="Profile">
            <div class="profile-details">
                <h2><?= htmlspecialchars($user['first_name'] ?? 'User') ?></h2>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Mobile:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                <p><strong>DOB:</strong> <?= htmlspecialchars($user['dob'] ?? ' ') ?></p>
                <?php if ($default_address): ?>
                    <p><strong>Address:</strong> <?= htmlspecialchars($default_address['line1'] ?? '') ?>
                        <?= htmlspecialchars($default_address['city'] ?? '') ?>,
                        <?= htmlspecialchars($default_address['state'] ?? '') ?>
                    </p>
                <?php endif; ?>
                <!-- <p class="loyalty">💰 Loyalty Points: <?= $user['loyalty_points'] ?></p> -->
                <br>
                <a href="edit_profile.php" class="btn">✏️ Edit Profile</a>
            </div>
        </div>

        <div class="tabs">
            <div class="tab <?= $tab == 'orders' ? 'active' : '' ?>" onclick="switchTab('orders')">📦 Orders</div>
            <div class="tab <?= $tab == 'favorites' ? 'active' : '' ?>" onclick="switchTab('favorites')">⭐ Favorites
            </div>
        </div>

        <?php if ($tab == 'orders'): ?>
            <div class="section">
                <h3>Your Recent Orders</h3>

                <?php if (count($orders) > 0): ?>

                    <?php foreach ($orders as $o): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-id">Order #<?php echo $o['order_id']; ?></div>
                                <div class="order-meta"><?php echo date('d M Y', strtotime($o['order_date'])); ?></div>
                                <div class="order-status <?php echo strtolower(preg_replace('/[^a-z0-9]+/i','', $o['status'])); ?>">
                                    <?php echo htmlspecialchars($o['status']); ?>
                                </div>
                            </div>

                            <div class="order-info">
                                <div class="order-left">
                                    <p>Payment: <?php echo htmlspecialchars($o['payment_method']); ?></p>
                                    <p class="order-total">Total: ₹<?php echo number_format($o['total_amount'], 2); ?></p>

                                    <?php
                                    $addr = is_string($o['shipping_address'])
                                        ? (json_decode($o['shipping_address'], true) ?? [])
                                        : $o['shipping_address'];
                                    ?>

                                    <?php if (!empty($addr)): ?>
                                        <p>
                                            Ship To: <?php echo htmlspecialchars($addr['name'] ?? ''); ?>,
                                            <?php echo htmlspecialchars($addr['phone'] ?? ''); ?>,
                                            <?php echo htmlspecialchars($addr['address'] ?? ''); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="order-products">
                                    <?php foreach ($o['products'] as $p): ?>
                                        <div class="order-product">
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                                            <div class="op-info">
                                                <div class="op-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                                <div class="op-meta">Qty: <?php echo (int)$p['quantity']; ?> • ₹<?php echo number_format($p['unit_price'], 2); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?tab=orders&page=<?= $page - 1 ?>">&laquo; Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_order_pages; $i++): ?>
                            <a href="?tab=orders&page=<?= $i ?>" class="<?= $i == $page ? 'current' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_order_pages): ?>
                            <a href="?tab=orders&page=<?= $page + 1 ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <p>No orders yet.</p>
                <?php endif; ?>
            </div>



        <?php else: /* Favorites Tab */ ?>
            <div class="section">
                <h3 style="color: #8b6b00; font-size: 1.8em; margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-heart" style="color: #d4af37;"></i> 
                    Your Favorite Products
                    <div style="font-size: 0.6em; color: #666; margin-top: 5px;">
                        <?php echo count($favorites); ?> items in your collection
                    </div>
                </h3>
                <?php if (!empty($favorites)): ?>
                    <?php foreach ($favorites as $f): ?>
                        <div class="product-card">
                            <div class="details-container">
                                <img src="<?php echo htmlspecialchars($f['image_url_main']); ?>" alt="Product Image">
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($f['name']); ?></h3>
                                    
                                    <!-- Product Description -->
                                    <p><?php echo htmlspecialchars($f['description'] ?? 'Elegant jewelry piece crafted with precision'); ?></p>
                                    
                                    <!-- Product Metadata Grid -->
                                    <div class="product-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-gem"></i>
                                            <span>Metal: <?php echo htmlspecialchars($f['metal_type'] ?? 'Gold'); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-weight-hanging"></i>
                                            <span>Weight: <?php echo number_format($f['weight_grams'] ?? 0, 2); ?> gm</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-paint-brush"></i>
                                            <span>Style: <?php echo htmlspecialchars($f['design_style'] ?? 'Traditional'); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Added: <?php echo date('d M Y', strtotime($f['added_at'])); ?></span>
                                        </div>
                                    </div>

                                    <!-- Price Display -->
                                    <div class="product-price">
                                        ₹<?php echo number_format($f['price'], 2); ?>
                                        <span class="price-detail">/piece</span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="product-actions">
                                        <a href="checkout.php?product_id=<?php echo $f['product_id']; ?>" class="btn">
                                            <i class="fas fa-shopping-cart"></i> Buy Now
                                        </a>
                                        <a href="profile.php?tab=favorites&remove_fav=1&product_id=<?php echo $f['product_id']; ?>&page=<?php echo $page; ?>"
                                            class="remove-btn">
                                            <i class="fas fa-heart-broken"></i> Remove
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="pagination">
                        <?php if ($page > 1): ?><a href="?tab=favorites&page=<?= $page - 1 ?>">&laquo;
                                Previous</a><?php endif; ?>
                        <?php for ($i = 1; $i <= $total_fav_pages; $i++): ?>
                            <a href="?tab=favorites&page=<?= $i ?>" class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_fav_pages): ?><a href="?tab=favorites&page=<?= $page + 1 ?>">Next
                                &raquo;</a><?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>No favorites added yet. <a href="index1.php" style="color:#d4af37; font-weight:600;">Explore Products</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>
