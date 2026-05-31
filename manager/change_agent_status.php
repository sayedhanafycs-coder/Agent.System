<?php

session_start();
include "../config/db.php";

if ($_SESSION["role"] != "manager") {
    exit("Access denied");
}

$id = $_GET['id'];
$status = $_GET['status'];

$allowed = ['online','break','pray'];

if(!in_array($status, $allowed)){
    exit("Invalid status");
}

$stmt = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ?
");

$stmt->execute([$status, $id]);

header("Location: dashboard.php");
exit();
?>