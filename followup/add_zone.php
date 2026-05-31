<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "followup") {
    echo json_encode(["success" => false, "msg" => "unauthorized"]);
    exit();
}

$sub_area = $_POST["sub_area"] ?? null;
$area     = $_POST["area"] ?? null;
$comment  = $_POST["comment"] ?? null;

/* ================= VALIDATION ================= */

if (!$sub_area || !$area) {
    echo json_encode(["success" => false, "msg" => "missing fields"]);
    exit();
}

/* ================= GET branch + main_area FROM zone_map ================= */

$get = $conn->prepare("
    SELECT TOP 1 branch, main_area
    FROM zone_map
    WHERE sub_area = ? AND area = ?
");

$get->execute([$sub_area, $area]);

$zone = $get->fetch(PDO::FETCH_ASSOC);

if(!$zone){
    echo json_encode([
        "success" => false,
        "msg" => "zone not found in zone_map"
    ]);
    exit();
}

$branch = $zone["branch"];
$main_area = $zone["main_area"];

/* ================= INSERT REQUEST ================= */

$stmt = $conn->prepare("
    INSERT INTO zone_requests 
    (sub_area, main_area, area, branch, comment, status, created_at)
    VALUES 
    (:sub_area, :main_area, :area, :branch, :comment, 'pending', GETDATE())
");

$success = $stmt->execute([
    ":sub_area"  => $sub_area,
    ":main_area" => $main_area,
    ":area"      => $area,
    ":branch"    => $branch,
    ":comment"   => $comment
]);

echo json_encode([
    "success" => $success
]);

?>