<?php
include "../config/db.php";

$id = $_POST["id"];

$stmt = $conn->prepare("
UPDATE zone_map 
SET sub_area=?, main_area=?, branch=? 
WHERE id=?
");

$stmt->execute([
$_POST["sub_area"],
$_POST["main_area"],
$_POST["branch"],
$id
]);

echo json_encode(["success"=>true]);