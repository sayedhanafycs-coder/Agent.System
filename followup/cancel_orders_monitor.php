<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION["role"])){
    header("Location: ../login.php");
    exit();
}

if($_SESSION["role"] != "followup"){
    header("Location: ../login.php");
    exit();
}

/* ===============================
   UPDATE STATUS
================================ */

if(isset($_GET['approve'])){
    $id = (int)$_GET['approve'];

    $stmt = $conn->prepare("
        UPDATE cancel_orders
        SET status='approved',
            agent_notified=0
        WHERE id=?
    ");
    $stmt->execute([$id]);

    header("Location: cancel_orders_monitor.php");
    exit();
}

if(isset($_GET['reject'])){
    $id = (int)$_GET['reject'];

    $stmt = $conn->prepare("
        UPDATE cancel_orders
        SET status='rejected',
            agent_notified=0
        WHERE id=?
    ");
    $stmt->execute([$id]);

    header("Location: cancel_orders_monitor.php");
    exit();
}

/* ===============================
   GET REQUESTS
================================ */

$stmt = $conn->prepare("
    SELECT *
    FROM cancel_orders
    ORDER BY id DESC
");
$stmt->execute();

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Cancel Orders Monitor</title>

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
    margin-bottom:20px;
}

.title{
    font-size:28px;
    font-weight:900;
    color:#d4af37;
}

.back-btn{
    padding:10px 14px;
    background:#1f2937;
    color:white;
    text-decoration:none;
    border-radius:10px;
}

.back-btn:hover{
    background:#d4af37;
    color:#111827;
}

/* TABLE */
.table-box{
    background:#111827;
    border-radius:20px;
    overflow:hidden;
    border:1px solid rgba(212,175,55,.2);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f2937;
    color:#d4af37;
    padding:15px;
}

td{
    padding:15px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.status{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
}

.pending{background:#78350f;color:#facc15;}
.approved{background:#14532d;color:#4ade80;}
.rejected{background:#7f1d1d;color:#f87171;}

/* BUTTONS */
.btn{
    padding:6px 10px;
    border-radius:8px;
    text-decoration:none;
    font-size:12px;
    margin:2px;
    display:inline-block;
}

.approve-btn{background:#16a34a;color:white;}
.reject-btn{background:#dc2626;color:white;}
.chat-btn{background:#2563eb;color:white;}

/* NOTIFICATION */
.live-notification{
    position:fixed;
    top:30px;
    right:30px;
    width:340px;
    background:#111827;
    border-left:5px solid #d4af37;
    border-radius:18px;
    padding:18px;
    transform:translateX(450px);
    opacity:0;
    transition:.4s;
    z-index:999999;
}

.live-notification.show{
    transform:translateX(0);
    opacity:1;
}

.notif-btn{
    width:100%;
    margin-top:10px;
    padding:10px;
    border:none;
    background:#d4af37;
    font-weight:bold;
    border-radius:10px;
    cursor:pointer;
}

</style>
</head>

<body>

<div class="header">
    <div class="title">Cancel Orders Monitor</div>
    <a class="back-btn" href="dashboard.php">🏠 Back</a>
</div>

<div class="table-box">

<table>
<tr>
    <th>ID</th>
    <th>Agent</th>
    <th>Order</th>
    <th>Reason</th>
    <th>Status</th>
    <th>Time</th>
    <th>Actions</th>
</tr>

<?php foreach($requests as $row): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['agent_name']) ?></td>
    <td><?= htmlspecialchars($row['order_number']) ?></td>
    <td><?= htmlspecialchars($row['reason']) ?></td>

    <td>
        <span class="status <?= $row['status'] ?>">
            <?= $row['status'] ?>
        </span>
    </td>

    <td>
        <?= date("Y-m-d h:i A", strtotime($row['created_at'])) ?>
    </td>

    <td>

        <?php if($row['status'] == 'pending'): ?>
            <a class="btn approve-btn" href="?approve=<?= $row['id'] ?>">Approve</a>
            <a class="btn reject-btn" href="?reject=<?= $row['id'] ?>">Reject</a>
        <?php endif; ?>

        <a class="btn chat-btn" href="../agent/cancel_order_chat.php?id=<?= $row['id'] ?>">
            Chat
        </a>

    </td>
</tr>
<?php endforeach; ?>

</table>

</div>

<!-- NOTIFICATION -->
<div id="liveNotification" class="live-notification">

    <div style="color:#d4af37;font-weight:800;">🔔 New Cancel Order</div>

    <div id="notifText" style="margin-top:10px;"></div>

    <button class="notif-btn" onclick="closeNotification()">
        تمام
    </button>
</div>

<audio id="notifySound">
    <source src="../assets/notification.mp3" type="audio/mpeg">
</audio>

<script>

let sound = document.getElementById("notifySound");
let soundUnlocked = false;
let lastId = 0;
let isRunning = false;

/* UNLOCK SOUND */
document.addEventListener("click", function () {
    sound.play().then(() => {
        sound.pause();
        sound.currentTime = 0;
        soundUnlocked = true;
    }).catch(()=>{});
}, { once:true });

/* SHOW NOTIFICATION */
function showNotification(msg){
    document.getElementById("notifText").innerHTML = msg;
    document.getElementById("liveNotification").classList.add("show");

    playSound();
}

/* PLAY SOUND */
function playSound(){
    if(!soundUnlocked) return;

    sound.pause();
    sound.currentTime = 0;

    sound.play().catch(()=>{});
}

/* CLOSE */
function closeNotification(){
    document.getElementById("liveNotification").classList.remove("show");
    sound.pause();
    sound.currentTime = 0;
}

/* POLLING */
setInterval(() => {

    if(isRunning) return;
    isRunning = true;

    fetch("../agent/check_new_cancel_orders.php")
    .then(res => res.json())
    .then(data => {

        if(!data.success) return;

        if(lastId === 0){
            lastId = data.id;
            return;
        }

        if(data.id > lastId){

            lastId = data.id;

            showNotification(
                "Order #" +
                data.order_number +
                " from " +
                data.agent_name
            );
        }

    })
    .catch(console.log)
    .finally(() => isRunning = false);

}, 4000);
</script>

</body>