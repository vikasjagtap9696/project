<?php

include("../project/db.php");


$upload_dir = "../uploads/"; 
$BASE_UPLOAD_PATH = "../uploads/"; 


if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$msg = "";
$product = null;

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if ($product_id > 0) {
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :id");
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        
        header('Location: view_products.php');
        exit;
    }
} else {
    
    header('Location: view_products.php');
    exit;
}


if (isset($_POST['update_product'])) {
    // Retrieve form data
	
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $weight_grams = $_POST['weight_grams'];
    $metal_type = $_POST['metal_type'];
    $design_style = $_POST['design_style'];
    $occasion = $_POST['occasion'];
    $is_trending = isset($_POST['is_trending']) ? true : false;
    $collection_key = $_POST['collection_key'];
    $stock_quantity = $_POST['stock_quantity'];

   
    $image_url_main = $product['image_url_main']; 
    $images_gallery_pg = $product['images_gallery'];

    
    if(isset($_FILES['image_main']) && $_FILES['image_main']['error'] == 0 && $_FILES['image_main']['size'] > 0){
        $ext = pathinfo($_FILES['image_main']['name'], PATHINFO_EXTENSION);
        $main_file_name = uniqid('main_').'.'.$ext;
        
        move_uploaded_file($_FILES['image_main']['tmp_name'], $upload_dir.$main_file_name);
        
       
        $image_url_main = $BASE_UPLOAD_PATH . $main_file_name; 
        
    }

    $raw_pg_array = trim($product['images_gallery'] ?? '', '{}');
    $current_gallery_urls = array_filter(array_map('trim', explode(',', $raw_pg_array)));
    
    
    $current_gallery_urls = 
	array_map(function($url)
	{ 
        return trim($url, '"');
    }, $current_gallery_urls);
    
   
    if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images']))
	{
        $images_to_delete = $_POST['delete_gallery_images'];
        
        
        $current_gallery_urls = array_filter($current_gallery_urls, function($url) use ($images_to_delete)
		{
            
            return !in_array($url, $images_to_delete);
        });
       
    }
   
    
    $new_gallery_urls = [];
    
    if(isset($_FILES['images_gallery']) && !empty(array_filter($_FILES['images_gallery']['name']))){

        
        foreach($_FILES['images_gallery']['tmp_name'] as $key => $tmp_name)
		{
            if($_FILES['images_gallery']['error'][$key] === 0 && $_FILES['images_gallery']['size'][$key] > 0)
			{
                $ext = pathinfo($_FILES['images_gallery']['name'][$key], PATHINFO_EXTENSION);
                $file_name = uniqid('gallery_').'.'.$ext;
                
                move_uploaded_file($tmp_name, $upload_dir.$file_name);
                
               
                $new_gallery_urls[] = $BASE_UPLOAD_PATH . $file_name;
            }
        }
    }
    
   
    $final_gallery_urls = array_merge($current_gallery_urls, $new_gallery_urls);

    
    if ($final_gallery_urls) {
        $quoted_urls = 
		array_map(function($url) 
		{ 
            return '"' . str_replace('"', '\"', $url) . '"'; 
        }, $final_gallery_urls);
        
        $images_gallery_pg = '{' . implode(',', $quoted_urls) . '}';
    } else {
        $images_gallery_pg = '{}'; 
    }
    
    $query = "UPDATE products SET 
              name = :name, description = :description, price = :price, 
              weight_grams = :weight_grams, metal_type = :metal_type, design_style = :design_style, 
              occasion = :occasion, is_trending = :is_trending, collection_key = :collection_key, 
              image_url_main = :image_url_main, images_gallery = :images_gallery, stock_quantity = :stock_quantity
              WHERE product_id = :id";
    
    
    $stmt = $conn->prepare($query); 
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':weight_grams', $weight_grams);
    $stmt->bindParam(':metal_type', $metal_type);
    $stmt->bindParam(':design_style', $design_style);
    $stmt->bindParam(':occasion', $occasion);
    $stmt->bindParam(':is_trending', $is_trending, PDO::PARAM_BOOL);
    $stmt->bindParam(':collection_key', $collection_key);
    $stmt->bindParam(':image_url_main', $image_url_main);
    $stmt->bindParam(':images_gallery', $images_gallery_pg); 
    $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $msg = "<p class='success'>✅ Product ID $product_id updated successfully! <a href='view_products.php'>View Products</a></p>";
        
        
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :id");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $msg = "<p class='error'>❌ Error updating product: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product (ID: <?php echo $product_id; ?>)</title>
    <link href="https://fonts.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #40739e;
            --secondary-color: #2f3640;
            --success-color: #44bd32;
            --error-color: #e84118;
            --bg-color: #f5f6fa;
        }
        body { font-family:'Roboto', sans-serif; background:var(--bg-color); margin:0; padding:20px; }
        .container { max-width:850px; margin:30px auto; background:#fff; padding:40px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1);}
        h1 { text-align:center; color:var(--primary-color); margin-bottom:30px; border-bottom: 2px solid #EEE; padding-bottom: 10px;}
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px;}
        form label { display:block; margin-top:15px; font-weight:600; color:var(--secondary-color);}
        form input:not([type="checkbox"]), form textarea, form select {
            width:100%; padding:10px 12px; margin-top:5px; border-radius:6px; border:1px solid #dcdde1; font-size:14px; box-sizing: border-box;
        }
        form textarea { resize:vertical; min-height:80px;}
        .full-width { grid-column: 1 / -1; }
        .image-section { border: 1px solid #CCC; padding: 15px; border-radius: 8px; margin-top: 15px; background: #FAFAFA;}
        .image-section h3 { margin-top: 0; color: var(--secondary-color); font-size: 1.1em;}
        .image-preview { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px;}
        .image-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #DDD;}
        .action-btns { display: flex; gap: 10px; margin-top: 25px;}
        button.update { flex: 1; padding:12px; background:var(--primary-color); color:#fff; font-size:16px; font-weight:600; border:none; border-radius:8px; cursor:pointer; transition:0.3s;}
        button.update:hover { background:#306089; }
        .success { color:var(--success-color); text-align:center; font-weight:600; margin-top:20px;}
        .error { color:var(--error-color); text-align:center; font-weight:600; margin-top:20px;}
    </style>
</head>
<body>
<div class="container">
<h1>Edit Product: #<?php echo $product_id; ?> (<?php echo htmlspecialchars($product['name']); ?>)</h1>
<?php if($msg) echo $msg; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="form-grid">
        <div>
            <label>Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
        
            <label>Price:</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required>
        
            <label>Metal Type:</label>
            <input type="text" name="metal_type" value="<?php echo htmlspecialchars($product['metal_type'] ?? ''); ?>" required>
        
            <label>Design Style:</label>
            <input type="text" name="design_style" value="<?php echo htmlspecialchars($product['design_style'] ?? ''); ?>">

            <label>Stock Quantity:</label>
            <input type="number" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity'] ?? ''); ?>">
        </div>

        <div>
            <label>Weight (grams):</label>
            <input type="number" step="0.01" name="weight_grams" value="<?php echo htmlspecialchars($product['weight_grams'] ?? ''); ?>">
            
            <label>Occasion:</label>
            <input type="text" name="occasion" value="<?php echo htmlspecialchars($product['occasion'] ?? ''); ?>">
            
            <label>Collection Key:</label>
            <input type="text" name="collection_key" value="<?php echo htmlspecialchars($product['collection_key'] ?? ''); ?>">

            <label><input type="checkbox" name="is_trending" <?php echo ($product['is_trending'] ?? 0) ? 'checked' : ''; ?>> Is Trending Product</label>
        </div>

        <div class="full-width">
            <label>Description:</label>
            <textarea name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>

        <div class="full-width image-section">
            <h3>Image Management (Current Images)</h3>

            <label>Current Main Image:</label>
            <div class="image-preview">
                <?php if ($product['image_url_main']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url_main']); ?>" alt="Main Image">
                <?php else: ?>
                    <p>No main image uploaded.</p>
                <?php endif; ?>
            </div>
            <label>Replace Main Image:</label>
            <input type="file" name="image_main" accept="image/*">
            <small style="color: #666;">(New file will replace the current main image.)</small>


            <label style="margin-top:25px;">Current Gallery Images:</label>
            <div class="image-preview">
                <?php 
                
                $raw_pg_array = trim($product['images_gallery'] ?? '', '{}');
                $gallery_urls_display = array_filter(array_map('trim', explode(',', $raw_pg_array)));
                $gallery_urls_display = array_map(function($url) { return trim($url, '"'); }, $gallery_urls_display); // Clean up quotes
                
                if (is_array($gallery_urls_display) && count($gallery_urls_display) > 0): 
                    foreach ($gallery_urls_display as $index => $url): 
                        if (!empty($url)):
                            ?>
                            <div style="position: relative; border: 1px solid #DDD; padding: 5px; border-radius: 6px;">
                                <img src="<?php echo htmlspecialchars(trim($url)); ?>" alt="Gallery Image <?php echo $index+1; ?>" style="width: 80px; height: 80px; object-fit: cover; display: block;">
                                
                                <label style="margin-top: 5px; font-weight: normal; font-size: 0.85em; display: flex; align-items: center; color: var(--error-color);">
                                    <input type="checkbox" name="delete_gallery_images[]" value="<?php echo htmlspecialchars(trim($url)); ?>" style="width: auto; margin: 0 5px 0 0;">
                                    Delete
                                </label>
                            </div>
                            <?php 
                        endif;
                    endforeach; 
                else: ?>
                    <p>No gallery images uploaded.</p>
                <?php endif; ?>
            </div>
            <label>Add More Gallery Images:</label>
            <input type="file" name="images_gallery[]" multiple accept="image/*">
            <small style="color: #666;">(New files will be **added** to the existing gallery. Check 'Delete' above to remove existing ones.)</small>
        </div>
    </div>
    
    <div class="action-btns">
        <button type="submit" name="update_product" class="update">Update Product</button>
        <a href="view_products.php" style="flex: 1; padding:12px; margin-top: 25px; background:#e84118; color:#fff; font-size:16px; font-weight:600; border-radius:8px; text-decoration: none; text-align: center; transition:0.3s;">Cancel / Back</a>
    </div>
</form>
</div>
</body>
</html>