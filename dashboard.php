<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';

$residentId = $_SESSION['resident_id'] ?? null;
if (!$residentId) {
    header("Location: login.php");
    exit;
}

// ✅ Log page visit
log_audit($residentId, 'Visited Dashboard', 'resident');

// Fetch data for demographics
$genderData = $conn->query("SELECT gender, COUNT(*) as count FROM residents GROUP BY gender");
$ageData = $conn->query("SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 0 AND 12 THEN 'Child (0-12)'
        WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 13 AND 19 THEN 'Teen (13-19)'
        WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 20 AND 59 THEN 'Adult (20-59)'
        ELSE 'Senior (60+)' 
    END as age_group, COUNT(*) as count 
    FROM residents GROUP BY age_group");
$voterData = $conn->query("SELECT voter_status, COUNT(*) as count FROM residents GROUP BY voter_status");
$totalResidents = $conn->query("SELECT COUNT(*) as total FROM residents")->fetch_assoc()['total'];
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
        

        .main {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
        }

        h1 {
            margin-bottom: 20px;
            color: #002855;
        }

        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .chart-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        canvas {
            width: 100% !important;
            height: auto !important;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../images/bago_logo.png" alt="App Logo">
        <h2>Residents Panel</h2>
   </div>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="view_residents.php" class="<?= basename($_SERVER['PHP_SELF']) == 'view_residents.php' ? 'active' : '' ?>">View Residents</a>
    <a href="certificates.php" class="<?= basename($_SERVER['PHP_SELF']) == 'certificates.php' ? 'active' : '' ?>">Certificates</a>
    <a href="announcements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>">Announcements</a>
     <a href="digital_id.php" class="<?= basename($_SERVER['PHP_SELF']) == 'digital_id.php' ? 'active' : '' ?>">View Digital ID</a>
    <a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>">Messages</a>
    <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ptofile.php' ? 'active' : '' ?>">My Profile</a>

    <a href="../logout.php">Logout</a>
</div>
<div class="main">
    <h1>Demographic Overview</h1>

    <div class="charts">
        <div class="chart-card">
            <h3>Gender Distribution</h3>
            <canvas id="genderChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Age Group</h3>
            <canvas id="ageChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Voter Status</h3>
            <canvas id="voterChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Total Population</h3>
            <canvas id="totalChart"></canvas>
        </div>
    </div>
</div>

<script>
    const genderData = {
        labels: [<?php while ($row = $genderData->fetch_assoc()) echo "'{$row['gender']}',"; ?>],
        datasets: [{
            label: 'Gender Count',
            data: [<?php $genderData->data_seek(0); while ($row = $genderData->fetch_assoc()) echo "{$row['count']},"; ?>],
            backgroundColor: ['#0066cc', '#ff6666', '#cccccc']
        }]
    };

    const ageData = {
        labels: [<?php while ($row = $ageData->fetch_assoc()) echo "'{$row['age_group']}',"; ?>],
        datasets: [{
            label: 'Age Group Count',
            data: [<?php $ageData->data_seek(0); while ($row = $ageData->fetch_assoc()) echo "{$row['count']},"; ?>],
            backgroundColor: ['#ffd166', '#06d6a0', '#118ab2', '#ef476f']
        }]
    };

    const voterData = {
        labels: [<?php while ($row = $voterData->fetch_assoc()) echo "'{$row['voter_status']}',"; ?>],
        datasets: [{
            label: 'Voter Status',
            data: [<?php $voterData->data_seek(0); while ($row = $voterData->fetch_assoc()) echo "{$row['count']},"; ?>],
            backgroundColor: ['#2a9d8f', '#e76f51']
        }]
    };

    const totalData = {
        labels: ['Total Residents'],
        datasets: [{
            label: 'Population',
            data: [<?= $totalResidents ?>],
            backgroundColor: ['#264653']
        }]
    };

    new Chart(document.getElementById('genderChart'), { type: 'pie', data: genderData });
    new Chart(document.getElementById('ageChart'), { type: 'bar', data: ageData });
    new Chart(document.getElementById('voterChart'), { type: 'doughnut', data: voterData });
    new Chart(document.getElementById('totalChart'), { type: 'bar', data: totalData });
</script>

</body>
</html>