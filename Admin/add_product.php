<?php
// PostgreSQL PDO connection (db.php मध्ये $conn = new PDO(); असणे आवश्यक)
include("../project/db.php");

$upload_dir = "../uploads/";  // ROOT/project/uploads/
if (!is_dir($upload_dir))
    mkdir($upload_dir, 0755, true);

$msg = "";

// ---------------------------------------------
// FORM SUBMIT
// ---------------------------------------------
if (isset($_POST['submit'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $weight_grams = floatval($_POST['weight_grams']);
    $metal_type = $_POST['metal_type'];
    $design_style = $_POST['design_style'];
    $occasion = $_POST['occasion'];
    $is_trending = isset($_POST['is_trending']) ? true : false;
    $collection_key = $_POST['collection_key'];
    $stock_quantity = intval($_POST['stock_quantity']);

    if (empty($name) || empty($metal_type) || $price <= 0) {
        $msg = "<p class='error'>⚠ Required fields missing</p>";
    } else {

        // ---------------------------------------------
        // MAIN IMAGE UPLOAD (ORIGINAL NAME + REPLACE)
        // ---------------------------------------------
        $image_url_main = null;

        if (!empty($_FILES['image_main']['name'])) {

            $original_name = $_FILES['image_main']['name'];  // ORIGINAL FILE NAME
            $target = $upload_dir . $original_name;          // FULL PATH

            // Upload (replace if exists)
            move_uploaded_file($_FILES['image_main']['tmp_name'], $target);

            // Correct path for DB (front/admin both)
            $image_url_main = "../uploads/" . $original_name;
        }

        // ---------------------------------------------
        // GALLERY IMAGES UPLOAD
        // ---------------------------------------------
        $gallery_urls = [];
        $images_gallery_pg = "{}";

        if (!empty($_FILES['images_gallery']['name'][0])) {

            foreach ($_FILES['images_gallery']['name'] as $key => $orig_name) {

                if ($_FILES['images_gallery']['error'][$key] === 0) {

                    $target_gal = $upload_dir . $orig_name;

                    move_uploaded_file($_FILES['images_gallery']['tmp_name'][$key], $target_gal);

                    // Save ORIGINAL name
                    $gallery_urls[] = "../uploads/" . $orig_name;
                }
            }

            // Convert to PostgreSQL array
            $quoted = array_map(fn($x) => '"' . $x . '"', $gallery_urls);
            $images_gallery_pg = '{' . implode(",", $quoted) . '}';
        }

        // ---------------------------------------------
        // DB INSERT
        // ---------------------------------------------
        $sql = "INSERT INTO products 
        (name, description, price, weight_grams, metal_type, design_style, occasion,
         is_trending, collection_key, image_url_main, images_gallery, stock_quantity)
        VALUES
        (:name, :description, :price, :weight_grams, :metal_type, :design_style, :occasion,
         :is_trending, :collection_key, :image_url_main, :images_gallery, :stock_quantity)";

        $stmt = $conn->prepare($sql);

        // Bind values
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":weight_grams", $weight_grams);
        $stmt->bindParam(":metal_type", $metal_type);
        $stmt->bindParam(":design_style", $design_style);
        $stmt->bindParam(":occasion", $occasion);
        $stmt->bindParam(":is_trending", $is_trending, PDO::PARAM_BOOL);
        $stmt->bindParam(":collection_key", $collection_key);
        $stmt->bindParam(":image_url_main", $image_url_main);
        $stmt->bindParam(":images_gallery", $images_gallery_pg);
        $stmt->bindParam(":stock_quantity", $stock_quantity);

        try {
            $stmt->execute();
            $msg = "<p class='success'>✅ Product Added Successfully!</p>";
            $_POST = [];  // clear form
        } catch (PDOException $e) {
            $msg = "<p class='error'>❌ DB Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Product — Admin</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --card: #ffffff;
            --accent: #0d9488;
            --accent-dark: #076a63;
            --muted: #6b7280;
            --danger: #dc2626;
            --radius: 10px;
        }

        * {
            box-sizing: border-box
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            background: var(--bg);
            margin: 0;
            padding: 30px;
            color: #111827
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto
        }

        .card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.06)
        }

        h1 {
            margin: 0 0 14px;
            font-size: 22px;
            color: var(--accent-dark)
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 12px
        }

        label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 600
        }

        input[type=text],
        input[type=number],
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            font-size: 14px;
            color: #111827
        }

        textarea {
            min-height: 120px;
            resize: vertical
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .controls {
            display: flex;
            gap: 12px;
            align-items: center
        }

        .btn {
            background: var(--accent);
            color: white;
            padding: 11px 14px;
            border-radius: 8px;
            border: 0;
            cursor: pointer;
            font-weight: 700
        }

        .btn.secondary {
            background: #f3f4f6;
            color: #111827
        }

        .note {
            font-size: 13px;
            color: var(--muted)
        }

        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 12px
        }

        .preview {
            background: #fafafa;
            border: 1px dashed #e6e9ef;
            padding: 12px;
            border-radius: 8px;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            justify-content: center
        }

        .preview img {
            max-width: 100%;
            max-height: 140px;
            border-radius: 6px;
            object-fit: contain
        }

        .thumbs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .thumbs img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee
        }

        .message {
            padding: 10px;
            border-radius: 8px
        }

        .success {
            background: #ecfdf5;
            color: #065f46
        }

        .error {
            background: #fff1f2;
            color: var(--danger)
        }

        @media (max-width:980px) {
            .grid {
                grid-template-columns: 1fr
            }

            .right-panel {
                order: 2
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <h1>Add New Product</h1>

            <?php if ($msg)
                echo "<div class='message " . (strpos($msg, '✅') !== false ? 'success' : 'error') . "'>" . $msg . "</div>"; ?>

            <div class="grid">
                <div>
                    <form method="POST" enctype="multipart/form-data" id="productForm">

                        <div class="row">
                            <div>
                                <label for="name">Name *</label>
                                <input id="name" type="text" name="name" required
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>

                            <div>
                                <label for="price">Price (INR) *</label>
                                <input id="price" type="number" step="0.01" name="price" required
                                    value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                            </div>
                        </div>

                        <label for="description">Description</label>
                        <textarea id="description"
                            name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

                        <div class="row">
                            <div>
                                <label for="weight_grams">Weight (grams)</label>
                                <input id="weight_grams" type="number" step="0.01" name="weight_grams"
                                    value="<?php echo htmlspecialchars($_POST['weight_grams'] ?? ''); ?>">
                            </div>
                            <div>
                                <label for="stock_quantity">Stock Quantity</label>
                                <input id="stock_quantity" type="number" name="stock_quantity" min="0"
                                    value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div>
                                <label for="metal_type">Metal Type *</label>
                                <input type="text " list="metalList" id="metal_type" name="metal_type" required>


                                <datalist id="metalList">
                                    <option value="Gold 22K">
                                    <option value="Gold 18K">
                                    <option value="Silver">
                                    <option value="Platinum">
                                    <option value="Diamond"></option>
                                </datalist>


                            </div>
                            <div>
                                <label for="design_style">Design Style</label>
                                <input id="design_style" type="text" name="design_style"
                                    value="<?php echo htmlspecialchars($_POST['design_style'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div>
                                <label for="occasion">Occasion</label>
                                <input id="occasion" type="text" name="occasion"
                                    value="<?php echo htmlspecialchars($_POST['occasion'] ?? ''); ?>">
                            </div>
                            <div>
                                <label for="collection_key">Collection</label>
                                <input type="text" name="collection_key">

                            </div>
                        </div>

                        <label style="display:flex; align-items:center; gap:10px; font-weight:600"><input
                                type="checkbox" name="is_trending" <?php if (isset($_POST['is_trending']))
                                echo 'checked' ; ?>> Trending Product</label>

                        <label for="image_main">Main Product Image *</label>
                        <input id="image_main" type="file" name="image_main" accept="image/*" required>

                        <label for="images_gallery">Gallery Images (multiple)</label>
                        <input id="images_gallery" type="file" name="images_gallery[]" accept="image/*" multiple>

                        <div class="controls">
                            <button class="btn" type="submit" name="submit">Save Product</button>
                            <button class="btn secondary" type="button" id="resetBtn">Reset</button>
                        </div>

                    </form>
                </div>

                <aside class="right-panel">
                    <div class="preview card">
                        <div class="note">Preview Main Image</div>
                        <div id="mainPreview"
                            style="width:100%; display:flex; align-items:center; justify-content:center;">No image
                            selected</div>
                    </div>

                    <div class="preview card">
                        <div class="note">Gallery Thumbnails</div>
                        <div class="thumbs" id="galleryPreview">No gallery images</div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        // Client-side image preview helpers
        const mainInput = document.getElementById('image_main');
        const mainPreview = document.getElementById('mainPreview');
        const galleryInput = document.getElementById('images_gallery');
        const galleryPreview = document.getElementById('galleryPreview');
        const resetBtn = document.getElementById('resetBtn');

        function showMainPreview(file) {
            if (!file) { mainPreview.textContent = 'No image selected'; return; }
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);
            mainPreview.innerHTML = '';
            mainPreview.appendChild(img);
        }

        function showGallery(files) {
            galleryPreview.innerHTML = '';
            if (!files || files.length === 0) { galleryPreview.textContent = 'No gallery images'; return; }
            Array.from(files).forEach(f => {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                img.onload = () => URL.revokeObjectURL(img.src);
                galleryPreview.appendChild(img);
            });
        }

        mainInput.addEventListener('change', (e) => showMainPreview(e.target.files[0]));
        galleryInput.addEventListener('change', (e) => showGallery(e.target.files));
        resetBtn.addEventListener('click', () => {
            document.getElementById('productForm').reset();
            mainPreview.textContent = 'No image selected';
            galleryPreview.textContent = 'No gallery images';
        });
    </script>
</body>

</html>