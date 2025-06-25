<?php
require_once '../includes/db_connection.php';
require_once '../includes/session.php';

function getCount($conn, $type = null, $interval = 'DAY') {
    $query = "SELECT COUNT(*) AS total FROM certificates WHERE DATE(created_at) >= ";
    if ($interval === 'DAY') {
        $query .= "CURDATE()";
    } elseif ($interval === 'WEEK') {
        $query .= "DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
    } elseif ($interval === 'MONTH') {
        $query .= "DATE_FORMAT(CURDATE(), '%Y-%m-01')";
    }

    if ($type) {
        $query .= " AND certificate_type = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $type);
    } else {
        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function getIDIssuedCount($conn, $interval = 'DAY') {
    $query = "SELECT COUNT(*) AS total FROM residents WHERE DATE(id_issued_at) >= ";
    if ($interval === 'DAY') {
        $query .= "CURDATE()";
    } elseif ($interval === 'WEEK') {
        $query .= "DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
    } elseif ($interval === 'MONTH') {
        $query .= "DATE_FORMAT(CURDATE(), '%Y-%m-01')";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function getCountInRange($conn, $type = null, $startDate = null, $endDate = null) {
    $query = "SELECT COUNT(*) AS total FROM certificates WHERE 1";
    $params = [];
    $types = "";

    if ($startDate && $endDate) {
        $query .= " AND DATE(created_at) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= "ss";
    }

    if ($type) {
        $query .= " AND certificate_type = ?";
        $params[] = $type;
        $types .= "s";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function getIDIssuedCountInRange($conn, $startDate = null, $endDate = null) {
    $query = "SELECT COUNT(*) AS total FROM residents WHERE 1";
    $params = [];
    $types = "";

    if ($startDate && $endDate) {
        $query .= " AND DATE(id_issued_at) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= "ss";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

$filterStart = $_GET['start_date'] ?? null;
$filterEnd = $_GET['end_date'] ?? null;

$certificateTypes = ['Barangay Clearance', 'Certificate of Indigency', 'Certificate of Residency'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
            margin: 0;
        }
        .sidebar {
            width: 220px;
            background-color: #002855;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
        }
        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #003366;
        }
        .main-content {
            margin-left: 220px;
            padding: 20px;
            background-color: #f4f4f4;
            width: 100%;
        }
        .report-box {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .report-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-box table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background-color:rgb(36, 78, 192);
            color: white;
        }
        .total {
            font-weight: bold;
            background-color: #e8e8e8;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
         <img src="../images/bago_logo.png" style="display: block; margin: 0 auto; max-width: 100px;" alt="Logo">
        <h2>Admin Panel</h2>
    </div>
    <a href="dashboard.php">Dashboard</a>
    <a href="residents.php">Residents</a>
    <a href="certificates.php">Certificates</a>
    <a href="announcements.php">Announcements</a>
    <a href="messages.php">Messages</a>
    <a href="reports.php" class="active">Reports</a>
    <a href="audit_trail.php">Audit Trail</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main-content">
    <form method="GET" style="margin-bottom: 20px;">
        <label for="start_date">Start Date: </label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($filterStart ?? '') ?>">
        <label for="end_date">End Date: </label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($filterEnd ?? '') ?>">
        <button type="submit">Filter</button>
    </form>

    <h2>Certificate Issuance Report</h2>
    <div class="report-box">
        <table>
            <tr>
                <th>Certificate Type</th>
                <th>Today (Filtered)</th>
                <th>This Week</th>
                <th>This Month</th>
            </tr>
            <?php foreach ($certificateTypes as $type): ?>
            <tr id="<?= strtolower(str_replace(' ', '-', $type)) ?>-row">
                <td><?= htmlspecialchars($type) ?></td>
                <td class="day"><?= getCountInRange($conn, $type, $filterStart, $filterEnd) ?></td>
                <td class="week">Loading...</td>
                <td class="month">Loading...</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <h2>Digital ID Issuance Report</h2>
    <div class="report-box">
        <table>
            <tr>
                <th>Range</th>
                <th>Total Issued</th>
            </tr>
            <tr>
                <td>Today (Filtered)</td>
                <td><span id="id-day"><?= getIDIssuedCountInRange($conn, $filterStart, $filterEnd) ?></span></td>
            </tr>
            <tr>
                <td>This Week</td>
                <td><span id="id-week">Loading...</span></td>
            </tr>
            <tr>
                <td>This Month</td>
                <td><span id="id-month">Loading...</span></td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td><span id="id-total">Loading...</span></td>
            </tr>
        </table>
    </div>
</div>

<script>
function fetchCertificateData(type, rowId) {
    fetch(`fetch_report_data.php?report=certificate&type=${encodeURIComponent(type)}`)
        .then(response => response.json())
        .then(data => {
            const row = document.getElementById(rowId);
            if (row) {
                row.querySelector('.week').textContent = data.week;
                row.querySelector('.month').textContent = data.month;
            }
        });
}

function fetchIDData() {
    fetch('fetch_report_data.php?report=id')
        .then(response => response.json())
        .then(data => {
            document.getElementById("id-week").textContent = data.week;
            document.getElementById("id-month").textContent = data.month;
            document.getElementById("id-total").textContent =
                parseInt(data.day) + (parseInt(data.week) - parseInt(data.day)) + (parseInt(data.month) - parseInt(data.week));
        });
}

function refreshReports() {
    fetchCertificateData('Barangay Clearance', 'barangay-clearance-row');
    fetchCertificateData('Certificate of Indigency', 'certificate-of-indigency-row');
    fetchCertificateData('Certificate of Residency', 'certificate-of-residency-row');
    fetchIDData();
}

document.addEventListener("DOMContentLoaded", refreshReports);
setInterval(refreshReports, 5000);
</script>
</body>
</html>
