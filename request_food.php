<?php
require_once 'config.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Receiver') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['donation_id'])) {
    $donation_id = (int)$_POST['donation_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if request already exists
    $stmt_check = $conn->prepare("SELECT id FROM food_requests WHERE user_id = ? AND donation_id = ?");
    $stmt_check->bind_param("ii", $user_id, $donation_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        // Already requested
        echo "<script>alert('You have already requested this donation.'); window.location.href='dashboard.php';</script>";
        exit();
    }
    
    // Insert new request
    $stmt = $conn->prepare("INSERT INTO food_requests (user_id, donation_id, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $user_id, $donation_id);
    
    if ($stmt->execute()) {
        // Fetch Donor details to send email
        $stmt_donor = $conn->prepare("SELECT u.email, u.name as donor_name, d.title FROM donations d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
        $stmt_donor->bind_param("i", $donation_id);
        $stmt_donor->execute();
        $donor = $stmt_donor->get_result()->fetch_assoc();
        
        // Fetch Receiver details
        $receiver_name = $_SESSION['user_name'];
        
        $subject = "New Food Request: " . $donor['title'];
        $body = "Hi " . $donor['donor_name'] . ",<br><br>";
        $body .= "<strong>$receiver_name</strong> has requested your food donation: " . $donor['title'] . ".<br><br>";
        $body .= "Please log in to your dashboard to accept or reject this request.<br><br>";
        $body .= "<a href='" . BASE_URL . "/login.php'>Login to Dashboard</a>";
        
        sendEmail($donor['email'], $subject, $body);
        
        echo "<script>alert('Food requested successfully!'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to request food. Please try again.'); window.location.href='dashboard.php';</script>";
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
