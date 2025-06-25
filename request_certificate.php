<?php
include '../config.php';
session_start();
require_once '../includes/audit_helper.php'; // Include your audit logger

if (!isset($_SESSION['resident_id'])) {
    header("Location: login.php");
    exit;
}

$resident_id = $_SESSION['resident_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['certificate_type'];

    $stmt = $conn->prepare("INSERT INTO certificate_requests (resident_id, certificate_type) VALUES (?, ?)");
    $stmt->bind_param("is", $resident_id, $type);
    
    if ($stmt->execute()) {
        // Log the audit trail
        log_audit($resident_id, "Requested a $type certificate", "resident");

        echo "<script>alert('Request submitted!'); location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to submit request.');</script>";
    }
}
?>

<form method="POST" onsubmit="return confirmRequest();">
    <label for="certificate_type">Select Certificate:</label>
    <select name="certificate_type" id="certificate_type" required>
        <option value="" disabled selected>-- Select Type --</option>
        <option value="Barangay Clearance">Barangay Clearance</option>
        <option value="Certificate of Indigency">Certificate of Indigency</option>
        <option value="Certificate of Residency">Certificate of Residency</option>
    </select>
    <button type="submit">Request Certificate</button>
</form>

<script>
function confirmRequest() {
    const selected = document.getElementById("certificate_type").value;
    if (!selected) {
        alert("Please select a certificate type.");
        return false;
    }
    return confirm("Are you sure you want to request a " + selected + "?");
}
</script>