<?php
// signup.php (Resident Side with Pending Approval)
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/db_connection.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BaGoApp/includes/session.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $middleName = trim($_POST['middle_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM residents WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Username already taken.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO pending_users (first_name, middle_name, last_name, username, email, birthday, gender, contact_number, address, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $firstName, $middleName, $lastName, $username, $email, $birthday, $gender, $contact, $address, $hashedPassword);

            if ($stmt->execute()) {
                $message = "Signup submitted for approval. Please wait for admin confirmation.";
            } else {
                $message = "Submission failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident Signup - BaGo App</title>
    <link rel="stylesheet" href="/BaGoApp/assets/style.css">
    <style>
        body {
            background: #f7f9fb;
            font-family: 'Segoe UI', sans-serif;
        }
        .signup-container {
            width: 450px;
            margin: 50px auto;
            padding: 25px 30px;
            background: #fff;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            border-radius: 10px;
            text-align: center;
        }
        .signup-container img {
            width: 100px;
            margin-bottom: 15px;
        }
        .signup-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .signup-container input,
        .signup-container select {
            width: 100%;
            padding: 10px;
            margin: 7px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .signup-container button {
            width: 100%;
            padding: 10px;
            background: #2e8b57;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        .signup-container button:hover {
            background: #246b45;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .login-link {
            margin-top: 15px;
            font-size: 14px;
        }
        .login-link a {
            text-decoration: none;
            color: #2e8b57;
        }
    </style>
</head>
<body>
   <div class="signup-container">
    <img src="/BaGoApp/images/bago_logo.png" alt="BaGo Logo">
    <h2>Resident Sign Up</h2><?php if ($message): ?>
    <p class="error"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="middle_name" placeholder="Middle Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="date" name="birthday" required>
    <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>
    <input type="text" name="contact_number" placeholder="Contact Number" required>
    <input type="text" name="address" placeholder="Address" required>
    <input type="password" name="password" placeholder="Password (min 6 characters)" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Sign Up</button>
</form>

<div class="login-link">
    Already have an account? <a href="login.php">Log in here</a>
</div>

</div>
</body>
</html>