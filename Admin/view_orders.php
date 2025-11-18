<?php
// FIX: $pdo chya jagi $conn vaparala
include("../project/db.php");


// Update order status
if(isset($_POST['update_status'])){
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    // FIX: $pdo chya jagi $conn vaparala
    $stmt = $conn->prepare("UPDATE orders SET status=:status WHERE order_id=:order_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    try {
        $stmt->execute();
        $msg = "<p class='success'>Order status updated!</p>";
    } catch(PDOException $e){
        $msg = "<p class='error'>Error: ".$e->getMessage()."</p>";
    }
}

// Fetch all orders
// FIX: $pdo chya jagi $conn vaparala
$stmt = $conn->query("SELECT * FROM orders ORDER BY order_id ASC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Orders</title>
    <style>
        body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f6fa; margin:0; padding:0;}
        .container{max-width:1200px; margin:50px auto; background:#fff; padding:30px 40px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
        h1{text-align:center; color:#2f3640; margin-bottom:30px;}
        table{width:100%; border-collapse:collapse;}
        th,td{padding:12px 15px; border:1px solid #dcdde1; text-align:left;}
        th{background:#40739e; color:white;}
        tr:nth-child(even){background:#f1f2f6;}
        select{padding:5px 8px; border-radius:5px;}
        button{padding:6px 12px; background:#4cd137; color:#fff; border:none; border-radius:6px; cursor:pointer;}
        button:hover{background:#44bd32;}
        .success{color:#44bd32; text-align:center; font-weight:600; margin-top:15px;}
        .error{color:#e84118; text-align:center; font-weight:600; margin-top:15px;}
    </style>
</head>
<body>
<div class="container">
    <h1>All Orders</h1>
    <?php if(isset($msg)) echo $msg; ?>
    <table>
        <tr>
            <th>Order ID</th>
            <th>User ID</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Order Date</th>
            <th>Update Status</th>
        </tr>
        <?php if($orders): ?>
            <?php foreach($orders as $order): ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['user_id'] ?: 'Guest'; ?></td>
                    <td><?php echo $order['total_amount']; ?></td>
                    <td><?php echo $order['status']; ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <select name="status">
                                <option value="Processing" <?php echo $order['status']=='Processing'?'selected':''; ?>>Processing</option>
                                <option value="Shipped" <?php echo $order['status']=='Shipped'?'selected':''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $order['status']=='Delivered'?'selected':''; ?>>Delivered</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No orders found.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>