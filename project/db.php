 <!-- <?php
$host = "localhost";
$dbname = "goldshop";
$user = "postgres";
$pass = "9696";  

try {
    $conn = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>  -->

<?php
$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");

$conn = new PDO("pgsql:host=$host;port=$port;dbname=$db;user=$user;password=$pass");

?>