<?php

session_start();
include('db.php'); 

if (!isset($_SESSION['favorites']))
    $_SESSION['favorites'] = [];

if (isset($_GET['fav_id'])) {
    $pid = intval($_GET['fav_id']);
    if (in_array($pid, $_SESSION['favorites'])) 
	{
        // Remove from favorites
        $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$pid]);
        echo json_encode(['status' => 'removed']);
    } else {
        // Add to favorites
        $_SESSION['favorites'][] = $pid;
        echo json_encode(['status' => 'added']);
    }
    exit;
}

// Handle search & filter parameters
$get_params = $_GET;
$search = $get_params['q'] ?? '';
$metal = $get_params['metal_type'] ?? '';
$style = $get_params['design_style'] ?? '';
$occasion = $get_params['occasion'] ?? '';
$collection = $get_params['collection_key'] ?? '';

// --- Database Query Logic ---
$sql = "SELECT * FROM products WHERE 1=1";
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

$sql .= " ORDER BY product_id DESC"; 

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    
    $products = [];
    
}

$metals = $conn->query("SELECT DISTINCT metal_type FROM products WHERE metal_type IS NOT NULL AND metal_type != '' ORDER BY metal_type")->fetchAll(PDO::FETCH_COLUMN);
$styles = $conn->query("SELECT DISTINCT design_style FROM products WHERE design_style IS NOT NULL AND design_style != '' ORDER BY design_style")->fetchAll(PDO::FETCH_COLUMN);
$occasions = $conn->query("SELECT DISTINCT occasion FROM products WHERE occasion IS NOT NULL AND occasion != '' ORDER BY occasion")->fetchAll(PDO::FETCH_COLUMN);
$collections = $conn->query("SELECT DISTINCT collection_key FROM products WHERE collection_key IS NOT NULL AND collection_key != '' ORDER BY collection_key")->fetchAll(PDO::FETCH_COLUMN);

$fav_ids = $_SESSION['favorites'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Jewellery Products | SuvarnaKart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link rel="stylesheet" href="style.css">
    
</head>

<body>
    <header>
        <div>💎 SuvarnaKart</div>
        <nav>
            <a href="index1.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
        </nav>
    </header>


    <div class="filters">
        <strong style="margin-right: 15px; font-size: 1.1em; color: var(--primary-color);">Filter By:</strong>
        <input type="text" id="search" placeholder="Search product name or description..."
            value="<?= htmlspecialchars($search) ?>" style="flex-grow: 1;">
        <select id="metal">
            <option value="">All Metals</option>
            <?php foreach ($metals as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>" <?= strtolower($m) == strtolower($metal) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="style">
            <option value="">All Styles</option>
            <?php foreach ($styles as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= strtolower($s) == strtolower($style) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="occasion">
            <option value="">All Occasions</option>
            <?php foreach ($occasions as $o): ?>
                <option value="<?= htmlspecialchars($o) ?>" <?= strtolower($o) == strtolower($occasion) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($o) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="collection">
            <option value="">All Collections</option>
            <?php foreach ($collections as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= strtolower($c) == strtolower($collection) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php $active_filters = array_filter(['q' => $search, 'metal_type' => $metal, 'design_style' => $style, 'occasion' => $occasion, 'collection_key' => $collection]); ?>
    <?php if (!empty($active_filters)): ?>
        <div class="active-filters-container">
            <strong>Applied Filters:</strong>
            <?php foreach ($active_filters as $key => $value): ?>
                <?php
                $label = match ($key) {
                    'q' => 'Search', 'metal_type' => 'Metal', 'design_style' => 'Style', 'occasion' => 'Occasion', 'collection_key' => 'Collection', default => $key
                };
                $new_params = $get_params;
                unset($new_params[$key]);
                $clear_url = 'products.php?' . http_build_query(array_filter($new_params)); 
                ?>
                <span class="filter-tag">
                    <?= $label ?>: <strong><?= htmlspecialchars($value) ?></strong>
                    <a href="<?= htmlspecialchars($clear_url) ?>"><i class="fas fa-times"></i></a>
                </span>
            <?php endforeach; ?>
            <a href="products.php" class="clear-all-link">Clear All Filters</a>
        </div>
    <?php endif; ?>

    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-gem" style="color: var(--primary-color); margin-right: 10px;"></i> Full Jewellery Catalogue
        </h2>
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div class="no-products"
                    style="grid-column: 1 / -1; text-align: center; padding: 50px; font-size: 1.1em; color: #666;">No
                    products found matching your filter criteria.</div>
            <?php else: ?>
                <?php foreach ($products as $p):
                    $gallery = [];
                    if (!empty($p['images_gallery'])) 
					{
                        $str = trim($p['images_gallery'], '{}');
                        $gallery = preg_split('/\s*,\s*/', $str, -1, PREG_SPLIT_NO_EMPTY);
                        $gallery = array_map(function ($url) {
                            return trim($url, '"'); }, $gallery);
                    }
                    $all_images = array_merge([$p['image_url_main']], $gallery);
                    $all_images = array_filter($all_images);
                    $is_favorite = in_array($p['product_id'], $fav_ids);
                    ?>
                    <div class="product-card" data-id="<?= $p['product_id'] ?>"
                        data-gallery='<?= htmlspecialchars(json_encode(array_filter($all_images)), ENT_QUOTES) ?>'>

                        <span class="fav-icon fas fa-heart <?= $is_favorite ? 'is-favorite' : '' ?>"
                            style="color:<?= $is_favorite ? 'red' : 'white' ?>;"></span>
                        <img class="main-img" src="<?= htmlspecialchars($p['image_url_main']) ?>"
                            alt="<?= htmlspecialchars($p['name']) ?>">

                        <?php if (count($all_images) > 1): ?>
                            <span class="prev fas fa-chevron-left"></span>
                            <span class="next fas fa-chevron-right"></span>
                        <?php endif; ?>

                        <div class="product-info">
                            <div>
                                <div class="meta-price-block">
                                    <div class="product-meta">
                                        <i class="fas fa-tags" style="color: var(--primary-dark);"></i>
                                        **<?= htmlspecialchars($p['metal_type'] ?? 'N/A') ?>**
                                        <br>
                                        <i class="fas fa-weight-hanging" style="color: var(--primary-dark);"></i>
                                        **<?= htmlspecialchars($p['weight_grams'] ?? 'N/A') ?>** gm
                                    </div>
                                    <span class="price"><i class="fas fa-rupee-sign"></i>
                                        <?= number_format($p['price'], 0) ?></span>
                                </div>

                                <h3><?= htmlspecialchars($p['name']) ?></h3>
                                <p style="font-size: 0.85em; color: #999;">Style:
                                    <?= htmlspecialchars($p['design_style'] ?? 'N/A') ?></p>
                                <p><?= htmlspecialchars($p['description'] ?? 'No description available.') ?></p>
                            </div>

                            <div class="action-bar">
                                <button class="buy-btn"
                                    onclick="location.href='checkout.php?product_id=<?= $p['product_id'] ?>'">
                                    View Details & Buy Now
                                </button>
                                <button class="add-icon-btn"
                                    onclick="location.href='cart.php?action=add&product_id=<?= $p['product_id'] ?>'">
                                    <i class="fas fa-shopping-bag"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    
    <script src="script.js"></script>
</body>

</html>