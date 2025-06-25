<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

$resident_id = $_GET['resident_id'] ?? 0;

// Fetch messages between admin (id=0) and the resident
$stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = 0 AND receiver_id = ?) OR (sender_id = ? AND receiver_id = 0) ORDER BY sent_at ASC");
$stmt->bind_param("ii", $resident_id, $resident_id);
$stmt->execute();
$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Chat</title>
    <link rel="stylesheet" href="/BaGoApp/assets/style.css">
</head>
<body>
    <h2>Chat with Resident #<?= $resident_id ?></h2>

    <div style="background:#f0f0f0; padding:10px; height:300px; overflow-y:auto;">
        <?php while ($row = $messages->fetch_assoc()): ?>
            <div>
                <strong><?= $row['sender_id'] == 0 ? "Admin" : "Resident" ?>:</strong>
                <?= htmlspecialchars($row['message']) ?>
                <small style="color:gray;">(<?= $row['sent_at'] ?>)</small>
            </div>
        <?php endwhile; ?>
    </div>

    <form action="send_reply.php" method="POST">
        <input type="hidden" name="receiver_id" value="<?= $resident_id ?>">
        <textarea name="message" required rows="4" style="width:100%; margin-top:10px;"></textarea>
        <button type="submit">Send Reply</button>
    </form>
</body>
</html>
