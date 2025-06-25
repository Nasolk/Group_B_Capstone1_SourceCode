<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['certificate_id']);
    $remarks = trim($_POST['remarks']);

    $stmt = $conn->prepare("UPDATE certificates SET remarks = ? WHERE id = ?");
    $stmt->bind_param("si", $remarks, $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: certificates.php");
exit;