<?php
require_once '../includes/db_connection.php';

$reportType = $_GET['report'] ?? '';

if ($reportType === 'certificate') {
    $type = $_GET['type'] ?? '';

    // Validate certificate type
    $allowedTypes = ['Barangay Clearance', 'Certificate of Indigency', 'Certificate of Residency'];
    if (!in_array($type, $allowedTypes)) {
        echo json_encode(['error' => 'Invalid certificate type']);
        exit;
    }

    // Fetch counts
    $counts = [];

    // Week
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM certificates 
        WHERE certificate_type = ? 
        AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
    ");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $counts['week'] = $result['total'];

    // Month
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM certificates 
        WHERE certificate_type = ? 
        AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $counts['month'] = $result['total'];

    echo json_encode($counts);

} elseif ($reportType === 'id') {
    $counts = [];

    // Day
    $result = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE DATE(id_issued_at) = CURDATE()
    ");
    $counts['day'] = $result->fetch_assoc()['total'];

    // Week
    $result = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE DATE(id_issued_at) >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
    ");
    $counts['week'] = $result->fetch_assoc()['total'];

    // Month
    $result = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE id_issued_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $counts['month'] = $result->fetch_assoc()['total'];

    echo json_encode($counts);
} else {
    echo json_encode(['error' => 'Invalid report type']);
}
