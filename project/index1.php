<?php
 
session_start();


include('db.php');

$user_id = $_SESSION['user_id'] ?? null;



if (isset($_GET['add_to_cart_ajax']) && is_numeric($_GET['add_to_cart_ajax'])) {
  header('Content-Type: application/json'); 
  $product_id = intval($_GET['add_to_cart_ajax']); 
  $cart_result = ['status' => 'error', 'message' => "❌ Please Login to add items to your cart."]; 

  if ($user_id) {
    try {
     
      $check_sql = "SELECT quantity FROM cart WHERE user_id = :uid AND product_id = :pid";
      $stmt = $conn->prepare($check_sql);
      $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
      $existing_item = $stmt->fetch(PDO::FETCH_ASSOC); 

      
      if ($existing_item) {

        $update_sql = "UPDATE cart SET quantity = quantity + 1, added_at = NOW() WHERE user_id = :uid AND product_id = :pid";
        $conn->prepare($update_sql)->execute([':uid' => $user_id, ':pid' => $product_id]);
        $cart_result['message'] = "✅ Quantity increased by 1 in cart!";
      } else {
        
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, 1)";
        $conn->prepare($insert_sql)->execute([':uid' => $user_id, ':pid' => $product_id]);
        $cart_result['message'] = "✅ Product added to cart!";
      }
      $cart_result['status'] = 'success';
    } catch (PDOException $e) {
      $cart_result['message'] = "❌ Database error: Could not add to cart.";
    }
  }

  
  if ($user_id) {
    $count_sql = "SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :uid"; 
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute([':uid' => $user_id]);
    $cart_result['cart_count'] = (int) $count_stmt->fetchColumn();
  } else {
    $cart_result['cart_count'] = 0; 
  }

  echo json_encode($cart_result);
  exit;
}


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



$get_params = $_GET;
$search = $get_params['q'] ?? '';
$metal = $get_params['metal_type'] ?? '';
$style = $get_params['design_style'] ?? '';
$occasion = $get_params['occasion'] ?? '';
$collection = $get_params['collection_key'] ?? '';


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
	<link rel="stylesheet" href="style.css">

</head>

<body>
  <header>
    <div>💎 SuvarnaKart</div>
    <nav>
       <a href="products.php">All Products</a> 
      <a href="profile.php"><i class="fa-solid fa-user"></i> profile</a>
    
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
             $gallery = explode(',', $str);
           
            $gallery = array_map(function ($url) {
              return trim($url, '"');
            }, $gallery);
          }
          $all_images = array_filter(array_merge([$p['image_url_main']], $gallery));

          $is_favorite = in_array($p['product_id'], $fav_ids);
          ?>
		  
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