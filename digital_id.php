<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';

$resident_id = $_SESSION['resident_id'] ?? null;
if (!$resident_id) {
    header("Location: login.php");
    exit;
}

// ✅ Log audit trail AFTER resident ID is defined
log_audit($resident_id, 'Viewed Digital ID', 'resident');

// ✅ Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT digital_id_front, digital_id_back FROM residents WHERE id = ?");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$front = $row['digital_id_front'] ?? null;
$back = $row['digital_id_back'] ?? null;
?>


<!DOCTYPE html>
<html>
<head>
    <title>Digital ID | Residents</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            margin-left: 240px;
            padding: 30px;
            flex: 1;
        }
        h1 {
            color: #002855;
            margin-bottom: 20px;
        }
        .id-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .id-card {
            background-color: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 48%;
            max-width: 500px;
        }
        .id-card img {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .buttons {
            margin-top: 20px;
        }
        .buttons button {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .buttons .print-btn {
            background-color: #007bff;
            color: white;
        }
        .buttons .download-btn {
            background-color: #28a745;
            color: white;
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
    <h1>My Digital Barangay ID</h1>

    <?php if ($front && $back): ?>
        <div class="id-container">
            <div class="id-card">
                <h3>Front</h3>
                <img id="idFront" src="../uploads/digital_ids/<?php echo $front; ?>" alt="Digital ID Front">
            </div>
            <div class="id-card">
                <h3>Back</h3>
                <img id="idBack" src="../uploads/digital_ids/<?php echo $back; ?>" alt="Digital ID Back">
            </div>
        </div>

        <div class="buttons">
            <button class="print-btn" onclick="window.print()">Print</button>
            <button class="download-btn" onclick="downloadCombinedPDF()">Download PDF</button>
        </div>
    <?php else: ?>
        <p>No Digital ID available. Please contact the barangay staff.</p>
    <?php endif; ?>
</div>

<script>
    async function downloadCombinedPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'px', format: 'a4' });

        const frontImg = document.getElementById("idFront");
        const backImg = document.getElementById("idBack");

        const frontDataURL = await loadImageAsDataURL(frontImg.src);
        const backDataURL = await loadImageAsDataURL(backImg.src);

        const pageWidth = doc.internal.pageSize.getWidth();
        const padding = 30;
        const imageWidth = (pageWidth - padding * 3) / 2;

        const frontDims = await getImageDimensions(frontImg.src);
        const backDims = await getImageDimensions(backImg.src);

        const frontHeight = frontDims.height * (imageWidth / frontDims.width);
        const backHeight = backDims.height * (imageWidth / backDims.width);
        const imageHeight = Math.max(frontHeight, backHeight);

        doc.text("Barangay Digital ID", pageWidth / 2, 30, { align: "center" });
        doc.addImage(frontDataURL, 'JPEG', padding, 60, imageWidth, imageHeight);
        doc.addImage(backDataURL, 'JPEG', padding * 2 + imageWidth, 60, imageWidth, imageHeight);

        doc.save('Barangay_Digital_ID.pdf');
    }

    function loadImageAsDataURL(url) {
        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = "anonymous";
            img.onload = function () {
                const canvas = document.createElement("canvas");
                canvas.width = this.naturalWidth;
                canvas.height = this.naturalHeight;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(this, 0, 0);
                resolve(canvas.toDataURL("image/jpeg"));
            };
            img.src = url;
        });
    }

    function getImageDimensions(url) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve({ width: img.naturalWidth, height: img.naturalHeight });
            img.src = url;
        });
    }
</script>

</body>
</html>