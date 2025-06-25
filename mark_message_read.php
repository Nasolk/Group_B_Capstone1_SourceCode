<?php
$mysqli = new mysqli("localhost", "root", "", "bago_app");
if (isset($_POST['message_id'])) {
    $id = intval($_POST['message_id']);
    $mysqli->query("UPDATE messages SET is_read = 1 WHERE id = $id");
}
?>