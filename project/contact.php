<?php
session_start();


$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $query = trim($_POST['query'] ?? '');

    if (empty($name) || empty($email) || empty($query)) {
        $message = '<div class="alert error">Please fill in all required fields.</div>';
    } else {
        
        $message = '<div class="alert success">Thank you, ' . htmlspecialchars($name) . '! Your query has been received. We will respond to ' . htmlspecialchars($email) . ' within 24 hours.</div>';
        
        
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | SuvarnaKart Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Base Styles matching other pages */
        :root {
            --primary-color: #D4AF37; /* Soft Gold */
            --secondary-color: #333333; /* Deep Charcoal */
            --bg-color: #F8F8F8;
            --header-bg: #333333;
            --light-gold: #FFFBEA;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-color);
            margin: 0;
            color: var(--secondary-color);
            line-height: 1.6;
        }

        header {
            background: var(--header-bg);
            color: white;
            padding: 18px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        header div:first-child {
            font-size: 1.5em;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary-color);
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: color 0.3s;
        }

        header a:hover {
            color: var(--primary-color);
        }

        .container {
            padding: 50px 60px;
            max-width: 1200px;
            margin: auto;
        }

        h1 {
            text-align: center;
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 40px;
            border-bottom: 2px solid #EEE;
            padding-bottom: 10px;
        }
        
        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr; /* Smaller column for details, larger for form */
            gap: 40px;
            margin-bottom: 50px;
        }

        /* Detail Block (Left Side) */
        .contact-details h3 {
            color: var(--secondary-color);
            font-size: 1.5em;
            margin-top: 0;
            border-left: 4px solid var(--primary-color);
            padding-left: 10px;
            margin-bottom: 20px;
        }

        .info-item {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .info-item strong {
            display: block;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        /* Contact Form (Right Side) */
        .contact-form {
            background: var(--light-gold);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form select,
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #CCC;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
        }

        .contact-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #A67C00;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #EAEAEA;
        }
        .faq-section h2 {
            text-align: center;
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 30px;
        }
        .faq-item {
            background: white;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        .faq-item strong {
            color: var(--primary-color);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            header, .container {
                padding: 15px 20px;
            }
        }
    </style>
</head>

<body>

    <header>
        <div>💎 SuvarnaKart</div>
        <div>
            <a href="index1.php">Home</a>
            <a href="products.php">Products</a>
            <a href="cart.php">Cart</a>
        </div>
    </header>

    <div class="container">
        <h1>Connect with SuvarnaKart Support</h1>
        
        <?php echo $message; 

        <div class="contact-grid">
            
            <div class="contact-details">
                <h3>Direct Contact Information</h3>
                <p>We are here to help! Choose the contact method that works best for you.</p>

                <div class="info-item">
                    <strong>Customer Support (Pre/Post-Sale)</strong>
                    <p>📧 Email: <a href="mailto:support@suvarnakart.com" style="color:#555;">support@suvarnakart.com</a></p>
                    <p>📞 Phone: +91 8888 777 666 (Mon-Sat, 10 AM - 6 PM IST)</p>
                </div>
                
                <div class="info-item">
                    <strong>Corporate & Press Enquiries</strong>
                    <p>📧 Email: <a href="mailto:corporate@suvarnakart.com" style="color:#555;">corporate@suvarnakart.com</a></p>
                </div>

                <div class="info-item">
                    <strong>Corporate Office (Mumbai)</strong>
                    <p>SuvarnaKart Jewelers Pvt. Ltd.<br>
                       201, Gold Tower, Bandra Kurla Complex,<br>
                       Bandra East, Mumbai - 400051, Maharashtra.
                    </p>
                </div>

            </div>
            
            <div class="contact-form">
                <h3>Send Us a Message</h3>
                <form method="POST" action="contact.php">
                    <label for="name">Your Name *</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    
                    <label for="email">Your Email *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject">
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Order Status">Order Status</option>
                        <option value="Return/Exchange">Return or Exchange</option>
                        <option value="Product Details">Product Details</option>
                    </select>
                    
                    <label for="query">Your Query / Message *</label>
                    <textarea id="query" name="query" required><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
                    
                    <button type="submit" name="submit_contact" class="submit-btn">Send Message</button>
                </form>
            </div>
            
        </div>
        
        <div class="faq-section">
            <h2>Frequently Asked Questions (FAQ)</h2>
            <div class="faq-item">
                <strong>Q: Are all your gold items BIS Hallmarked?</strong>
                <p>A: Yes, absolutely. Every piece of gold jewellery we sell is certified by the Bureau of Indian Standards (BIS).</p>
            </div>
            <div class="faq-item">
                <strong>Q: What is your return policy?</strong>
                <p>A: We offer a 7-day hassle-free return and exchange policy from the date of delivery. Please refer to our Returns page for full details.</p>
            </div>
            <div class="faq-item">
                <strong>Q: How can I track my order?</strong>
                <p>A: Once your order is shipped, you will receive an email and SMS with a tracking number and a link to the courier's website for real-time tracking.</p>
            </div>
        </div>

    </div>
    <?php include 'footer.php'; ?>

</body>

</html>