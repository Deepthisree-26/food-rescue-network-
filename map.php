<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('map'); ?> - Food Rescue Network System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            width: 100%;
            height: 70vh;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 1rem;
        }
        .legend {
            background: white;
            padding: 10px 14px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            line-height: 1.8;
        }
        .legend h4 { margin: 0 0 6px 0; font-size: 14px; }
        .legend i {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="index.php">Food Rescue Network System</a></div>
        <div class="nav-links">
            <a href="dashboard.php" class="btn btn-outline"><?php echo t('dashboard'); ?></a>
            <a href="logout.php" class="btn btn-primary"><?php echo t('logout'); ?></a>
        </div>
    </nav>
    <div class="container" style="max-width: 100%;">
        <h2><?php echo t('map'); ?></h2>
        <p>Explore nearby food donations, NGOs, and volunteers.</p>
        
        <div id="map"></div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/map.js"></script>
</body>
</html>
