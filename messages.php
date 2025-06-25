<?php
$mysqli = new mysqli("localhost", "root", "", "bago_app");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$search = $_GET['search'] ?? '';
$selectedSenderId = $_GET['sender_id'] ?? null;
$viewArchived = isset($_GET['archived']) && $_GET['archived'] == 1;

// Delete conversation
if (isset($_POST['delete_conversation'])) {
    $senderId = intval($_POST['sender_id']);
    $mysqli->query("DELETE FROM messages WHERE sender_id = $senderId");
    header("Location: messages.php");
    exit;
}

// Archive conversation
if (isset($_POST['archive_conversation'])) {
    $senderId = intval($_POST['sender_id']);
    $mysqli->query("UPDATE messages SET archived = 1 WHERE sender_id = $senderId");
    header("Location: messages.php");
    exit;
}

// Count unread messages for notification
$unreadCount = $mysqli->query("SELECT COUNT(*) as total FROM messages WHERE is_read = 0 AND receiver_id = 0 AND archived = 0")->fetch_assoc()['total'];

// Fetch latest messages (grouped)
$archivedFlag = $viewArchived ? 1 : 0;
$latestMessages = $mysqli->query("
    SELECT m1.*
    FROM messages m1
    INNER JOIN (
        SELECT sender_id, MAX(sent_at) AS latest
        FROM messages
        WHERE archived = $archivedFlag
        GROUP BY sender_id
    ) m2 ON m1.sender_id = m2.sender_id AND m1.sent_at = m2.latest
    JOIN residents r ON m1.sender_id = r.id
    WHERE (CONCAT(r.first_name, ' ', r.last_name) LIKE '%$search%' OR r.id LIKE '%$search%')
    AND m1.archived = $archivedFlag
    ORDER BY m1.sent_at DESC
");

// Fetch conversation
$conversation = [];
if ($selectedSenderId) {
   $stmt = $mysqli->prepare("SELECT m.*, 
    CASE 
        WHEN m.sender_id = 0 THEN 'Admin'
        ELSE r.first_name 
    END AS sender_name
    FROM messages m
    LEFT JOIN residents r ON m.sender_id = r.id
    WHERE (m.sender_id = ? OR m.receiver_id = ?) OR (m.sender_id = 0 AND m.receiver_id = ?)
    ORDER BY m.sent_at ASC");
$stmt->bind_param("iii", $selectedSenderId, $selectedSenderId, $selectedSenderId);
    $stmt->execute();
    $conversation = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $mysqli->query("UPDATE messages SET is_read = 1 WHERE sender_id = $selectedSenderId AND receiver_id = 0");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Messages | BaGo Admin</title>
    <style>
        body { font-family: Arial; margin: 0; display: flex; }
        .sidebar { width: 220px; background: #002855; color: #fff; height: 100vh; padding-top: 20px; position: fixed; text-align: center; }
        .sidebar img.logo { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px; object-fit: cover; }
        .sidebar h2 { margin: 10px 0; font-size: 20px; }
        .sidebar a { display: block; padding: 12px 20px; color: #fff; text-decoration: none; text-align: left; position: relative; }
        .sidebar a:hover, .sidebar a.active { background: #00509e; }
        .notification-badge {
            position: absolute;
            top: 10px;
            right: 15px;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 3px 7px;
            font-size: 12px;
        }

        .content { margin-left: 220px; display: flex; width: calc(100% - 220px); height: 100vh; }
        .message-list { width: 35%; border-right: 1px solid #ccc; padding: 20px; overflow-y: auto; }
        .message-item { background:rgb(247, 243, 243); padding: 10px; margin-bottom: 10px; border-radius: 8px; cursor: pointer; }
        .message-item.unread { background-color: #d6e9ff; }
        .message-item strong { color: #002855; }
        .message-view { flex: 1; display: flex; flex-direction: column; padding: 20px; }
        .conversation { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .message-bubble { padding: 10px; border-radius: 15px; max-width: 70%; font-size: 14px; }
        .from-resident { background: #f0f0f0; align-self: flex-start; }
        .from-admin { background: #002855; color: #fff; align-self: flex-end; }
        .reply-form { margin-top: 10px; display: flex; gap: 10px; }
        .reply-form textarea { flex: 1; padding: 10px; }
        .reply-form button { background: #002855; color: #fff; padding: 10px 20px; border: none; cursor: pointer; }
        .conversation-actions { margin-bottom: 10px; display: flex; justify-content: flex-end; gap: 10px; }
        .conversation-actions form { display: inline; }
        .conversation-actions button { background: #555; color: #fff; padding: 5px 10px; border: none; cursor: pointer; border-radius: 5px; }
        .archive-toggle { text-align: center; margin: 10px; }
    </style>
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this conversation?");
        }
    </script>
</head>
<body>

<div class="sidebar">
    <img src="../images/bago_logo.png" alt="Logo" class="logo">
    <h2>Admin Panel</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="residents.php">Residents</a>
    <a href="certificates.php">Certificates</a>
    <a href="announcements.php">Announcements</a>
    <a href="messages.php" class="active">
        Messages
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
     <a href="reports.php">Reports</a>
      <a href="audit_trail.php">Audit Trail</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="content">
    <div class="message-list">
        <form method="get">
            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="width:100%; padding: 10px;">
        </form>
        <div class="archive-toggle">
            <a href="messages.php?archived=<?= $viewArchived ? 0 : 1 ?>" style="text-decoration: none;"><?= $viewArchived ? 'View Active' : 'View Archived' ?></a>
        </div>
        <?php while ($msg = $latestMessages->fetch_assoc()): ?>
            <a href="?sender_id=<?= $msg['sender_id'] ?>" style="text-decoration: none;">
                <div class="message-item <?= $msg['is_read'] ? '' : 'unread' ?>">
                    <strong><?= htmlspecialchars($msg['sender_id']) ?> - <?= htmlspecialchars($msg['message']) ?></strong><br>
                    <small><em><?= $msg['sent_at'] ?></em></small>
                </div>
            </a>
        <?php endwhile; ?>
    </div>

    <div class="message-view">
        <?php if (!empty($conversation)): ?>
            <div class="conversation-actions">
                <form method="POST"><input type="hidden" name="sender_id" value="<?= $selectedSenderId ?>"><button name="archive_conversation">Archive</button></form>
                <form method="POST" onsubmit="return confirmDelete();"><input type="hidden" name="sender_id" value="<?= $selectedSenderId ?>"><button name="delete_conversation">Delete</button></form>
            </div>
            <div class="conversation">
                <?php foreach ($conversation as $msg): ?>
                    <div class="message-bubble <?= $msg['receiver_id'] == 0 ? 'from-resident' : 'from-admin' ?>">
                        <strong><?= $msg['receiver_id'] == 0 ? $msg['sender_id'] . ' ' . $msg['receiver_id'] : 'Admin' ?>:</strong><br>
                        <?= nl2br(htmlspecialchars($msg['message'])) ?><br>
                        <small><em><?= $msg['sent_at'] ?></em></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <form action="send_reply.php" method="POST" class="reply-form">
                <input type="hidden" name="receiver_id" value="<?= $selectedSenderId ?>">
                <textarea name="message" rows="3" placeholder="Type your reply..." required></textarea>
                <button type="submit">Send</button>
            </form>
        <?php else: ?>
            <p style="color:#777;">Select a conversation to view and reply.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>