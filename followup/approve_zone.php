<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "followup") {
    exit("unauthorized");
}

$id = $_GET["id"] ?? null;

if (!$id) {
    exit("missing id");
}

/* GET REQUEST */
$stmt = $conn->prepare("SELECT * FROM zone_requests WHERE id = :id");
$stmt->execute([":id" => $id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    exit("not found");
}

try {

    /* INSERT INTO zone_map */
    $insert = $conn->prepare("
        INSERT INTO zone_map (sub_area, main_area, area, branch, created_at)
        VALUES (:sub_area, :main_area, :area, :branch, GETDATE())
    ");

    $insert->execute([
        ":sub_area"  => $request["sub_area"],
        ":main_area" => $request["main_area"],
        ":area"      => $request["area"],
        ":branch"    => $request["branch"]
    ]);

    /* UPDATE STATUS */
    $update = $conn->prepare("
        UPDATE zone_requests
        SET status = 'approved'
        WHERE id = :id
    ");

    $update->execute([":id" => $id]);

    /* NOTIFICATION */
    $notify = $conn->prepare("
        INSERT INTO notifications (user_role, message, is_read, created_at)
        VALUES ('fu', :msg, 0, GETDATE())
    ");

    $notify->execute([
        ":msg" => "Zone approved: " . $request["main_area"]
    ]);

    /* REDIRECT */
    header("Location: zone_requests.fu.php?approved=1");
    exit();

} catch (Exception $e) {
    exit("Error: " . $e->getMessage());
}