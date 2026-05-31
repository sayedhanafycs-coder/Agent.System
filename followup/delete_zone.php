<?php
include "../config/db.php";

$id = $_GET["id"];

$stmt = $conn->prepare("DELETE FROM zone_map WHERE id=?");
$stmt->execute([$id]);

echo json_encode(["success"=>true]);