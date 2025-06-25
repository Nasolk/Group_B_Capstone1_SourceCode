<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['certificate_file'])) {
    $certificateId = intval($_POST['certificate_id']);
    $file = $_FILES['certificate_file'];

    $uploadDir = "../uploads/";
    $fileName = time() . '_' . basename($file["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        $stmt = $conn->prepare("UPDATE certificates SET certificate_image = ? WHERE id = ?");
        $stmt->bind_param("si", $fileName, $certificateId);
        $stmt->execute();
        $stmt->close();
        header("Location: certificates.php");
        exit;
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "Invalid request.";
}
?>