<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "followup") {
    exit("Unauthorized");
}

/* GET ID */
$id = $_GET["id"] ?? null;

if (!$id) {
    exit("Missing ID");
}

try {

    /* UPDATE STATUS */
    $stmt = $conn->prepare("
        UPDATE zone_requests
        SET status = 'rejected'
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    /* NOTIFICATION */
    $notify = $conn->prepare("
        INSERT INTO notifications (user_role, message, is_read, created_at)
        VALUES ('fu', :msg, 0, GETDATE())
    ");

    $notify->execute([
        ":msg" => "Zone request rejected"
    ]);

    /* REDIRECT */
    header("Location: zone_requests.fu.php?rejected=1");
    exit();

} catch (Exception $e) {

    echo $e->getMessage();
}