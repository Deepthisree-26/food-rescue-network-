<?php
require_once 'config.php';

$res = $conn->query("SELECT id, name, role, is_approved FROM users");
$users = $res->fetch_all(MYSQLI_ASSOC);

echo "<h3>All Users in Local Database</h3><table border='1'><tr><th>ID</th><th>Name</th><th>Role</th><th>Approved</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['role']}</td><td>" . ($u['is_approved'] === null ? 'NULL' : $u['is_approved']) . "</td></tr>";
}
echo "</table>";

$pending = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_approved = 0")->fetch_assoc()['c'];
echo "<p>Total Pending Approvals (is_approved = 0): $pending</p>";

?>
