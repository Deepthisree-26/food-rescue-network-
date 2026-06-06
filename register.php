<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : null;

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif (empty($lat) || empty($lng)) {
        $error = "Please select a valid address from the dropdown or ensure location is detected.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_approved = ($role == 'Donor' || $role == 'Receiver') ? 1 : 0;
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, latitude, longitude, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssddi", $name, $email, $hashed_password, $role, $lat, $lng, $is_approved);
            
            if ($stmt->execute()) {
                if ($is_approved) {
                    $success = "Registration successful. You can now login.";
                } else {
                    $success = "Registration successful. Your account is awaiting Admin approval before you can log in.";
                }
            } else {
                $error = "Error during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('register'); ?> - Food Rescue Network System</title>
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
            var input = document.getElementById('address');
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
                            document.getElementById('address').value = data.display_name;
                        }
                    });
                }, function() {
                    alert("Geolocation failed or was denied.");
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
    </nav>
    <div class="container">
        <div class="form-container">
            <h2 style="text-align:center;"><?php echo t('register'); ?></h2>
            
            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if ($success) echo "<div class='alert alert-success'>$success <br><a href='login.php'>Login here</a></div>"; ?>
            
            <form action="" method="post" id="registerForm">
                <div class="form-group">
                    <label><?php echo t('name'); ?></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('email'); ?></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('password'); ?></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('role'); ?></label>
                    <select name="role" class="form-control" required>
                        <option value="Donor"><?php echo t('donor'); ?></option>
                        <option value="Volunteer"><?php echo t('volunteer'); ?></option>
                        <option value="NGO"><?php echo t('ngo'); ?></option>
                        <option value="Receiver"><?php echo t('receiver'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Address (Start typing to locate)</label>
                    <div class="location-wrapper" style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" id="address" class="form-control" placeholder="<?php echo t('address_placeholder'); ?>" oninput="searchAddress()" autocomplete="off" required>
                        <button type="button" class="btn btn-outline" onclick="detectLocation()" title="Use Current Location">📍</button>
                        <div id="address-dropdown" class="autocomplete-dropdown" style="display:none;"></div>
                    </div>
                </div>
                <!-- Hidden inputs for coordinates -->
                <input type="hidden" id="latitude" name="latitude" required>
                <input type="hidden" id="longitude" name="longitude" required>

                <div class="form-group" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="width:100%"><?php echo t('register'); ?></button>
                </div>
                <p style="text-align:center;"><?php echo t('already_have_account'); ?> <a href="login.php"><?php echo t('login'); ?></a></p>
            </form>
        </div>
    </div>
</body>
</html>
