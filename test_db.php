<?php
$mysqli = new mysqli("localhost", "root", "", "food_rescue");

if ($mysqli->connect_error) {
    echo "Connection error with empty password: " . $mysqli->connect_error . "<br>";
} else {
    echo "Connected successfully to MySQL with an EMPTY password.<br>";
}

$mysqli2 = new mysqli("localhost", "root", "12345", "food_rescue");

if ($mysqli2->connect_error) {
    echo "Connection error with password '12345': " . $mysqli2->connect_error . "<br>";
} else {
    echo "Connected successfully to MySQL with password '12345'.<br>";
}

// Check what passwords exist for users
$mysqli3 = new mysqli("localhost", "root", "");
if (!$mysqli3->connect_error) {
    if ($result = $mysqli3->query("SELECT user, host, plugin FROM mysql.user")) {
        echo "<br>Users and plugins:<br>";
        while($row = $result->fetch_assoc()) {
            echo "User: " . $row['user'] . "@" . $row['host'] . " (Plugin: " . $row['plugin'] . ")<br>";
        }
    }
}
?>