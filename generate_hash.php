<?php
$password = '12345678'; // Change this to your desired password
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashed;
?>
