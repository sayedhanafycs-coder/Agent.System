<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

if ($_SESSION["role"] != "followup") {
    echo json_encode(["success"=>false]);
    exit();
}

$stmt = $conn->prepare("
INSERT INTO zone_map (branch, main_area, sub_area, area, created_at)
VALUES (:branch, :main_area, :sub_area, :area, GETDATE())
");

$stmt->execute([
":branch" => $_POST["branch"],
":main_area" => $_POST["main_area"],
":sub_area" => $_POST["sub_area"],
":area" => $_POST["area"]
]);

echo json_encode(["success"=>true]);