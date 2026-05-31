<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION["role"])){
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];

/* ===============================
   SAVE REQUEST
================================ */

if(isset($_POST['reserve_prayer'])){

    $prayer_name = $_POST['prayer_name'];
    $slot_time   = $_POST['slot_time'];
    $notes       = trim($_POST['notes']);

    /* ===============================
       CHECK SLOT
    ================================ */

    $check = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM pray_requests
        WHERE slot_time = ?
        AND request_date = CAST(GETDATE() AS DATE)
        AND status != 'rejected'
    ");

    $check->execute([
        $slot_time
    ]);

    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if($exists['total'] > 0){

        $error = "This slot is already reserved";

    }else{

        $stmt = $conn->prepare("
            INSERT INTO pray_requests
            (
                agent_name,
                prayer_name,
                prayer_time,
                slot_time,
                request_date,
                status,
                created_at
            )
            VALUES
            (
                ?, ?, ?, ?, CAST(GETDATE() AS DATE), 'pending', GETDATE()
            )
        ");

        $stmt->execute([
            $name,
            $prayer_name,
            $prayer_name,
            $slot_time
        ]);

        $success = "Prayer reserved successfully";
    }
}

/* ===============================
   GET TODAY REQUESTS
================================ */

$stmt = $conn->prepare("
    SELECT *
    FROM pray_requests
    WHERE CAST(created_at AS DATE) = CAST(GETDATE() AS DATE)
    ORDER BY slot_time ASC
");

$stmt->execute();

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   HELPER
================================ */

function generateTimes($start, $end){

    $times = [];

    $current = strtotime($start);
    $finish  = strtotime($end);

    while($current <= $finish){

        $times[] = date("H:i", $current);

        $current = strtotime("+10 minutes", $current);
    }

    return $times;
}

/* ===============================
   PRAYER WINDOWS
================================ */

$dhuhr_times   = generateTimes("13:00", "16:20");
$asr_times     = generateTimes("16:30", "19:40");
$maghrib_times = generateTimes("19:50", "21:00");
$isha_times    = generateTimes("21:10", "23:50");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<title>Prayer Time Request</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Cairo',sans-serif;
}

body{
    background:#0f172a;
    color:white;
    padding:25px;
}

.page-title{
    font-size:32px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:25px;
}

.main-grid{
    display:grid;
    grid-template-columns:420px 1fr;
    gap:25px;
}

.card{
    background:#111827;
    border-radius:20px;
    padding:22px;
    border:1px solid rgba(212,175,55,.15);
}

.card-title{
    font-size:22px;
    font-weight:800;
    color:#d4af37;
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
    color:#cbd5e1;
}

select,
textarea{
    width:100%;
    background:#1f2937;
    border:none;
    outline:none;
    color:white;
    border-radius:12px;
    padding:12px;
    margin-bottom:18px;
}

.submit-btn{
    width:100%;
    border:none;
    background:#d4af37;
    color:#111827;
    font-size:16px;
    font-weight:900;
    padding:13px;
    border-radius:14px;
    cursor:pointer;
}

.success{
    background:#14532d;
    color:#4ade80;
    padding:12px;
    border-radius:12px;
    margin-bottom:18px;
}

.error{
    background:#7f1d1d;
    color:#fca5a5;
    padding:12px;
    border-radius:12px;
    margin-bottom:18px;
}

.widget-box{
    overflow:hidden;
    border-radius:18px;
    border:1px solid rgba(255,255,255,.08);
    margin-bottom:20px;
}

iframe{
    width:100%;
    border:none;
}

.table-box{
    overflow:auto;
    border-radius:18px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f2937;
    color:#d4af37;
    padding:14px;
}

td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.pending{color:#facc15;font-weight:bold;}
.approved{color:#4ade80;font-weight:bold;}
.rejected{color:#f87171;font-weight:bold;}

.queue-badge{
    display:inline-block;
    background:#1e293b;
    color:#cbd5e1;
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
}

</style>

</head>

<?php include "../includes/global_notifications.php"; ?>

<body>

<div class="page-title">
    Prayer Time Request
</div>

<div class="main-grid">

<div>

<div class="card">

<div class="card-title">
    Prayer Times
</div>

<div class="widget-box">

<iframe
id="iframe"
title="prayerWidget"
style="height:358px;"
scrolling="no"
src="https://www.islamicfinder.org/prayer-widget/360630/shafi/2/0/19.5/17.5">
</iframe>

</div>

</div>

<div class="card" style="margin-top:25px;">

<div class="card-title">
    Reserve Prayer Time
</div>

<?php if(isset($success)): ?>
<div class="success">
    <?= $success ?>
</div>
<?php endif; ?>

<?php if(isset($error)): ?>
<div class="error">
    <?= $error ?>
</div>
<?php endif; ?>

<form method="POST">

<label>Prayer</label>

<select
name="prayer_name"
id="prayerSelect"
onchange="updateTimes()"
required
>

<option value="">Choose Prayer</option>

<option value="Dhuhr">Dhuhr</option>
<option value="Asr">Asr</option>
<option value="Maghrib">Maghrib</option>
<option value="Isha">Isha</option>

</select>

<label>Reserve Time</label>

<select
name="slot_time"
id="timeSelect"
required
>
<option value="">Choose Time</option>
</select>

<label>Notes</label>

<textarea
name="notes"
placeholder="Optional notes..."
></textarea>

<button
type="submit"
name="reserve_prayer"
class="submit-btn"
>
Reserve Now
</button>

</form>

</div>

</div>

<div class="card">

<div class="card-title">
Today's Prayer Queue
</div>

<div class="table-box">

<table>

<tr>
<th>#</th>
<th>Agent</th>
<th>Prayer</th>
<th>Time</th>
<th>Status</th>
</tr>

<?php
$counter = 1;
foreach($requests as $row):
?>

<tr>

<td><?= $counter++ ?></td>

<td><?= htmlspecialchars($row['agent_name']) ?></td>

<td><?= htmlspecialchars($row['prayer_name']) ?></td>

<td><?= date("h:i A", strtotime($row['slot_time'])) ?></td>

<td>
<span class="<?= $row['status'] ?>">
<?= ucfirst($row['status']) ?>
</span>
</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</div>

</div>

<script>

const prayerTimes = {

"Dhuhr": [
<?php foreach($dhuhr_times as $time){ echo '"' . $time . '",'; } ?>
],

"Asr": [
<?php foreach($asr_times as $time){ echo '"' . $time . '",'; } ?>
],

"Maghrib": [
<?php foreach($maghrib_times as $time){ echo '"' . $time . '",'; } ?>
],

"Isha": [
<?php foreach($isha_times as $time){ echo '"' . $time . '",'; } ?>
]

};

function updateTimes(){

    let prayer = document.getElementById("prayerSelect").value;

    let timeSelect = document.getElementById("timeSelect");

    timeSelect.innerHTML =
        '<option value="">Choose Time</option>';

    if(!prayer) return;

    prayerTimes[prayer].forEach(function(time){

        let option = document.createElement("option");

        option.value = time;
        option.innerHTML = time;

        timeSelect.appendChild(option);
    });
}

</script>

</body>
</html>