<?php

session_start();
include "../config/db.php";

header('Content-Type: application/json');

if(!isset($_SESSION["role"]) || $_SESSION["role"] != "manager"){
    echo json_encode([
        "success" => false
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT TOP 1 *
    FROM pray_requests
    WHERE status='pending'
    ORDER BY id DESC
");

$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if($row){

    echo json_encode([
        "success" => true,
        "id" => $row['id'],
        "agent_name" => $row['agent_name'],
        "prayer_name" => $row['prayer_name'],
        "slot_time" => date("h:i A", strtotime($row['slot_time'])),
        "created_at" => date("Y-m-d h:i A", strtotime($row['created_at']))
    ]);

}else{

    echo json_encode([
        "success" => false
    ]);
}