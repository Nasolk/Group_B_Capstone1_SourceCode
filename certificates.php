<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

// Approve request
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE certificates SET status='approved' WHERE id=$id");
    echo "<script>location.href='certificates.php';</script>";
    exit;
}

// Deny request
if (isset($_GET['deny'])) {
    $id = intval($_GET['deny']);
    $conn->query("UPDATE certificates SET status='denied' WHERE id=$id");
    echo "<script>location.href='certificates.php';</script>";
    exit;
}

// Delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM certificates WHERE id=$id");
    echo "<script>location.href='certificates.php';</script>";
    exit;
}

// Update remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remarks_submit'])) {
    $remarks = $conn->real_escape_string($_POST['remarks']);
    $cert_id = intval($_POST['certificate_id']);
    $conn->query("UPDATE certificates SET remarks='$remarks' WHERE id=$cert_id");
    echo "<script>location.href='certificates.php';</script>";
    exit;
}

// Fetch certificate requests
$query = "
    SELECT c.*, r.first_name, r.middle_name, r.last_name 
    FROM certificates c 
    JOIN residents r ON c.resident_id = r.id 
    ORDER BY c.request_date DESC
";
$results = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Certificate Requests</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f0f0f5;
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
            padding: 20px;
            text-align: center;
        }

        .sidebar-header img {
            width: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
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

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #00509e;
        }

        .container {
            margin-left: 260px;
            padding: 30px;
            width: 100%;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            vertical-align: top;
        }

        th {
            background-color: #004080;
            color: white;
        }

        img.certificate-image {
            max-width: 200px;
            height: auto;
            border: 1px solid #ccc;
        }

        .btn {
            padding: 6px 12px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-approve { background-color: #28a745; color: white; }
        .btn-deny { background-color: #dc3545; color: white; }
        .btn-delete { background-color: #6c757d; color: white; }
        .btn-save { background-color: #007bff; color: white; }
        .btn-edit { background-color: #ffc107; color: black; }

        .status-approved { color: green; font-weight: bold; }
        .status-denied { color: red; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }

        .sidebar a span {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }

        .remarks-box {
            width: 100%;
            resize: vertical;
        }

        .remarks-buttons {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
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
        <?php
            $notifResult = $conn->query("SELECT COUNT(*) as total FROM certificates WHERE status = 'pending'");
            $notifCount = $notifResult->fetch_assoc()['total'];
        ?>
        <a href="certificates.php" class="active">
            Certificates
            <?php if ($notifCount > 0): ?>
                <span><?= $notifCount ?></span>
            <?php endif; ?>
        </a>
        <a href="announcements.php">Announcements</a>
        <a href="messages.php">Messages</a>
        <a href="reports.php">Reports</a>
        <a href="audit_trail.php">Audit Trail</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="container">
        <h1>📄 Certificate Requests</h1>
        <table>
            <thead>
                <tr>
                    <th>Resident</th>
                    <th>Type</th>
                    <th>Purpose</th>
                    <th>Upload</th>
                    <th>Remarks</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results->num_rows > 0): ?>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['certificate_type']) ?></td>
                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'denied'): ?>
                                    <em>Denied. Cannot upload.</em>
                                <?php elseif ($row['certificate_image']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($row['certificate_image']) ?>" class="certificate-image">
                                <?php else: ?>
                                    <form class="upload-form" action="upload_certificate.php" method="POST" enctype="multipart/form-data" onsubmit="setTimeout(() => location.reload(), 500);">
                                        <input type="file" name="certificate_file" accept="image/*,application/pdf" required>
                                        <input type="hidden" name="certificate_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-approve">Upload</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="remarks-form" id="remarks-form-<?= $row['id'] ?>">
                                    <textarea id="remarks-<?= $row['id'] ?>" name="remarks" class="remarks-box" rows="2" disabled><?= htmlspecialchars($row['remarks']) ?></textarea>
                                    <input type="hidden" name="certificate_id" value="<?= $row['id'] ?>">
                                    <div class="remarks-buttons">
                                        <button type="button" class="btn btn-edit" onclick="enableEdit(<?= $row['id'] ?>)">Edit</button>
                                        <button type="submit" name="remarks_submit" id="save-btn-<?= $row['id'] ?>" class="btn btn-save" style="display: none;">Save</button>
                                    </div>
                                </form>
                            </td>
                            <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="?approve=<?= $row['id'] ?>" class="btn btn-approve" onclick="return confirm('Approve this request?')">Approve</a>
                                    <a href="?deny=<?= $row['id'] ?>" class="btn btn-deny" onclick="return confirm('Deny this request?')">Deny</a>
                                <?php else: ?>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this request?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No certificate requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function enableEdit(id) {
            const textarea = document.getElementById('remarks-' + id);
            const saveBtn = document.getElementById('save-btn-' + id);
            textarea.removeAttribute('disabled');
            textarea.focus();
            saveBtn.style.display = 'inline-block';
        }
    </script>
</body>
</html>