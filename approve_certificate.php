<?php
include '../config.php';

$id = $_GET['id'];
$stmt = $conn->prepare("UPDATE certificate_requests SET status='Approved', approved_at=NOW() WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: certificates.php");
?>