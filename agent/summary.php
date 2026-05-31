<?php
session_start();
include $_SERVER['DOCUMENT_ROOT']."/config/db.php";

$agent_id = $_SESSION["id"];

$today = new DateTime();
$day = $today->format("d");

if ($day >= 21) {
    $start = $today->format("Y-m-21");
    $end = date("Y-m-20", strtotime("+1 month", strtotime($start)));
} else {
    $start = date("Y-m-21", strtotime("-1 month"));
    $end = date("Y-m-20");
}

$sql = "
SELECT action,
ISNULL(SUM(DATEDIFF(MINUTE, time_from, time_to)),0) AS minutes
FROM performance_issues
WHERE agent_id=:id
AND date BETWEEN :start AND :end
GROUP BY action
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id"=>$agent_id,
    ":start"=>$start,
    ":end"=>$end
]);

$coaching = 0;
$logout = 0;

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if($row["action"]=="Coashing"){
        $coaching = $row["minutes"];
    }
    if($row["action"]=="Logout"){
        $logout = $row["minutes"];
    }
}

echo json_encode([
    "coaching"=>$coaching,
    "logout"=>$logout
]);