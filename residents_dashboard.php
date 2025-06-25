<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
// Redirect if not logged in

if (!isset($_SESSION['resident_id'])) {
    header("Location: /BaGoApp/residents_auth/login.php");
    exit();
}


// Fetch resident info
$residentId = $_SESSION['resident_id'];
$stmt = $conn->prepare("SELECT first_name, last_name FROM residents WHERE id = ?");
$stmt->bind_param("i", $residentId);
$stmt->execute();
$result = $stmt->get_result();
$resident = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resident Dashboard</title>
    <link rel="stylesheet" href="/BaGoApp/assets/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Welcome, <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>!</h2>
        <a href="/BaGoApp/residents_auth/logout.php" class="btn btn-danger">Logout</a>
    </div>
    <hr>
    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <a href="/BaGoApp/residents/certificates_view.php" class="btn btn-outline-primary w-100 p-3">
                📄 View Certificate Requests
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="/BaGoApp/residents/request_certificate.php" class="btn btn-outline-success w-100 p-3">
                📝 Request New Certificate
            </a>
        </div>
        <!-- You can add more features here -->
    </div>
</div>
</body>
</html>
