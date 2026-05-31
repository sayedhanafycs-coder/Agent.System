<?php
include "../config/db.php";

header('Content-Type: application/json; charset=utf-8');

$search = trim($_GET["search"] ?? "");
$branch = trim($_GET["branch"] ?? "");

$sql = "
    SELECT TOP (100)
        branch,
        main_area,
        sub_area
    FROM zone_map
    WHERE 1=1
";

$params = [];

if ($branch !== "") {
    $sql .= " AND branch = ? ";
    $params[] = $branch;
}

if ($search !== "") {
    $sql .= " AND (sub_area LIKE ? OR main_area LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY branch ASC ";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));