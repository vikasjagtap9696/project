<?php
session_start();

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Optionally delete cookies (if any used for remember me)
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/');
}

// Redirect to login page (or homepage)
header("Location: login.php");
exit;
?>
