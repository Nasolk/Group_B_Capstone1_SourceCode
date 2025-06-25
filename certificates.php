<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';

$residentId = $_SESSION['resident_id'] ?? null;
if (!$residentId) {
    header("Location: login.php");
    exit;
}

// ✅ Optional: Log page visit
log_audit($residentId, 'Visited Certificate Request Page', 'resident');

$notification = null;

// ✅ Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['certificate_type'], $_POST['purpose'])) {
    $type = $_POST['certificate_type'];
    $purpose = trim($_POST['purpose']);

    $stmt = $conn->prepare("INSERT INTO certificates (resident_id, certificate_type, purpose, status, request_date) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iss", $residentId, $type, $purpose);
    $stmt->execute();
    $stmt->close();

    // ✅ Log the request submission
    log_audit($residentId, "Requested a certificate: $type - Purpose: $purpose", 'resident');

    // ✅ Redirect to prevent resubmission
    header("Location: certificates.php?requested=1");
    exit;
}

// ✅ Show notification if redirected
if (isset($_GET['requested'])) {
    $notification = "Your request has been submitted.";
}

// ✅ Fetch existing requests
$stmt = $conn->prepare("SELECT * FROM certificates WHERE resident_id = ? ORDER BY request_date DESC");
$stmt->bind_param("i", $residentId);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Status-based notification
if ($requests) {
    $latest = $requests[0];
    if ($latest['status'] === 'approved') {
        $notification = "✅ Your certificate request has been approved!";
    } elseif ($latest['status'] === 'denied') {
        $notification = "❌ Your certificate request was denied.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Certificates</title>
    <style>
        /* Your styling remains unchanged */
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
            padding: 30px 40px;
            max-width: 1000px;
            width: 100%;
        }
        .cert-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .cert-card {
            flex: 1 1 30%;
            background: #e9f0fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .cert-card h3 {
            margin-bottom: 10px;
        }
        .cert-card form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .cert-card textarea {
            width: 90%;
            height: 50px;
            margin-bottom: 10px;
            padding: 8px;
        }
        .cert-card button {
            padding: 8px 16px;
            border: none;
            background: #004080;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .cert-card button:hover {
            background: #002855;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #004080;
            color: white;
        }
        .status-pending { color: orange; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-denied { color: red; font-weight: bold; }
        .download-btn {
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
        }
        .download-btn:hover {
            background: #0056b3;
        }
        .notification {
            padding: 10px 15px;
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 5px solid #0c5460;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/bago_logo.png" alt="App Logo">
            <h2>Residents Panel</h2>
        </div>
        <a href="dashboard.php">Dashboard</a>
        <a href="view_residents.php">View Residents</a>
        <a href="certificates.php" class="active">Certificates</a>
        <a href="announcements.php">Announcements</a>
        <a href="digital_id.php">View Digital ID</a>
        <a href="messages.php">Messages</a>
        <a href="profile.php">My Profile</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="container">
        <h1>📄 Request Certificate</h1>

        <?php if ($notification): ?>
            <div class="notification"><?= htmlspecialchars($notification) ?></div>
        <?php endif; ?>

        <div class="cert-buttons">
            <?php
            $types = ['Residency', 'Barangay Clearance', 'Indigency'];
            foreach ($types as $type):
            ?>
            <div class="cert-card">
                <h3><?= $type ?></h3>
                <form method="POST">
                    <textarea name="purpose" placeholder="Enter purpose" required></textarea>
                    <input type="hidden" name="certificate_type" value="<?= $type ?>">
                    <button type="submit">Request</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <h2>📁 My Certificate Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Request Date</th>
                    <th>Admin Remarks</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['certificate_type']) ?></td>
                            <td><?= htmlspecialchars($req['purpose']) ?></td>
                            <td class="status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></td>
                            <td><?= date("M d, Y", strtotime($req['request_date'])) ?></td>
                            <td><?= $req['remarks'] ? htmlspecialchars($req['remarks']) : '-' ?></td>
                            <td>
                                <?php if ($req['status'] === 'approved' && $req['certificate_image']): ?>
                                    <a class="download-btn" href="../uploads/<?= htmlspecialchars($req['certificate_image']) ?>" download>Download</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No certificate requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", function(e) {
                const type = form.querySelector('input[name="certificate_type"]').value;
                const purposeField = form.querySelector('textarea[name="purpose"]');
                const purpose = purposeField.value.trim();
                const button = form.querySelector("button");

                if (!purpose) {
                    alert("Please enter a purpose for your request.");
                    e.preventDefault();
                    return;
                }

                const confirmed = confirm("Are you sure you want to request a \"" + type + "\" certificate for:\n\n" + purpose + "?");
                if (!confirmed) {
                    e.preventDefault();
                } else {
                    button.disabled = true;
                    button.textContent = "Requesting...";
                }
            });
        });
    </script>
</body>
</html>