<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION["role"])){
    header("Location: ../login.php");
    exit();
}

/* ===============================
   MANAGER ONLY
================================ */

if($_SESSION["role"] != "manager"){
    exit("Access Denied");
}

/* ===============================
   APPROVE REQUEST
================================ */

if(isset($_GET['approve'])){

    $id = (int)$_GET['approve'];

    $stmt = $conn->prepare("
        UPDATE pray_requests
        SET
            status='approved',
            agent_notified = 0
        WHERE id=?
    ");

    $stmt->execute([$id]);

    echo "<script>
        localStorage.setItem('prayerAction','approved');
        window.location='prayer_requests_monitor.php';
    </script>";

    exit();
}

/* ===============================
   REJECT REQUEST
================================ */

if(isset($_GET['reject'])){

    $id = (int)$_GET['reject'];

    $stmt = $conn->prepare("
        UPDATE pray_requests
        SET
            status='rejected',
            agent_notified = 0
        WHERE id=?
    ");

    $stmt->execute([$id]);

    echo "<script>
        localStorage.setItem('prayerAction','rejected');
        window.location='prayer_requests_monitor.php';
    </script>";

    exit();
}

/* ===============================
   FILTER DATE
================================ */

$selected_date = !empty($_GET['filter_date'])
    ? $_GET['filter_date']
    : date("Y-m-d");

/* ===============================
   GET REQUESTS
================================ */

$stmt = $conn->prepare("
    SELECT *
    FROM pray_requests
    WHERE CAST(request_date AS DATE) = ?
    ORDER BY created_at DESC
");

$stmt->execute([$selected_date]);

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Prayer Requests Monitor</title>

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

/* HEADER */

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    gap:20px;
    flex-wrap:wrap;
}

.page-title{
    font-size:32px;
    font-weight:900;
    color:#d4af37;
}

.header-right{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.back-btn{
    background:#1f2937;
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:12px;
    font-weight:700;
    transition:.3s;
}

.back-btn:hover{
    background:#d4af37;
    color:#111827;
}

/* FILTER */

.filter-form{
    display:flex;
    gap:10px;
    align-items:center;
    background:#111827;
    padding:12px;
    border-radius:14px;
    border:1px solid rgba(212,175,55,.15);
}

.filter-form input{
    background:#1f2937;
    border:none;
    color:white;
    padding:10px 14px;
    border-radius:10px;
}

.filter-btn{
    border:none;
    background:#d4af37;
    color:#111827;
    padding:10px 18px;
    border-radius:10px;
    font-weight:800;
    cursor:pointer;
}

/* TABLE */

.table-box{
    background:#111827;
    border-radius:22px;
    overflow:auto;
    border:1px solid rgba(212,175,55,.15);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f2937;
    color:#d4af37;
    padding:16px;
    font-size:14px;
}

td{
    padding:16px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,.05);
    font-size:14px;
}

.status{
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:800;
}

.pending{
    background:#78350f;
    color:#facc15;
}

.approved{
    background:#14532d;
    color:#4ade80;
}

.rejected{
    background:#7f1d1d;
    color:#f87171;
}

.actions{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
}

.btn{
    border:none;
    padding:10px 14px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-size:12px;
    font-weight:800;
    transition:.3s;
}

.btn:hover{
    transform:translateY(-2px);
}

.approve-btn{
    background:#16a34a;
}

.reject-btn{
    background:#dc2626;
}

.empty{
    padding:40px;
    text-align:center;
    color:#94a3b8;
}

.queue-badge{
    display:inline-block;
    background:#1e293b;
    color:#cbd5e1;
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
}

/* LIVE NOTIFICATION */

.live-notification{
    position:fixed;
    top:25px;
    right:25px;
    width:350px;
    background:#111827;
    border-left:5px solid #d4af37;
    border-radius:18px;
    padding:18px;
    z-index:99999;
    transform:translateX(450px);
    opacity:0;
    transition:.4s;
    box-shadow:0 10px 30px rgba(0,0,0,.4);
}

.live-notification.show{
    transform:translateX(0);
    opacity:1;
}

.notif-title{
    font-size:18px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:8px;
}

.notif-body{
    color:#e5e7eb;
    line-height:1.6;
}

.notif-btn{
    margin-top:14px;
    width:100%;
    border:none;
    background:#d4af37;
    color:#111827;
    padding:10px;
    border-radius:10px;
    font-weight:800;
    cursor:pointer;
}

.notif-btn:hover{
    background:#facc15;
}

@media(max-width:900px){

    .table-box{
        overflow-x:auto;
    }

    table{
        min-width:1000px;
    }

    .header{
        flex-direction:column;
        align-items:flex-start;
    }

    .live-notification{
        width:90%;
        right:5%;
    }
}

</style>

</head>

<body>

<div class="header">

    <div class="page-title">
        Prayer Requests Monitor
    </div>

    <div class="header-right">

        <form class="filter-form" method="GET">

            <input
                type="date"
                name="filter_date"
                value="<?= $selected_date ?>"
            >

            <button class="filter-btn" type="submit">
                Filter
            </button>

        </form>

        <a href="dashboard.php" class="back-btn">
            ← Back To Dashboard
        </a>

    </div>

</div>

<div class="table-box">

<table id="requestsTable">

<tr>

    <th>ID</th>
    <th>Agent</th>
    <th>Prayer</th>
    <th>Reserved Time</th>
    <th>Queue</th>
    <th>Status</th>
    <th>Date</th>
    <th>Actions</th>

</tr>

<?php if(count($requests) > 0): ?>

<?php foreach($requests as $row): ?>

<tr>

<td>
    <?= $row['id'] ?>
</td>

<td>
    <?= htmlspecialchars($row['agent_name']) ?>
</td>

<td>
    <?= htmlspecialchars($row['prayer_name']) ?>
</td>

<td>
    <?= date("h:i A", strtotime($row['slot_time'])) ?>
</td>

<td>

<?php

$queueStmt = $conn->prepare("
    SELECT COUNT(*) + 1 AS queue_num
    FROM pray_requests
    WHERE prayer_name = ?
    AND slot_time < ?
    AND request_date = ?
");

$queueStmt->execute([
    $row['prayer_name'],
    $row['slot_time'],
    $row['request_date']
]);

$queue = $queueStmt->fetch(PDO::FETCH_ASSOC);

?>

<span class="queue-badge">
    Queue #<?= $queue['queue_num'] ?>
</span>

</td>

<td>

<span class="status <?= $row['status'] ?>">

    <?= ucfirst($row['status']) ?>

</span>

</td>

<td>

<?= date("Y-m-d h:i A", strtotime($row['created_at'])) ?>

</td>

<td>

<?php if($row['status'] == 'pending'): ?>

<div class="actions">

    <a
        class="btn approve-btn"
        href="?approve=<?= $row['id'] ?>"
    >
        Approve
    </a>

    <a
        class="btn reject-btn"
        href="?reject=<?= $row['id'] ?>"
    >
        Reject
    </a>

</div>

<?php else: ?>

—

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="8" class="empty">

    No prayer requests found

</td>

</tr>

<?php endif; ?>

</table>

</div>

<!-- LIVE NOTIFICATION -->

<div id="liveNotification" class="live-notification">

    <div class="notif-title">
        🔔 New Prayer Request
    </div>

    <div class="notif-body" id="notifText">
        New request received
    </div>

    <button class="notif-btn" onclick="closeNotification()">
        OK
    </button>

</div>

<!-- SOUND -->

<audio id="notifySound" loop>
    <source src="../assets/notification.mp3" type="audio/mpeg">
</audio>

<script>

/* ===============================
   SOUND SYSTEM
================================ */

let sound = document.getElementById("notifySound");
let soundUnlocked = false;
let oldRowsCount = document.querySelectorAll("#requestsTable tr").length;

document.addEventListener("click", unlockSound, { once:true });

function unlockSound(){

    sound.play().then(() => {

        sound.pause();
        sound.currentTime = 0;

        soundUnlocked = true;

    }).catch(()=>{});
}

/* ===============================
   NOTIFICATION
================================ */

function showNotification(message){

    document.getElementById("notifText").innerHTML = message;

    document.getElementById("liveNotification")
        .classList.add("show");

    if(soundUnlocked){

        sound.pause();
        sound.currentTime = 0;

        sound.play().catch(()=>{});
    }
}

function closeNotification(){

    document.getElementById("liveNotification")
        .classList.remove("show");

    sound.pause();
    sound.currentTime = 0;
}

/* ===============================
   SUCCESS ACTION NOTIFICATION
================================ */

window.addEventListener("load", () => {

    let action = localStorage.getItem("prayerAction");

    if(action === "approved"){

        showNotification("✅ Prayer request approved");

        localStorage.removeItem("prayerAction");
    }

    if(action === "rejected"){

        showNotification("❌ Prayer request rejected");

        localStorage.removeItem("prayerAction");
    }
});

/* ===============================
   AUTO REFRESH TABLE
================================ */

setInterval(() => {

    fetch("prayer_requests_monitor.php?filter_date=<?= $selected_date ?>")
    .then(res => res.text())
    .then(html => {

        let parser = new DOMParser();

        let doc = parser.parseFromString(html, "text/html");

        let newTable =
            doc.querySelector("#requestsTable").innerHTML;

        document.querySelector("#requestsTable").innerHTML =
            newTable;

        let currentRows =
            document.querySelectorAll("#requestsTable tr").length;

        if(currentRows > oldRowsCount){

            oldRowsCount = currentRows;

            showNotification(
                "📢 New prayer request received"
            );
        }

    });

}, 5000);

</script>

</body>
</html>