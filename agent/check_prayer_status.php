<?php
session_start();
include "../config/db.php";

header("Content-Type: application/json");

if(!isset($_SESSION["name"])){
    echo json_encode([
        "success" => false
    ]);
    exit();
}

$name = $_SESSION["name"];

$stmt = $conn->prepare("
    SELECT TOP 1 *
    FROM pray_requests
    WHERE agent_name = ?
    AND status != 'pending'
    AND agent_notified = 0
    ORDER BY id DESC
");

$stmt->execute([$name]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if($row){

    $update = $conn->prepare("
        UPDATE pray_requests
        SET agent_notified = 1
        WHERE id = ?
    ");

    $update->execute([$row['id']]);

    echo json_encode([
        "success" => true,
        "status" => $row['status'],
        "prayer" => $row['prayer_name'],
        "time" => $row['slot_time']
    ]);

}else{

    echo json_encode([
        "success" => false
    ]);
}
?>