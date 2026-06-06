<?php
$servers = [];
// Let's scrape the phpMyAdmin config automatically to mirror it perfectly
if (file_exists('C:\\xampp\\phpMyAdmin\\config.inc.php')) {
    include 'C:\\xampp\\phpMyAdmin\\config.inc.php';
    if (isset($cfg['Servers'][1])) {
        $phpmyadmin_cfg = $cfg['Servers'][1];
        echo "phpMyAdmin configured HOST: " . (isset($phpmyadmin_cfg['host']) ? $phpmyadmin_cfg['host'] : 'localhost') . "<br>";
        echo "phpMyAdmin configured PORT: " . (isset($phpmyadmin_cfg['port']) ? $phpmyadmin_cfg['port'] : '3306') . "<br>";
        echo "phpMyAdmin configured USER: " . $phpmyadmin_cfg['user'] . "<br>";
        echo "phpMyAdmin configured PASS lengths: " . strlen($phpmyadmin_cfg['password']) . "<br>";
    }
}
?>
