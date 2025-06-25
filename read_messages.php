<?php
$mysqli = new mysqli("localhost", "root", "", "bago_app");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$id = $_GET['id'];
$mysqli->query("UPDATE messages SET is_read = 1 WHERE id = $id");
$message = $mysqli->query("SELECT * FROM messages WHERE id = $id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Read Message</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .box { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Message from: <?= $message['sender_id'] ?></h2>
        <p><strong>Sent at:</strong> <?= $message['sent_at'] ?></p>
        <p><strong>Message:</strong></p>
        <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
        <a href="messages.php">Back to Messages</a>
    </div>
</body>
</html>
