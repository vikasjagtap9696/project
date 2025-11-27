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

/*  REMOVE FAVORITE PRODUCT  */
if (isset($_GET['remove_fav']) && isset($_GET['product_id'])) {
    $pid = intval($_GET['product_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $user_id, ':pid' => $pid]);
    } catch (PDOException $e) {}

    header("Location: profile.php?tab=favorites&page=$page");
    exit;
}

/* USER DATA */
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* FIXED ADDRESS BOOK */
$address_book = !empty($user['address_book'])
    ? json_decode($user['address_book'], true)
    : [];

$default_address = $address_book[0] ?? [
    'line1' => '',
    'city' => '',
    'state' => '',
    'pincode' => ''
];

/************************************
 FAVORITES  
*************************************/
$favorites = [];
$total_favorites = 0;
$total_fav_pages = 1;

try {
    $stmt = $conn->prepare("SELECT COUNT(product_id) AS total FROM favorites WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $total_favorites = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_fav_pages = max(1, ceil($total_favorites / $limit));

    $sql_fav = "
        SELECT p.*, f.added_at
        FROM favorites f
        JOIN products p ON p.product_id = f.product_id
        WHERE f.user_id = :uid
        ORDER BY f.added_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $conn->prepare($sql_fav);
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {}

/************************************
 ORDERS 
*************************************/
$stmt = $conn->prepare("SELECT COUNT(DISTINCT order_id) AS total FROM orders WHERE user_id=:uid");
$stmt->execute([':uid' => $user_id]);
$total_orders = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_order_pages = max(1, ceil($total_orders / $limit));

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
    JOIN order_items oi ON oi.order_id = o.order_id
    JOIN products p ON p.product_id = oi.product_id
    WHERE o.user_id = :uid
    ORDER BY o.order_date DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$orders_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orders = [];
foreach ($orders_raw as $o) {
    $oid = $o['order_id'];

    if (!isset($orders[$oid])) {
        $addr = [];
        if (!empty($o['shipping_address'])) {
            $addr = is_string($o['shipping_address'])
                ? (json_decode($o['shipping_address'], true) ?? [])
                : $o['shipping_address'];
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
