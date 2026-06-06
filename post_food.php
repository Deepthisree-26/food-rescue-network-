<?php
require_once 'config.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Donor') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $quantity = trim($_POST['quantity']);
    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : null;
    
    if (empty($title) || empty($description) || empty($quantity) || empty($lat) || empty($lng)) {
        $error = "Please fill in all details and select a location from the suggestions.";
    } else {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO donations (user_id, title, description, quantity, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdd", $user_id, $title, $description, $quantity, $lat, $lng);
        
        if ($stmt->execute()) {
            $success = "Donation posted successfully!";
            
            // Notify nearby users (Volunteers and Receivers) within 5km
            $stmt_users = $conn->prepare("SELECT id, name, email, latitude, longitude, role FROM users WHERE role IN ('Volunteer', 'Receiver')");
            $stmt_users->execute();
            $result_users = $stmt_users->get_result();
            
            while ($u = $result_users->fetch_assoc()) {
                $dist = calculateDistance($lat, $lng, $u['latitude'], $u['longitude']);
                if ($dist <= 5) {
                    $subject = "New Food Donation Nearby: " . $title;
                    $body = "Hi " . $u['name'] . ",<br><br>";
                    $body .= "A new food donation has been posted nearby (" . round($dist, 2) . " km away).<br>";
                    $body .= "<strong>Title:</strong> $title<br>";
                    $body .= "<strong>Quantity:</strong> $quantity<br>";
                    $body .= "<strong>Description:</strong> $description<br><br>";
                    $body .= "Log in to the Food Rescue Network to view it on the map and navigate or request it.<br><br>";
                    $body .= "<a href='" . BASE_URL . "/login.php'>Login Here</a>";
                    
                    sendEmail($u['email'], $subject, $body);
                }
            }
        } else {
            $error = "Failed to post donation.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('post_food'); ?> - Food Rescue Network System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .autocomplete-dropdown {
            position: absolute;
            background: #fff;
            border: 1px solid #ccc;
            border-top: none;
            z-index: 9999;
            max-height: 200px;
            overflow-y: auto;
            width: calc(100% - 60px);
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .autocomplete-dropdown .ac-item {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 0.95rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .autocomplete-dropdown .ac-item:hover {
            background-color: #e8f5e9;
        }
        .location-wrapper { position: relative; }
    </style>
    <script>
        let searchTimeout = null;

        function searchAddress() {
            var input = document.getElementById('pickup_address');
            var query = input.value.trim();
            var dropdown = document.getElementById('address-dropdown');

            if (query.length < 3) {
                dropdown.innerHTML = '';
                dropdown.style.display = 'none';
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&limit=5&addressdetails=1', {
                    headers: { 'Accept-Language': 'en' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    dropdown.innerHTML = '';
                    if (data.length === 0) {
                        dropdown.innerHTML = '<div class="ac-item" style="color:#999;cursor:default;">No results found</div>';
                        dropdown.style.display = 'block';
                        return;
                    }
                    data.forEach(function(place) {
                        var item = document.createElement('div');
                        item.className = 'ac-item';
                        item.textContent = place.display_name;
                        item.addEventListener('click', function() {
                            input.value = place.display_name;
                            document.getElementById('latitude').value = place.lat;
                            document.getElementById('longitude').value = place.lon;
                            dropdown.innerHTML = '';
                            dropdown.style.display = 'none';
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.style.display = 'block';
                })
                .catch(function(err) { console.error('Geocoding error:', err); });
            }, 400);
        }

        function detectLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;

                    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.display_name) {
                            document.getElementById('pickup_address').value = data.display_name;
                        }
                    });
                });
            } else {
                alert("Browser doesn't support Geolocation.");
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            var dropdown = document.getElementById('address-dropdown');
            if (dropdown && !e.target.closest('.location-wrapper')) {
                dropdown.innerHTML = '';
                dropdown.style.display = 'none';
            }
        });
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="index.php">Food Rescue Network System</a></div>
        <div class="nav-links">
            <a href="dashboard.php" class="btn btn-outline"><?php echo t('dashboard'); ?></a>
            <a href="logout.php" class="btn btn-primary"><?php echo t('logout'); ?></a>
        </div>
    </nav>
    <div class="container">
        <div class="form-container">
            <h2><?php echo t('post_food'); ?></h2>
            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
            
            <form action="" method="post">
                <div class="form-group">
                    <label><?php echo t('title'); ?></label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('description'); ?></label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo t('quantity'); ?></label>
                    <input type="text" name="quantity" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Pickup Address (Search or use current location)</label>
                    <div class="location-wrapper" style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" id="pickup_address" class="form-control" placeholder="<?php echo t('address_placeholder'); ?>" oninput="searchAddress()" autocomplete="off" required>
                        <button type="button" class="btn btn-outline" onclick="detectLocation()" title="Use Current Location">📍</button>
                        <div id="address-dropdown" class="autocomplete-dropdown" style="display:none;"></div>
                    </div>
                </div>
                <!-- Hidden inputs for coordinates -->
                <input type="hidden" id="latitude" name="latitude" required>
                <input type="hidden" id="longitude" name="longitude" required>

                <div class="form-group" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="width:100%"><?php echo t('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
