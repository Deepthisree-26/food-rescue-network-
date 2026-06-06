<?php
require_once 'config.php';

$queries = [
    "ALTER TABLE users MODIFY COLUMN role ENUM('Donor', 'Volunteer', 'NGO', 'Receiver', 'Admin') NOT NULL",
    "ALTER TABLE users ADD COLUMN is_approved BOOLEAN DEFAULT 1",
    "INSERT IGNORE INTO users (name, email, password, role, is_approved) VALUES ('Administrator', 'admin@foodrescue.com', '\$2y\$10\$YjU5uH3p3Rj7x7.8Y8u6/.e1L9Hl8Z0/1vKxVp8p.nJ.pE3a.r5nO', 'Admin', 1)"
];

foreach ($queries as $q) {
    if ($conn->query($q) === TRUE) {
        echo "Successfully executed: $q\n";
    } else {
        echo "Error or already executed '$q': " . $conn->error . "\n";
    }
}
?>
