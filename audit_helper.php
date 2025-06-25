<?php
require_once 'db_connection.php'; // adjust path if needed

function log_audit($user_id, $activity, $role = 'resident') {  
    global $conn;  
    $stmt = $conn->prepare("INSERT INTO audit_trail (user_id, activity, role, timestamp) VALUES (?, ?, ?, NOW())");  
    $stmt->bind_param("iss", $user_id, $activity, $role);  
    $stmt->execute();  
}
?>