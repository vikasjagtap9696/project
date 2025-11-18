<?php
 // ------------------------------------------------------------
// index1.php - HOME PAGE (LIMIT 10 PRODUCTS)
// ------------------------------------------------------------

// Start session (needed for user info like login)
session_start();

// Include database connection (db.php should define $conn as PDO object)
include('db.php');

// ------------------------------------------------------------
// USER ID DEFINITION
// Get the logged-in user's ID from session, if available
//(Null Coalescing Operator) using
$user_id = $_SESSION['user_id'] ?? null;
// ------------------------------------------------------------

// ------------------------------------------------------------
// LIVE GOLD PRICE FUNCTION (SIMULATION MODE)
// This function simulates live gold prices with minor fluctuations
// ------------------------------------------------------------
// function fetch_live_gold_price($default_price = 6350)
// {
//   $current_hour = (int) date('H'); // Current hour
//   $fluctuation = ($current_hour % 5) - 2; // Simple hourly fluctuation
//   $live_price = $default_price + $fluctuation * 10;

//   return [
//     'price' => number_format($live_price, 0, '.', ','), // Formatted price
//     'change' => $fluctuation // Price change indicator
//   ];
// }

// ------------------------------------------------------------
// AJAX: Add to Cart Logic (User Must Be Logged In)
//Asynchronous Operation) AJAX full form 
// ------------------------------------------------------------
if (isset($_GET['add_to_cart_ajax']) && is_numeric($_GET['add_to_cart_ajax'])) {
  header('Content-Type: application/json'); // Return JSON response
  $product_id = intval($_GET['add_to_cart_ajax']); // convert string to intger
  $cart_result = ['status' => 'error', 'message' => "❌ Please Login to add items to your cart."]; //Default array  value set

  if ($user_id) {
    try {
      // 1. Check if product already exists in cart
      $check_sql = "SELECT quantity FROM cart WHERE user_id = :uid AND product_id = :pid";
      $stmt = $conn->prepare($check_sql);
      $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
      $existing_item = $stmt->fetch(PDO::FETCH_ASSOC); // return value/false;

      // 2. If exists, increase quantity by 1
      //NOW() function  current timestamp returns

      if ($existing_item) {

        $update_sql = "UPDATE cart SET quantity = quantity + 1, added_at = NOW() WHERE user_id = :uid AND product_id = :pid";
        $conn->prepare($update_sql)->execute([':uid' => $user_id, ':pid' => $product_id]);
        $cart_result['message'] = "✅ Quantity increased by 1 in cart!";
      } else {
        // 3. Else, insert new product into cart
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, 1)";
        $conn->prepare($insert_sql)->execute([':uid' => $user_id, ':pid' => $product_id]);
        $cart_result['message'] = "✅ Product added to cart!";
      }
      $cart_result['status'] = 'success';
    } catch (PDOException $e) {
      $cart_result['message'] = "❌ Database error: Could not add to cart.";
    }
  }

  // 4. Get total cart count for this user
  if ($user_id) {
    $count_sql = "SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :uid"; //COALESCE() function use  quantity sum  value null asle tar 0  set value 
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute([':uid' => $user_id]);
    $cart_result['cart_count'] = (int) $count_stmt->fetchColumn();
  } else {
    $cart_result['cart_count'] = 0; // 0 if user not logged in
  }

  echo json_encode($cart_result);
  exit;
}

// ------------------------------------------------------------
// AJAX: Favorites / Wishlist Handler (User Must Be Logged In)
// ------------------------------------------------------------
if (isset($_GET['fav_id'])) {
  header('Content-Type: application/json'); // Return JSON
  $pid = intval($_GET['fav_id']);
  $fav_result = ['status' => 'error', 'message' => '❌ Please Login to add items to your Wishlist.']; // Default array value set

  if ($user_id) {
    try {
      // 1. Check if product is already in favorites
      $check_sql = "SELECT fav_id FROM favorites WHERE user_id = :uid AND product_id = :pid";
      $stmt = $conn->prepare($check_sql);
      $stmt->execute([':uid' => $user_id, ':pid' => $pid]);

      if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        // 2. Remove from favorites if exists
        $delete_sql = "DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid";
        $conn->prepare($delete_sql)->execute([':uid' => $user_id, ':pid' => $pid]);
        $fav_result = ['status' => 'removed', 'message' => '💔 Product removed from Wishlist!'];
      } else {
        // 3. Add to favorites
        $insert_sql = "INSERT INTO favorites (user_id, product_id) VALUES (:uid, :pid)";
        $conn->prepare($insert_sql)->execute([':uid' => $user_id, ':pid' => $pid]);
        $fav_result = ['status' => 'added', 'message' => '💖 Product added to Wishlist!'];
      }
    } catch (PDOException $e) {
      $fav_result['message'] = "❌ Database error: Could not update wishlist.";
    }
  }
  echo json_encode($fav_result);
  exit;
}

// ------------------------------------------------------------
// INITIAL PAGE LOAD: Fetch Cart Count & Favorites IDs
// ------------------------------------------------------------
$current_cart_count = 0;
$fav_ids = [];

if ($user_id) {
  try {
    // 1. Get total cart count
    $count_sql = "SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :uid";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute([':uid' => $user_id]);
    $current_cart_count = (int) $count_stmt->fetchColumn();

    // 2. Get list of favorite product IDs
    $fav_sql = "SELECT product_id FROM favorites WHERE user_id = :uid";
    $fav_stmt = $conn->prepare($fav_sql);
    $fav_stmt->execute([':uid' => $user_id]);
    $fav_ids = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);

  } catch (PDOException $e) {
    $current_cart_count = 0;
    $fav_ids = [];
  }
}

// Fetch live gold price
//$gold_data = fetch_live_gold_price();

// Handle search and filter GET parameters
$get_params = $_GET;// $get_params store all key value pair value (Superglobal Array)  
$search = $get_params['q'] ?? '';
$metal = $get_params['metal_type'] ?? '';
$style = $get_params['design_style'] ?? '';
$occasion = $get_params['occasion'] ?? '';
$collection = $get_params['collection_key'] ?? '';

// ------------------------------------------------------------
// DATABASE QUERY: Fetch products based on filters
// ------------------------------------------------------------
$sql = "SELECT * FROM products WHERE 1=1"; // Base query
$params = [];

if ($search != '') {
  $sql .= " AND (LOWER(name) LIKE LOWER(:search) OR LOWER(description) LIKE LOWER(:search))";
  $params[':search'] = "%$search%";
}
if ($metal != '') {
  $sql .= " AND LOWER(metal_type)=LOWER(:metal)";
  $params[':metal'] = $metal;
}
if ($style != '') {
  $sql .= " AND LOWER(design_style)=LOWER(:style)";
  $params[':style'] = $style;
}
if ($occasion != '') {
  $sql .= " AND LOWER(occasion)=LOWER(:occ)";
  $params[':occ'] = $occasion;
}
if ($collection != '') {
  $sql .= " AND LOWER(collection_key)=LOWER(:col)";
  $params[':col'] = $collection;
}

$sql .= " ORDER BY product_id DESC LIMIT 10"; // Limit to 10 featured products

try {
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $products = [];
}

// Fetch distinct values for filters (metals, styles, occasions, collections)
function getDistinctValues($conn, $column)
{
  $sql = "SELECT DISTINCT $column 
            FROM products 
            WHERE $column IS NOT NULL AND $column != '' 
            ORDER BY $column";
  return $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

$metals = getDistinctValues($conn, 'metal_type');
$styles = getDistinctValues($conn, 'design_style');
$occasions = getDistinctValues($conn, 'occasion');
$collections = getDistinctValues($conn, 'collection_key');

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SuvarnaKart | Elegant Gold & Diamond Jewellery</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    /* ---------------------------------------------------------------------- */
    /* --- SUPER ADVANCED DESIGN STYLES --- (NO CHANGE) */
    /* ---------------------------------------------------------------------- */
    :root {
      /* Warmer, Richer Gold */
      --primary-color: #C0A87A;
      --primary-dark: #A3855F;
      --secondary-color: #1A1A1A;
      /* Deep Charcoal/Black */
      --bg-color: #FDFCF8;
      /* Light Cream/Parchment */
      --card-bg: #FFFFFF;
      --header-bg: #1A1A1A;
      --border-color: #EFEFEF;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg-color);
      margin: 0;
      color: var(--secondary-color);
      line-height: 1.6;
    }

    /* --- Header & Live Price Bar --- */
    header {
      background: var(--header-bg);
      color: white;
      padding: 15px 60px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      z-index: 100;
      position: sticky;
      top: 0;
    }

    header div:first-child {
      font-size: 1.8em;
      font-weight: 700;
      letter-spacing: 2px;
      color: var(--primary-color);
    }

    header nav a {
      color: white;
      text-decoration: none;
      margin-left: 30px;
      font-weight: 500;
      transition: color 0.3s;
      padding: 5px 0;
      position: relative;
    }

    header nav a:hover {
      color: var(--primary-color);
    }

    header nav a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -5px;
      left: 0;
      background-color: var(--primary-color);
      transition: width 0.3s ease-out;
    }

    header nav a:hover::after {
      width: 100%;
    }

    #live-price-bar {
      background: var(--primary-color);
      color: var(--secondary-color);
      padding: 8px 60px;
      text-align: center;
      font-size: 0.9em;
      font-weight: 600;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    #live-price-bar .change-indicator {
      margin-left: 5px;
      font-size: 1.1em;
    }

    /* --- Hero Section & Filters --- */
    #hero-section {
      background-image: url('../uploads/hero-section-backgroundimage.jpg');
      background-position: center;
      background-size: cover;
      background-repeat: no-repeat;

      height: 400px;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      padding: 0 60px;
      position: relative;
    }


    #hero-section::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.4);
    }

    .hero-content {
      z-index: 10;
      color: white;
      text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);

    }

    .hero-content h1 {
      font-size: 3em;
      margin: 0 0 10px 0;
      font-weight: 800;
      /* Bolder Font */
      color: var(--primary-color);
      letter-spacing: 2px;
    }

    .hero-content p {
      font-size: 1.2em;
      margin-bottom: 25px;
      font-weight: 300;
    }

    .hero-btn {
      background: var(--primary-color);
      color: var(--secondary-color);
      padding: 12px 30px;
      text-decoration: none;
      font-weight: 600;
      border-radius: 4px;
      transition: background 0.3s;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }

    .hero-btn:hover {
      background: var(--primary-dark);
      color: white;
    }

    .filters {
      padding: 20px 60px;
      background: var(--card-bg);
      border-bottom: 1px solid var(--border-color);
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
      align-items: center;
    }

    .filters select,
    .filters input {
      padding: 10px 15px;
      border-radius: 4px;
      border: 1px solid var(--border-color);
      outline: none;
      font-size: 14px;
      min-width: 150px;
      background: #FFFFFF;
      transition: border-color 0.3s;
    }

    .filters select:focus,
    .filters input:focus {
      border-color: var(--primary-color);
    }

    .active-filters-container {
      padding: 15px 60px;
      background: #FFF;
      border-bottom: 1px solid #EEE;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }

    .filter-tag {
      background: #F8F8F8;
      color: var(--secondary-color);
      border: 1px solid var(--primary-color);
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85em;
      display: flex;
      align-items: center;
      font-weight: 500;
    }

    /* --- Container and Grid --- */
    .container {
      padding: 40px 60px;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      /* Slightly wider cards */
      gap: 45px;
      /* More space between cards */
    }

    .section-title {
      text-align: center;
      margin-bottom: 40px;
      color: var(--secondary-color);
      font-weight: 700;
      font-size: 2.2em;
      text-transform: uppercase;
      letter-spacing: 3px;
    }

    /* ---------------------------------------------------------------------- */
    /* --- PRODUCT CARD (LUXURIOUS MINIMALISM) --- */
    /* ---------------------------------------------------------------------- */
    .product-card {
      border-radius: 12px;
      /* Smoother corners */
      border: none;
      /* No visible border */
      /* Floating Effect */
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      position: relative;
      transition: transform 0.4s ease-out, box-shadow 0.4s ease-out;
      display: flex;
      flex-direction: column;
      background: var(--card-bg);
    }

    .product-card:hover {
      transform: translateY(-10px);
      /* Subtle Gold Aura Glow on Hover */
      box-shadow:
        0 15px 40px rgba(0, 0, 0, 0.2),
        0 0 20px rgba(192, 168, 122, 0.6);
    }

    .product-card img {
      width: 100%;
      height: 350px;
      /* Slightly taller image */
      object-fit: cover;
      transition: transform 0.6s ease-out;
      border-bottom: 1px solid var(--border-color);
    }

    .product-card:hover img {
      transform: scale(1.05);
      /* Zoom more on hover */
    }

    .product-info {
      padding: 20px 25px;
      text-align: left;
      flex-grow: 1;
    }

    .product-info h3 {
      margin: 0 0 5px 0;
      font-size: 1.5em;
      /* Larger title */
      font-weight: 700;
      color: var(--secondary-color);
    }

    /* --- New: Price & Meta Block --- */
    .meta-price-block {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 10px;
      margin-bottom: 15px;
    }

    .product-meta {
      font-size: 0.9em;
      color: #777;
      font-weight: 500;
      line-height: 1.4;
    }

    .product-meta strong {
      color: var(--primary-dark);
      font-weight: 600;
    }

    .price {
      color: var(--primary-color);
      font-weight: 800;
      /* Extra bold price */
      font-size: 1.6em;
      letter-spacing: 0.5px;
    }

    .product-info p:last-of-type {
      font-size: 0.9em;
      color: #555;
      height: 40px;
      overflow: hidden;
    }

    /* --- Action Bar (Advanced UX) --- */
    .action-bar {
      display: flex;
      align-items: center;
      margin-top: 15px;
      gap: 10px;
    }

    /* Buy Now Button (Full Width Primary) */
    .buy-btn {
      /* Changed to .view-btn or kept as buy-btn, depending on your final decision */
      flex: 1;
      background: var(--primary-color);
      color: var(--secondary-color);
      padding: 12px 10px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      border: none;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      font-size: 15px;
    }

    .buy-btn:hover {
      background: var(--primary-dark);
      color: white;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
    }

    /* Add to Cart Icon Button */
    .add-icon-btn {
      background: transparent;
      border: 2px solid var(--primary-color);
      border-radius: 6px;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3em;
      color: var(--primary-color);
      cursor: pointer;
      transition: all 0.3s;
    }

    .add-icon-btn:hover {
      background: var(--primary-color);
      color: white;
      transform: scale(1.05);
    }

    /* --- Icons --- */
    .fav-icon {
      position: absolute;
      top: 25px;
      right: 25px;
      font-size: 24px;
      z-index: 10;
      cursor: pointer;
      color: white;
      text-shadow: 0 0 10px rgba(0, 0, 0, 0.9);
      transition: color 0.3s, transform 0.3s;
    }

    .fav-icon.is-favorite {
      color: red !important;
    }

    .fav-icon:hover {
      transform: scale(1.2);
    }

    .prev,
    .next {
      background: rgba(0, 0, 0, 0.6);
      border-radius: 50%;
      padding: 8px 12px;
      font-size: 0.9em;
      color: white;
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 20;
      cursor: pointer;
      transition: background 0.3s;
    }

    .prev:hover,
    .next:hover {
      background: rgba(192, 168, 122, 0.8);
    }

    .prev {
      left: 15px;
    }

    .next {
      right: 15px;
    }

    /* --- Message Box --- */
    #ajax-message {
      position: fixed;
      top: 100px;
      right: 20px;
      background: #e6f7d9;
      color: #4b8b4c;
      padding: 15px 25px;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
      transform: translateY(-20px);
      font-weight: 600;
      border-left: 5px solid #4b8b4c;
    }

    #ajax-message.error {
      background: #f7e6e6;
      color: #8d4b4b;
      border-left-color: #8d4b4b;
    }

    #ajax-message.show {
      opacity: 1;
      transform: translateY(0);
    }

    /* --- Footer Styling --- */
    footer {
      background: var(--secondary-color);
      color: #CCCCCC;
      padding: 50px 60px;
      border-top: 5px solid var(--primary-color);
      font-size: 0.9em;
      line-height: 1.8;
    }

    .footer-content {
      display: flex;
      justify-content: space-between;
      max-width: 1200px;
      margin: 0 auto 30px;
    }

    .footer-section {
      flex: 1;
      padding: 0 20px;
    }

    .footer-section h4 {
      color: var(--primary-color);
      font-size: 1.2em;
      border-bottom: 2px solid var(--primary-dark);
      padding-bottom: 5px;
      margin-bottom: 15px;
    }

    .footer-section a {
      color: #AAA;
      text-decoration: none;
      display: block;
      margin-bottom: 5px;
      transition: color 0.3s;
    }

    .footer-section a:hover {
      color: var(--primary-color);
    }

    .footer-bottom {
      text-align: center;
      border-top: 1px solid #333;
      padding-top: 20px;
    }

    @media (max-width: 768px) {

      header,
      .filters,
      .container,
      .active-filters-container,
      #live-price-bar,
      footer {
        padding: 15px 20px;
      }

      .product-grid {
        grid-template-columns: 1fr;
        gap: 30px;
      }

      #hero-section {
        height: 300px;
        padding: 0 20px;
      }

      .hero-content h1 {
        font-size: 2em;
      }

      .product-card img {
        height: 280px;
      }

      .footer-content {
        flex-direction: column;
        gap: 30px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div>💎 SuvarnaKart</div>
    <nav>
      <!-- <a href="products.php">All Products</a> -->
      <a href="profile.php"><i class="fa-solid fa-user"></i> profile</a>
      <!-- Cart link with dynamic badge showing number of items -->
      <a href="cart.php" id="cart-nav-link">
        <i class="fas fa-shopping-cart"></i>
        Cart
        <?php if ($current_cart_count > 0): ?>
        <span class="cart-badge" style="position: static; 
                  margin-left: 5px; 
                  background: none; 
                  color: var(--primary-color); 
                  font-weight: 700;">
          <?php echo htmlspecialchars($current_cart_count); ?>
        </span>
        <?php endif; ?>
      </a>
    </nav>
  </header>

  <!-- /* <?php
  $change_color = $gold_data['change'] >= 0 ? '#1C7430' : '#8D0B1C'; // Darker, less distracting colors for text
  $change_arrow = $gold_data['change'] >= 0 ? '&#9650;' : '&#9660;';
  ?> */ -->
  <!-- <div id="live-price-bar">
    <span style="font-weight: 400;">**Live Gold Price (24K):**</span>
    <span style="font-size: 1.1em; font-weight: 700;">₹ <?= $gold_data['price'] ?>/gm</span>
    <span class="change-indicator" style="color:<?= $change_color ?>;"><?= $change_arrow ?>
      <?= abs($gold_data['change']) ?></span>
    <span style="font-weight: 400; margin-left: 15px; color: #444;">(Last Update: <?= date('h:i A') ?>)</span>
  </div> -->

  <div id="ajax-message" style="display: none;"></div>


  <section id="hero-section">
    <div class="hero-content" >
      <h1>Discover Your Shine.</h1>
      <p>Handcrafted Gold & Diamond Jewellery, Ethically Sourced.</p>
      <a href="products.php?collection_key=Wedding" class="hero-btn">Shop Wedding Collection <i
          class="fas fa-arrow-right"></i></a>
    </div>
  </section>


  <div class="filters">
    <strong style="margin-right: 15px; font-size: 1.1em; color: var(--primary-color);">
      Filter By:
    </strong>


    <!-- Search input field -->
    <input type="text" id="search" placeholder="Search product..." value="<?php echo htmlspecialchars($search); ?>"
      style="flex-grow: 1;">
    <!-- Metal type filter dropdown -->
    <select id="metal">
      <option value="">All Metals</option>
      <?php foreach ($metals as $m): ?>
      <option value="<?php echo htmlspecialchars($m); ?>"
         <?php echo strtolower($m)==strtolower($metal) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($m); ?>
      </option>
      <?php endforeach; ?>
    </select>


    <!-- Design style filter dropdown -->
    <select id="style">
      <option value="">All Styles</option>
      <?php foreach ($styles as $s): ?>
      <option value="<?php echo htmlspecialchars($s); ?>"
         <?php echo strtolower($s)==strtolower($style) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($s); ?>
      </option>
      <?php endforeach; ?>
    </select>


    <!-- Occasion filter dropdown -->
    <select id="occasion">
      <option value="">All Occasions</option>
      <?php foreach ($occasions as $o): ?>
      <option value="<?php echo htmlspecialchars($o); ?>" 
        <?php echo strtolower($o)==strtolower($occasion) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($o); ?>
      </option>
      <?php endforeach; ?>
    </select>

    <!-- Collection filter dropdown -->
    <select id="collection">
      <option value="">All Collections</option>
      <?php foreach ($collections as $c): ?>
      <option value="<?php echo htmlspecialchars($c); ?>" <?php echo strtolower($c)==strtolower($collection) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($c); ?>
      </option>
      <?php endforeach; ?>
    </select>

  </div>



  <?php 
$active_filters = array_filter([
  'q' => $search, 
  'metal_type' => $metal, 
  'design_style' => $style, 
  'occasion' => $occasion, 
  'collection_key' => $collection
]); 
?>

<?php if (!empty($active_filters)): ?>
  <div class="active-filters-container">
        <strong>Active Filters:</strong>
        <?php foreach ($active_filters as $key => $value): ?>
          <?php
            $label = match ($key)
             {
              'q' => 'Search', 
              'metal_type' => 'Metal', 
              'design_style' => 'Style', 
              'occasion' => 'Occasion', 
              'collection_key' => 'Collection', 
              default => $key
            };
            $new_params = $get_params;
            unset($new_params[$key]);
            $clear_url = 'index1.php?' . http_build_query(array_filter($new_params));
          ?>
          <span class="filter-tag">
            <?= $label ?>: 
            <strong><?= htmlspecialchars($value) ?></strong>
            <a href="<?= htmlspecialchars($clear_url) ?>">
              <i class="fas fa-times"></i>
            </a>
          </span>
        <?php endforeach; ?>
        <a href="index1.php" class="clear-all-link">Clear All</a>
  </div>
<?php endif; ?>


  <div class="container">
    <h2 class="section-title">
      <i class="fas fa-crown" style="color: var(--primary-color); margin-right: 10px;"></i> Featured Jewellery
    </h2>

    <div class="product-grid">
      <?php if (empty($products)): ?>
      <div class="no-products"
        style="grid-column: 1 / -1; text-align: center; padding: 50px; font-size: 1.1em; color: #666;">No featured
        products found based on filters.</div>
      <?php else: ?>

      <?php foreach ($products as $p):
          $gallery = [];
          if (!empty($p['images_gallery'])) {
            $str = trim($p['images_gallery'], '{}');
           // $gallery = preg_split('/\s*,\s*/', $str, -1, PREG_SPLIT_NO_EMPTY);
             $gallery = explode(',', $str);
           
            $gallery = array_map(function ($url) {
              return trim($url, '"');
            }, $gallery);
          }
          $all_images = array_filter(array_merge([$p['image_url_main']], $gallery));

          $is_favorite = in_array($p['product_id'], $fav_ids);
          ?>
      <!-- Product card with gallery data and favorite status -->
      <div class="product-card" data-id="<?php echo $p['product_id']; ?>"
        data-gallery='<?php echo htmlspecialchars(json_encode($all_images), ENT_QUOTES); ?>'>

        <!-- Favorite heart icon -->
        <span class="fav-icon fas fa-heart <?php echo $is_favorite ? 'is-favorite' : ''; ?>"
          style="color:<?php echo $is_favorite ? 'red' : 'white'; ?>;"></span>
        <!-- Main product image -->
        <img class="main-img" src="<?php echo htmlspecialchars($p['image_url_main']); ?>"
          alt="<?php echo htmlspecialchars($p['name']); ?>">

        <?php if (count($all_images) > 1): ?>
        <span class="prev fas fa-chevron-left"></span>
        <span class="next fas fa-chevron-right"></span>
        <?php endif; ?>

        <div class="product-info">
          <div>
            <div class="meta-price-block">
              <div class="product-meta">
                <i class="fas fa-tags" style="color: var(--primary-dark);"></i>
                **
                <?= htmlspecialchars($p['metal_type'] ?? 'N/A') ?>**
                <br>
                <i class="fas fa-weight-hanging" style="color: var(--primary-dark);"></i>
                **
                <?= htmlspecialchars($p['weight_grams'] ?? 'N/A') ?>** gm
              </div>
              <span class="price"><i class="fas fa-rupee-sign"></i>
                <?= number_format($p['price'], 0) ?>
              </span>
            </div>

            <h3>
              <?= htmlspecialchars($p['name']) ?>
            </h3>
            
            <p style="font-size: 0.85em; color: #999;">Style:
              <?= htmlspecialchars($p['design_style'] ?? 'N/A') ?>
            </p>
            <p>
              <?= htmlspecialchars($p['description'] ?? 'No description available.') ?>
            </p>
          </div>

          <div class="action-bar">
            <button class="buy-btn" onclick="location.href='checkout.php?product_id=<?= $p['product_id'] ?>'">
              Buy Now
            </button>
            
            <button class="add-icon-btn add-to-cart-btn" data-id="<?= $p['product_id'] ?>">
              <i class="fas fa-shopping-bag"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="text-align: center; padding: 50px 0;">
      <a href="products.php" style="
            background: var(--secondary-color); 
            color: white; 
            padding: 15px 35px; 
            text-decoration: none; 
            font-weight: 600; 
            border-radius: 4px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
            font-size: 1.1em;
            transition: background 0.3s;" 
          onmouseover="this.style.background='var(--primary-dark)'"
          onmouseout="this.style.background='var(--secondary-color)'"
        >
        Explore Our Full Catalogue &gt;&gt;
      </a>
    </div>
  </div>

  <?php include 'footer.php'; ?>

<script src="script.js"></script>
  
</body>

</html>