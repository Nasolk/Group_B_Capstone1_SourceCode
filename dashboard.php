<?php
require_once '../includes/db_connection.php';
require_once 'auth_session.php';

// Gender count
$genderQuery = "SELECT gender, COUNT(*) as count FROM residents GROUP BY gender";
$genderResult = mysqli_query($conn, $genderQuery);
$genderData = [];
while ($row = mysqli_fetch_assoc($genderResult)) {
    $genderData[$row['gender']] = $row['count'];
}

// Age group count
$ageGroups = [
    '0-17' => 0,
    '18-30' => 0,
    '31-45' => 0,
    '46-60' => 0,
    '61+' => 0
];
$ageQuery = "SELECT birthday FROM residents";
$ageResult = mysqli_query($conn, $ageQuery);
$today = new DateTime();
while ($row = mysqli_fetch_assoc($ageResult)) {
    $birthDate = new DateTime($row['birthday']);
    $age = $today->diff($birthDate)->y;
    if ($age <= 17) $ageGroups['0-17']++;
    elseif ($age <= 30) $ageGroups['18-30']++;
    elseif ($age <= 45) $ageGroups['31-45']++;
    elseif ($age <= 60) $ageGroups['46-60']++;
    else $ageGroups['61+']++;
}

// Voter status
$voterQuery = "SELECT voter_status, COUNT(*) as count FROM residents GROUP BY voter_status";
$voterResult = mysqli_query($conn, $voterQuery);
$voterData = [];
while ($row = mysqli_fetch_assoc($voterResult)) {
    $voterData[$row['voter_status']] = $row['count'];
}

// Total population
$totalQuery = "SELECT COUNT(*) as total FROM residents";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalPopulation = $totalRow['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | BaGo Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        h1 {
            color: #002855;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../images/bago_logo.png" alt="App Logo">
        <h2>Admin Panel</h2>
   </div>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="residents.php" class="<?= basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'active' : '' ?>">Residents</a>
   <?php
// Count new/pending requests
$notifResult = $conn->query("SELECT COUNT(*) as total FROM certificates WHERE status = 'pending'");
$notifCount = $notifResult->fetch_assoc()['total'];
?>

<a href="certificates.php" class="active">
    Certificates
    <?php if ($notifCount > 0): ?>
        <span style="background:red; color:white; border-radius:50%; padding:3px 7px; font-size:12px; margin-left:5px;">
            <?= $notifCount ?>
        </span>
    <?php endif; ?>
</a>
    <a href="announcements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>">Announcements</a>
    <a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>">Messages</a>
    <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">Reports</a>
    <a href="audit_trail.php" class="<?= basename($_SERVER['PHP_SELF']) == 'audit_trail.php' ? 'active' : '' ?>">Audit Trail</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="content">
    <h1>BaGo Demographic Dashboard</h1>

    <div class="card">
        <h2>Total Population</h2>
        <p><?= $totalPopulation ?> residents</p>
    </div>

    <div class="charts-container">
        <div class="card">
            <h3>Gender Distribution</h3>
            <canvas id="genderChart"></canvas>
        </div>

        <div class="card">
            <h3>Age Groups</h3>
            <canvas id="ageChart"></canvas>
        </div>

        <div class="card">
            <h3>Voter Status</h3>
            <canvas id="voterChart"></canvas>
        </div>
    </div>
</div>

<script>
    const genderChart = new Chart(document.getElementById('genderChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($genderData)) ?>,
            datasets: [{
                label: 'Gender',
                data: <?= json_encode(array_values($genderData)) ?>,
                backgroundColor: ['#007bff', '#dc3545', '#ffc107']
            }]
        }
    });

    const ageChart = new Chart(document.getElementById('ageChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($ageGroups)) ?>,
            datasets: [{
                label: 'Age Group',
                data: <?= json_encode(array_values($ageGroups)) ?>,
                backgroundColor: '#28a745'
            }]
        }
    });

    const voterChart = new Chart(document.getElementById('voterChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($voterData)) ?>,
            datasets: [{
                label: 'Voter Status',
                data: <?= json_encode(array_values($voterData)) ?>,
                backgroundColor: ['#17a2b8', '#6c757d']
            }]
        }
    });
</script>

</body>
</html>
