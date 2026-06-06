<?php
require_once 'config.php';

$queries = [
    "ALTER TABLE donations ADD COLUMN status ENUM('Available', 'Claimed', 'Delivered') DEFAULT 'Available'",
    "ALTER TABLE donations ADD COLUMN volunteer_id INT DEFAULT NULL",
    "ALTER TABLE donations ADD CONSTRAINT fk_donations_volunteer FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE SET NULL"
];

foreach ($queries as $q) {
    if ($conn->query($q) === TRUE) {
        echo "Successfully executed: $q\n";
    } else {
        echo "Error or already executed '$q': " . $conn->error . "\n";
    }
}
?>
