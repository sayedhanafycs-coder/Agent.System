<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "followup") {
    exit("Access denied");
}

$requests = $conn->query("
    SELECT * FROM zone_requests
    WHERE status = 'pending'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if(isset($_GET["approved"])): ?>
<script>
window.onload = function(){

    let box = document.createElement("div");

    box.innerText = "✔ تم إضافة الزون بنجاح";
    box.style.position = "fixed";
    box.style.top = "20px";
    box.style.right = "20px";
    box.style.background = "#16a34a";
    box.style.color = "#fff";
    box.style.padding = "15px 20px";
    box.style.borderRadius = "12px";
    box.style.boxShadow = "0 10px 30px rgba(0,0,0,.2)";
    box.style.zIndex = "99999";

    document.body.appendChild(box);

    setTimeout(()=>{
        box.remove();
    },3000);

};
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Zone Requests</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Cairo',sans-serif;
}

body{
    background:#f4f7fb;
    padding:30px;
}

.container{
    max-width:1200px;
    margin:auto;
}

.title{
    font-size:30px;
    font-weight:700;
    color:#0f172a;
    margin-bottom:25px;
}

/* ================= NEW BUTTON ================= */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.back-btn{
    background:#0f172a;
    color:#fff;
    padding:10px 15px;
    border-radius:10px;
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    transition:.2s;
}

.back-btn:hover{
    background:#1e293b;
}

/* ================= TABLE ================= */

.table-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#0f172a;
    color:#fff;
    padding:18px;
    font-size:15px;
}

td{
    padding:16px;
    text-align:center;
    border-bottom:1px solid #edf2f7;
    font-size:14px;
}

.approve-btn{
    background:#16a34a;
    color:#fff;
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:700;
}

.reject-btn{
    background:#dc2626;
    color:#fff;
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:700;
}

.actions{
    display:flex;
    gap:10px;
    justify-content:center;
}

.empty{
    padding:40px;
    text-align:center;
    color:#64748b;
    font-size:16px;
}

</style>
</head>

<body>

<div class="container">

    <!-- ✅ NEW TOP BAR -->
    <div class="top-bar">

        <div class="title">
            طلبات إضافة الزون
        </div>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

    </div>

    <div class="table-card">

        <table>

            <thead>
                <tr>
                    <th>Sub Area</th>
                    <th>Main Area</th>
                    <th>Zone</th>
                    <th>Branch</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if(count($requests) > 0): ?>

                <?php foreach ($requests as $r): ?>

                <tr>

                    <td><?= $r["sub_area"] ?></td>

                    <td><?= $r["main_area"] ?></td>

                    <td><?= $r["area"] ?></td>

                    <td><?= $r["branch"] ?></td>

                    <td>

                        <div class="actions">

                            <a
                                class="approve-btn"
                                href="./approve_zone.php?id=<?= $r['id'] ?>"
                            >
                                Approve
                            </a>

                            <a
                                class="reject-btn"
                                href="reject_zone.php?id=<?= $r['id'] ?>"
                            >
                                Reject
                            </a>

                        </div>

                    </td>

                </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="empty">
                        لا توجد طلبات حالياً
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>