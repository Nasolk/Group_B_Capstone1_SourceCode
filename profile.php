<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/residents_auth/auth_session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';

$resident_id = $_SESSION['resident_id'] ?? null;

// ✅ Log after defining $resident_id
if ($resident_id) {
    log_audit($resident_id, 'Resident viewed profile', 'resident');
}

$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$result = $stmt->get_result();
$resident = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="/BaGoApp/assets/style.css">
    <style>
       {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f9f9f9;
        }
        .sidebar {
            width: 240px;
            background-color: #002855;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 0;
        }
        .sidebar-header {
            background-color: #001f3f;
            padding: 20px 15px;
            text-align: center;
        }
        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .sidebar-header h2 {
            font-size: 18px;
            margin: 0;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            transition: 0.3s;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #00509e;
        }
        

        .main {
            flex: 1;
            padding: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 750px;
            margin: auto;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #002855;
            margin: 0 auto 20px;
            display: block;
        }

        .profile-group {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .profile-group input,
        .profile-group select {
            flex: 1;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-actions {
            text-align: right;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #002855;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-outline {
            background: none;
            border: 2px solid #002855;
            color: #002855;
            margin-top: 15px;
        }

        .readonly-view p {
            margin: 8px 0;
        }

        .toggle-btns {
            text-align: right;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="sidebar">
   <div class="sidebar-header">
        <img src="../images/bago_logo.png" alt="App Logo">
        <h2>Residents Panel</h2>
 </div>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="view_residents.php" class="<?= basename($_SERVER['PHP_SELF']) == 'view_residents.php' ? 'active' : '' ?>">View Residents</a>
    <a href="certificates.php" class="<?= basename($_SERVER['PHP_SELF']) == 'certificates.php' ? 'active' : '' ?>">Certificates</a>
    <a href="announcements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : '' ?>">Announcements</a>
     <a href="digital_id.php" class="<?= basename($_SERVER['PHP_SELF']) == 'digital_id.php' ? 'active' : '' ?>">View Digital ID</a>
    <a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>">Messages</a>
    <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ptofile.php' ? 'active' : '' ?>">My Profile</a>

    <a href="../logout.php">Logout</a>
</div>

<div class="main">
    <div class="card">
        <!-- Profile Photo -->
        <img src="/BaGoApp/uploads/<?= htmlspecialchars($resident['profile_photo']) ?>" alt="Profile Photo" class="profile-photo" id="previewPhoto">

        <!-- Toggle Buttons -->
        <div class="toggle-btns">
            <button class="btn-outline" onclick="toggleEdit()">Edit Profile</button>
            <button class="btn-outline" onclick="togglePassword()">Change Password</button>
        </div>

        <!-- Read-Only Display -->
        <div id="readonlyInfo">
            <p><strong>Name:</strong> <?= htmlspecialchars($resident['first_name']) ?> <?= htmlspecialchars($resident['middle_name']) ?> <?= htmlspecialchars($resident['last_name']) ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($resident['gender']) ?></p>
            <p><strong>Birthday:</strong> <?= htmlspecialchars($resident['birthday']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($resident['email']) ?></p>
            <p><strong>Contact Number:</strong> <?= htmlspecialchars($resident['contact_number']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($resident['address']) ?></p>
        </div>

        <!-- Edit Profile Form -->
        <form id="editForm" action="update_profile.php" method="POST" enctype="multipart/form-data" style="display:none;">
            <div class="profile-group">
                <input type="text" name="first_name" placeholder="First Name" value="<?= htmlspecialchars($resident['first_name']) ?>" required>
                <input type="text" name="middle_name" placeholder="Middle Name" value="<?= htmlspecialchars($resident['middle_name']) ?>">
                <input type="text" name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($resident['last_name']) ?>" required>
            </div>

            <div class="profile-group">
                <select name="gender" required>
                    <option value="Male" <?= $resident['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $resident['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
                <input type="date" name="birthday" value="<?= $resident['birthday'] ?>" required>
            </div>

            <div class="profile-group">
                <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($resident['email']) ?>" required>
                <input type="text" name="contact_number" placeholder="Contact Number" value="<?= htmlspecialchars($resident['contact_number']) ?>">
            </div>

            <div class="form-group">
                <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($resident['address']) ?>" style="width:100%">
            </div>

            <div class="form-group">
                <label for="profile_photo">Change Photo</label>
                <input type="file" name="profile_photo" accept="image/*" onchange="previewImage(event)">
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">Save Information</button>
            </div>
        </form>

        <!-- Change Password -->
        <form id="passwordForm" action="change_password.php" method="POST" style="display:none;">
            <div class="form-group">
                <input type="password" name="current_password" placeholder="Current Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="new_password" placeholder="New Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">Change Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleEdit() {
        const form = document.getElementById('editForm');
        const readonly = document.getElementById('readonlyInfo');
        const passForm = document.getElementById('passwordForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        readonly.style.display = readonly.style.display === 'none' ? 'block' : 'none';
        passForm.style.display = 'none';
    }

    function togglePassword() {
        const passForm = document.getElementById('passwordForm');
        const editForm = document.getElementById('editForm');
        const readonly = document.getElementById('readonlyInfo');
        passForm.style.display = passForm.style.display === 'none' ? 'block' : 'none';
        editForm.style.display = 'none';
        readonly.style.display = passForm.style.display === 'none' ? 'block' : 'none';
    }

    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('previewPhoto');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
</body>
</html>