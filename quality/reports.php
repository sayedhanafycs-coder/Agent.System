<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "quality") {
    header("Location: ../login.php");
    exit();
}

/* =====================================
   SYSTEM DATES
===================================== */

$system_start = "2026-05-21";
$system_end   = "2026-12-20";

/* =====================================
   TODAY / YESTERDAY
===================================== */

$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 day"));

/* =====================================
   TODAY STATS
===================================== */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_calls,
        AVG(total_score) as avg_score
    FROM evaluations
    WHERE CAST(call_date AS DATE) = ?
");

$stmt->execute([$today]);

$today_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$today_avg = round((($today_stats["avg_score"] ?? 0) / 150) * 100, 1);

/* =====================================
   YESTERDAY STATS
===================================== */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_calls,
        AVG(total_score) as avg_score
    FROM evaluations
    WHERE CAST(call_date AS DATE) = ?
");

$stmt->execute([$yesterday]);

$yesterday_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$yesterday_avg = round((($yesterday_stats["avg_score"] ?? 0) / 150) * 100, 1);

$diff = round($today_avg - $yesterday_avg,1);

/* =====================================
   BEST / WORST AGENT
===================================== */

$stmt = $conn->prepare("
    SELECT
        users.name,
        ROUND(AVG(evaluations.total_score),1) as avg_score
    FROM evaluations
    INNER JOIN users
        ON users.id = evaluations.user_id
    WHERE CAST(call_date AS DATE) = ?
    GROUP BY users.name
    ORDER BY avg_score DESC
");

$stmt->execute([$yesterday]);

$daily_agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$best_agent = $daily_agents[0] ?? null;
$worst_agent = end($daily_agents);

/* =====================================
   BUILD MONTHS
===================================== */

$months = [];

$current = strtotime($system_start);

$month_number = 1;

while($current <= strtotime($system_end)){

    $start = date("Y-m-21", $current);

    $end = date(
        "Y-m-20",
        strtotime("+1 month", strtotime($start))
    );

    $months[$month_number] = [
        "start" => $start,
        "end" => $end,
        "title" => date("F Y", strtotime($start))
    ];

    $current = strtotime("+1 month", strtotime($start));

    $month_number++;
}

/* =====================================
   BUILD ALL WEEKS
===================================== */

$all_weeks = [];

$month_start = strtotime($system_start);
$week_number = 1;

while ($month_start <= strtotime($system_end)) {

    $month_end = strtotime("+1 month -1 day", strtotime(date("Y-m-21", $month_start)));

    if ($month_end > strtotime($system_end)) {
        $month_end = strtotime($system_end);
    }

    $current_start = strtotime(date("Y-m-21", $month_start));

    $days_range = ($month_end - $current_start) / (60 * 60 * 24) + 1;

    $week_size = ceil($days_range / 4);

    $temp_start = $current_start;

    for ($i = 1; $i <= 4; $i++) {

        $temp_end = strtotime("+".($week_size - 1)." days", $temp_start);

        if ($i == 4 || $temp_end > $month_end) {
            $temp_end = $month_end;
        }

        $all_weeks[$week_number] = [
            "start" => date("Y-m-d", $temp_start),
            "end" => date("Y-m-d", $temp_end)
        ];

        $temp_start = strtotime("+1 day", $temp_end);
        $week_number++;

        if ($temp_start > $month_end) {
            break;
        }
    }

    $month_start = strtotime("+1 month", $month_start);
}
/* =====================================
   SELECTED WEEK
===================================== */

$current_week = $_GET["week"] ?? 1;

$selected_week = $all_weeks[$current_week];

/* =====================================
   WEEK DATA
===================================== */

$stmt = $conn->prepare("
    SELECT
        evaluations.*,
        users.name
    FROM evaluations
    INNER JOIN users
        ON users.id = evaluations.user_id
    WHERE call_date BETWEEN ? AND ?
    ORDER BY total_score DESC
");

$stmt->execute([
    $selected_week["start"],
    $selected_week["end"]
]);

$weekly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================
   WEEK STATS
===================================== */

$week_total = 0;

foreach($weekly_data as $w){

    $week_total += $w["total_score"];
}

$week_max = count($weekly_data) * 150;

$week_percent = 0;

if($week_max > 0){

    $week_percent = round(
        ($week_total / $week_max) * 100,
        1
    );
}

/* =====================================
   SELECTED MONTH
===================================== */

$current_month = $_GET["month"] ?? 1;

$selected_month = $months[$current_month];

/* =====================================
   MONTH DATA
===================================== */

$stmt = $conn->prepare("
    SELECT
        evaluations.*,
        users.name
    FROM evaluations
    INNER JOIN users
        ON users.id = evaluations.user_id
    WHERE call_date BETWEEN ? AND ?
    ORDER BY call_date ASC
");

$stmt->execute([
    $selected_month["start"],
    $selected_month["end"]
]);

$month_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================
   MONTH WEEKS
===================================== */

$weeks = [];

foreach($month_data as $eval){

    $days = floor(
        (
            strtotime($eval["call_date"])
            -
            strtotime($selected_month["start"])
        ) / (60*60*24)
    );

    $week = floor($days / 7) + 1;

    if($week > 4){
        $week = 4;
    }

    if(!isset($weeks[$week])){

        $weeks[$week] = [
            "total" => 0,
            "count" => 0
        ];
    }

    $weeks[$week]["total"] += $eval["total_score"];
    $weeks[$week]["count"]++;
}

/* =====================================
   MONTH CHART
===================================== */

$chart = [];

foreach($weeks as $week_num => $week){

    $max = $week["count"] * 150;

    $percent = 0;

    if($max > 0){

        $percent = round(
            ($week["total"] / $max) * 100,
            1
        );
    }

    $chart[$week_num] = [
        "score" => $week["total"],
        "max" => $max,
        "percent" => $percent
    ];
}

?>

<!DOCTYPE html>
<html lang="ar">

<head>

<meta charset="UTF-8">
<title>Advanced Reports</title>

<style>

body{
    margin:0;
    direction:rtl;
    font-family:'Segoe UI';
    background:#0f172a;
    color:#fff;
}

.container{
    width:95%;
    max-width:1450px;
    margin:30px auto;
}

.back-btn{
    display:inline-block;
    margin-bottom:20px;
    background:#d4af37;
    color:#fff;
    text-decoration:none;
    padding:12px 18px;
    border-radius:12px;
    font-weight:bold;
    transition:.2s;
}

.back-btn:hover{
    opacity:.85;
}

.title{
    font-size:32px;
    margin-bottom:25px;
    font-weight:bold;
}

.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
    margin-bottom:25px;
}

.card{
    background:#111827;
    border:1px solid #374151;
    border-radius:18px;
    padding:20px;
}

.card-title{
    color:#9ca3af;
    margin-bottom:10px;
    font-size:14px;
}

.card-value{
    font-size:28px;
    font-weight:bold;
}

.green{
    color:#22c55e;
}

.red{
    color:#ef4444;
}

.yellow{
    color:#fbbf24;
}

.section{
    background:#111827;
    border:1px solid #374151;
    border-radius:20px;
    padding:25px;
    margin-bottom:25px;
}

.section-title{
    font-size:24px;
    margin-bottom:20px;
    color:#fbbf24;
}

select{
    background:#1f2937;
    border:1px solid #374151;
    color:#fff;
    padding:12px;
    border-radius:12px;
    min-width:250px;
    margin-bottom:20px;
}

.chart-box{
    margin-bottom:25px;
}

.chart-title{
    margin-bottom:10px;
}

.progress{
    width:100%;
    height:28px;
    background:#1e293b;
    border-radius:30px;
    overflow:hidden;
}

.fill{
    height:100%;
    background:linear-gradient(90deg,#d4af37,#fbbf24);
}

.percent{
    margin-top:8px;
    color:#fbbf24;
    font-weight:bold;
}

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

@media(max-width:992px){

.grid{
    grid-template-columns:1fr 1fr;
}

.table{
    display:block;
    overflow-x:auto;
}

}

</style>

</head>

<body>

<div class="container">

<a href="dashboard.php" class="back-btn">
⬅ الرجوع للداش بورد
</a>

<div class="title">
📊 التقارير المتقدمة
</div>

<!-- TOP CARDS -->

<div class="grid">

<div class="card">
<div class="card-title">
📞 تقييمات اليوم
</div>

<div class="card-value">
<?= $today_stats["total_calls"] ?: 0 ?>
</div>
</div>

<div class="card">
<div class="card-title">
⭐ متوسط اليوم
</div>

<div class="card-value">
<?= $today_avg ?>%
</div>
</div>

<div class="card">
<div class="card-title">
📈 مقارنة بالأمس
</div>

<div class="card-value">

<?php if($diff > 0): ?>

<span class="green">
+<?= $diff ?>%
</span>

<?php elseif($diff < 0): ?>

<span class="red">
<?= $diff ?>%
</span>

<?php else: ?>

<span class="yellow">
0%
</span>

<?php endif; ?>

</div>
</div>

<div class="card">
<div class="card-title">
📅 النظام الحالي
</div>

<div class="card-value yellow">
4 Weeks / Month
</div>
</div>

</div>

<!-- BEST / WORST -->

<div class="grid">

<div class="card">

<div class="card-title">
🏆 أفضل Agent بالأمس
</div>

<div class="card-value yellow">

<?= $best_agent["name"] ?? "-" ?>

</div>

</div>

<div class="card">

<div class="card-title">
⭐ Score
</div>

<div class="card-value green">

<?= $best_agent["avg_score"] ?? 0 ?>%

</div>

</div>

<div class="card">

<div class="card-title">
⚠️ أقل Agent بالأمس
</div>

<div class="card-value red">

<?= $worst_agent["name"] ?? "-" ?>

</div>

</div>

<div class="card">

<div class="card-title">
📉 Score
</div>

<div class="card-value red">

<?= $worst_agent["avg_score"] ?? 0 ?>%

</div>

</div>

</div>

<!-- WEEKLY REPORT -->

<div class="section">

<div class="section-title">
📅 التقارير الأسبوعية
</div>

<form method="GET">

<select name="week" onchange="this.form.submit()">

<?php foreach($all_weeks as $key => $week): ?>

<option
value="<?= $key ?>"
<?= $current_week == $key ? 'selected' : '' ?>
>

الأسبوع <?= $key ?>

(
<?= $week["start"] ?>

→

<?= $week["end"] ?>
)

</option>

<?php endforeach; ?>

</select>

</form>

<div class="chart-box">

<div class="chart-title">

📊 إجمالي الأسبوع

<?= $week_total ?>/<?= $week_max ?>

</div>

<div class="progress">

<div
class="fill"
style="width:<?= $week_percent ?>%;"
>
</div>

</div>

<div class="percent">

<?= $week_percent ?>%

</div>

</div>

<table class="table">

<tr>
<th>Agent</th>
<th>التاريخ</th>
<th>النوع</th>
<th>السكور</th>
<th>النسبة</th>
</tr>

<?php foreach($weekly_data as $w): ?>

<?php

$p = round(
($w["total_score"] / 150) * 100,
1
);

?>

<tr>

<td><?= $w["name"] ?></td>

<td><?= $w["call_date"] ?></td>

<td><?= $w["call_type"] ?></td>

<td><?= $w["total_score"] ?>/150</td>

<td><?= $p ?>%</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<!-- MONTHLY REPORT -->

<div class="section">

<div class="section-title">
🗓️ التقارير الشهرية
</div>

<form method="GET">

<select name="month" onchange="this.form.submit()">

<?php foreach($months as $key => $month): ?>

<option
value="<?= $key ?>"
<?= $current_month == $key ? 'selected' : '' ?>
>

<?= $month["title"] ?>

(
<?= $month["start"] ?>

→

<?= $month["end"] ?>
)

</option>

<?php endforeach; ?>

</select>

</form>

<?php foreach($chart as $week_num => $data): ?>

<div class="chart-box">

<div class="chart-title">

📅 الأسبوع <?= $week_num ?>

—

<?= $data["score"] ?>/<?= $data["max"] ?>

</div>

<div class="progress">

<div
class="fill"
style="width:<?= $data["percent"] ?>%;"
>
</div>

</div>

<div class="percent">

<?= $data["percent"] ?>%

</div>

</div>

<?php endforeach; ?>

<table class="table">

<tr>
<th>الأسبوع</th>
<th>إجمالي الدرجات</th>
<th>الدرجة الكاملة</th>
<th>النسبة</th>
</tr>

<?php foreach($chart as $week_num => $data): ?>

<tr>

<td>
الأسبوع <?= $week_num ?>
</td>

<td>
<?= $data["score"] ?>
</td>

<td>
<?= $data["max"] ?>
</td>

<td>
<?= $data["percent"] ?>%
</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</body>
</html>