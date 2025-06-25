<?php
$mysqli = new mysqli("localhost", "root", "", "bago_app");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$unread = $mysqli->query("SELECT COUNT(*) as count FROM messages WHERE is_read = 0")->fetch_assoc();
$unread_count = $unread['count'];
?>
