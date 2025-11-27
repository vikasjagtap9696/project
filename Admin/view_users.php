<?php

include("../project/db.php");



$msg = "";


if(isset($_GET['delete'])){
    $id = $_GET['delete'];
   
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=:id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    try {
        $stmt->execute();
        $msg = "<p class='success'>User deleted successfully!</p>";
    } catch(PDOException $e) {
        $msg = "<p class='error'>Error: ".$e->getMessage()."</p>";
    }
}


$stmt = $conn->query("SELECT * FROM users ORDER BY user_id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Users</title>
    <style>
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f6fa; margin:0; padding:0;}
        .container { max-width:1000px; margin:50px auto; background:#fff; padding:30px 40px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1);}
        h1 { text-align:center; color:#2f3640; margin-bottom:30px;}
        table { width:100%; border-collapse: collapse;}
        th, td{ padding:12px 15px; border:1px solid #dcdde1; text-align:left;}
        th{ background:#40739e; color:white;}
        tr:nth-child(even){background:#f1f2f6;}
        a.button{ text-decoration:none; padding:6px 12px; border-radius:6px; color:#fff; font-weight:600; margin-right:5px; font-size:14px;}
        .edit{background-color:#44bd32;} .edit:hover{background-color:#4cd137;}
        .delete{background-color:#e84118;} .delete:hover{background-color:#c23616;}
        .success{color:#44bd32; text-align:center; font-weight:600; margin-top:15px;}
        .error{color:#e84118; text-align:center; font-weight:600; margin-top:15px;}
    </style>
</head>
<body>
<div class="container">
    <h1>All Users</h1>
    <?php if(isset($msg)) echo $msg; ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>First Name</th>
            <th>Phone</th>
            <th>Loyalty Points</th>
            <th>Actions</th>
        </tr>
        <?php if($users): ?>
            <?php foreach($users as $user): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                    <td><?php echo $user['loyalty_points']; ?></td>
                    <td>
                        <a class="button edit" href="edit_user.php?id=<?php echo $user['user_id']; ?>">Edit</a>
                        <a class="button delete" href="?delete=<?php echo $user['user_id']; ?>" onclick="return confirm('Are you sure to delete this user?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No users found.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>