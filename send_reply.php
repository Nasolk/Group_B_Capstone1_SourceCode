<?php
$mysqli = new mysqli("localhost", "root", "", "bago_app");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $mysqli->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at, is_read, archived) VALUES (?, ?, ?, NOW(), 0, 0)");
        $admin_id = 0; // Admin sender_id is 0

        $stmt->bind_param("iis", $admin_id, $receiver_id, $message);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: messages.php?sender_id=" . $receiver_id);
    exit;
} else {
    echo "Invalid request.";
}