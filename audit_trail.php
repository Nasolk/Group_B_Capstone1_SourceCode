<?php
require_once '../includes/db_connection.php';
require_once '../includes/session.php';

$search_name = $_GET['search_name'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$sort = $_GET['sort'] ?? 'timestamp';
$order = $_GET['order'] ?? 'desc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Sanitize allowed sort columns
$allowed_sorts = ['first_name', 'last_name', 'activity', 'timestamp'];
if (!in_array($sort, $allowed_sorts)) $sort = 'timestamp';
$order = ($order === 'asc') ? 'asc' : 'desc';

$where = "a.role = 'resident'";
if (!empty($search_name)) {
    $search_name_safe = $conn->real_escape_string($search_name);
    $where .= " AND CONCAT(r.first_name, ' ', r.last_name) LIKE '%$search_name_safe%'";
}
if (!empty($start_date)) {
    $where .= " AND DATE(a.timestamp) >= '" . $conn->real_escape_string($start_date) . "'";
}
if (!empty($end_date)) {
    $where .= " AND DATE(a.timestamp) <= '" . $conn->real_escape_string($end_date) . "'";
}

// Get total for pagination
$total_query = "SELECT COUNT(*) as total
                FROM audit_trail a
                LEFT JOIN residents r ON a.user_id = r.id
                WHERE $where";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];
$total_pages = ceil($total / $limit);

// Get paginated results
$sql = "SELECT a.*, r.first_name, r.last_name
        FROM audit_trail a
        LEFT JOIN residents r ON a.user_id = r.id
        WHERE $where
        ORDER BY $sort $order
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// For sort toggle
function sort_link($column, $label, $current_sort, $current_order) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    $query = http_build_query(array_merge($_GET, ['sort' => $column, 'order' => $new_order]));
    return "<a href=\"?{$query}\">$label</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f4f4;
        }

        .sidebar {
            width: 220px;
            background-color: #002855;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
        }

        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: block;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: #003366;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

 table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #eee; }
        

        h2 {
            margin-bottom: 15px;
        }

        .filter-form {
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-form input {
            padding: 5px 10px;
        }

        .filter-form button {
            padding: 6px 12px;
            background-color: #002855;
            color: white;
            border: none;
            cursor: pointer;
        }

        .filter-form button:hover {
            background-color:rgb(165, 190, 214);
        }
        .pagination a { padding: 5px 10px; margin: 0 2px; text-decoration: none; border: 1px solid #ccc; }
        .pagination strong { padding: 5px 10px; margin: 0 2px; background-color: #ddd; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="../images/bago_logo.png" alt="Logo">
        <h2>Admin Panel</h2>
    </div>
    <a href="dashboard.php">Dashboard</a>
    <a href="residents.php">Residents</a>
    <a href="certificates.php">Certificates</a>
    <a href="announcements.php">Announcements</a>
    <a href="messages.php">Messages</a>
    <a href="reports.php">Reports</a>
    <a href="audit_trail.php" class="active">Audit Trail</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main-content">
    <h2>Resident Activity Logs</h2>

    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="search_name" placeholder="Search by name" value="<?= htmlspecialchars($search_name) ?>">
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        <button type="submit">Filter</button>
        <a href="audit_trail.php"><button type="button">Reset</button></a>
    </form>

    <table>
        <tr>
            <th><?= sort_link('first_name', 'First Name', $sort, $order) ?></th>
            <th><?= sort_link('last_name', 'Last Name', $sort, $order) ?></th>
            <th><?= sort_link('activity', 'Activity', $sort, $order) ?></th>
            <th><?= sort_link('timestamp', 'Date & Time', $sort, $order) ?></th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name']) ?></td>
                <td><?= htmlspecialchars($row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['activity']) ?></td>
                <td><?= $row['timestamp'] ?></td>
            </tr>
        <?php } ?>
    </table>

    <div class="pagination">
        <?php if ($total_pages > 1): ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</body>
</html>