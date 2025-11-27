<?php

session_start();

include('db.php');

$user_id = $_SESSION['user_id'] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    
    $remove_id = intval($_POST['remove_id']);

    if ($user_id) {
        try {
           
            $delete_sql = "DELETE FROM cart WHERE user_id = :uid AND product_id = :pid";
            $stmt = $conn->prepare($delete_sql);
            $stmt->execute([':uid' => $user_id, ':pid' => $remove_id]);

           
            $_SESSION['message'] = "✅ Product removed from cart.";
        } catch (PDOException $e) {
            
            $_SESSION['message'] = "❌ Database Error: Could not remove item.";
        }
    } else {
        
        $_SESSION['message'] = "❌ Please login to manage your cart.";
    }

    
    header('Location: cart.php');
    exit;
}



$products_in_cart = [];
$total = 0.0;

if ($user_id)
	{
    try {
        
        $sql = "SELECT p.product_id, p.name, p.price, p.image_url_main, c.quantity
                FROM cart c
                JOIN products p ON c.product_id = p.product_id
                WHERE c.user_id = :uid
                ORDER BY c.added_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':uid' => $user_id]);
        $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

       
        foreach ($db_products as $product) 
		{
            $product['subtotal'] = (float) $product['price'] * (int) $product['quantity'];
            $total += $product['subtotal'];
            $products_in_cart[] = $product;
        }
    } catch (PDOException $e) {
       
        $products_in_cart = [];
        $_SESSION['message'] = "❌ Error fetching cart data from database.";
    }
}


$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | SuvarnaKart</title>
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Theme color variables */
        :root {
            --primary-color: #C0A87A;
            /* gold */
            --primary-dark: #A3855F;
            --secondary-color: #1A1A1A;
            --bg-color: #FDFCF8;
            --card-bg: #FFFFFF;
            --header-bg: #1A1A1A;
            --error-red: #8D0B1C;
            --success-green: #28a745;
        }

        /* Basic page styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-color);
            margin: 0;
            color: var(--secondary-color);
            line-height: 1.6;
        }

        /* Header */
        header {
            background: var(--header-bg);
            color: white;
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        header div:first-child {
            font-size: 1.8em;
            font-weight: 800;
            color: var(--primary-color);
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
        }

        header a:hover {
            color: var(--primary-color);
        }

        /* Page container */
        .container {
            padding: 40px 60px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: var(--secondary-color);
            margin-bottom: 25px;
            font-size: 2.2em;
            font-weight: 700;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 10px;
            display: inline-block;
        }

        /* Flash messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .message.error {
            background-color: #fcebeb;
            color: var(--error-red);
            border-left: 5px solid var(--error-red);
        }

        .message.success {
            background-color: #e6f7d9;
            color: #4b8b4c;
            border-left: 5px solid #4b8b4c;
        }

        /* Main layout: cart items and order summary */
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            align-items: start;
        }

        /* Product card */
        .product-card {
            display: flex;
            align-items: center;
            background: var(--card-bg);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #EFEFEF;
            transition: border-color 0.3s;
        }

        .product-card:hover {
            border-color: var(--primary-color);
        }

        .product-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 30px;
            border: 1px solid var(--primary-color);
        }

        .product-info {
            flex: 1;
        }

        .product-info h3 {
            margin: 0 0 5px 0;
            color: var(--secondary-color);
            font-weight: 700;
            font-size: 1.2em;
        }

        .product-info p {
            margin: 6px 0;
            color: #666;
        }

        .product-info .subtotal-display {
            font-weight: 800;
            color: var(--primary-color);
            font-size: 1.1em;
            margin-top: 10px;
        }

        /* Actions area (checkout single item / remove) */
        .card-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 150px;
        }

        .remove-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            background: var(--error-red);
            color: white;
            cursor: pointer;
            font-weight: 600;
        }

        .remove-btn:hover {
            background: #6D0816;
            transform: translateY(-1px);
        }

        .buy-this-item-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            background: var(--success-green);
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 600;
        }

        .buy-this-item-btn:hover {
            background: #1e7e34;
            transform: translateY(-1px);
        }

        /* Total summary styling */
        .total-summary {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            border: 2px solid var(--primary-color);
        }

        .total {
            font-size: 1.8em;
            font-weight: 800;
            text-align: right;
            color: var(--secondary-color);
            margin-bottom: 20px;
            padding-top: 10px;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px 20px;
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
            font-weight: 700;
        }

        .checkout-btn:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive behavior */
        @media (max-width:1024px) {
            .container {
                padding: 30px;
            }

            .cart-layout {
                grid-template-columns: 1fr;
            }

            .total-summary {
                position: static;
                margin-top: 30px;
            }
        }

        @media (max-width:600px) {

            header,
            .container {
                padding: 15px 20px;
            }

            .product-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }

            .product-card img {
                width: 80px;
                height: 80px;
                margin-right: 0;
                margin-bottom: 15px;
            }

            .card-actions {
                flex-direction: row;
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>

<body>

    <!-- Top navigation/header -->
    <header>
        <div>💎 SuvarnaKart</div>
        <div>
            <a href="index1.php"><i class="fas fa-home"></i> Home</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
        </div>
    </header>

    <div class="container">
        <!-- Page title -->
        <h2>Shopping Cart</h2>

        <!-- Show flash message if present -->
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '❌') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- If user not logged in, show prompt -->
        <?php if (!$user_id): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error:</strong> You are not logged in. Your cart is not visible.
                <a href="login.php" style="color: #8D0B1C; text-decoration: underline;"> Please login to view your cart.</a>
            </div>
        <?php endif; ?>

        <!-- If there are items in the cart, list them -->
        <?php if (!empty($products_in_cart)): ?>

            <div class="cart-layout">
                <!-- Left column: cart items -->
                <div class="cart-items">
                    <?php foreach ($products_in_cart as $p): ?>
                        <div class="product-card">
                            <!-- Product image -->
                            <img src="<?php echo htmlspecialchars($p['image_url_main']); ?>"
                                alt="<?php echo htmlspecialchars($p['name']); ?>">

                            <!-- Product information -->
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                                <p><i class="fas fa-tag"></i> Price (per item): ₹<?php echo number_format($p['price']); ?></p>
                                <p class="quantity-display"><i class="fas fa-cube"></i> Quantity:
                                    <?php echo (int) $p['quantity']; ?></p>
                                <p class="subtotal-display"><i class="fas fa-rupee-sign"></i> Subtotal:
                                    ₹<?php echo number_format($p['subtotal']); ?></p>
                            </div>

                            <!-- Actions: buy single item or remove -->
                            <div class="card-actions">
                                <!-- Link to checkout for this product only -->
                                <a href="checkout.php?product_id=<?php echo $p['product_id']; ?>" class="buy-this-item-btn">
                                    <i class="fas fa-bolt"></i> Buy This Item
                                </a>

                                <!-- Form to remove the item from the cart -->
                                <form method="post" action="cart.php" style="margin:0;">
                                    <input type="hidden" name="remove_id" value="<?php echo $p['product_id']; ?>">
                                    <button type="submit" class="remove-btn"><i class="fas fa-trash-alt"></i> Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Right column: order summary -->
                <div class="total-summary">
                    <h4><i class="fas fa-file-invoice-dollar"></i> Order Summary</h4>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:1em;">
                        <span>Item Count:</span>
                        <span style="font-weight:600;"><?php echo count($products_in_cart); ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:25px; font-size:1em;">
                        <span>Shipping Estimate:</span>
                        <span style="font-weight:600; color:#4b8b4c;">FREE</span>
                    </div>

                    <div class="total">Total: ₹<?php echo number_format($total); ?></div>

                  
                    <form action="checkout.php" method="POST">
                        <button type="submit" class="checkout-btn"><i class="fas fa-credit-card"></i> Proceed to Checkout
                            (All Items)</button>
                    </form>

                    <p style="text-align:center; margin-top:15px; font-size:0.9em; color:#999;">Prices include GST.</p>
                </div>
            </div>

        <?php elseif ($user_id): ?>
          
            <p>Your cart is empty. <a href="index1.php" style="color: var(--primary-color); font-weight: 600;">Continue
                    Shopping</a></p>
        <?php endif; ?>

    </div>

    <?php
	include 'footer.php'; ?>

</body>

</html>