<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Fetch user data including location
$stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$user_lat = $user_data['latitude'];
$user_lng = $user_data['longitude'];

// Fetch data based on role
$my_donations = [];
$incoming_requests = [];
$nearby_donations = [];
$all_donations = [];

if ($user_role == 'Donor') {
    // Fetch my donations
    $stmt = $conn->prepare("SELECT * FROM donations WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $my_donations[] = $row;
    
    // Fetch requests for my donations
    $stmt = $conn->prepare("SELECT r.id, r.status, r.created_at, d.title, u.name as receiver_name 
                            FROM food_requests r 
                            JOIN donations d ON r.donation_id = d.id 
                            JOIN users u ON r.user_id = u.id 
                            WHERE d.user_id = ? ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res2 = $stmt->get_result();
    while ($row = $res2->fetch_assoc()) $incoming_requests[] = $row;

} elseif ($user_role == 'Volunteer' || $user_role == 'Receiver') {
    // Fetch nearby donations (within approx 5 km) using SQL haversine approximation or doing it in PHP
    // Doing it in PHP for better compatibility here and utilizing our Haversine function
    $stmt = $conn->prepare("SELECT d.*, u.name as donor_name, u.role as donor_role FROM donations d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $dist = calculateDistance($user_lat, $user_lng, $row['latitude'], $row['longitude']);
        if ($dist <= 5) { // 5km radius
            $row['distance'] = round($dist, 2);
            $nearby_donations[] = $row;
        }
    }
    
    // Fetch my requests
    $my_requests = [];
    $stmt_req = $conn->prepare("SELECT r.id, r.status, r.created_at, d.title, d.latitude, d.longitude, u.name as donor_name 
                                FROM food_requests r 
                                JOIN donations d ON r.donation_id = d.id 
                                JOIN users u ON d.user_id = u.id 
                                WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt_req->bind_param("i", $user_id);
    $stmt_req->execute();
    $res_req = $stmt_req->get_result();
    while ($row = $res_req->fetch_assoc()) $my_requests[] = $row;

} elseif ($user_role == 'NGO') {
    // Fetch all donations
    $stmt = $conn->prepare("SELECT d.*, u.name as donor_name FROM donations d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $all_donations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('dashboard'); ?> - Food Rescue Network System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="index.php">Food Rescue Network System</a></div>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($user_name); ?> (<?php echo $user_role; ?>)</span>
            <a href="map.php" class="btn btn-outline"><?php echo t('map'); ?></a>
            <a href="logout.php" class="btn btn-primary"><?php echo t('logout'); ?></a>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard-header">
            <h2><?php echo t('dashboard'); ?></h2>
            <?php if ($user_role == 'Donor'): ?>
                <a href="post_food.php" class="btn btn-primary"><?php echo t('post_food'); ?></a>
            <?php endif; ?>
        </div>

        <?php if ($user_role == 'Donor'): ?>
            <h3><?php echo t('my_donations'); ?></h3>
            <div class="card-grid" style="margin-bottom: 3rem;">
                <?php if (empty($my_donations)): ?>
                    <p>No donations posted yet.</p>
                <?php else: ?>
                    <?php foreach ($my_donations as $don): ?>
                        <div class="card">
                            <h4><?php echo htmlspecialchars($don['title']); ?></h4>
                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($don['quantity']); ?></p>
                            <p><?php echo htmlspecialchars($don['description']); ?></p>
                            <small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($don['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h3><?php echo t('view_requests'); ?></h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Donation</th>
                            <th>Receiver</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($incoming_requests)): ?>
                            <tr><td colspan="5">No requests received yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($incoming_requests as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['title']); ?></td>
                                    <td><?php echo htmlspecialchars($req['receiver_name']); ?></td>
                                    <td>
                                        <?php 
                                            // Make badges look nice
                                            $badge_class = 'secondary';
                                            if ($req['status'] == 'accepted') $badge_class = 'accepted';
                                            if ($req['status'] == 'rejected') $badge_class = 'rejected';
                                            if ($req['status'] == 'picked_up') $badge_class = 'pending'; // Orange
                                            if ($req['status'] == 'delivered') $badge_class = 'accepted'; // Green
                                            if ($req['status'] == 'pending') $badge_class = 'pending';
                                        ?>
                                        <span class="badge badge-<?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <?php if ($req['status'] == 'pending'): ?>
                                            <form action="update_request.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                <button type="submit" name="action" value="accept" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.8rem;">Accept</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-secondary" style="background-color:var(--danger); padding:0.25rem 0.5rem; font-size:0.8rem;">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($user_role == 'Volunteer' || $user_role == 'Receiver'): ?>
            <h3><?php echo t('nearby_donations'); ?> (Within 5km)</h3>
            <div class="card-grid">
                <?php if (empty($nearby_donations)): ?>
                    <p>No donations found nearby currently. Check back later.</p>
                <?php else: ?>
                    <?php foreach ($nearby_donations as $don): ?>
                        <div class="card">
                            <h4><?php echo htmlspecialchars($don['title']); ?></h4>
                            <p><strong>Donor:</strong> <?php echo htmlspecialchars($don['donor_name']); ?></p>
                            <p><strong>Distance:</strong> <?php echo $don['distance']; ?> km</p>
                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($don['quantity']); ?></p>
                            <p><?php echo htmlspecialchars($don['description']); ?></p>
                            <div style="margin-top:10px; display:flex; gap:10px;">
                                <?php if ($user_role == 'Receiver'): ?>
                                    <form action="request_food.php" method="POST">
                                        <input type="hidden" name="donation_id" value="<?php echo $don['id']; ?>">
                                        <button type="submit" class="btn btn-primary"><?php echo t('request'); ?></button>
                                    </form>
                                <?php endif; ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $don['latitude']; ?>,<?php echo $don['longitude']; ?>" target="_blank" class="btn btn-outline"><?php echo t('navigate'); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h3 style="margin-top: 3rem;">My Requests / Deliveries</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Donation</th>
                            <th>Donor</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_requests)): ?>
                            <tr><td colspan="5">You haven't requested any food yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_requests as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['title']); ?></td>
                                    <td><?php echo htmlspecialchars($req['donor_name']); ?></td>
                                    <td>
                                        <?php 
                                            // Make badges look nice
                                            $badge_class = 'secondary';
                                            if ($req['status'] == 'accepted') $badge_class = 'accepted';
                                            if ($req['status'] == 'rejected') $badge_class = 'rejected';
                                            if ($req['status'] == 'picked_up') $badge_class = 'pending'; // Orange
                                            if ($req['status'] == 'delivered') $badge_class = 'accepted'; // Green
                                            if ($req['status'] == 'pending') $badge_class = 'pending';
                                        ?>
                                        <span class="badge badge-<?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $req['latitude']; ?>,<?php echo $req['longitude']; ?>" target="_blank" class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.8rem;">Navigate</a>
                                            
                                            <?php if ($req['status'] == 'accepted'): ?>
                                                <form action="update_delivery.php" method="POST" style="margin:0;">
                                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                    <button type="submit" name="action" value="pickup" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.8rem;">Mark Picked Up</button>
                                                </form>
                                            <?php elseif ($req['status'] == 'picked_up'): ?>
                                                <form action="update_delivery.php" method="POST" style="margin:0;">
                                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                    <button type="submit" name="action" value="deliver" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.8rem; background-color: var(--secondary);">Mark Delivered</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($user_role == 'NGO'): ?>
            <h3>Monitor All Donations</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Donation</th>
                            <th>Donor</th>
                            <th>Quantity</th>
                            <th>Date Posted</th>
                            <th>Location Map</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_donations)): ?>
                            <tr><td colspan="5">No donations in the system yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_donations as $don): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($don['title']); ?></td>
                                    <td><?php echo htmlspecialchars($don['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($don['quantity']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($don['created_at'])); ?></td>
                                    <td>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $don['latitude']; ?>,<?php echo $don['longitude']; ?>" target="_blank" class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.8rem;">View Map</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h3 style="margin-top: 3rem;">Track Registered Volunteers</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Volunteer Name</th>
                            <th>Email</th>
                            <th>Registered Date</th>
                            <th>Location / Track</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Fetch all volunteers
                        $stmt_vols = $conn->prepare("SELECT id, name, email, latitude, longitude, created_at FROM users WHERE role = 'Volunteer' ORDER BY created_at DESC");
                        $stmt_vols->execute();
                        $res_vols = $stmt_vols->get_result();
                        $volunteers = [];
                        while ($row = $res_vols->fetch_assoc()) {
                            $volunteers[] = $row;
                        }
                        
                        if (empty($volunteers)): ?>
                            <tr><td colspan="4">No volunteers registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($volunteers as $vol): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vol['name']); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($vol['email']); ?>"><?php echo htmlspecialchars($vol['email']); ?></a></td>
                                    <td><?php echo date('M d, Y', strtotime($vol['created_at'])); ?></td>
                                    <td>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $vol['latitude']; ?>,<?php echo $vol['longitude']; ?>" target="_blank" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.8rem;">Track Location</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
