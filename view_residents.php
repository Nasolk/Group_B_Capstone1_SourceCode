<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php'; 

// Check if resident is logged in
if (!isset($_SESSION['resident_id'])) {
    header("Location: login.php");
    exit;
}

$resident_id = $_SESSION['resident_id']; // ✅ Define resident_id for log_audit
log_audit($resident_id, 'Visited Resident Dashboard', 'resident'); // ✅ Log audit trail

// Handle search
$search = '';
$search_sql = '';
$search_param = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $search_sql = "WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR contact_number LIKE ?";
    $search_param = "%$search%";
}

// Pagination setup
$limit = 8;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Count total results
if ($search_sql) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM residents $search_sql");
    $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_rows = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query("SELECT COUNT(*) as total FROM residents");
    $total_rows = $result->fetch_assoc()['total'];
}
$total_pages = ceil($total_rows / $limit);

// Fetch residents
if ($search_sql) {
    $stmt = $conn->prepare("SELECT * FROM residents $search_sql ORDER BY last_name ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("sssssii", $search_param, $search_param, $search_param, $search_param, $search_param, $limit, $offset);
    $stmt->execute();
    $residents = $stmt->get_result();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM residents ORDER BY last_name ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $residents = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Residents</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            margin: 0;
            background: #f1f1f1;
        }

        .sidebar {
            width: 240px;
            background-color: #002855;
            color: white;
            height: 100vh;
            position: fixed;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            background-color: #001f3f;
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
            padding: 14px 20px;
            color: white;
            text-decoration: none;
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
            color:rgb(249, 250, 252);
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input[type="text"] {
            padding: 8px;
            width: 250px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .search-bar button {
            padding: 8px 15px;
            background-color: #00509e;
            color: white;
            border: none;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #00509e;
            color: white;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 12px;
            background: #00509e;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .pagination a.active {
            background: #003f7f;
            font-weight: bold;
        }

        .pagination a:hover {
            background: #004f9f;
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
    <a href="view_residents.php" class="active">View Residents</a>
    <a href="certificates.php">Certificates</a>
    <a href="announcements.php">Announcements</a>
    <a href="digital_id.php">View Digital ID</a>
    <a href="messages.php">Messages</a>
    <a href="profile.php">My Profile</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main-content">
    <h1>📋 Residents Directory</h1>

    <form method="GET" class="search-bar">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email, or contact...">
        <button type="submit">Search</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Resident ID</th>
                <th>Last Name</th>
                <th>Voter Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($residents->num_rows > 0): ?>
                <?php while ($resident = $residents->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($resident['id']) ?></td>
                        <td><?= htmlspecialchars($resident['last_name']) ?></td>
                        <td><?= htmlspecialchars($resident['voter_status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10">No residents found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
</body>
</html>