<?php
session_start();
include('db.php');

$user_id = $_SESSION['user_id'] ?? 1;
$product_id = intval($_GET['product_id'] ?? 0);
if (!$product_id) {
    header("Location:index1.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id=?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product)
    die("Product not found.");

$quantity = max(1, intval($_POST['quantity'] ?? 1));
$total_amount = $product['price'] * $quantity;


$error = '';
 if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) 
    {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];

    if (!$full_name || !$email || !$phone || !$address) {
        $error = "Please fill all fields.";
    } else {
        $shipping_address = json_encode([
            'name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ]);

        $stmt = $conn->prepare("INSERT INTO orders(user_id,total_amount,status,shipping_address,payment_method)  VALUES(?,?,?,?,?)");
        $stmt->execute([$user_id, $total_amount, 'Pending', $shipping_address, $payment_method]);
        $order_id = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO order_items(order_id,product_id,quantity,unit_price_at_sale)
                                VALUES(?,?,?,?)");
        $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);

        if ($payment_method == 'COD') {
            header("Location: order_success.php?order_id=$order_id&method=COD");
            exit;
        } else {
            $_SESSION['order_id'] = $order_id;
            $_SESSION['total_amount'] = $total_amount;
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SuvarnaKart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        :root {
            --primary: #b8860b;
            --primary-light: #d4af37;
            --primary-dark: #8b6b00;
            --bg-gradient: linear-gradient(145deg, #fff8e1, #fff3d6);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 5px 20px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #333;
        }

        .page-header {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 20px 0;
            margin-bottom: 40px;
            box-shadow: var(--shadow-md);
        }

        .page-header h1 {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            font-size: 1.8em;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }

        .checkout-card {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .checkout-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            padding: 20px;
            font-size: 1.2em;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-dark);
            font-weight: 500;
        }

        .form-group i {
            position: absolute;
            left: 12px;
            top: 40px;
            color: #666;
        }

        input, select {
            width: 100%;
            padding: 12px 12px 12px 35px;
            border: 2px solid #eee;
            border-radius: var(--radius-sm);
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(184,134,11,0.1);
            outline: none;
        }

        .submit-btn {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(184,134,11,0.3);
        }

        .product-summary {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .product-box {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: transform 0.3s ease;
        }

        .product-box:hover {
            transform: translateX(5px);
        }

        .product-box img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
        }

        .product-info h4 {
            margin: 0 0 8px 0;
            color: var(--primary-dark);
            font-size: 1.1em;
        }

        .product-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }

        .price-tag {
            background: var(--primary-light);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-block;
            margin-top: 8px;
        }

        .summary {
            padding: 20px;
            background: #fafafa;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            color: #666;
        }

        .summary-row.total {
            border-top: 2px solid #eee;
            margin-top: 10px;
            padding-top: 15px;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.2em;
        }

        .error-msg {
            background: #fee;
            color: #e44;
            padding: 10px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.5em;
            }
            
            .card-body {
                padding: 20px;
            }

            .product-box {
                flex-direction: column;
                text-align: center;
            }

            .product-box img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body>

    <header class="page-header">
        <h1>
            <i class="fas fa-shopping-cart"></i> 
            Secure Checkout
        </h1>
    </header>

    <div class="container">
        <div class="checkout-card">
            <div class="card-header">
                <i class="fas fa-shipping-fast"></i> Shipping Details
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label>Delivery Address</label>
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="address" required>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <i class="fas fa-credit-card"></i>
                        <select name="payment_method" required>
                            <option value="Razorpay"> Pay Online (UPI/Card/Wallet)</option>
                            <option value="COD">Cash on Delivery</option>
                        </select>
                    </div>

                    <button type="submit" name="place_order" class="submit-btn">
                        <i class="fas fa-lock"></i> Place Secure Order
                    </button>
                </form>
            </div>
        </div>

        <div class="product-summary checkout-card">
            <div class="card-header">
                <i class="fas fa-receipt"></i> Order Summary
            </div>
            <div class="card-body">
                <div class="product-box">
                    <img src="<?php echo htmlspecialchars($product['image_url_main']); ?>" alt="Product Image">
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p>Quantity: <?php echo $quantity; ?></p>
                        <div class="price-tag">
                            ₹<?php echo number_format($product['price']); ?> per piece
                        </div>
                    </div>
                </div>

                <div class="summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($total_amount); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><strong>Free</strong></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>Included</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total_amount); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (isset($_SESSION['order_id']) && isset($_SESSION['total_amount'])):
        $order_id = $_SESSION['order_id'];
        $total_amount = $_SESSION['total_amount'];
        unset($_SESSION['order_id'], $_SESSION['total_amount']);
        ?>
        <script>
            var options = {
                "key": "rzp_test_RVowQdE4X3ETgJ",  
                "amount": "<?= $total_amount * 100 ?>",
                "currency": "INR",
                "name": "SuvarnaKart",
                "description": "Gold Purchase - Order #<?= $order_id ?>",
                "image": "https://yourwebsite.com/logo.png",
                "handler": function (response) {
                   
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'payment_process.php';
                    form.innerHTML = `
            <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
            <input type="hidden" name="razorpay_order_id" value="${response.razorpay_order_id}">
            <input type="hidden" name="razorpay_signature" value="${response.razorpay_signature}">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <input type="hidden" name="amount" value="<?= $total_amount ?>">
            <input type="hidden" name="currency" value="INR">
        `;
                    document.body.appendChild(form);
                    form.submit();
                },
                "theme": { "color": "#b8860b" },
                "prefill": {
                    "name": "<?= $_POST['full_name'] ?? 'Customer' ?>",
                    "email": "<?= $_POST['email'] ?? '' ?>",
                    "contact": "<?= $_POST['phone'] ?? '' ?>"
                },
                "method": {
                    "upi": true,
                    "card": true,
                    "netbanking": true,
                    "wallet": true
                }
            };
            var rzp1 = new Razorpay(options);
            rzp1.open();
        </script>
    <?php endif; ?>
    
<?php include 'footer.php'; ?>

</body>

</html>