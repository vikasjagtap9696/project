<?php
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? null;
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? null;
    $razorpay_signature = $_POST['razorpay_signature'] ?? null;
    $order_id = $_POST['order_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $currency = $_POST['currency'] ?? 'INR';

    try {
        $stmt = $conn->prepare("INSERT INTO payments(order_id, razorpay_order_id, razorpay_payment_id, razorpay_signature, amount, currency, status)
                                VALUES(?,?,?,?,?,?, 'Success')");
        $stmt->execute([$order_id,$razorpay_order_id,$razorpay_payment_id,$razorpay_signature,$amount,$currency]);

        $update = $conn->prepare("UPDATE orders SET status='Paid' WHERE order_id=?");
        $update->execute([$order_id]);

        header("Location: order_success.php?order_id=$order_id&method=Razorpay");
        exit;
    } catch (PDOException $e) {
        echo "DB Error: " . $e->getMessage();
    }
}
?>
