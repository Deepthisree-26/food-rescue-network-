<?php
session_start();
include 'db.php';

$id = $_SESSION['id'];
$food = $_POST['food'];
$qty = $_POST['qty'];
$loc = $_POST['location'];

mysqli_query($conn,"INSERT INTO donations(donor_id,food_type,quantity,location,status)
VALUES($id,'$food',$qty,'$loc','Available')");

echo "Food Added Successfully";
?>
