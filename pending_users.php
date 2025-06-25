<?php
require_once '../includes/db_connection.php';
require_once '../includes/session.php';
require_once '../includes/audit_helper.php';

$limit = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$filter = "";
$params = [];

if (!empty($_GET['search_name'])) {
    $filter .= " AND (first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%" . $_GET['search_name'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $filter .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $_GET['from_date'];
    $params[] = $_GET['to_date'];
}

$totalQuery = "SELECT COUNT(*) AS total FROM pending_users WHERE 1 $filter";
$totalStmt = $conn->prepare($totalQuery);
if ($params) {
    $types = str_repeat('s', count($params));
    $totalStmt->bind_param($types, ...$params);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result()->fetch_assoc();
$totalPages = ceil($totalResult['total'] / $limit);

$query = "SELECT * FROM pending_users WHERE 1 $filter ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($query);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending User Approvals</title>
    <link rel="stylesheet" href="/BaGoApp/assets/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            margin: 0;
        }
        .sidebar {
            width: 220px;
            background: #2e8b57;
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        .sidebar img {
            width: 100px;
            margin-bottom: 20px;
        }
        .sidebar h2 {
            margin: 0;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 12px 0;
        }
        .sidebar a:hover {
            text-decoration: underline;
        }
        .content {
            flex: 1;
            padding: 30px;
        }
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        input[type="text"], input[type="date"], button {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .actions button {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .approve { background: #4CAF50; color: white; }
        .reject { background: #f44336; color: white; }
        .pagination {
            margin-top: 20px;
        }
        .pagination a {
            padding: 6px 12px;
            margin-right: 5px;
            border: 1px solid #ccc;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        .pagination a.active {
            background-color: #2e8b57;
            color: white;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 20px;
            width: 300px;
            border-radius: 8px;
            text-align: center;
        }
        .modal-content h3 {
            margin-top: 0;
        }
        .modal-content button {
            margin: 10px 5px 0;
            padding: 6px 12px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="/BaGoApp/images/bago_logo.png" alt="BaGo Logo">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="pending_users.php">Pending Approvals</a>
        <a href="residents.php">Residents</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1>Pending User Approvals</h1>

        <form method="GET" class="filter-form">
            <input type="text" name="search_name" placeholder="Search by name" value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>">
            <input type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
            <input type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
            <button type="submit">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>First</th>
                    <th>Last</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Birthday</th>
                    <th>Gender</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['first_name']) ?></td>
                        <td><?= htmlspecialchars($row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['birthday']) ?></td>
                        <td><?= htmlspecialchars($row['gender']) ?></td>
                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="actions">
                            <button class="approve" onclick="openModal('approve', <?= $row['id'] ?>)">Approve</button>
                            <button class="reject" onclick="openModal('reject', <?= $row['id'] ?>)">Reject</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10">No pending users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h3 id="modalText">Are you sure?</h3>
            <form id="confirmForm" method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <button type="submit" class="approve">Yes</button>
                <button type="button" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, userId) {
            const form = document.getElementById('confirmForm');
            const userInput = document.getElementById('modalUserId');
            const text = document.getElementById('modalText');
            const modal = document.getElementById('confirmModal');

            if (action === 'approve') {
                form.action = 'approve_user.php';
                text.innerText = "Approve this user?";
            } else {
                form.action = 'reject_user.php';
                text.innerText = "Reject this user?";
            }

            userInput.value = userId;
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>