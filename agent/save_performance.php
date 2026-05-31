<?php
session_start();
include "config/db.php";

$agent_id = $_SESSION["id"];

$sql = "INSERT INTO performance_issues
(agent_id, date, day, action, problem, time_from, time_to)
VALUES (:agent_id,:date,:day,:action,:problem,:from,:to)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ":agent_id"=>$agent_id,
    ":date"=>$_POST["date"],
    ":day"=>$_POST["day"],
    ":action"=>$_POST["action"],
    ":problem"=>$_POST["problem"],
    ":from"=>$_POST["time_from"],
    ":to"=>$_POST["time_to"]
]);

echo "saved";