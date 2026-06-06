<?php
// start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // default XAMPP user
define('DB_PASS', '');     // default XAMPP password is empty
define('DB_NAME', 'food_rescue');

// API Keys Configuration
// REPLACE THESE WITH YOUR ACTUAL API KEYS / CREDENTIALS
define('GOOGLE_MAPS_API_KEY', '');

// PHPMailer Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password'); // Use an App Password, not your real password
define('MAIL_FROM_EMAIL', 'your_email@gmail.com');
define('MAIL_FROM_NAME', 'Food Rescue Network');

// Application Base URL for use in redirects and links (adjust if running on a different port/path)
define('BASE_URL', 'http://localhost/food_rescue');

// Create database connection using MySQLi
try {
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_INDEX);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("<h3>Database Connection Error</h3><p>Could not connect to the database. Please double check that your DB_HOST, DB_USER, DB_PASS, and DB_NAME in <code>config.php</code> perfectly match the values provided by InfinityFree.</p><p>Error details: " . $e->getMessage() . "</p>");
}
$conn->set_charset("utf8mb4");

// Language settings
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // default language
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'hi'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Function to load translations
function loadTranslations() {
    $langFile = __DIR__ . '/languages/' . $_SESSION['lang'] . '.json';
    if (file_exists($langFile)) {
        $jsonString = file_get_contents($langFile);
        return json_decode($jsonString, true);
    }
    return [];
}

$translations = loadTranslations();

// Translation helper function
function t($key) {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
}

// Haversine formula to calculate distance between two lat/lng pairs in kilometers
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    $d = $earth_radius * $c;

    return $d;
}
?>
