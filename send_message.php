<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    $sent_at = date('Y-m-d H:i:s');

    if (!empty($message)) {
        $query = "INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $sent_at);
        $stmt->execute();
    }
}

header("Location: /BaGoApp/residents_auth/messages.php");
exit;
