<?php
$conn = mysqli_connect(
    "127.0.0.1",
    "root",
    "",
    "food_rescue",
    3307
);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
