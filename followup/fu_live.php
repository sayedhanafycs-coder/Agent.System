<?php
include "../config/db.php";

$pending = $conn->query("
    SELECT COUNT(*) FROM zone_requests WHERE status='pending'
")->fetchColumn();

$lastId = $conn->query("
    SELECT TOP 1 id FROM zone_requests ORDER BY id DESC
")->fetchColumn();

echo json_encode([
    "pending" => $pending,
    "last_id" => $lastId
]);