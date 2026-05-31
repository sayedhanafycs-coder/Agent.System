<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION["role"]) || $_SESSION["role"] != "followup"){
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"] ?? "Followup User";
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Followup Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Cairo',sans-serif;
}

body{
    display:flex;
    background:#0f172a;
    color:white;
}

/* ================= SIDEBAR ================= */

.sidebar{
    width:260px;
    height:100vh;
    background:#111827;
    border-right:1px solid rgba(255,255,255,.05);
    padding:20px;
    position:fixed;
    left:0;
    top:0;
}

.logo{
    font-size:22px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:30px;
    text-align:center;
}

.user{
    font-size:14px;
    color:#9ca3af;
    margin-bottom:30px;
    text-align:center;
}

.nav a{
    display:block;
    padding:12px 15px;
    margin-bottom:10px;
    border-radius:10px;
    text-decoration:none;
    color:#e5e7eb;
    background:#1f2937;
    transition:.3s;
    font-size:14px;
}

.nav a:hover{
    background:#d4af37;
    color:#111827;
    font-weight:bold;
}

/* ================= CONTENT ================= */

.content{
    margin-left:260px;
    padding:30px;
    width:100%;
}

.card{
    background:#111827;
    padding:20px;
    border-radius:15px;
    border:1px solid rgba(255,255,255,.05);
    margin-bottom:20px;
}

.card h2{
    color:#d4af37;
    margin-bottom:10px;
}

.card p{
    color:#9ca3af;
}

/* ================= RESPONSIVE ================= */

@media(max-width:900px){
    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }

    .content{
        margin-left:0;
    }
}

</style>

</head>

<body>

<!-- ================= SIDEBAR ================= -->
<div class="sidebar">

    <div class="logo">FOLLOWUP</div>

    <div class="user">
        👤 <?= htmlspecialchars($name) ?>
    </div>

    <div class="nav">

        <a href="zone_application.php">📍 Zone Application</a>

        <a href="cancel_orders_monitor.php">❌ Order Cancelation</a>

        <a href="store_performance.php">📊 Store Performance</a>

    </div>

</div>

<!-- ================= CONTENT ================= -->
<div class="content">

    <div class="card">
        <h2>Welcome 👋</h2>
        <p>This is your Followup Dashboard. Choose a module from sidebar.</p>
    </div>

    <div class="card">
        <h2>System Status</h2>
        <p>All systems running normally.</p>
    </div>

</div>

</body>

</html>