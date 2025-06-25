<!-- residents_auth/sidebar.php -->
<div class="sidebar">
    <h2>BaGo App</h2>
    <a href="/BaGoApp/residents_auth/dashboard.php">Dashboard</a>
    <a href="/BaGoApp/residents_auth/messages.php">Messages</a>
    <a href="/BaGoApp/residents/certificates.php">Certificate Requests</a>
    <a href="/BaGoApp/residents_auth/profile.php">My Info</a>
    <a href="/BaGoApp/residents_auth/digital_id.php">My Digital ID</a>
    <a href="/BaGoApp/residents_auth/logout.php">Logout</a>
</div>

<style>
.sidebar {
    width: 220px;
    background-color: #002855;
    color: white;
    padding: 20px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
}

.sidebar h2 {
    font-size: 18px;
    margin-bottom: 20px;
}

.sidebar a {
    color: white;
    text-decoration: none;
    display: block;
    padding: 10px 0;
}

.sidebar a:hover {
    background-color: #1a1a1a;
}
</style>
