<?php
require_once 'config.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle actions (Approve, Reject, Delete, Assign Volunteer)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'approve_user') {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success_msg = "User approved successfully.";
        } else {
            $error_msg = "Error approving user.";
        }
    } elseif ($action == 'delete_user' || $action == 'reject_user') {
        $user_id = intval($_POST['user_id']);
        // Don't allow admin to delete themselves
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success_msg = "User removed successfully.";
            } else {
                $error_msg = "Error removing user.";
            }
        }
    } elseif ($action == 'assign_volunteer') {
        $donation_id = intval($_POST['donation_id']);
        $volunteer_id = intval($_POST['volunteer_id']);
        
        $stmt = $conn->prepare("UPDATE donations SET volunteer_id = ?, status = 'Claimed' WHERE id = ?");
        $stmt->bind_param("ii", $volunteer_id, $donation_id);
        if ($stmt->execute()) {
            $success_msg = "Volunteer successfully assigned to donation.";
        } else {
            $error_msg = "Error assigning volunteer.";
        }
    }
}

// Fetch Statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0],
    'pending_approvals' => $conn->query("SELECT COUNT(*) FROM users WHERE is_approved = 0")->fetch_row()[0],
    'total_donations' => $conn->query("SELECT COUNT(*) FROM donations")->fetch_row()[0],
    'completed_deliveries' => $conn->query("SELECT COUNT(*) FROM donations WHERE status = 'Delivered'")->fetch_row()[0]
];

// Fetch Pending Approvals
$pending_users = [];
$res_pending = $conn->query("SELECT id, name, email, role, created_at FROM users WHERE is_approved = 0 ORDER BY created_at ASC");
while ($row = $res_pending->fetch_assoc()) {
    $pending_users[] = $row;
}

// Fetch All Users for Management
$all_users = [];
$res_users = $conn->query("SELECT id, name, email, role, is_approved, created_at FROM users WHERE role != 'Admin' ORDER BY role ASC, name ASC");
while ($row = $res_users->fetch_assoc()) {
    $all_users[] = $row;
}

// Fetch Active Donations
$active_donations = [];
$res_donations = $conn->query("
    SELECT d.id, d.title, d.quantity, d.status, d.created_at, u.name as donor_name, v.name as volunteer_name 
    FROM donations d 
    JOIN users u ON d.user_id = u.id 
    LEFT JOIN users v ON d.volunteer_id = v.id 
    WHERE d.status != 'Delivered' 
    ORDER BY d.created_at DESC
");
while ($row = $res_donations->fetch_assoc()) {
    $active_donations[] = $row;
}

// Fetch Approved Volunteers for Assignment dropdown
$approved_volunteers = [];
$res_vols = $conn->query("SELECT id, name FROM users WHERE role = 'Volunteer' AND is_approved = 1");
while ($row = $res_vols->fetch_assoc()) {
    $approved_volunteers[] = $row;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo t('app_title'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            flex: 1;
            min-width: 200px;
        }
        .stat-card h3 {
            margin: 0;
            color: var(--primary-color);
            font-size: 2rem;
        }
        .stat-card p {
            margin: 5px 0 0;
            color: var(--text-color);
            font-weight: 500;
        }
        .stats-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .admin-section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><a href="index.php"><?php echo t('app_title'); ?> - Admin Console</a></div>
        <div class="nav-links">
            <span>Welcome, Administrator</span>
            <a href="map.php" class="btn btn-outline"><?php echo t('map'); ?></a>
            <a href="logout.php" class="btn btn-primary"><?php echo t('logout'); ?></a>
        </div>
    </nav>
    <div class="container">
        <?php if ($success_msg) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
        <?php if ($error_msg) echo "<div class='alert alert-error'>$error_msg</div>"; ?>
        
        <!-- Statistics Section -->
        <h2 style="margin-top:0;">System Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_users']; ?></h3>
                <p>Total Registered Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['pending_approvals']; ?></h3>
                <p>Pending Approvals</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_donations']; ?></h3>
                <p>Total Donations</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['completed_deliveries']; ?></h3>
                <p>Completed Deliveries</p>
            </div>
        </div>

        <!-- Pending Approvals Section -->
        <div class="admin-section">
            <h3>Pending User Approvals (NGOs & Volunteers)</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registration Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_users)): ?>
                            <tr><td colspan="5">No pending approvals at this time.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge badge-pending"><?php echo $u['role']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="action" value="approve_user" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.8rem;">Approve</button>
                                            <button type="submit" name="action" value="reject_user" class="btn btn-secondary" style="background-color:var(--danger); padding:0.25rem 0.5rem; font-size:0.8rem;" onclick="return confirm('Are you sure you want to reject and delete this user?');">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Donation Management Section -->
        <div class="admin-section">
            <h3>Active Donations & Volunteer Assignment</h3>
            <p style="margin-top:0;">Monitor donations and manually assign volunteers if needed.</p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Donation File</th>
                            <th>Donor</th>
                            <th>Status</th>
                            <th>Assigned Volunteer</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($active_donations)): ?>
                            <tr><td colspan="5">No active donations.</td></tr>
                        <?php else: ?>
                            <?php foreach ($active_donations as $don): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($don['title']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($don['quantity']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($don['donor_name']); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($don['status'] == 'Available' ? 'pending' : 'accepted'); ?>"><?php echo $don['status']; ?></span></td>
                                    <td><?php echo $don['volunteer_name'] ? htmlspecialchars($don['volunteer_name']) : '<em>None</em>'; ?></td>
                                    <td>
                                        <?php if ($don['status'] == 'Available'): ?>
                                            <form action="" method="POST" style="display:flex; gap:10px; align-items:center;">
                                                <input type="hidden" name="donation_id" value="<?php echo $don['id']; ?>">
                                                <input type="hidden" name="action" value="assign_volunteer">
                                                <select name="volunteer_id" class="form-control" style="padding:0.25rem; font-size:0.8rem; width:150px;" required>
                                                    <option value="">Select Volunteer...</option>
                                                    <?php foreach ($approved_volunteers as $v): ?>
                                                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary" style="padding:0.25rem 0.5rem; font-size:0.8rem;">Assign</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:var(--text-color); font-size:0.8rem;">Already assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="admin-section">
            <h3>Manage Users</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_users)): ?>
                            <tr><td colspan="5">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo $u['role']; ?></td>
                                    <td>
                                        <?php if ($u['is_approved']): ?>
                                            <span style="color:green;">&#10004; Active</span>
                                        <?php else: ?>
                                            <span style="color:orange;">&#8987; Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="action" value="delete_user" class="btn btn-secondary" style="background-color:var(--danger); padding:0.25rem 0.5rem; font-size:0.8rem;" onclick="return confirm('Are you sure you want to permanently delete this user? This cannot be undone.');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
