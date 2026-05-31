<?php
session_start();
include "../config/db.php";

header("Content-Type: application/json");

if ($_SESSION["role"] != "agent") {
    echo json_encode(["success"=>false, "msg"=>"Unauthorized"]);
    exit();
}

$name = $_SESSION["name"];

$date = $_POST['date'] ?? null;
$day = $_POST['day'] ?? null;
$action = $_POST['action'] ?? null;
$problem = $_POST['problem'] ?? null;
$from = $_POST['time_from'] ?? null;
$to = $_POST['time_to'] ?? null;
$minutes = $_POST['minutes'] ?? 0;

if(!$date || !$day || !$action){
    echo json_encode(["success"=>false,"msg"=>"Missing data"]);
    exit();
}

$sql = "INSERT INTO performance_issues
(agent_name, issue_date, day_name, action_type, problem, time_from, time_to, minutes)
VALUES (?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $name,
    $date,
    $day,
    $action,
    $problem,
    $from,
    $to,
    $minutes
]);

echo json_encode(["success"=>true, "msg"=>"Saved successfully"]);
?>