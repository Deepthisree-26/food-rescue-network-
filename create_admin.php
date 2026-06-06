<?php
require_once 'config.php';

// New credentials
$name = 'System Admin';
$email = 'admin@example.com';
$password = 'admin123';
$role = 'Admin';
$lat = 0;
$lng = 0;
$is_approved = 1;

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, latitude, longitude, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssddi", $name, $email, $hashed_password, $role, $lat, $lng, $is_approved);

if ($stmt->execute()) {
    echo "<h1>Admin Account Created!</h1>";
    echo "<p>You can now log in with the following credentials:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
    echo "<li><strong>Password:</strong> " . htmlspecialchars($password) . "</li>";
    echo "</ul>";
    echo "<a href='login.php'>Go to Login</a>";
    echo "<br><br><small>Please delete this file (create_admin.php) after use for security reasons.</small>";
} else {
    echo "<h1>Error creating admin account</h1>";
    echo "<p>Error details: " . $conn->error . "</p>";
}
?>
