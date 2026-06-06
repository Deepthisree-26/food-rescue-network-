<?php
require_once 'config.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action']; // 'pickup' or 'deliver'
    
    // Only Volunteers or Receivers who own the request can update it, OR Admin
    // First verify ownership
    $stmt_verify = $conn->prepare("SELECT r.id, r.user_id, r.status, d.title, d.latitude as d_lat, d.longitude as d_lng, d.user_id as donor_id FROM food_requests r JOIN donations d ON r.donation_id = d.id WHERE r.id = ?");
    $stmt_verify->bind_param("i", $request_id);
    $stmt_verify->execute();
    $result = $stmt_verify->get_result();
    
    if ($result->num_rows === 0) {
        die("Request not found.");
    }
    
    $req = $result->fetch_assoc();
    
    if ($req['user_id'] != $user_id && $user_role != 'Admin') {
        die("Unauthorized to update this request.");
    }

    $new_status = '';
    if ($action === 'pickup' && $req['status'] === 'accepted') {
        $new_status = 'picked_up';
    } elseif ($action === 'deliver' && $req['status'] === 'picked_up') {
        $new_status = 'delivered';
    } else {
        echo "<script>alert('Invalid state transition. Please refresh and try again.'); window.location.href='dashboard.php';</script>";
        exit();
    }

    // Update status
    $stmt_update = $conn->prepare("UPDATE food_requests SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_status, $request_id);
    
    if ($stmt_update->execute()) {
        $volunteer_name = $_SESSION['user_name'];
        
        // 1. If Picked Up -> Notify Nearby NGOs (Task Completed by Volunteer, handing off to NGO)
        if ($new_status === 'picked_up') {
            // Find all NGOs
            $stmt_ngos = $conn->prepare("SELECT email, name, latitude, longitude FROM users WHERE role = 'NGO'");
            $stmt_ngos->execute();
            $res_ngos = $stmt_ngos->get_result();
            
            while ($ngo = $res_ngos->fetch_assoc()) {
                // Calculate distance from donation location to NGO
                $dist = calculateDistance($req['d_lat'], $req['d_lng'], $ngo['latitude'], $ngo['longitude']);
                
                // Notify NGOs within 10km that food is picked up and ready for them / on the way
                if ($dist <= 10) {
                    $subject = "Task Completed: Volunteer Picked Up Food (" . $req['title'] . ")";
                    $body = "Hi " . $ngo['name'] . ",<br><br>";
                    $body .= "Volunteer <strong>$volunteer_name</strong> has successfully picked up the food donation: <strong>" . $req['title'] . "</strong>.<br><br>";
                    $body .= "They will be delivering or handing this over soon. Please check your dashboard to coordinate if needed.<br><br>";
                    $body .= "<a href='" . BASE_URL . "/login.php'>Login to Dashboard</a>";
                    
                    sendEmail($ngo['email'], $subject, $body);
                }
            }
            echo "<script>alert('Marked as Picked Up! KPIs and Nearby NGOs have been notified.'); window.location.href='dashboard.php';</script>";
        }
        
        // 2. If Delivered -> Notify Donor
        else if ($new_status === 'delivered') {
            // Get Donor Info
            $stmt_donor = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt_donor->bind_param("i", $req['donor_id']);
            $stmt_donor->execute();
            $donor = $stmt_donor->get_result()->fetch_assoc();
            
            $subject = "Success: Food Delivered Successfully (" . $req['title'] . ")";
            $body = "Hi " . $donor['name'] . ",<br><br>";
            $body .= "Great news! The volunteer <strong>$volunteer_name</strong> has successfully delivered / handed over your food donation: <strong>" . $req['title'] . "</strong> to the destination (NGO/Receiver).<br><br>";
            $body .= "Thank you for your generous contribution to reducing food waste and helping the community!<br><br>";
            $body .= "<a href='" . BASE_URL . "/login.php'>Login to Dashboard</a>";
            
            sendEmail($donor['email'], $subject, $body);
            
            echo "<script>alert('Marked as Delivered! The donor has been notified of the successful delivery.'); window.location.href='dashboard.php';</script>";
        }
        
    } else {
        echo "<script>alert('Error updating delivery status.'); window.location.href='dashboard.php';</script>";
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
