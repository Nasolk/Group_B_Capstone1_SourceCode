<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
log_audit($conn, $_SESSION['resident_id'], "Updated profile information");

$resident_id = $_SESSION['resident_id'];

$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$gender = $_POST['gender'];
$birthday = $_POST['birthday'];
$email = $_POST['email'];
$contact = $_POST['contact_number'];
$address = $_POST['address'];

$profile_photo = '';
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/uploads/';
    $filename = time() . '_' . basename($_FILES['profile_photo']['name']);
    $target_file = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
        $profile_photo = $filename;
    }
}

$query = "UPDATE residents SET first_name = ?, middle_name = ?, last_name = ?, gender = ?, birthday = ?, email = ?, contact_number = ?, address = ?";
$params = [$first_name, $middle_name, $last_name, $gender, $birthday, $email, $contact, $address];

if ($profile_photo !== '') {
    $query .= ", profile_photo = ?";
    $params[] = $profile_photo;
}

$query .= " WHERE id = ?";
$params[] = $resident_id;

$stmt = $conn->prepare($query);
$types = str_repeat('s', count($params) - 1) . 'i';
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    header("Location: profile.php?success=1");
    exit();
} else {
    echo "Error updating profile: " . $stmt->error;
}
?>