<?php
include 'db.php';
$result = mysqli_query($conn,"SELECT * FROM donations WHERE status='Available'");

echo "<h2>Available Food</h2>";

while($row=mysqli_fetch_assoc($result)){
  echo $row['food_type']." - ".$row['location']." ";
  echo "<a href='pickup.php?id=".$row['id']."'>Pick</a><br>";
}
?>
<a href="logout.php">Logout</a>

