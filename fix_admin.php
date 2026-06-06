<?php
require_once 'config.php';

echo "<h2>Admin Account Repair Tool</h2>";

$email = 'admin@foodrescue.com';
$password = 'password'; // You can change this to something more secure
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$name = 'Administrator';
$role = 'Admin';
$is_approved = 1;

// First, check if the admin user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing user
    $user = $result->fetch_assoc();
    $id = $user['id'];
    $update = $conn->prepare("UPDATE users SET password = ?, role = 'Admin', is_approved = 1 WHERE id = ?");
    $update->bind_param("si", $hashed_password, $id);
    if ($update->execute()) {
        echo "<p style='color:green;'>Success: Admin account ($email) found and password reset to 'password'.</p>";
    } else {
        echo "<p style='color:red;'>Error updating admin: " . $conn->error . "</p>";
    }
} else {
    // Create new admin user
    $insert = $conn->prepare("INSERT INTO users (name, email, password, role, is_approved) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("ssssi", $name, $email, $hashed_password, $role, $is_approved);
    if ($insert->execute()) {
        echo "<p style='color:green;'>Success: New Admin account ($email) created with password 'password'.</p>";
    } else {
        echo "<p style='color:red;'>Error creating admin: " . $conn->error . "</p>";
    }
}

echo "<p><b>Important:</b> Delete this file (<code>fix_admin.php</code>) from your server immediately after running it for security!</p>";
echo "<a href='login.php'>Go to Login Page</a>";
?>
