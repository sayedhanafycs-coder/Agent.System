<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "agent") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];
$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("
    UPDATE users
    SET
        is_online = 1,
        status = 'online',
        status_start = GETDATE()
    WHERE id = ?
");

$stmt->execute([$user_id]);

/* =========================
   UPDATE STATUS AJAX
========================= */

if(isset($_POST["update_status"])){

    $status = $_POST["status"];

    $stmt = $conn->prepare("
        UPDATE users
        SET
            status = ?,
            status_start = GETDATE(),
            is_online = 1
        WHERE id = ?
    ");

    $stmt->execute([$status,$user_id]);

    exit("done");
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>Agent Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Cairo',sans-serif;
}

:root{
    --bg:#0b0b0f;
    --bg2:#111827;
    --sidebar:#0f172a;
    --card:rgba(255,255,255,.03);
    --text:#fff;
    --sub:#94a3b8;
    --gold:#d4af37;
    --border:rgba(212,175,55,.12);
}

body.light{
    --bg:#eef2ff;
    --bg2:#dbeafe;
    --sidebar:#ffffff;
    --card:#ffffff;
    --text:#111827;
    --sub:#475569;
    --gold:#b88900;
    --border:rgba(184,137,0,.18);
}

body{
    background:linear-gradient(180deg,var(--bg),var(--bg2));
    color:var(--text);
    display:flex;
    min-height:100vh;
    overflow:auto;
    transition:.3s;
}

.sidebar{
    width:255px;
    background:var(--sidebar);
    border-right:1px solid var(--border);
    display:flex;
    flex-direction:column;
    padding:14px 10px;
    overflow-y:auto;
}

.profile{
    display:flex;
    flex-direction:column;
    margin-bottom:14px;
    gap:10px;
}

.profile-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
}

.profile-left{
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
}

.avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    background:var(--gold);
    color:#000;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:900;
    font-size:15px;
}

.profile-name{
    font-size:13px;
    font-weight:700;
}

.lang-switch,
.theme-btn{
    background:var(--bg2);
    border:1px solid var(--border);
    color:var(--text);
    padding:6px 9px;
    border-radius:10px;
    cursor:pointer;
    transition:.2s;
    font-size:11px;
}

.theme-btn{
    position:fixed;
    top:16px;
    left:275px;
    z-index:999;
}

.lang-switch:hover,
.theme-btn:hover{
    border-color:var(--gold);
    color:var(--gold);
}

.status-box{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.status-select{
    width:100%;
    padding:11px;
    border-radius:12px;
    border:1px solid var(--border);
    background:var(--bg2);
    color:var(--text);
    outline:none;
    font-weight:700;
}

.status-timer{
    text-align:center;
    font-size:12px;
    color:#cbd5e1;
    border:1px solid var(--border);
    padding:8px;
    border-radius:10px;
    font-weight:700;
}

.timer-danger{
    color:#ff4d4d !important;
    border-color:#ff4d4d;
}

.menu-title{
    border:1px solid rgba(212,175,55,.18);
    color:var(--gold);
    padding:7px 10px;
    border-radius:10px;
    margin:10px 0 4px;
    font-size:11px;
    font-weight:800;
    background:rgba(212,175,55,.04);
}

.menu{
    display:flex;
    flex-direction:column;
    gap:2px;
}

.menu a{
    color:var(--text);
    text-decoration:none;
    padding:8px 10px;
    border-radius:10px;
    transition:.2s;
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
}

.menu a:hover,
.menu a.active{
    background:rgba(212,175,55,.08);
    color:var(--gold);
    transform:translateX(-2px);
}

.menu i{
    width:18px;
    text-align:center;
    font-size:12px;
}

.logout{
    margin-top:auto;
    padding-top:12px;
}

.logout a{
    background:#dc2626;
    color:#fff !important;
    justify-content:center;
    font-weight:700;
}

.main{
    flex:1;
    padding:18px;
    overflow-y:auto;
}

#contentArea{
    width:100%;
}

.top-title{
    font-size:24px;
    font-weight:900;
    margin-bottom:4px;
}

.sub{
    color:var(--sub);
    margin-bottom:16px;
    font-size:12px;
}

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:10px;
}

.stat-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:14px;
    padding:14px;
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-3px);
    border-color:var(--gold);
}

.stat-card i{
    font-size:20px;
    color:var(--gold);
    margin-bottom:8px;
}

.stat-number{
    font-size:24px;
    font-weight:900;
}

.stat-title{
    color:var(--sub);
    margin-top:3px;
    font-size:12px;
}

.content-frame{
    width:100%;
    height:calc(100vh - 50px);
    border:none;
    border-radius:16px;
    background:var(--sidebar);
}

.popup{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:99999;
}

.popup-box{
    width:400px;
    background:var(--sidebar);
    border:1px solid var(--border);
    border-radius:18px;
    padding:22px;
    text-align:center;
}

.popup-title{
    font-size:22px;
    font-weight:900;
    color:var(--gold);
    margin-bottom:15px;
}

.popup-text{
    color:var(--text);
    line-height:1.8;
    margin-bottom:18px;
    font-size:14px;
}

.popup-actions{
    display:flex;
    gap:10px;
}

.popup-btn{
    flex:1;
    border:none;
    padding:12px;
    border-radius:10px;
    cursor:pointer;
    font-weight:900;
}

.confirm-btn{
    background:var(--gold);
    color:#000;
}

.cancel-btn{
    background:#374151;
    color:#fff;
}

.popup-box input{
    width:100%;
    padding:12px;
    background:var(--bg);
    border:1px solid #374151;
    border-radius:10px;
    color:var(--text);
    margin-bottom:12px;
    outline:none;
}

.popup-save{
    width:100%;
    background:var(--gold);
    color:#000;
    border:none;
    padding:12px;
    border-radius:10px;
    font-weight:900;
    cursor:pointer;
}

@media(max-width:900px){

    body{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        border-right:none;
        border-bottom:1px solid rgba(212,175,55,.15);
    }

    .theme-btn{
        left:auto;
        right:15px;
        top:15px;
    }

}

</style>
</head>

<body>

<button class="theme-btn" onclick="toggleTheme()">
    <i class="fa fa-moon"></i>
</button>

<div class="sidebar">

    <div class="profile">

        <div class="profile-top">

            <div class="profile-left" onclick="openProfilePopup()">

                <div class="avatar">
                    <?= strtoupper(substr($name,0,1)) ?>
                </div>

                <div class="profile-name" id="employeeName">
                    <?= $name ?>
                </div>

            </div>

            <button class="lang-switch" onclick="toggleLanguage()">
                🌐 <span id="langText">العربية</span>
            </button>

        </div>

        <div class="status-box">

            <select class="status-select" id="statusSelect">
                <option value="online">🟢 Online</option>
                <option value="break">☕ Break</option>
                <option value="pray">🕌 Pray</option>
                <option value="call">📞 On A Call</option>
            </select>

            <div class="status-timer" id="timer">
                00:00:00
            </div>

        </div>

    </div>

    <div class="menu">
        <a href="#" class="active" onclick="goHome(this)">
            <i class="fa fa-house"></i>
            <span class="tr-home">Home</span>
        </a>
    </div>

    <div class="menu-title tr-daily">
        Daily Work
    </div>

    <div class="menu">

        <a href="#" onclick="loadPage('cancel_order.php',this)">
            <i class="fa fa-xmark"></i>
            <span class="tr-cancel">Cancel Order</span>
        </a>

        <a href="#" onclick="loadPage('break_request.php',this)">
            <i class="fa fa-mug-hot"></i>
            <span class="tr-break">Break Request</span>
        </a>

        <a href="#" onclick="loadPage('pray_time_request.php',this)">
            <i class="fa fa-mosque"></i>
            <span class="tr-pray">Pray Time Request</span>
        </a>

        <a href="#" onclick="loadPage('leave_early.php',this)">
            <i class="fa fa-clock"></i>
            <span class="tr-leave">Leave Early Request</span>
        </a>

    </div>

    <div class="menu-title tr-kpi">
        KPI & Performance
    </div>

    <div class="menu">

        <a href="#" onclick="loadPage('../agent/quality.php',this)">
            <i class="fa fa-star"></i>
            <span class="tr-quality">Quality</span>
        </a>

        <a href="#" onclick="loadPage('productivity.php',this)">
            <i class="fa fa-chart-line"></i>
            <span class="tr-productivity">Productivity</span>
        </a>

        <a href="#" onclick="loadPage('final_points.php',this)">
            <i class="fa fa-ranking-star"></i>
            <span class="tr-final">Final Points</span>
        </a>

    </div>

    <div class="menu-title tr-requests">
        Agent Requests & Issue
    </div>

    <div class="menu">

        <a href="#" onclick="loadPage('annual_request.php',this)">
            <i class="fa fa-calendar-days"></i>
            <span class="tr-annual">Annual Request</span>
        </a>

        <a href="#" onclick="loadPage('schedule_request.php',this)">
            <i class="fa fa-calendar-check"></i>
            <span class="tr-schedule">Schedule Request</span>
        </a>

        <a href="#" onclick="loadPage('performance_issue.php',this)">
            <i class="fa fa-triangle-exclamation"></i>
            <span class="tr-performance">Performance Issue</span>
        </a>

    </div>

    <div class="menu-title tr-help">
        Agent Help
    </div>

    <div class="menu">

        <a href="#" onclick="loadPage('add_zone.php',this)">
            <i class="fa fa-map-location-dot"></i>
            <span class="tr-zone">Zone Map</span>
        </a>

        <a href="#" onclick="loadPage('es2alny.php',this)">
            <i class="fa fa-circle-question"></i>
            <span class="tr-es2alny">Es2alny</span>
        </a>

        <a href="#" onclick="loadPage('store_information.php',this)">
            <i class="fa fa-store"></i>
            <span class="tr-store">Store Information</span>
        </a>

        <a href="#" onclick="loadPage('handbook.php',this)">
            <i class="fa fa-book"></i>
            <span class="tr-handbook">Hand Book</span>
        </a>

    </div>

    <div class="logout">

        <div class="menu">
            <a href="../logout.php">
                <i class="fa fa-right-from-bracket"></i>
                <span class="tr-logout">Logout</span>
            </a>
        </div>

    </div>

</div>

<div class="main">

    <div id="contentArea">

        <div class="top-title" id="dashboardTitle">
            Agent Dashboard
        </div>

        <div class="sub" id="dashboardSub">
            Welcome back, monitor all your KPIs and requests from one place.
        </div>

        <div class="stats">

            <div class="stat-card">
                <i class="fa fa-star"></i>
                <div class="stat-number">95%</div>
                <div class="stat-title">Quality Score</div>
            </div>

            <div class="stat-card">
                <i class="fa fa-chart-line"></i>
                <div class="stat-number">88%</div>
                <div class="stat-title">Productivity</div>
            </div>

            <div class="stat-card">
                <i class="fa fa-ranking-star"></i>
                <div class="stat-number">91%</div>
                <div class="stat-title">Final Points</div>
            </div>

            <div class="stat-card">
                <i class="fa fa-envelope"></i>
                <div class="stat-number">3</div>
                <div class="stat-title">Active Requests</div>
            </div>

        </div>

    </div>

</div>

<div class="popup" id="profilePopup">

    <div class="popup-box">

        <div class="popup-title">
            Profile Settings
        </div>

        <input type="text" value="<?= $name ?>" placeholder="Employee Name">

        <input type="file">

        <button class="popup-save">
            Save Changes
        </button>

    </div>

</div>

<div class="popup" id="statusPopup">

    <div class="popup-box">

        <div class="popup-title" id="popupTitle">
            Status Update
        </div>

        <div class="popup-text" id="popupText"></div>

        <div class="popup-actions">
            <button class="popup-btn confirm-btn" id="confirmBtn">OK</button>
            <button class="popup-btn cancel-btn" id="cancelBtn">Cancel</button>
        </div>

    </div>

</div>

<script>

let arabic = false;
let currentStatus = "online";
let selectedStatus = "online";
let startTime = new Date();

const timer = document.getElementById("timer");
const statusSelect = document.getElementById("statusSelect");
const popup = document.getElementById("statusPopup");
const popupText = document.getElementById("popupText");
const confirmBtn = document.getElementById("confirmBtn");
const cancelBtn = document.getElementById("cancelBtn");

const homeContent = document.getElementById("contentArea").innerHTML;

function loadPage(page,el){

    event.preventDefault();

    document.querySelectorAll(".menu a").forEach(a=>{
        a.classList.remove("active");
    });

    el.classList.add("active");

    document.getElementById("contentArea").innerHTML = `
        <iframe class="content-frame" src="${page}"></iframe>
    `;
}

function goHome(el){

    event.preventDefault();

    document.querySelectorAll(".menu a").forEach(a=>{
        a.classList.remove("active");
    });

    el.classList.add("active");

    document.getElementById("contentArea").innerHTML = homeContent;
}

function openProfilePopup(){
    document.getElementById("profilePopup").style.display = "flex";
}

function updateTimer(){

    let now = new Date();
    let diff = Math.floor((now - startTime)/1000);

    let h = String(Math.floor(diff/3600)).padStart(2,'0');
    let m = String(Math.floor((diff%3600)/60)).padStart(2,'0');
    let s = String(diff%60).padStart(2,'0');

    timer.innerText = `${h}:${m}:${s}`;

    timer.classList.remove("timer-danger");

    if(currentStatus === "break" && diff > 1200){
        timer.classList.add("timer-danger");
    }

    if(currentStatus === "pray" && diff > 900){
        timer.classList.add("timer-danger");
    }

}

setInterval(updateTimer,1000);

statusSelect.addEventListener("change",function(){

    let val = this.value;

    selectedStatus = val;

    if(selectedStatus === currentStatus){
        return;
    }

    fetch("dashboard.php",{
        method:"POST",
        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },
        body:"update_status=1&status="+val
    });

    if(selectedStatus === "break"){
        openPopup("☕ Break Time","انت متأكد انك طالع بريك ؟");
    }

    else if(selectedStatus === "pray"){
        openPopup("🕌 Pray Time","انت خارج تصلي متنساش تضوضي وياريت تجدد وضوئك");
    }

    else if(selectedStatus === "call"){
        openPopup("📞 Phone Call","متطولش حاول تنجز المكالمة واتمني تكون مكالمة خير");
    }

    else if(selectedStatus === "online"){

        let duration = timer.innerText;

        if(currentStatus === "break"){
            openPopup("☕ Break Finished",`انت بقالك بريك ${duration}`);
        }

        else if(currentStatus === "pray"){
            openPopup("🕌 Pray Finished",`حرماً وصلاة مقبولة ان شاء الله - الوقت ${duration}`);
        }

        else if(currentStatus === "call"){
            openPopup("📞 Call Finished",`انت بتتكلم بقالك ${duration}`);
        }

    }

});

function openPopup(title,text){
    document.getElementById("popupTitle").innerText = title;
    popupText.innerText = text;
    popup.style.display = "flex";
}

confirmBtn.onclick = function(){
    currentStatus = selectedStatus;
    startTime = new Date();
    popup.style.display = "none";
}

cancelBtn.onclick = function(){
    statusSelect.value = currentStatus;
    popup.style.display = "none";
}

window.onclick = function(e){

    if(e.target.id === "profilePopup"){
        document.getElementById("profilePopup").style.display = "none";
    }

    if(e.target.id === "statusPopup"){
        popup.style.display = "none";
        statusSelect.value = currentStatus;
    }

}

function toggleTheme(){
    document.body.classList.toggle("light");
}

function toggleLanguage(){

    arabic = !arabic;

    if(arabic){

        document.documentElement.lang = "ar";
        document.documentElement.dir = "rtl";

        document.getElementById("langText").innerText = "English";

        statusSelect.options[0].text = "🟢 اونلاين";
        statusSelect.options[1].text = "☕ بريك";
        statusSelect.options[2].text = "🕌 صلاة";
        statusSelect.options[3].text = "📞 مكالمة";

    }else{

        document.documentElement.lang = "en";
        document.documentElement.dir = "ltr";

        document.getElementById("langText").innerText = "العربية";

        statusSelect.options[0].text = "🟢 Online";
        statusSelect.options[1].text = "☕ Break";
        statusSelect.options[2].text = "🕌 Pray";
        statusSelect.options[3].text = "📞 On A Call";

    }

}

</script>
</body>
</html>