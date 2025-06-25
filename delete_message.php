<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

$residentId = $_SESSION['resident_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message_id'])) {
    $messageId = intval($_POST['message_id']);

    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("ii", $messageId, $residentId);
    $stmt->execute();
}
header("Location: messages.php");
exit;