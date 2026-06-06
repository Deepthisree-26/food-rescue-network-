<?php
require_once 'config.php';
require_once 'mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Donor') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'] === 'accept' ? 'accepted' : 'rejected';
    $donor_id = $_SESSION['user_id'];
    
    // Verify that this request belongs to one of the donor's donations
    $stmt_verify = $conn->prepare("SELECT r.id FROM food_requests r JOIN donations d ON r.donation_id = d.id WHERE r.id = ? AND d.user_id = ?");
    $stmt_verify->bind_param("ii", $request_id, $donor_id);
    $stmt_verify->execute();
    if ($stmt_verify->get_result()->num_rows === 0) {
        die("Unauthorized action.");
    }
    
    // Update request status
    $stmt = $conn->prepare("UPDATE food_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $request_id);
    if ($stmt->execute()) {
        // Fetch Receiver info to notify them
        $stmt_info = $conn->prepare("SELECT u.email, u.name as receiver_name, d.title FROM food_requests r JOIN users u ON r.user_id = u.id JOIN donations d ON r.donation_id = d.id WHERE r.id = ?");
        $stmt_info->bind_param("i", $request_id);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        
        $subject = "Food Request " . ucfirst($action) . ": " . $info['title'];
        $body = "Hi " . $info['receiver_name'] . ",<br><br>";
        $body .= "Your request for the food donation <strong>" . $info['title'] . "</strong> has been <strong>" . $action . "</strong>.<br><br>";
        
        if ($action === 'accepted') {
            $body .= "Please proceed to coordinate the pickup as soon as possible.<br><br>";
        }
        $body .= "<a href='" . BASE_URL . "/login.php'>Login to Dashboard</a>";
        
        sendEmail($info['email'], $subject, $body);
        
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Error updating request.'); window.location.href='dashboard.php';</script>";
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
