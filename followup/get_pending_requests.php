<?php
include "../config/db.php";

$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM zone_requests
    WHERE status = 'pending'
");

$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);
?>