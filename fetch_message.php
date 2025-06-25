<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

$residentId = $_SESSION['resident_id'];

// Fetch all messages between the resident and admin
$query = "SELECT * FROM messages 
          WHERE sender_id = ? OR receiver_id = ?
          ORDER BY sent_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $residentId, $residentId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $isResidentSender = $row['sender_id'] == $residentId;

    $messageClass = $isResidentSender ? 'resident' : 'admin';
    $senderLabel = $isResidentSender ? 'You' : 'Admin';

    echo "<div class='message $messageClass'>";
    echo "<strong>$senderLabel:</strong> " . htmlspecialchars($row['message']);
    echo "<br><small>" . date("M d, Y h:i A", strtotime($row['sent_at'])) . "</small>";
    echo "</div>";

    if ($isResidentSender) {
    echo " <form method='post' action='delete_message.php' style='display:inline;'>
              <input type='hidden' name='message_id' value='" . $row['id'] . "'>
              <button type='submit' style='background:none;border:none;color:red;cursor:pointer;'>Delete</button>
          </form>";
}
}
?>