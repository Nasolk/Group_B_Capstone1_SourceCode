<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

$adminId = $_GET['admin_id'];
$residentId = $_GET['resident_id'];

$stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY sent_at ASC");
$stmt->bind_param("iiii", $adminId, $residentId, $residentId, $adminId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $class = ($row['sender_id'] == $adminId) ? 'admin' : 'resident';
    echo "<div class='chat-message $class'>" . htmlspecialchars($row['message']) . "</div>";
}
?>
