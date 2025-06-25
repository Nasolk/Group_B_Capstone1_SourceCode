<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

$senderId = $_POST['sender_id'];
$receiverId = $_POST['receiver_id'];
$message = $_POST['message'];
$now = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $senderId, $receiverId, $message, $now);
$stmt->execute();
