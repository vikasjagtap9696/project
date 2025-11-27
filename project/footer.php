<?php

?>
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h4>SuvarnaKart</h4>
            <p>The finest online store for handcrafted gold and diamond jewellery.</p>
            <div class="social-icons" style="margin-top: 15px;">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <a href="products.php">All Products</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact & Support</a>
        </div>
        <div class="footer-section">
            <h4>Customer Service</h4>
            <a href="return.php">Returns & Exchange</a>
            <a href="shipping.php">Shipping Policy</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
        <div class="footer-section">
            <h4>Get in Touch</h4>
            <p><i class="fas fa-envelope"></i> Email: support@suvarnakart.com</p>
            <p><i class="fas fa-phone"></i> Phone: +91 9145317002</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> SuvarnaKart. All rights reserved. | Handcrafted with <i class="fas fa-heart" style="color: red;"></i> in India.
    </div>
</footer>

<style>
footer {
    background: #1A1A1A;
    color: #CCCCCC;
    padding: 50px 60px;
    border-top: 5px solid #C0A87A;
    font-size: 0.9em;
    line-height: 1.8;
}
.footer-content {
    display: flex;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto 30px;
}
.footer-section { flex: 1; padding: 0 20px; }
.footer-section h4 { color: #C0A87A; font-size: 1.2em; border-bottom: 2px solid #A3855F; padding-bottom: 5px; margin-bottom: 15px; }
.footer-section a { color: #AAA; text-decoration: none; display: block; margin-bottom: 5px; transition: color 0.3s; }
.footer-section a:hover { color: #C0A87A; }
.footer-bottom { text-align: center; border-top: 1px solid #333; padding-top: 20px; }
@media (max-width: 768px) {
    .footer-content { flex-direction: column; gap: 30px; }
    footer { padding: 30px 20px; }
}
</style>

<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
