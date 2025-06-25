<?php
$mysqli = new mysqli("localhost", "root", "", "bago_app");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

// Count total records for pagination
$countQuery = "SELECT COUNT(*) as total FROM residents";
if (!empty($search)) {
    $search = $mysqli->real_escape_string($search);
    $countQuery .= " WHERE id LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%'";
}
$countResult = $mysqli->query($countQuery);
$totalResidents = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalResidents / $limit);

// Build main query
$query = "SELECT * FROM residents";
if (!empty($search)) {
    $query .= " WHERE id LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%'";
}
$query .= " ORDER BY last_name ASC LIMIT $limit OFFSET $offset";
$residents = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Residents | BaGo Admin</title>
    <style>
        * {
            box-sizing: border-box;
        }
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
            margin-bottom: 10px;
            align: center;
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
            transition: 0.3s;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #00509e;
        }

        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
        }

        .alert {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert button {
            background: none;
            border: none;
            font-size: 18px;
            color: #155724;
            cursor: pointer;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .top-bar form input[type="text"] {
            padding: 8px;
            width: 300px;
            max-width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .top-bar a {
            background-color: #00509e;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            table-layout: fixed;
        }

        th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
    font-size: 14px;
    word-wrap: break-word; /* This can remain */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px; /* You can customize width per column with classes if needed */
}

        th {
            background-color: #002855;
            color: white;
        }

        
        .action-btn {
    cursor: pointer;
}
table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        th, td {
            padding: 10px;
            text-align: left;
            vertical-align: middle;
            white-space: nowrap;
        }

        th:last-child,
        td:last-child {
            width: 180px;
        }

        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            margin-right: 5px;
        }

        .edit-btn {
            background-color: #ffc107;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        /* Optional: hover effects */
        .action-btn:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .top-bar form {
                margin-bottom: 10px;
                width: 100%;
            }
        }
    </style>
    <script>
    function dismissAlert() {
        document.getElementById('successAlert').style.display = 'none';
    }

    
    // Prevent closing dropdown if clicking inside it
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dropdown-content').forEach(dropdown => {
            dropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        });
    });

    function confirmNavigation(event, message) {
        if (!confirm(message)) {
            event.preventDefault();
        }
    }
</script>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../images/bago_logo.png" alt="App Logo">
        <h2>Admin Panel</h2>
     </div>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="residents.php" class="<?= basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'active' : '' ?>">Residents</a>
    <a href="certificates.php" class="<?= basename($_SERVER['PHP_SELF']) == 'certificates.php' ? 'active' : '' ?>">Certificates</a>
    <a href="announcements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>">Announcements</a>
    <a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>">Messages</a>
    <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">Reports</a>
    <a href="audit_trail.php" class="<?= basename($_SERVER['PHP_SELF']) == 'audit_trail.php' ? 'active' : '' ?>">Audit Trail</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="content">
    <h2>Resident List</h2>

    <?php $status = isset($_GET['status']) ? $_GET['status'] : ''; ?>
<?php if ($status == 'added' || $status == 'edited' || $status == 'deleted'): ?>
    <div id="successAlert" class="alert">
        <span>
            <?php
            if ($status == 'added') echo "✅ Resident successfully added.";
            elseif ($status == 'edited') echo "✅ Resident details updated.";
            elseif ($status == 'deleted') echo "🗑️ Resident deleted successfully.";
            ?>
        </span>
        <button onclick="dismissAlert()">×</button>
    </div>
<?php endif; ?>

    <div class="top-bar">
        <form method="get">
            <input type="text" name="search" placeholder="Search by ID or name..." value="<?= htmlspecialchars($search) ?>">
        </form>
        <a href="residents/add.php" onclick="confirmNavigation(event, 'Proceed to add a new resident?')">+ Add Resident</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First</th>
                <th>Middle</th>
                <th>Last</th>
                <th>Gender</th>
                <th>Birthday</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Email</th>
                <th>Voter</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($residents->num_rows > 0): ?>
            <?php $rowIndex = 0; ?>
            <?php while($row = $residents->fetch_assoc()): ?>
                <?php $dropdownId = "dropdown_" . $rowIndex++; ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                    <td><?= htmlspecialchars($row['middle_name']) ?></td>
                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                    <td><?= htmlspecialchars($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['birthdate']) ?></td>
                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['voter_status']) ?></td>
                    <td>
                        <a href="residents/edit.php?id=<?= $row['id'] ?>" 
   style="background-color:rgb(170, 253, 121); color: white; padding: 5px 10px; margin-right: 5px; border-radius: 4px; text-decoration: none;"
   onclick="return confirm('Edit this resident?')">Edit</a>
<a href="residents/delete.php?id=<?= $row['id'] ?>" 
   style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none;"
   onclick="return confirm('Are you sure you want to delete this resident?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="11" style="text-align:center;">No residents found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php
if ($totalPages > 1) {
    echo '<div style="margin-top: 20px; text-align: center;">';
    for ($i = 1; $i <= $totalPages; $i++) {
        echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '" style="display: inline-block; margin: 0 5px; padding: 8px 12px; border: 1px solid #ccc; background-color: ' . ($i == $page ? '#00509e' : '#fff') . '; color: ' . ($i == $page ? '#fff' : '#00509e') . '; text-decoration: none; border-radius: 4px;">' . $i . '</a>';
    }
    echo '</div>';
}
?>
</div>

</body>
</html>