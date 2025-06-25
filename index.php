<?php
session_start();
include('includes/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        } else {
            header("Location: resident/dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BaGo App Login</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(145deg, #f5f7fa, #c3cfe2);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container img.logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }

        .login-container h2 {
            color: #002855;
            margin-bottom: 30px;
        }

        .login-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #0056b3;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #004099;
        }

        .error {
            color: red;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- LOGO -->
    <img src="images/bago_logo.png" alt="BaGo Logo" class="logo">

    <h2>BaGo e-Services Login</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Enter your password" required>
        <button type="submit">Login</button>
    </form>

    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

    <div class="footer">© 2025 BaGo App | Barangay Digital Services</div>
</div>

</body>
</html>
