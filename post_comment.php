<?php
include '../connection.php';

$announcement_id = $_POST['announcement_id'];
$resident_id = $_POST['resident_id'];
$comment = mysqli_real_escape_string($conn, $_POST['comment']);

mysqli_query($conn, "INSERT INTO announcement_comments (announcement_id, resident_id, comment) VALUES ($announcement_id, $resident_id, '$comment')");

header("Location: announcements.php");
exit;
