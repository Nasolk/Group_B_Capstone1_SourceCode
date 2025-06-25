<?php
include '../connection.php';

$announcement_id = $_POST['announcement_id'];

// Update the likes count in your table
mysqli_query($conn, "UPDATE announcement SET likes = likes + 1 WHERE id = $announcement_id");

echo "liked";
