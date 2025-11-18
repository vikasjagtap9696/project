<?php
// about_us.php

// You might include your standard header and footer files here
// session_start();
// include('header.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SuvarnaKart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* -------------------------------------------------- */
        /* GENERAL STYLES (MATCHING PROFILE.PHP THEME) */
        /* -------------------------------------------------- */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: linear-gradient(90deg, #a57b00, #d4af37); /* Gold Gradient */
            color: #fff;
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        header a {
            color: #fff;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        header a:hover {
            color: #fff9c4;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* -------------------------------------------------- */
        /* ABOUT US SPECIFIC STYLES */
        /* -------------------------------------------------- */
        .about-section {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .about-section h1 {
            color: #a57b00; /* Dark Gold */
            font-weight: 700;
            font-size: 2.8em;
            margin-bottom: 10px;
            border-bottom: 3px solid #d4af37; /* Gold underline */
            display: inline-block;
            padding-bottom: 5px;
        }

        .about-section h2 {
            color: #444;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .mission-vision-grid {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            text-align: left;
        }

        .mission-vision-item {
            flex: 1;
            padding: 30px;
            border-radius: 10px;
            background: #fff8e1; /* Light gold/cream background */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-top: 5px solid #d4af37;
            transition: transform 0.3s;
        }

        .mission-vision-item:hover {
            transform: translateY(-3px);
        }

        .mission-vision-item h3 {
            color: #a57b00;
            font-size: 1.5em;
            margin-top: 0;
        }

        .core-values {
            margin-top: 50px;
            text-align: center;
        }

        .value-card-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .value-card {
            width: 200px;
            padding: 20px;
            border-radius: 10px;
            background: #f9f9f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #d4af37;
        }

        .value-card .icon {
            font-size: 2em;
            color: #d4af37;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .mission-vision-grid {
                flex-direction: column;
            }
            .value-card-container {
                flex-direction: column;
                align-items: center;
            }
            .value-card {
                width: 90%;
            }
        }
    </style>
</head>

<body>

    <header>
        <div>💎 SuvarnaKart</div>
        <div>
            <a href="index1.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="about-section">
            <h1>About SuvarnaKart</h1>
            
            <p style="font-size: 1.1em; max-width: 800px; margin: 20px auto;">
                **SuvarnaKart** is not just an e-commerce website; it is a celebration of trust, purity, and traditional craftsmanship. 
                We are dedicated to providing you with the finest quality Gold, Silver, and precious jewelry, delivered securely right to your doorstep.
            </p>

            <div class="mission-vision-grid">
                <div class="mission-vision-item">
                    <h3>🎯 Our Mission</h3>
                    <p>To make high-quality, certified jewelry accessible to every Indian family at affordable prices with complete transparency and trust. We blend traditional art with modern designs.</p>
                </div>
                <div class="mission-vision-item">
                    <h3>🔭 Our Vision</h3>
                    <p>To become India's most trusted and preferred online jewelry brand, contributing to and becoming a part of every customer's life milestones.</p>
                </div>
            </div>
            
            <div class="core-values">
                <h2>⭐ Our Core Values</h2>
                <div class="value-card-container">
                    <div class="value-card">
                        <div class="icon">✨</div>
                        <h3>Purity</h3>
                        <p>We prioritize purity and certified hallmarking above all else in our product sourcing and creation.</p>
                    </div>
                    <div class="value-card">
                        <div class="icon">🤝</div>
                        <h3>Trust</h3>
                        <p>We operate with complete transparency, ensuring our customers have absolute faith in every transaction.</p>
                    </div>
                    <div class="value-card">
                        <div class="icon">🎨</div>
                        <h3>Excellence</h3>
                        <p>We maintain the highest standards of excellence in our craftsmanship, product design, and customer service.</p>
                    </div>
                </div>
            </div>

            <h2 style="margin-top: 50px;">🛍️ Why Shop with SuvarnaKart?</h2>
            <p style="max-width: 800px; margin: 15px auto 0;">
                **✓ Certified Products:** Every piece of jewelry comes with **BIS Hallmark** certification.<br>
                **✓ Secure Delivery:** Your product is shipped with secure and fully insured delivery.<br>
                **✓ 24/7 Support:** Our customer support team is always ready to assist you with any queries.
            </p>

        </div>
    </div>

    <?php 
    // include('footer.php'); 
    ?>
</body>
</html>