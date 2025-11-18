<?php
// products.php - ALL PRODUCTS PAGE (NO LIMIT)
session_start();
include('db.php'); // PDO connection for PostgreSQL (or compatible DB)

// --- LIVE GOLD PRICE FUNCTION (SIMULATION MODE) ---
function fetch_live_gold_price($default_price = 6350)
{
    $current_hour = (int) date('H');
    // Gold price fluctuation: +/- 20 rupees depending on the hour
    $fluctuation = ($current_hour % 5) - 2;
    $live_price = $default_price + $fluctuation * 10;
    return ['price' => number_format($live_price, 0, '.', ','), 'change' => $fluctuation];
}

$gold_data = fetch_live_gold_price();

if (!isset($_SESSION['favorites']))
    $_SESSION['favorites'] = [];
// --- AJAX Handler for Favorites ---
if (isset($_GET['fav_id'])) {
    $pid = intval($_GET['fav_id']);
    if (in_array($pid, $_SESSION['favorites'])) {
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

$sql .= " ORDER BY product_id DESC"; // NO LIMIT HERE!

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database error gracefully
    $products = [];
    // You might want to log the error here: error_log($e->getMessage());
}

// Fetch distinct filters (Used for filter options)
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
    <style>
        /* ---------------------------------------------------------------------- */
        /* --- SUPER ADVANCED DESIGN STYLES (FROM index1.php) --- */
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

        header a {
            color: white;
            text-decoration: none;
            margin-left: 30px;
            font-weight: 500;
            transition: color 0.3s;
            padding: 5px 0;
            position: relative;
        }

        header a:hover {
            color: var(--primary-color);
        }

        header a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--primary-color);
            transition: width 0.3s ease-out;
        }

        header a:hover::after {
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

        /* --- Filters & Active Filters --- */
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

        .filter-tag a {
            color: var(--secondary-color);
            text-decoration: none;
            margin-left: 8px;
            font-weight: 700;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .filter-tag a:hover {
            opacity: 1;
        }

        .clear-all-link {
            color: var(--primary-dark);
            text-decoration: none;
            margin-left: 10px;
            font-size: 0.9em;
            cursor: pointer;
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
            opacity: 0;
            /* Hide by default */
        }

        .product-card:hover .prev,
        .product-card:hover .next {
            opacity: 1;
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

        .social-icons a {
            display: inline-block;
            margin-right: 15px;
            font-size: 1.5em;
            color: white;
        }

        .social-icons a:hover {
            color: var(--primary-color);
            transform: scale(1.1);
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
            <a href="index1.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
        </nav>
    </header>

    <?php
    $change_color = $gold_data['change'] >= 0 ? '#1C7430' : '#8D0B1C'; // Darker, less distracting colors for text
    $change_arrow = $gold_data['change'] >= 0 ? '&#9650;' : '&#9660;';
    ?>
    <div id="live-price-bar">
        <span style="font-weight: 400;">**Live Gold Price (24K):**</span>
        <span style="font-size: 1.1em; font-weight: 700;">₹ <?= $gold_data['price'] ?>/gm</span>
        <span class="change-indicator" style="color:<?= $change_color ?>;"><?= $change_arrow ?>
            <?= abs($gold_data['change']) ?></span>
        <span style="font-weight: 400; margin-left: 15px; color: #444;">(Last Update: <?= date('h:i A') ?>)</span>
    </div>

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
                $clear_url = 'products.php?' . http_build_query(array_filter($new_params)); // Link back to products.php
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
                    if (!empty($p['images_gallery'])) {
                        // Remove outer braces {} and split
                        $str = trim($p['images_gallery'], '{}');
                        $gallery = preg_split('/\s*,\s*/', $str, -1, PREG_SPLIT_NO_EMPTY);
                        // FIX: Remove double quotes from each URL (if present from DB array format)
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

    <!-- <script>
        // --- Filters Auto Submit Logic ---
        ['metal', 'style', 'occasion', 'collection'].forEach(id => {
            const element = document.getElementById(id);
            if (element) { element.addEventListener('change', () => { filterProducts(); }); }
        });

        const searchInput = document.getElementById('search');
        if (searchInput) { searchInput.addEventListener('keyup', debounce(() => { filterProducts(); }, 500)); }

        function debounce(func, delay) { let timeout; return function() { const context = this; const args = arguments; clearTimeout(timeout); timeout = setTimeout(() => func.apply(context, args), delay); }; }
        
        function filterProducts() {
            let params = new URLSearchParams();
            const fields = [ {id: 'search', key: 'q'}, {id: 'metal', key: 'metal_type'}, {id: 'style', key: 'design_style'}, {id: 'occasion', key: 'occasion'}, {id: 'collection', key: 'collection_key'} ];
            fields.forEach(field => {
                const element = document.getElementById(field.id);
                const value = element ? element.value.trim() : '';
                if (value) { params.set(field.key, value); }
            });
            window.location.href = 'products.php?' + params.toString(); 
        }

        // --- Heart icon (Favorite) click handler ---
        document.querySelectorAll('.fav-icon').forEach(el => {
            el.addEventListener('click', () => {
                let card = el.closest('.product-card');
                let pid = card.dataset.id;
                fetch('products.php?fav_id=' + pid) // Use products.php for AJAX
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'added') {
                            el.style.color = 'red';
                            el.classList.add('is-favorite');
                        } else {
                            el.style.color = 'white';
                            el.classList.remove('is-favorite');
                        }
                    });
            });
        });

        // --- Auto-Slide Product Image Gallery Slider (on hover) ---
        document.querySelectorAll('.product-card').forEach(card => {
            // Ensure array is parsed correctly and defaults to an empty array
            let allImages = JSON.parse(card.getAttribute('data-gallery') || '[]') || [];
            
            // Filter out any potential empty strings from parsing
            allImages = allImages.filter(url => url && url.trim() !== '');

            if (allImages.length <= 1) return; // Stop if only one or no images
            
            let index = 0;
            let mainImg = card.querySelector('.main-img');
            let prev = card.querySelector('.prev');
            let next = card.querySelector('.next');
            let slideInterval;
            
            const updateImage = (newIndex) => { 
                index = (newIndex + allImages.length) % allImages.length; 
                mainImg.src = allImages[index]; 
            };
            
            const startSlide = () => { 
                clearInterval(slideInterval); 
                slideInterval = setInterval(() => { updateImage(index + 1); }, 3000); 
            };
            
            const stopSlide = () => { 
                clearInterval(slideInterval); 
            };
            
            card.addEventListener('mouseenter', startSlide);
            card.addEventListener('mouseleave', stopSlide);
            
            // Manual clicks (stop auto-slide)
            if (prev) {
                prev.addEventListener('click', (e) => { 
                    e.stopPropagation(); 
                    stopSlide(); 
                    updateImage(index - 1); 
                });
            }
            if (next) {
                next.addEventListener('click', (e) => { 
                    e.stopPropagation(); 
                    stopSlide(); 
                    updateImage(index + 1); 
                });
            }
        });
    </script> -->
    <script src="script.js"></script>
</body>

</html>