<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "agent") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =========================
   SETTINGS
========================= */

$call_max_score = 145;

/* =========================
   AGENT INFO
========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id = ?
");

$stmt->execute([$user_id]);

$agent = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   STATS
========================= */

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_calls,
        AVG(total_score) as avg_score,
        MAX(total_score) as best_score,
        MIN(total_score) as worst_score
    FROM evaluations
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   AGENT RANKING
========================= */

$rankingQuery = $conn->query("
    SELECT 
        users.id,
        users.name,
        ROUND(AVG(evaluations.total_score),1) as avg_score
    FROM evaluations
    INNER JOIN users 
        ON users.id = evaluations.user_id
    WHERE users.role = 'agent'
    GROUP BY users.id, users.name
    ORDER BY avg_score DESC
");

$ranking = $rankingQuery->fetchAll(PDO::FETCH_ASSOC);

$agent_rank = "-";

foreach($ranking as $index => $r){
    if($r["id"] == $user_id){
        $agent_rank = $index + 1;
        break;
    }
}

/* =========================
   EVALUATIONS
========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM evaluations
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$user_id]);

$evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   GROUP BY WEEK (ACTUAL)
========================= */

$weeks = [];

foreach ($evaluations as $index => $evaluation) {

    $week_number = floor($index / 6) + 1; // grouping logic فقط للعرض

    if (!isset($weeks[$week_number])) {
        $weeks[$week_number] = [
            "calls" => [],
            "total" => 0,
            "count" => 0
        ];
    }

    $weeks[$week_number]["calls"][] = $evaluation;
    $weeks[$week_number]["total"] += $evaluation["total_score"];
    $weeks[$week_number]["count"]++;
}

/* =========================
   WEEKLY COMPARISON (ACTUAL BASED)
========================= */

$weekly_percentages = [];

foreach($weeks as $week_number => $week){

    $week_max = $week["count"] * $call_max_score;

    if ($week_max == 0) {
        $weekly_percentages[$week_number] = 0;
    } else {
        $weekly_percentages[$week_number] = round(
            ($week["total"] / $week_max) * 100,
            1
        );
    }
}

?>

<!DOCTYPE html>
<html lang="ar">

<head>

<meta charset="UTF-8">
<title>Quality Dashboard</title>

<style>

body{
    margin:0;
    direction:rtl;
    font-family:'Segoe UI';
    background:#1e293b;
    color:#fff;
}

.container{
    width:95%;
    max-width:1400px;
    margin:30px auto;
}

.title{
    font-size:30px;
    font-weight:bold;
    margin-bottom:25px;
}

.grid{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:15px;
    margin-bottom:25px;
}

.card{
    background:#111827;
    padding:20px;
    border-radius:18px;
    border:1px solid #374151;
    box-shadow:0 10px 25px rgba(0,0,0,.25);
}

.card-title{
    color:#9ca3af;
    margin-bottom:10px;
    font-size:14px;
}

.card-value{
    font-size:30px;
    font-weight:bold;
}

.rank{
    color:#fbbf24;
}

.week-box{
    background:#111827;
    border:1px solid #374151;
    border-radius:20px;
    margin-bottom:30px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
}

.week-header{
    background:linear-gradient(135deg,#8b6914,#d4af37);
    color:#fff;
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:20px;
    font-weight:bold;
}

.week-stats{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.week-average{
    background:rgba(255,255,255,.15);
    padding:8px 14px;
    border-radius:12px;
    font-size:15px;
}

.progress-area{
    padding:20px;
    background:#0f172a;
    border-bottom:1px solid #374151;
}

.progress-title{
    margin-bottom:12px;
    color:#cbd5e1;
    font-size:14px;
}

.progress-bar{
    width:100%;
    height:18px;
    background:#1e293b;
    border-radius:30px;
    overflow:hidden;
}

.progress-fill{
    height:100%;
    background:linear-gradient(90deg,#d4af37,#fbbf24);
    border-radius:30px;
}

.progress-text{
    margin-top:10px;
    font-size:14px;
    color:#fbbf24;
    font-weight:bold;
}

.compare{
    padding:15px 20px;
    background:#172033;
    border-bottom:1px solid #374151;
    font-size:15px;
}

.up{ color:#22c55e; font-weight:bold; }
.down{ color:#ef4444; font-weight:bold; }
.same{ color:#fbbf24; font-weight:bold; }

.table{
    width:100%;
    border-collapse:collapse;
}

.table th{
    background:#374151;
    padding:15px;
}

.table td{
    background:#1f2937;
    padding:14px;
    text-align:center;
    border-bottom:1px solid #374151;
}

.btn{
    background:#d4af37;
    color:#fff;
    padding:8px 14px;
    border-radius:10px;
    text-decoration:none;
    font-size:14px;
    font-weight:bold;
}

.logout{
    float:left;
    background:#dc2626;
}

.empty{
    text-align:center;
    padding:40px;
    color:#9ca3af;
}

</style>

</head>

<body>

<div class="container">

<a href="../logout.php" class="btn logout">🚪 Logout</a>

<div class="title">👋 Welcome <?= $agent["name"] ?></div>

<div class="grid">

<div class="card">
<div class="card-title">📞 عدد التقييمات</div>
<div class="card-value"><?= $stats["total_calls"] ?: 0 ?></div>
</div>

<div class="card">
<div class="card-title">⭐ متوسط السكور</div>
<div class="card-value"><?= round($stats["avg_score"],1) ?: 0 ?>%</div>
</div>

<div class="card">
<div class="card-title">🔥 أعلى Score</div>
<div class="card-value"><?= $stats["best_score"] ?: 0 ?></div>
</div>

<div class="card">
<div class="card-title">⚠️ أقل Score</div>
<div class="card-value"><?= $stats["worst_score"] ?: 0 ?></div>
</div>

<div class="card">
<div class="card-title">🏆 ترتيبك</div>
<div class="card-value rank">#<?= $agent_rank ?></div>
</div>

</div>

<?php if(count($weeks) > 0): ?>

<?php foreach($weeks as $week_number => $week): ?>

<?php
$total_score = $week["total"];
$week_max = $week["count"] * $call_max_score;

$percentage = ($week_max > 0)
    ? round(($total_score / $week_max) * 100, 1)
    : 0;

$progress = $percentage;
?>

<div class="week-box">

<div class="week-header">

<div>📅 الأسبوع <?= $week_number ?></div>

<div class="week-stats">

<div class="week-average">
🏆 Total: <?= $total_score ?>/<?= $week_max ?>
</div>

<div class="week-average">
📞 Calls: <?= $week["count"] ?>
</div>

<div class="week-average">
⭐ <?= $percentage ?>%
</div>

</div>

</div>

<div class="progress-area">

<div class="progress-title">Weekly Progress</div>

<div class="progress-bar">
<div class="progress-fill" style="width:<?= $progress ?>%;"></div>
</div>

<div class="progress-text"><?= $progress ?>%</div>

</div>

<table class="table">

<tr>
<th>#</th>
<th>التاريخ</th>
<th>النوع</th>
<th>الاسكور</th>
<th>النسبة</th>
<th>التفاصيل</th>
</tr>

<?php foreach($week["calls"] as $e): ?>

<?php
$call_percent = round(($e["total_score"] / $call_max_score) * 100,1);
?>

<tr>

<td><?= $e["id"] ?></td>
<td><?= $e["call_date"] ?></td>
<td><?= $e["call_type"] ?></td>
<td><?= $e["total_score"] ?>/<?= $call_max_score ?></td>
<td><?= $call_percent ?>%</td>

<td>
<a class="btn" href="view_evaluation.php?id=<?= $e["id"] ?>">👁️ عرض</a>
</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="empty">لا يوجد تقييمات حتى الآن</div>

<?php endif; ?>

</div>

</body>
</html>