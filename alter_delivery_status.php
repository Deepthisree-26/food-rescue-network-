<?php
require_once 'config.php';

echo "<h2>Updating Database Schema for Delivery Tracking...</h2>";

// Connect to database
try {
    // Modify the status ENUM to include the new delivery stages
    $sql = "ALTER TABLE food_requests MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'picked_up', 'delivered') NOT NULL DEFAULT 'pending'";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Successfully updated `food_requests` table to support 'picked_up' and 'delivered' statuses.</p>";
    } else {
        echo "<p style='color:red;'>Error updating table: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Exception: " . $e->getMessage() . "</p>";
}

echo "<br><a href='dashboard.php'>Return to Dashboard</a>";
?>
