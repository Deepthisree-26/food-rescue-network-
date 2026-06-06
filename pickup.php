<?php
include 'db.php';
$id = $_GET['id'];

mysqli_query($conn,"UPDATE donations SET status='Picked' WHERE id=$id");
echo "Pickup Assigned";
?>
