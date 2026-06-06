<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor') {
    header("Location: login.php");
    exit;
}
?>

<h2>Welcome Donor, <?php echo $_SESSION['name']; ?> 🍽️</h2>
<a href="logout.php">Logout</a>
