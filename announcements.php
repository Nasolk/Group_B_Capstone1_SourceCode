<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';

$resident_id = $_SESSION['resident_id'] ?? null;
if (!$resident_id) {
    header("Location: login.php");
    exit;
}

// ✅ Delete comment
if (isset($_GET['delete_comment_id'])) {
    $comment_id = intval($_GET['delete_comment_id']);
    $stmt = $conn->prepare("DELETE FROM announcement_comments WHERE id = ? AND resident_id = ?");
    $stmt->bind_param("ii", $comment_id, $resident_id);
    $stmt->execute();

    log_audit($resident_id, "Deleted a comment on announcement", "resident");

    header("Location: announcements.php");
    exit;
}

// ✅ Update comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_comment_id'], $_POST['updated_comment'])) {
    $comment_id = intval($_POST['update_comment_id']);
    $updated_comment = trim($_POST['updated_comment']);
    if ($updated_comment !== "") {
        $stmt = $conn->prepare("UPDATE announcement_comments SET comment = ? WHERE id = ? AND resident_id = ?");
        $stmt->bind_param("sii", $updated_comment, $comment_id, $resident_id);
        $stmt->execute();

        log_audit($resident_id, "Updated a comment on announcement", "resident");
    }
    header("Location: announcements.php");
    exit;
}

// ✅ New comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'], $_POST['announcement_id'])) {
    $comment = trim($_POST['comment']);
    $announcement_id = intval($_POST['announcement_id']);

    if ($comment !== "") {
        $stmt = $conn->prepare("INSERT INTO announcement_comments (announcement_id, resident_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $announcement_id, $resident_id, $comment);
        $stmt->execute();

        log_audit($resident_id, "Commented on an announcement", "resident");
    }
}

// ✅ Like
if (isset($_GET['like_id'])) {
    $announcement_id = intval($_GET['like_id']);
    $check = $conn->prepare("SELECT id FROM announcement_likes WHERE announcement_id = ? AND resident_id = ?");
    $check->bind_param("ii", $announcement_id, $resident_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $like = $conn->prepare("INSERT INTO announcement_likes (announcement_id, resident_id) VALUES (?, ?)");
        $like->bind_param("ii", $announcement_id, $resident_id);
        $like->execute();
        $conn->query("UPDATE announcements SET likes = likes + 1 WHERE id = $announcement_id");

        log_audit($resident_id, "Liked an announcement", "resident");
    }

    header("Location: announcements.php");
    exit;
}

$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");

$edit_comment_data = null;
if (isset($_GET['edit_comment_id'])) {
    $edit_id = intval($_GET['edit_comment_id']);
    $stmt = $conn->prepare("SELECT comment FROM announcement_comments WHERE id = ? AND resident_id = ?");
    $stmt->bind_param("ii", $edit_id, $resident_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_comment_data = $edit_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barangay Announcements</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f1f1f1;
        }
        .sidebar {
            width: 240px;
            background-color: #002855;
            color: white;
            height: 100vh;
            position: fixed;
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
            margin: 0;
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
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
        }
        h2 {
            color:rgb(250, 245, 245);
            margin-bottom: 20px;
        }
        .announcement {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 4px rgba(0,0,0,0.1);
        }
        .announcement img {
            max-width: 100%;
            border-radius: 5px;
        }
        .meta {
            font-size: 12px;
            color: gray;
        }
        .likes {
            margin-top: 10px;
        }
        .comment-section {
            margin-top: 15px;
        }
        .comment {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .comment small {
            color: gray;
        }
        .comment-form textarea {
            width: 100%;
            height: 50px;
        }
        .comment-form button {
            margin-top: 5px;
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
    <a href="announcements.php" class="active">Announcements</a>
    <a href="digital_id.php">View Digital ID</a>
    <a href="messages.php">Messages</a>
    <a href="profile.php">My Profile</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main-content">
    <h2>📢 Barangay Announcements</h2>

    <?php while ($row = $announcements->fetch_assoc()): ?>
        <?php
        $stmt = $conn->prepare("SELECT ac.*, r.first_name FROM announcement_comments ac JOIN residents r ON ac.resident_id = r.id WHERE ac.announcement_id = ? ORDER BY ac.created_at ASC");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $comments = $stmt->get_result();
        ?>

        <div class="announcement">
            <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
            <?php if (!empty($row['image_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/BaGoApp/uploads/" . $row['image_path'])): ?>
                <img src="/BaGoApp/uploads/<?= htmlspecialchars($row['image_path']) ?>" alt="Announcement Image">
            <?php else: ?>
                <p><i>No image attached.</i></p>
            <?php endif; ?>
            <p class="meta">Posted on <?= date("F j, Y g:i A", strtotime($row['created_at'])) ?></p>

            <div class="likes">
                ❤️ <?= $row['likes'] ?> likes
                <a href="?like_id=<?= $row['id'] ?>">Like</a>
            </div>

            <div class="comment-section">
                <strong>Comments:</strong>
                <?php while ($comment = $comments->fetch_assoc()): ?>
                    <div class="comment">
                        <strong><?= htmlspecialchars($comment['firstname']) ?>:</strong> <?= htmlspecialchars($comment['comment']) ?>
                        <br><small><?= date("M j, Y g:i A", strtotime($comment['created_at'])) ?></small>

                        <?php if ($comment['resident_id'] == $resident_id): ?>
                            <br>
                            <a href="?edit_comment_id=<?= $comment['id'] ?>">Edit</a> |
                            <a href="?delete_comment_id=<?= $comment['id'] ?>" onclick="return confirm('Delete this comment?')">Delete</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>

                <?php if ($edit_comment_data): ?>
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="update_comment_id" value="<?= htmlspecialchars($_GET['edit_comment_id']) ?>">
                        <textarea name="updated_comment" required><?= htmlspecialchars($edit_comment_data['comment']) ?></textarea>
                        <button type="submit">Update Comment</button>
                        <a href="announcements.php">Cancel</a>
                    </form>
                <?php else: ?>
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="announcement_id" value="<?= $row['id'] ?>">
                        <textarea name="comment" placeholder="Add a comment..." required></textarea>
                        <button type="submit">Comment</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>
</body>
</html>