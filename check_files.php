<?php
echo "<h1>📁 File Checker Tool</h1>";
echo "<p>This script checks if your system files are correctly uploaded to your server.</p>";
$required_files = ['config.php', 'dashboard.php', 'update_delivery.php', 'mailer.php', 'request_food.php', 'test_noti.php'];
echo "<table border='1' cellpadding='10'>";
foreach ($required_files as $file) {
    echo "<tr><td>$file</td><td>" . (file_exists($file) ? "✅ FOUND" : "❌ MISSING") . "</td></tr>";
}
echo "</table>";
?>
