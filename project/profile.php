<?php
// profile.php
// User profile page — shows user info, recent orders and favorites (wishlist)
// This file expects 'db.php' to provide a PDO connection in $conn.
session_start();
include('db.php');

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Basic request and pagination setup
$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'orders'; // active tab: 'orders' or 'favorites'
$limit = 3; // items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit; // next page var kuthun start  karaycha aahe

// -----------------------------------------------------------
// 1) Handle 'Remove Favorite' action
// If user clicks remove on a favorite item we delete the row
// from the 'favorites' table and redirect back to the favorites tab.
// -----------------------------------------------------------
if (isset($_GET['remove_fav']) && isset($_GET['product_id'])) {
    $pid = intval($_GET['product_id']);

    try {
        $delete_sql = "DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid";
        $stmt = $conn->prepare($delete_sql);
        $stmt->execute([':uid' => $user_id, ':pid' => $pid]);
    } catch (PDOException $e) {
        // Optional: log or show a friendly message in production
        // error_log('Favorite removal error: ' . $e->getMessage());
    }

    // Redirect back to the favorites tab on the same page
    header('Location: profile.php?tab=favorites&page=' . $page);
    exit;
}

// -----------------------------------------------------------
// 2) Fetch current user's profile information
// -----------------------------------------------------------
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Decode stored address book JSON (if present) and pick first address as default
$addresses = json_decode($user['address_book'], true) ?? [];
$default_address = $addresses[0] ?? [];

// -----------------------------------------------------------
// 3) Favorites (wishlist) retrieval with pagination
// - Count total favorites
// - Fetch favorite product rows joined with product details
// -----------------------------------------------------------
$favorites = [];
$total_favorites = 0;
$total_fav_pages = 1;

try {
    // Get total favorites count for pagination
    $stmt = $conn->prepare("SELECT COUNT(product_id) AS total FROM favorites WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $total_favorites = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_fav_pages = max(1, (int) ceil($total_favorites / $limit)); //ceil()  use round up to next integer and convert into int

    // Fetch favorites with product details for the current page
    $sql_fav = "
        SELECT p.*, f.added_at
        FROM favorites f
        JOIN products p ON f.product_id = p.product_id
        WHERE f.user_id = :uid
        ORDER BY f.added_at DESC
        LIMIT :limit OFFSET :offset
    ";

    // $stmt = $conn->prepare($sql_fav);
    // $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    // $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    // $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    // $stmt->execute();
    // $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $stmt = $conn->prepare($sql_fav);
    $stmt->execute([
        ':uid' => $user_id,
        ':limit' => $limit,
        ':offset' => $offset
    ]);

    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    // Optional: log the error in production
    //error_log('Favorites fetch error: ' . $e->getMessage());
}


// -----------------------------------------------------------
// 4) Orders retrieval and grouping
// - Count total orders for pagination
// - Fetch recent orders and the related order_items + product info
// - Group rows by order_id so each order has an array of products
// -----------------------------------------------------------
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


// Group flat rows into orders with product lists
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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #f5f5f5;
        }

        header {
            background: linear-gradient(90deg, #b8860b, #d4af37);
            color: #fff;
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header a {
            color: #fff;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: 0.3s;
        }

        header a:hover {
            color: #fff9c4;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 25px;
            background: #fff;
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .profile-header img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #d4af37;
        }

        .profile-details h2 {
            margin: 0;
            color: #8b6b00;
        }

        .profile-details p {
            margin: 4px 0;
            color: #444;
        }

        .loyalty {
            color: #b8860b;
            font-weight: 600;
            margin-top: 10px;
        }

        .btn {
            background: linear-gradient(90deg, #b8860b, #d4af37);
            padding: 8px 15px;
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn:hover {
            background: linear-gradient(90deg, #a97c00, #c1a832);
            transform: scale(1.05);
            cursor: pointer;
        }

        .remove-btn {
            background: #8D0B1C;
            text-decoration: none;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #6D0816;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 12px 0;
            background: #fff3d6;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .tab.active {
            background: #fff8e1;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        /* Orders: card, header and product list styling */
        .order-card {
            background: linear-gradient(180deg, #fffdf8, #fff8e8);
            padding: 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(212,175,55,0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }

        .order-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .order-header .order-id {
            font-weight: 700;
            color: #6b4f00;
            font-size: 1.05em;
        }

        .order-header .order-meta {
            color: #666;
            font-size: 0.95em;
        }

        .order-status {
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.85em;
            color: white;
            text-transform: capitalize;
        }

        .order-status.pending { background: #f39c12; }
        .order-status.processing { background: #3498db; }
        .order-status.completed { background: #2ecc71; }
        .order-status.cancelled { background: #e74c3c; }

        .order-info {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 18px;
            align-items: start;
        }

        .order-left p { margin: 6px 0; color: #444; }

        .order-total {
            font-size: 1.2em;
            font-weight: 800;
            color: #b8860b;
            margin-top: 6px;
        }

        .order-products {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .order-product {
            display: flex;
            gap: 12px;
            align-items: center;
            background: rgba(255,255,255,0.6);
            border-radius: 10px;
            padding: 10px;
            min-width: 220px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.03);
        }

        .order-product img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 8px;
        }

        .order-product .op-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .order-product .op-name {
            font-weight: 700;
            color: #333;
            font-size: 0.95em;
        }

        .order-product .op-meta {
            color: #666;
            font-size: 0.9em;
        }

        @media(max-width:900px) {
            .order-info { grid-template-columns: 1fr; }
            .order-products { justify-content: flex-start; }
        }

        /* Advanced Product Card Styling */
        .product-card {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            margin-top: 20px;
            background: linear-gradient(145deg, #fff8e1, #fff3d6);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(184, 134, 11, 0.15);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #b8860b, #d4af37);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-card img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-right: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }

        .product-card .details-container {
            display: flex;
            align-items: flex-start;
            flex: 1;
            padding: 10px 0;
        }

        .product-info {
            flex: 1;
            padding-right: 20px;
        }

        .product-info h3 {
            margin: 0 0 10px 0;
            color: #8b6b00;
            font-size: 1.4em;
            font-weight: 700;
        }

        .product-info p {
            margin: 8px 0;
            color: #666;
            line-height: 1.6;
        }

        .product-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 15px 0;
            font-size: 0.9em;
        }

        .meta-item {
            padding: 8px 12px;
            background: rgba(255,255,255,0.7);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: #b8860b;
        }

        .product-price {
            font-size: 1.6em;
            font-weight: 700;
            color: #b8860b;
            margin: 15px 0;
            display: flex;
            align-items: baseline;
            gap: 5px;
        }

        .price-detail {
            font-size: 0.6em;
            color: #666;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .pagination a {
            padding: 6px 12px;
            background: #d4af37;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .pagination a:hover {
            background: #b8860b;
        }

        .pagination .current {
            background: #a97c00;
        }

        @media(max-width:768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-header img {
                margin-bottom: 15px;
            }

            .tabs {
                flex-direction: column;
            }
        }
    </style>
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