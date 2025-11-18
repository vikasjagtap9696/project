<?php
// db.php file include kara. Connection variable $conn ahe.
include("../project/db.php");

// ---------------------------------------------------
// 1. Fetch Summary Data (Dashboard Cards sathi)
// ---------------------------------------------------
// Note: Dashboard data ha main page var display zala pahije.
// Tya sathi dashboard chi summary ithech fetch karu.
try {
    // Total Products
    $stmt_products = $conn->query("SELECT COUNT(*) AS total_products FROM products");
    $counts = $stmt_products->fetch(PDO::FETCH_ASSOC);
    $total_products = $counts['total_products'];

    // Total Orders
    $stmt_orders = $conn->query("SELECT COUNT(*) AS total_orders FROM orders");
    $counts = $stmt_orders->fetch(PDO::FETCH_ASSOC);
    $total_orders = $counts['total_orders'];

    // Total Users
    $stmt_users = $conn->query("SELECT COUNT(*) AS total_users FROM users");
    $counts = $stmt_users->fetch(PDO::FETCH_ASSOC);
    $total_users = $counts['total_users'];
    
    // Recent Orders (Fakt Dashboard display sathi)
    $stmt_recent_orders = $conn->query("SELECT order_id, total_amount, status, order_date FROM orders ORDER BY order_date DESC LIMIT 5");
    $recent_orders = $stmt_recent_orders->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Database connection chukicha zala tar error disel
    $db_error = "Database Error: " . $e->getMessage();
    $total_products = $total_orders = $total_users = 'N/A';
    $recent_orders = [];
}

// Default content URL (jevha dashboard first time open hoto)
$default_page = 'dashboard_summary.php'; 
// Note: Tumhala navin 'dashboard_summary.php' file banvavi lagel, 
// jyat khali diela main content (cards ani recent orders) asel. 
// Pan sadhya sathi, apan 'view_products.php' as default thevu.
$default_page = 'view_products.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard | Gattu Goldshop</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00897b; /* Teal */
            --primary-light: #4db6ac;
            --secondary-color: #263238; /* Blue Grey */
            --bg-color: #f4f6f9;
            --card-bg: #ffffff;
            --danger-color: #e57373;
            --warning-color: #ffb74d;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            display: flex; /* Sidebar ani Main content sathi */
            min-height: 100vh;
        }

        /* --- Sidebar Navigation --- */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: #ffffff;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            position: sticky; /* Sidebar tikun rahil */
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            font-weight: 700;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li a {
            display: block;
            padding: 15px 20px;
            color: #cfd8dc;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
            border-left: 5px solid transparent;
            font-weight: 500;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #37474f;
            color: #ffffff;
            border-left: 5px solid var(--primary-color);
        }
        
        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        /* --- Main Content Area for iFrame --- */
        .main-content {
            flex-grow: 1;
            padding: 0; /* Padding iFrame chya aat madhe asel */
            display: flex;
            flex-direction: column;
        }

        /* Navin iFrame Style */
        #content-iframe {
            width: 100%;
            flex-grow: 1;
            border: none;
            min-height: calc(100vh - 0px); /* IFrame height full screen sathi */
            background-color: var(--bg-color);
            padding: 20px;
            box-sizing: border-box;
        }

    </style>
</head>
<body>

    <div class="sidebar">
        <h2> ADMIN</h2>
        <ul>
            <li><a href="view_products.php" target="content-iframe" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            
            <li><a href="view_products.php" target="content-iframe"><i class="fas fa-gem"></i> Products</a></li>
            
            <li><a href="add_product.php" target="content-iframe"><i class="fas fa-plus-circle"></i> Add Product</a></li>
            
            <li><a href="view_orders.php" target="content-iframe"><i class="fas fa-clipboard-list"></i> Orders</a></li>
            
            <li><a href="view_users.php" target="content-iframe"><i class="fas fa-users"></i> Users</a></li>
            
            
        </ul>
    </div>

    <div class="main-content">
        <iframe 
            id="content-iframe" 
            name="content-iframe" 
            src="<?php echo $default_page; ?>" 
            title="Admin Content Frame">
        </iframe>
    </div>
    
    <script>
        // Sidebar link active karanyasathi JavaScript (optional but good for UX)
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarLinks = document.querySelectorAll('.sidebar ul li a');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove 'active' class from all links
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add 'active' class to the clicked link
                    this.classList.add('active');
                });
            });
            
            // Jevha page load hoto, ti link active thevaychi
            const currentUrl = document.getElementById('content-iframe').src.split('/').pop();
            sidebarLinks.forEach(link => {
                if (link.getAttribute('href') === currentUrl) {
                    link.classList.add('active');
                }
            });
            
            // Note: Tumhala 'view_products.php' (default page) chya aat madhe body tag chi margin/padding remove karavi lagel.
        });
    </script>
</body>
</html>