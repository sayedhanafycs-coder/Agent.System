<?php

session_start();
include "../config/db.php";

if ($_SESSION["role"] != "manager") {
    exit("Access denied");
}

$id = $_GET['id'];

$stmt = $conn->prepare("
    UPDATE users
    SET
        is_online = 0,
        status = 'offline'
    WHERE id = ?
");

$stmt->execute([$id]);

header("Location: dashboard.php");
exit();
?>