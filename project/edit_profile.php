<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("User not found!");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $address_line = $_POST['address_line'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];

    $photo_path = $user['profile_photo'];
    if (!empty($_FILES['profile_photo']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $file_name = time() . "_" . basename($_FILES["profile_photo"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
            $photo_path = $target_file;
        }
    }

    $address_book = json_encode([[ 
        'line1' => $address_line, 
        'city' => $city, 
        'state' => $state, 
        'pincode' => $pincode 
    ]]);

    $update = $conn->prepare("
        UPDATE users
        SET first_name = :fname,
            email = :email,
            phone_number = :phone,
            dob = :dob,
            address_book = :address,
            profile_photo = :photo
        WHERE user_id = :id
    ");
    $update->execute([
        ':fname' => $first_name,
        ':email' => $email,
        ':phone' => $phone,
        ':dob' => $dob,
        ':address' => $address_book,
        ':photo' => $photo_path,
        ':id' => $user_id
    ]);

    header('Location: profile.php?updated=1');
    exit;
}

$address_book = json_decode($user['address_book'], true);
$address = $address_book[0] ?? ['line1'=>'','city'=>'','state'=>'','pincode'=>''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile | SuvarnaKart</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #fff9e6, #fbe5a2);
    margin: 0; padding: 0;
}
header {
    background: linear-gradient(90deg, #c29100, #e3b800);
    color: white; padding: 18px 40px;
    display: flex; justify-content: space-between; align-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
header h1 {
    margin: 0;
    font-weight: 700;
    font-size: 22px;
}
header a {
    color: white; text-decoration: none; margin-left: 20px;
    font-weight: 500; transition: 0.3s;
}
header a:hover { text-decoration: underline; }

.container {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    padding: 40px;
}
h2 {
    text-align: center;
    color: #a67c00;
    font-size: 24px;
    margin-bottom: 30px;
    text-shadow: 0 1px 0 #fff3b0;
}
.profile-preview {
    text-align: center;
    margin-bottom: 25px;
}
.profile-preview img {
    width: 120px; height: 120px;
    border-radius: 50%;
    border: 3px solid #c29100;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.profile-preview img:hover { transform: scale(1.05); }

form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 25px;
}
label {
    font-weight: 600;
    color: #4b3b00;
    font-size: 14px;
}
input[type="text"], input[type="email"], input[type="date"], input[type="file"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    width: 100%;
    background: #fffef8;
    transition: all 0.3s ease;
}
input:focus {
    border-color: #c29100;
    outline: none;
    box-shadow: 0 0 6px rgba(194,145,0,0.3);
}
button {
    grid-column: 1 / 3;
    background: linear-gradient(90deg, #c29100, #a67c00);
    color: white;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 17px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s;
}
button:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}
.back-btn {
    display: inline-block;
    background: #eee;
    color: #333;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
    margin-bottom: 20px;
}
.back-btn:hover {
    background: #ddd;
}
@media (max-width: 768px) {
    form { grid-template-columns: 1fr; }
    .container { padding: 25px; }
}
</style>
</head>
<body>

<header>
    <h1>💎 SuvarnaKart</h1>
    <div>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container">
    <a href="profile.php" class="back-btn">← Back to Profile</a>
    <h2>Edit Profile</h2>

    <div class="profile-preview">
        <img src="<?= htmlspecialchars($user['profile_photo'] ?? 'default_profile.png') ?>" alt="Profile Photo">
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Mobile Number</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone_number']) ?>" required>

        <label>Date of Birth</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($user['dob']) ?>">

        <label>Address Line</label>
        <input type="text" name="address_line" value="<?= htmlspecialchars($address['line1']) ?>">

        <label>City</label>
        <input type="text" name="city" value="<?= htmlspecialchars($address['city']) ?>">

        <label>State</label>
        <input type="text" name="state" value="<?= htmlspecialchars($address['state']) ?>">

        <label>Pincode</label>
        <input type="text" name="pincode" value="<?= htmlspecialchars($address['pincode']) ?>">

        <label>Profile Photo</label>
        <input type="file" name="profile_photo" accept="image/*">

        <button type="submit">💾 Save Changes</button>
    </form>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
