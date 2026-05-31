<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "manager") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];

/* ===============================
   FORCE ACTIONS
================================ */

if(isset($_GET['force_logout'])){

    $id = (int) $_GET['force_logout'];

    $stmt = $conn->prepare("
        UPDATE users
        SET is_online = 0
        WHERE id=?
    ");

    $stmt->execute([$id]);

    header("Location: manager.php");
    exit();
}

if(isset($_GET['set_status'])){

    $id = (int) $_GET['id'];
    $status = $_GET['set_status'];

    $allowed = ['online','break','pray'];

    if(in_array($status,$allowed)){

        $stmt = $conn->prepare("
            UPDATE users
            SET
                status=?,
                status_start=GETDATE(),
                is_online=1
            WHERE id=?
        ");

        $stmt->execute([$status,$id]);
    }

    header("Location: manager.php");
    exit();
}

/* ===============================
   GET AGENTS
================================ */

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        status,
        status_start,
        is_online
    FROM users
    WHERE role='agent'
    ORDER BY is_online DESC, name ASC
");

$stmt->execute();

$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   AGENT COUNTS
================================ */

$online_agents = [];
$break_agents  = [];
$pray_agents   = [];
$offline_agents = [];

foreach($agents as $a){

    if($a['is_online'] == 1){

        if($a['status'] == 'online'){
            $online_agents[] = $a;
        }

        if($a['status'] == 'break'){
            $break_agents[] = $a;
        }

        if($a['status'] == 'pray'){
            $pray_agents[] = $a;
        }

    }else{
        $offline_agents[] = $a;
    }
}

/* ===============================
   PRAYER REQUESTS TODAY
================================ */

$prayStmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM pray_requests
    WHERE CAST(request_date AS DATE) = CAST(GETDATE() AS DATE)
");

$prayStmt->execute();

$prayReq = $prayStmt->fetch(PDO::FETCH_ASSOC)['total'];

/* ===============================
   PENDING REQUESTS
================================ */

$pendingStmt = $conn->prepare("
    SELECT TOP 10 *
    FROM pray_requests
    WHERE status='pending'
    ORDER BY created_at DESC
");

$pendingStmt->execute();

$pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>Manager Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
/>

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
    display:flex;
}

/* ===============================
   SIDEBAR
================================ */

.sidebar{
    width:280px;
    background:#111827;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    overflow-y:auto;
    border-right:1px solid rgba(255,255,255,.08);
    padding:20px 14px;
}

.logo{
    font-size:28px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:25px;
    text-align:center;
}

.menu-section{
    margin-bottom:16px;
}

.menu-title{
    background:#1f2937;
    padding:14px 16px;
    border-radius:14px;
    font-size:15px;
    font-weight:900;
    color:#d4af37;
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:.3s;
}

.menu-title:hover{
    background:#243041;
}

.submenu{
    display:none;
    margin-top:8px;
    padding-left:10px;
}

.submenu a{
    display:block;
    padding:12px 14px;
    margin-bottom:6px;
    background:#0f172a;
    border-radius:12px;
    text-decoration:none;
    color:#d1d5db;
    transition:.3s;
    font-size:14px;
}

.submenu a:hover{
    background:#243041;
    color:#d4af37;
    transform:translateX(5px);
}

.logout-btn{
    display:block;
    margin-top:25px;
    background:#dc2626;
    text-align:center;
    padding:14px;
    border-radius:14px;
    color:white;
    text-decoration:none;
    font-weight:900;
    transition:.3s;
}

.logout-btn:hover{
    background:#ef4444;
}

/* ===============================
   MAIN
================================ */

.main{
    margin-left:280px;
    width:calc(100% - 280px);
    padding:25px;
}

/* ===============================
   IFRAME
================================ */

#contentFrame{
    width:100%;
    height:calc(100vh - 40px);
    border:none;
    display:none;
    background:#0f172a;
    border-radius:20px;
}

/* ===============================
   DASHBOARD HOME
================================ */

.top-bar{
    margin-bottom:25px;
}

.page-title{
    font-size:34px;
    font-weight:900;
    color:#d4af37;
}

.page-sub{
    color:#94a3b8;
    margin-top:5px;
}

.dashboard-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
}

.panel{
    background:#111827;
    border-radius:22px;
    border:1px solid rgba(255,255,255,.08);
    overflow:hidden;
}

.panel-header{
    padding:18px 20px;
    background:#1f2937;
    display:flex;
    justify-content:space-between;
    align-items:center;
    cursor:pointer;
}

.panel-title{
    font-size:20px;
    font-weight:900;
    color:#d4af37;
}

.panel-count{
    background:#d4af37;
    color:#111827;
    padding:6px 12px;
    border-radius:30px;
    font-size:13px;
    font-weight:900;
}

.panel-content{
    display:none;
    padding:18px;
}

/* ===============================
   AGENT CARD
================================ */

.agent-card{
    background:#0f172a;
    border-radius:16px;
    padding:14px;
    margin-bottom:12px;
    border:1px solid rgba(255,255,255,.06);
}

.agent-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.agent-left{
    display:flex;
    align-items:center;
    gap:12px;
}

.avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    background:#d4af37;
    color:#111827;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:900;
}

.agent-name{
    font-size:15px;
    font-weight:700;
}

.agent-status{
    font-size:13px;
    color:#94a3b8;
}

.status-badge{
    padding:7px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:800;
}

.online{
    background:#14532d;
    color:#4ade80;
}

.break{
    background:#78350f;
    color:#facc15;
}

.pray{
    background:#1e3a8a;
    color:#93c5fd;
}

.offline{
    background:#374151;
    color:#d1d5db;
}

/* ===============================
   AGENT ACTIONS
================================ */

.agent-actions{
    margin-top:15px;
    display:none;
    gap:10px;
    flex-wrap:wrap;
}

.action-btn{
    padding:9px 14px;
    border-radius:12px;
    text-decoration:none;
    color:white;
    font-size:13px;
    font-weight:800;
    transition:.3s;
}

.action-btn:hover{
    opacity:.85;
}

.btn-online{
    background:#16a34a;
}

.btn-break{
    background:#d97706;
}

.btn-pray{
    background:#2563eb;
}

.btn-logout{
    background:#dc2626;
}

/* ===============================
   REQUEST CARD
================================ */

.request-card{
    background:#0f172a;
    border-radius:16px;
    padding:15px;
    margin-bottom:14px;
    border:1px solid rgba(255,255,255,.06);
}

.request-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}

.request-agent{
    font-size:15px;
    font-weight:800;
}

.request-time{
    color:#d4af37;
    font-size:13px;
}

.request-details{
    color:#cbd5e1;
    margin-bottom:14px;
    line-height:1.7;
}

.actions{
    display:flex;
    gap:10px;
}

.btn{
    flex:1;
    text-align:center;
    padding:10px;
    border-radius:12px;
    text-decoration:none;
    color:white;
    font-size:13px;
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

/* ===============================
   QUICK LINKS
================================ */

.quick-links{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
}

.quick-card{
    background:#111827;
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    padding:18px;
    text-decoration:none;
    color:white;
    transition:.3s;
}

.quick-card:hover{
    transform:translateY(-4px);
    border-color:#d4af37;
}

.quick-card h3{
    color:#d4af37;
    margin-bottom:8px;
    font-size:24px;
}

.quick-card p{
    color:#cbd5e1;
    font-size:14px;
}

/* ===============================
   MOBILE
================================ */

@media(max-width:1100px){

    .dashboard-grid{
        grid-template-columns:1fr;
    }

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .main{
        margin-left:0;
        width:100%;
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">
        Manager Panel
    </div>

    <!-- AGENT REQUESTS -->

    <div class="menu-section">

        <div class="menu-title" onclick="toggleMenu('reqMenu')">

            <span>
                <i class="fa fa-bell"></i>
                Agent Requests
            </span>

            <i class="fa fa-chevron-down"></i>

        </div>

        <div class="submenu" id="reqMenu">

            <a href="#" onclick="loadPage('break_requests.php')">
                ☕ Break Requests
            </a>

            <a href="#" onclick="loadPage('prayer_requests_monitor.php')">
                🕌 Prayer Requests
            </a>

            <a href="#" onclick="loadPage('annual_requests.php')">
                📅 Annual Leave
            </a>

            <a href="#" onclick="loadPage('leave_requests.php')">
                📝 Leave Request
            </a>

        </div>

    </div>

    <!-- REPORTING -->

    <div class="menu-section">

        <div class="menu-title" onclick="toggleMenu('reportMenu')">

            <span>
                <i class="fa fa-chart-line"></i>
                Reporting
            </span>

            <i class="fa fa-chevron-down"></i>

        </div>

        <div class="submenu" id="reportMenu">

            <a href="#" onclick="loadPage('agent_quality.php')">
                Agent Quality
            </a>

            <a href="#">
                Agent Performance
            </a>

            <a href="#">
                Agent Points
            </a>

            <a href="#">
                CS Quality
            </a>

            <a href="#">
                CS Performance
            </a>

            <a href="#">
                CS Points
            </a>

            <a href="#">
                Online Performance
            </a>

            <a href="#">
                Online Points
            </a>

        </div>

    </div>

    <!-- SALES -->

    <div class="menu-section">

        <div class="menu-title" onclick="toggleMenu('salesMenu')">

            <span>
                <i class="fa fa-coins"></i>
                Sales
            </span>

            <i class="fa fa-chevron-down"></i>

        </div>

        <div class="submenu" id="salesMenu">

            <a href="#">
                Daily Sales
            </a>

            <a href="#">
                Menu Sales
            </a>

            <a href="#">
                Channel Sales
            </a>

        </div>

    </div>

    <!-- SETTINGS -->

    <div class="menu-section">

        <div class="menu-title" onclick="toggleMenu('settingsMenu')">

            <span>
                <i class="fa fa-gear"></i>
                Settings
            </span>

            <i class="fa fa-chevron-down"></i>

        </div>

        <div class="submenu" id="settingsMenu">

            <a href="#">
                User Manager
            </a>

            <a href="#">
                Company Settings
            </a>

        </div>

    </div>

    <a href="../logout.php" class="logout-btn">
        Logout
    </a>

</div>

<!-- MAIN -->

<div class="main">

    <!-- DASHBOARD HOME -->

    <div id="dashboardHome">

        <div class="top-bar">

            <div class="page-title">
                Welcome <?= htmlspecialchars($name) ?>
            </div>

            <div class="page-sub">
                Live Manager Dashboard
            </div>

        </div>

        <div class="dashboard-grid">

            <!-- LEFT -->

            <div>

                <div class="panel">

                    <div
                        class="panel-header"
                        onclick="togglePanel('agentMonitor')"
                    >

                        <div class="panel-title">
                            👨‍💻 Agent Monitor
                        </div>

                        <div class="panel-count">
                            <?= count($online_agents) ?> Online
                        </div>

                    </div>

                    <div class="panel-content" id="agentMonitor">

                        <?php foreach($agents as $a): ?>

                        <?php
                        $status = $a['is_online'] ? $a['status'] : 'offline';
                        ?>

                        <div class="agent-card">

                            <div
                                class="agent-top"
                                onclick="toggleAgentActions('agent<?= $a['id'] ?>')"
                                style="cursor:pointer;"
                            >

                                <div class="agent-left">

                                    <div class="avatar">
                                        <?= strtoupper(substr($a['name'],0,1)) ?>
                                    </div>

                                    <div>

                                        <div class="agent-name">
                                            <?= htmlspecialchars($a['name']) ?>
                                        </div>

                                        <div class="agent-status">
                                            Agent Status
                                        </div>

                                    </div>

                                </div>

                                <div class="status-badge <?= $status ?>">
                                    <?= ucfirst($status) ?>
                                </div>

                            </div>

                            <!-- ACTIONS -->

                            <div
                                class="agent-actions"
                                id="agent<?= $a['id'] ?>"
                            >

                                <a
                                    class="action-btn btn-online"
                                    href="?set_status=online&id=<?= $a['id'] ?>"
                                >
                                    Online
                                </a>

                                <a
                                    class="action-btn btn-break"
                                    href="?set_status=break&id=<?= $a['id'] ?>"
                                >
                                    Break
                                </a>

                                <a
                                    class="action-btn btn-pray"
                                    href="?set_status=pray&id=<?= $a['id'] ?>"
                                >
                                    Pray
                                </a>

                                <a
                                    class="action-btn btn-logout"
                                    href="?force_logout=<?= $a['id'] ?>"
                                    onclick="return confirm('Force logout this agent?')"
                                >
                                    Force Logout
                                </a>

                            </div>

                        </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <!-- QUICK LINKS -->

                <div style="height:20px;"></div>

                <div class="quick-links">

                    <a href="#" onclick="loadPage('prayer_requests_monitor.php')" class="quick-card">

                        <h3><?= $prayReq ?></h3>

                        <p>Prayer Requests Today</p>

                    </a>

                    <a href="#" class="quick-card">

                        <h3>0</h3>

                        <p>Break Requests</p>

                    </a>

                    <a href="#" class="quick-card">

                        <h3>0</h3>

                        <p>Leave Requests</p>

                    </a>

                    <a href="#" class="quick-card">

                        <h3>0</h3>

                        <p>Annual Requests</p>

                    </a>

                </div>

            </div>

            <!-- RIGHT -->

            <div>

                <div class="panel">

                    <div class="panel-header">

                        <div class="panel-title">
                            🔔 Latest Requests
                        </div>

                        <div class="panel-count">
                            <?= count($pendingRequests) ?>
                        </div>

                    </div>

                    <div
                        class="panel-content"
                        style="display:block;"
                    >

                        <?php if(count($pendingRequests) > 0): ?>

                            <?php foreach($pendingRequests as $row): ?>

                            <div class="request-card">

                                <div class="request-top">

                                    <div class="request-agent">

                                        <?= htmlspecialchars($row['agent_name']) ?>

                                    </div>

                                    <div class="request-time">

                                        <?= date("h:i A", strtotime($row['created_at'])) ?>

                                    </div>

                                </div>

                                <div class="request-details">

                                    Prayer:
                                    <?= htmlspecialchars($row['prayer_name']) ?>

                                    <br>

                                    Reserved Time:
                                    <?= date("h:i A", strtotime($row['slot_time'])) ?>

                                </div>

                                <div class="actions">

                                    <a
                                        class="btn approve-btn"
                                        href="prayer_requests_monitor.php?approve=<?= $row['id'] ?>"
                                    >
                                        Approve
                                    </a>

                                    <a
                                        class="btn reject-btn"
                                        href="prayer_requests_monitor.php?reject=<?= $row['id'] ?>"
                                    >
                                        Reject
                                    </a>

                                </div>

                            </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div style="padding:20px;color:#94a3b8;">
                                No pending requests
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- IFRAME -->

    <iframe id="contentFrame"></iframe>

</div>

<script>

/* ===============================
   SIDEBAR TOGGLE
================================ */

function toggleMenu(id){

    let menu = document.getElementById(id);

    if(menu.style.display === "block"){

        menu.style.display = "none";

    }else{

        menu.style.display = "block";
    }
}

/* ===============================
   PANEL TOGGLE
================================ */

function togglePanel(id){

    let panel = document.getElementById(id);

    if(panel.style.display === "block"){

        panel.style.display = "none";

    }else{

        panel.style.display = "block";
    }
}

/* ===============================
   LOAD PAGE INSIDE DASHBOARD
================================ */

function loadPage(page){

    let home =
        document.getElementById("dashboardHome");

    let frame =
        document.getElementById("contentFrame");

    home.style.display = "none";

    frame.style.display = "block";

    frame.src = page;
}

/* ===============================
   AGENT ACTIONS TOGGLE
================================ */

function toggleAgentActions(id){

    let actions = document.getElementById(id);

    if(actions.style.display === "flex"){

        actions.style.display = "none";

    }else{

        actions.style.display = "flex";
    }
}

</script>

</body>
</html>