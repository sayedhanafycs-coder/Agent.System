<?php

include "../config/db.php";

header('Content-Type: application/json');

$pending = $conn->query("
    SELECT COUNT(*)
    FROM zone_requests
    WHERE status='pending'
")->fetchColumn();

$notifications = $conn->query("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_role='followup'
    AND is_read=0
")->fetchColumn();

echo json_encode([
    "pending" => $pending,
    "notifications" => $notifications
]);

?>