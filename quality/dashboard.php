<?php
session_start();

if ($_SESSION["role"] != "quality") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Quality Dashboard</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', sans-serif;
}

body{
    background: linear-gradient(135deg,#0f172a,#1e293b);
    color:#fff;
    direction:rtl;
}

/* =========================
   CONTAINER
========================= */

.container{
    max-width:1200px;
    margin:30px auto;
    padding:20px;
}

/* =========================
   TOP BAR
========================= */

.top-bar{
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(15px);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:16px;
    padding:20px;
    margin-bottom:20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.welcome{
    font-size:18px;
}

.welcome span{
    color:#fbbf24;
    font-weight:bold;
}

/* =========================
   TOP ACTIONS
========================= */

.top-actions{
    display:flex;
    gap:10px;
    align-items:center;
}

.action-btn{
    padding:9px 14px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
    font-size:13px;
    transition:0.3s;
    color:#fff;
    background:#1e293b;
}

.action-btn:hover{
    transform:scale(1.05);
}

.logout{
    background:#ef4444;
}

.logout:hover{
    background:#b91c1c;
}

/* =========================
   GRID CARDS
========================= */

.grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
}

.card{
    background: rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:16px;
    padding:22px;
    transition:0.3s;
}

.card:hover{
    transform:translateY(-4px);
    border-color:#fbbf24;
}

.card h4{
    color:#fbbf24;
    margin-bottom:10px;
}

.card p{
    color:#cbd5e1;
    font-size:14px;
}

/* =========================
   BUTTON
========================= */

.btn{
    display:inline-block;
    margin-top:12px;
    padding:9px 15px;
    background: linear-gradient(90deg,#d4af37,#fbbf24);
    color:#111;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
    font-size:13px;
    transition:0.3s;
}

.btn:hover{
    transform:scale(1.05);
}

/* =========================
   RESPONSIVE
========================= */

@media(max-width:768px){
    .grid{
        grid-template-columns:1fr;
    }

    .top-bar{
        flex-direction:column;
        gap:10px;
        text-align:center;
    }
}

</style>

</head>

<body>

<div class="container">

    <!-- TOP BAR -->
    <div class="top-bar">

        <div class="welcome">
            👋 مرحباً، <span><?= $name ?></span>
        </div>

        <div class="top-actions">

            <a class="action-btn" href="dashboard.php">
                🏠 Home
            </a>

            <a class="action-btn logout" href="../logout.php">
                ⏻ Logout
            </a>

        </div>

    </div>

    <!-- CARDS -->
    <div class="grid">

        <div class="card">
            <h4>📞 سماع مكالمة جديدة</h4>
            <p>ابدأ تقييم مكالمات جديدة للـ agents</p>
            <a class="btn" href="evaluate.php">ابدأ الآن</a>
        </div>

        <div class="card">
            <h4>✏️ تعديل تقييم مكالمة</h4>
            <p>تعديل أو مراجعة تقييمات سابقة</p>
            <a class="btn" href="edit_evaluation.php">دخول</a>
        </div>

        <div class="card">
            <h4>⚠️ طلبات التظلم</h4>
            <p>مراجعة اعتراضات الـ agents على التقييم</p>
            <a class="btn" href="appeals.php">عرض الطلبات</a>
        </div>

        <div class="card">
            <h4>📊 التقارير</h4>
            <p>عرض تقارير الأداء الأسبوعية والشهرية</p>
            <a class="btn" href="reports.php">عرض التقارير</a>
        </div>

    </div>

</div>

</body>
</html>