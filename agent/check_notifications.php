<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

if(!isset($_SESSION["name"])){
    exit(json_encode([]));
}

$agent_name = $_SESSION["name"];

$stmt = $conn->prepare("
    SELECT TOP 1 *
    FROM cancel_orders
    WHERE
        agent_name = ?
        AND status != 'pending'
        AND is_notified = 0
    ORDER BY id DESC
");

$stmt->execute([$agent_name]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if($data){

    $update = $conn->prepare("
        UPDATE cancel_orders
        SET is_notified = 1
        WHERE id=?
    ");

    $update->execute([$data['id']]);

    echo json_encode([
        "status" => $data['status'],
        "order_number" => $data['order_number']
    ]);

}else{

    echo json_encode([]);
}
?>