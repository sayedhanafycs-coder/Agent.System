<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

if(!isset($_SESSION["role"]) || $_SESSION["role"] != "followup"){
    echo json_encode(["success" => false]);
    exit();
}

try {

    // 🔥 هات أحدث طلب pending فقط بدون ما تقفله
    $stmt = $conn->prepare("
        SELECT TOP 1 *
        FROM cancel_orders
        WHERE status = 'pending'
        ORDER BY id DESC
    ");

    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if($order){

        echo json_encode([
            "success" => true,
            "id" => $order['id'],
            "agent_name" => $order['agent_name'],
            "order_number" => $order['order_number']
        ]);

        exit();
    }

    echo json_encode([
        "success" => false,
        "id" => 0
    ]);

} catch(Exception $e){

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}