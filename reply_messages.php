<?php
$id = $_GET['id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reply Message</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .box { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reply to Message</h2>
        <form action="send_reply.php" method="POST">
            <input type="hidden" name="receiver_id" value="<?= $id ?>">
            <textarea name="message" rows="6" cols="60" placeholder="Type your reply..." required></textarea><br><br>
            <button type="submit">Send Reply</button>
        </form>
        <a href="messages.php">Cancel</a>
    </div>
</body>
</html>
