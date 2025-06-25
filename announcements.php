<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "bago_app");

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
    $announcement = $_POST['announcement'];
    $imagePath = null;

    if (!empty($_FILES['image']['name'])) {
        $imageName = basename($_FILES['image']['name']);
        $targetDir = "../uploads/";
        $targetFile = $targetDir . $imageName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = "uploads/" . $imageName;
        }
    }

    $stmt = $mysqli->prepare("INSERT INTO announcements (content, image_path, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $announcement, $imagePath);
    $stmt->execute();
    header("Location: announcements.php");
    exit();
}

// Handle likes
if (isset($_GET['like_id'])) {
    $id = $_GET['like_id'];
    $mysqli->query("UPDATE announcements SET likes = likes + 1 WHERE id = $id");
    header("Location: announcements.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_announcement'])) {
    $id = $_GET['delete_announcement'];
    $mysqli->query("DELETE FROM announcements WHERE id = $id");
    $mysqli->query("DELETE FROM comments WHERE announcement_id = $id");
    header("Location: announcements.php");
    exit();
}

if (isset($_GET['delete_comment'])) {
    $id = $_GET['delete_comment'];
    $mysqli->query("DELETE FROM comments WHERE id = $id");
    header("Location: announcements.php");
    exit();
}

// Handle comment post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $announcement_id = $_POST['announcement_id'];
    $comment = $_POST['comment'];
    $mysqli->query("INSERT INTO comments (announcement_id, sender_name, comment, created_at) VALUES ($announcement_id, 'Admin', '$comment', NOW())");
    header("Location: announcements.php");
    exit();
}

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$dateFilter = isset($_GET['date']) ? $mysqli->real_escape_string($_GET['date']) : '';

// Build SQL with filters
$sql = "SELECT * FROM announcements WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM announcements WHERE 1=1";

if ($search !== '') {
    $sql .= " AND content LIKE '%$search%'";
    $countSql .= " AND content LIKE '%$search%'";
}
if ($dateFilter !== '') {
    $sql .= " AND DATE(created_at) = '$dateFilter'";
    $countSql .= " AND DATE(created_at) = '$dateFilter'";
}
$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$result = $mysqli->query($sql);

// Get total count for pagination
$totalResult = $mysqli->query($countSql);
$totalRow = $totalResult->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Announcements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f0f2f5;
        }
        .sidebar {
            width: 220px;
            background-color: #002855;
            height: 100vh;
            color: white;
            position: fixed;
            padding-top: 20px;
        }
        .sidebar img {
            width: 100px;
            margin: 0 auto;
            display: block;
            border-radius: 50%;
        }
        .sidebar h2 {
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background: #00509e;
        }

        .main {
            margin-left: 240px;
            padding: 20px;
            max-width: 800px;
        }

        .post-form {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .post-form textarea {
            width: 100%;
            padding: 10px;
            resize: none;
        }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-bar input[type="text"],
        .search-bar input[type="date"] {
            padding: 8px;
            flex: 1;
        }

        .announcement {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .announcement img {
            max-width: 100%;
            margin-top: 10px;
        }

        .comment-section {
            margin-top: 10px;
            padding-left: 20px;
        }

        .comment {
            background: #f5f5f5;
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 5px;
        }

        form.comment-form {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        form.comment-form input[type="text"] {
            flex: 1;
            padding: 8px;
        }

        .action-links {
            font-size: 13px;
        }
        .action-links a {
            margin-right: 10px;
            color: #00509e;
            text-decoration: none;
        }
        .action-links a:hover {
            text-decoration: underline;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 6px 12px;
            background: #00509e;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .pagination a.active {
            background: #003f7d;
            pointer-events: none;
        }

        .pagination a:hover {
            background: #003f7d;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <img src="../images/bago_logo.png" alt="Logo">
    <h2>BaGo Admin</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="residents.php">Residents</a>
    <a href="certificates.php">Certificates</a>
    <a href="announcements.php">Announcements</a>
    <a href="messages.php">Messages</a>
    <a href="reports.php">Reports</a>
    <a href="audit_trail.php">Audit Trail</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main">
    <h1>Post an Announcement</h1>

    <form method="POST" enctype="multipart/form-data" class="post-form">
        <textarea name="announcement" rows="3" placeholder="Write an announcement..." required></textarea>
        <input type="file" name="image" accept="image/*"><br><br>
        <button type="submit">Post</button>
    </form>

    <!-- Search and Filter Form -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search announcements..." value="<?= htmlspecialchars($search) ?>">
        <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
        <button type="submit">Search</button>
    </form>

    <?php while ($a = $result->fetch_assoc()): ?>
        <div class="announcement">
            <p><?= nl2br(htmlspecialchars($a['content'])) ?></p>
            <?php if ($a['image_path']): ?>
                <img src="../<?= $a['image_path'] ?>" alt="Announcement image">
            <?php endif; ?>
            <p><em>Posted by Admin | <?= date("F j, Y g:i A", strtotime($a['created_at'])) ?></em></p>
            <p class="action-links">
                <a href="?like_id=<?= $a['id'] ?>">👍 Like (<?= $a['likes'] ?>)</a>
                <a href="?delete_announcement=<?= $a['id'] ?>" onclick="return confirm('Delete this announcement?')">🗑 Delete</a>
            </p>

            <!-- Comment Section -->
            <div class="comment-section">
                <?php
                $comments = $mysqli->query("SELECT * FROM comments WHERE announcement_id = {$a['id']} ORDER BY created_at ASC");
                while ($c = $comments->fetch_assoc()):
                ?>
                    <div class="comment">
                        <strong><?= $c['sender_name'] ?>:</strong> <?= htmlspecialchars($c['comment']) ?>
                        <div class="action-links">
                            <a href="?delete_comment=<?= $c['id'] ?>" onclick="return confirm('Delete this comment?')">🗑 Delete</a>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- Comment Form -->
                <form method="POST" class="comment-form">
                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                    <input type="text" name="comment" placeholder="Write a comment..." required>
                    <button type="submit">Reply</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&date=<?= $dateFilter ?>" class="<?= $p == $page ? 'active' : '' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

</body>
</html>

   