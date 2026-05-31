<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "agent") {
    exit();
}

$name = $_SESSION["name"];

$from = $_GET['from'];
$to = $_GET['to'];

$sql = "SELECT * FROM performance_issues
WHERE agent_name=?
AND issue_date BETWEEN ? AND ?
ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$name,$from,$to]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>