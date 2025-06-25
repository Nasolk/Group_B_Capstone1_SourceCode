<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once '../includes/audit_helper.php';
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password FROM residents WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $user, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION["resident_id"] = $id;

            // ✅ Audit log after successful login
            log_audit($id, 'Resident logged in', 'resident');

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Resident not found.";
    }
    $stmt->close();
}
?>

<!-- Login Form UI -->
<!DOCTYPE html>
<html>
<head>
    <title>Resident Login</title>
    <link rel="stylesheet" href="/BaGoApp/assets/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 30px;
            border: 1px solid #ccc;
            border-radius: 12px;
            background-color: #fefefe;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .login-container img.logo {
            width: 90px;
            margin-bottom: 15px;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 6px;
            margin-top: 10px;
        }

        .error-msg {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <img src="/BaGoApp/images/bago_logo.png" alt="BaGo Logo" class="logo">
    <h2>Resident Login</h2>
    <?php if (!empty($error)) echo '<div class="error-msg">' . $error . '</div>'; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>

        <p style="margin-top: 10px;">Don't have an account? <a href="signup.php">Sign up here</a></p>

    </form>
</div>
</body>
</html>