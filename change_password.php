<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

$resident_id = $_SESSION['resident_id'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

if ($new_password !== $confirm_password) {
    echo "Passwords do not match.";
    exit();
}

$stmt = $conn->prepare("SELECT password FROM residents WHERE id = ?");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($current_password, $user['password'])) {
    echo "Current password is incorrect.";
    exit();
}

$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE residents SET password = ? WHERE id = ?");
$stmt->bind_param("si", $new_password_hash, $resident_id);

if ($stmt->execute()) {
    header("Location: profile.php?password_changed=1");
    exit();
} else {
    echo "Error updating password: " . $stmt->error;
}