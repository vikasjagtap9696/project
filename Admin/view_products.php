<?php
// Note: Assuming 'db.php' returns $conn (PDO connection object).

// File path tumchya folder structure pramane barobar theva
include("../project/db.php");


// Initialize message variable
$msg = '';

// Handle Delete Request
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    // FIX: $pdo chya jagi $conn vaparala (Non-breaking space removed)
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    try {
        $stmt->execute();
        $msg = "<p class='success'>Product deleted successfully!</p>";
    } catch (PDOException $e) {
        $msg = "<p class='error'>Error: Product could not be deleted. " . $e->getMessage() . "</p>";
    }
}

// Fetch products
// FIX: $pdo chya jagi $conn vaparala
$stmt = $conn->query("SELECT product_id, name, metal_type, price, stock_quantity, is_trending, image_url_main, images_gallery FROM products ORDER BY product_id ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html>
<head>
    <title>View Products | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* ... (Your CSS styles remain unchanged) ... */
        :root {
            --primary-color: #40739e; /* Blue */
            --secondary-color: #2f3640; /* Dark text */
            --success-color: #44bd32;
            --error-color: #e84118;
            --bg-color: #f5f6fa;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1300px; 
            margin: 30px auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 40px;
            font-weight: 700;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 10px;
        }

        /* --- Table Styles --- */
        table {
            width: 100%;
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 20px;
        }

        th, td {
            padding: 15px 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }
        
        /* Table Header */
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        /* --- Image Styles --- */
        .product-img {
            width: 60px; 
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #c2c2c2;
            margin-bottom: 5px;
        }
        
        .gallery-panel {
            max-height: 120px;
            overflow-y: auto;
            padding: 5px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .gallery-panel img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 3px;
        }


        /* --- Action Buttons --- */
        a.button {
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            color: #fff;
            font-weight: 500;
            margin-bottom: 5px; 
            display: block; 
            text-align: center;
            transition: background-color 0.3s;
        }

        .edit { background-color: var(--success-color); }
        .edit:hover { background-color: #3aa829; }

        .delete { background-color: var(--error-color); }
        .delete:hover { background-color: #d13216; }

        /* --- Messages --- */
        .success, .error {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid;
        }
        .success {
            background-color: #d4edda;
            color: var(--success-color);
            border-color: #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: var(--error-color);
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Product Inventory Management</h1>
        <?php if(!empty($msg)) echo $msg; ?>
        <a class="button edit" href="add_product.php" style="width: 200px; margin: 0 0 20px auto; display: block;">+ Add New Product</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Main Image</th> 
                    <th>Name</th>
                    <th>Gallery Images (<?php echo count($products); ?> Products)</th> 
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($products): ?>
                    <?php foreach($products as $product): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            
                            <td>
                                <?php if (!empty($product['image_url_main'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url_main']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                <span style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($product['metal_type']); ?> | Trending: <?php echo $product['is_trending'] ? 'Yes' : 'No'; ?></span>
                            </td>
                            
                            <td>
                                <div class="gallery-panel">
                                    <?php 
                                    // FIX: PostgreSQL Array String parser use kela
                                    $raw_pg_array = trim($product['images_gallery'] ?? '', '{}');
                                    $gallery_urls = array_filter(array_map('trim', explode(',', $raw_pg_array)));
                                    $gallery_urls = array_map(function($url) { return trim($url, '"'); }, $gallery_urls); // Clean up quotes
                                    
                                    // Check if decoding was successful and it's a non-empty array
                                    if (is_array($gallery_urls) && count($gallery_urls) > 0): 
                                        foreach ($gallery_urls as $url): 
                                            // Ensure URL is not empty before displaying
                                            if (!empty($url)):
                                                ?>
                                                <img src="<?php echo htmlspecialchars(trim($url)); ?>" alt="Gallery Image" title="Gallery Image" />
                                                <?php
                                            endif;
                                        endforeach; 
                                    else: ?>
                                        <span style="font-size: 0.8em; color: #aaa;">No gallery images.</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                            <td style="font-weight: 500; color: <?php echo $product['stock_quantity'] < 10 ? 'var(--error-color)' : 'var(--success-color)'; ?>;"><?php echo $product['stock_quantity']; ?></td>
                            
                            <td>
                                <a class="button edit" href="edit_product.php?id=<?php echo $product['product_id']; ?>">Edit</a>
                                <a class="button delete" href="?delete=<?php echo $product['product_id']; ?>" onclick="return confirm('Confirm deletion of <?php echo htmlspecialchars($product['name']); ?> (ID: <?php echo $product['product_id']; ?>)?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No products found in the inventory.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>