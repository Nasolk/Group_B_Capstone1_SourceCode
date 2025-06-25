<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';

$resident_id = $_SESSION['resident_id'] ?? null;

// ✅ Log audit only if resident is logged in
if ($resident_id) {
    log_audit($resident_id, 'Resident accessed Messages', 'resident');
}

// ✅ Handle message send
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['message']) && $resident_id) {
    $message = trim($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, 0, ?, NOW())");
    $stmt->bind_param("is", $resident_id, $message);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages | BaGo Resident</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f9f9f9;
        }
        .sidebar {
            width: 240px;
            background-color: #002855;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 0;
        }
        .sidebar-header {
            background-color: #001f3f;
            padding: 20px 15px;
            text-align: center;
        }
        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }
        .sidebar-header h2 {
            font-size: 18px;
            margin: 10px 0 0;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px 20px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #00509e;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
        }

        .chat-box {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 15px;
            background-color: #fff;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }

        .message {
            margin-bottom: 10px;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 20px;
            word-wrap: break-word;
            position: relative;
        }

        .message.resident {
            background-color:rgb(133, 92, 92);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 0;
        }

        .message.admin {
            background-color:rgb(62, 86, 133);
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 0;
        }

        .delete-btn {
            position: absolute;
            top: 5px;
            right: 10px;
            background: transparent;
            color: white;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }

        .message.admin .delete-btn {
            display: none;
        }

        .chat-form {
            display: flex;
            gap: 10px;
        }

        .chat-form input[type="text"] {
            flex: 1;
            padding: 10px;
            font-size: 14px;
        }

        .chat-form input[type="submit"] {
            padding: 10px 15px;
            font-size: 14px;
            background-color: #00509e;
            color: white;
            border: none;
            cursor: pointer;
        }

        .chat-form input[type="submit"]:hover {
            background-color:rgb(24, 27, 29);
        }
        .chat-box {
    display: flex;
    flex-direction: column;
}
#chat-box {
  border: 1px solid red;
  color: black;
  padding: 10px;
  max-height: 400px;
  overflow-y: auto;
}


.message {
    max-width: 70%;
    overflow-wrap: break-word;
    padding: 12px;
    margin-bottom: 12px;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.4;
}

.message.resident {
    background-color:rgb(40, 58, 61);
    align-self: flex-end;
    text-align: right;
}

.message.admin {
    background-color: #f8d7da;
    align-self: flex-start;
}
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../images/bago_logo.png" alt="Logo">
        <h2>Residents Panel</h2>
    </div>
    <a href="dashboard.php">Dashboard</a>
    <a href="view_residents.php">View Residents</a>
    <a href="certificates.php">Certificates</a>
    <a href="announcements.php">Announcements</a>
    <a href="digital_id.php">View Digital ID</a>
    <a href="messages.php" class="active">Messages</a>
    <a href="profile.php">My Profile</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main">
    <h2>Messages</h2>

    <!-- ✅ Message container -->
    <div id="chat-box" class="chat-box">
        <!-- Messages will be loaded here -->
    </div>

    <!-- ✅ Message form -->
    <form method="POST" class="chat-form">
        <input type="text" name="message" placeholder="Type your message..." required>
        <input type="submit" value="Send">
    </form>
</div>

<script>
function fetchMessages() {
    fetch('fetch_message.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('chat-box').innerHTML = data;
            // Optional: scroll to latest message
            const chatBox = document.getElementById('chat-box');
            chatBox.scrollTop = chatBox.scrollHeight;
        })
        .catch(error => console.error('Fetch error:', error));
}

window.onload = fetchMessages;
setInterval(fetchMessages, 3000);
</script>
</body>
</html>