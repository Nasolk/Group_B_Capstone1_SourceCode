<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/audit_helper.php';

// ✅ Get resident ID BEFORE destroying session
$resident_id = $_SESSION['resident_id'] ?? null;

if ($resident_id) {
    log_audit($resident_id, 'Resident logged out', 'resident');
}

session_unset();
session_destroy();

header("Location: /BaGoApp/residents_auth/login.php");
exit();