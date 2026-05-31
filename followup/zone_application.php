<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "followup") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];

$totalZones = $conn->query("SELECT COUNT(*) FROM zone_map")->fetchColumn();

$totalBranches = $conn->query("SELECT COUNT(DISTINCT branch) FROM zone_map")->fetchColumn();

$todayAdded = $conn->query("
    SELECT COUNT(*) FROM zone_map
    WHERE CAST(created_at AS DATE) = CAST(GETDATE() AS DATE)
")->fetchColumn();

$pendingRequests = $conn->query("
    SELECT COUNT(*) FROM zone_requests WHERE status='pending'
")->fetchColumn();

$notiCount = $conn->query("
    SELECT COUNT(*) FROM notifications
    WHERE user_role='fu' AND is_read=0
")->fetchColumn();

$recentZones = $conn->query("
    SELECT TOP 10 *
    FROM zone_requests
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ================= GET DROPDOWN DATA ================= */

$branches = $conn->query("
    SELECT DISTINCT branch
    FROM zone_map
    ORDER BY branch
")->fetchAll(PDO::FETCH_COLUMN);

$mainAreas = $conn->query("
    SELECT DISTINCT main_area
    FROM zone_map
    ORDER BY main_area
")->fetchAll(PDO::FETCH_COLUMN);

/* ================= SAVE ZONE ================= */

if(isset($_POST["save_zone"])){

    $branch = trim($_POST["branch"]);
    $main_area = trim($_POST["main_area"]);
    $sub_area = trim($_POST["sub_area"]);
    $area = trim($_POST["area"]);

    // check duplicate
    $check = $conn->prepare("
        SELECT COUNT(*)
        FROM zone_map
        WHERE
            branch = ?
            AND main_area = ?
            AND sub_area = ?
            AND area = ?
    ");

    $check->execute([
        $branch,
        $main_area,
        $sub_area,
        $area
    ]);

    if($check->fetchColumn() > 0){

        echo "
        <script>
            alert('Zone already exists');
        </script>
        ";

    } else {

        $insert = $conn->prepare("
            INSERT INTO zone_map
            (
                branch,
                main_area,
                sub_area,
                area,
                created_at
            )
            VALUES(?,?,?,?,GETDATE())
        ");

        $insert->execute([
            $branch,
            $main_area,
            $sub_area,
            $area
        ]);

        echo "
        <script>
            alert('Zone Added Successfully');
            window.location.href = window.location.href;
        </script>
        ";
    }
}

/* ================= LIVE SEARCH ================= */

if(isset($_GET['ajax'])){

    header('Content-Type: application/json; charset=utf-8');

    $search = trim($_GET["search"] ?? "");

    $sql = "
        SELECT TOP (50)
            branch,
            main_area,
            sub_area,
            area
        FROM zone_map
        WHERE 1=1
    ";

    $params = [];

    if ($search !== "") {
        $sql .= "
            AND (
                branch LIKE ?
                OR main_area LIKE ?
                OR sub_area LIKE ?
                OR area LIKE ?
            )
        ";

        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY id DESC ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Followup Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>

/* ================= GLOBAL ================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Cairo',sans-serif;
}

body{
    background:linear-gradient(135deg,#0b0b0f,#1a1a1f);
    padding:30px;
    color:#fff;
}

.container{
    max-width:1400px;
    margin:auto;
}

/* ================= HEADER ================= */

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    flex-wrap:wrap;
    gap:20px;
}

.page-title{
    font-size:32px;
    font-weight:900;
    color:#d4af37;
}

.welcome{
    color:#aaa;
}

.header-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.btn{
    border:none;
    padding:12px 18px;
    border-radius:12px;
    cursor:pointer;
    font-size:14px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
}

.btn-primary{
    background:#d4af37;
    color:#000;
}

.btn-dark{
    background:#111;
    color:#fff;
}

/* ================= CARDS ================= */

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.card{
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(212,175,55,0.2);
    border-radius:16px;
    padding:22px;
}

/* ================= TABLE ================= */

.table-card{
    background:rgba(255,255,255,0.03);
    border-radius:16px;
    overflow:hidden;
    border:1px solid rgba(212,175,55,0.2);
    margin-bottom:25px;
}

.section-title{
    padding:18px;
    font-size:20px;
    font-weight:900;
    color:#d4af37;
    border-bottom:1px solid rgba(255,255,255,.05);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#111;
    color:#d4af37;
    padding:16px;
}

td{
    padding:16px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,0.05);
}

.badge{
    background:rgba(212,175,55,0.15);
    color:#d4af37;
    padding:5px 10px;
    border-radius:20px;
}

/* ================= SEARCH ================= */

.search-box{
    padding:20px;
}

.search-box input{
    width:100%;
    padding:14px;
    border-radius:12px;
    border:1px solid #333;
    background:#000;
    color:#fff;
}

/* ===== HIGHLIGHT GOLD ===== */

.hl{
    background:#d4af37;
    color:#000;
    padding:2px 4px;
    border-radius:4px;
    font-weight:700;
}

/* ================= POPUP ================= */

.popup{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.85);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.popup.show{
    display:flex;
}

.popup-box{
    background:#111;
    padding:25px;
    border-radius:14px;
    width:420px;
    border:1px solid rgba(212,175,55,0.3);
}

input,
select{
    width:100%;
    padding:12px;
    margin-bottom:10px;
    border-radius:10px;
    border:1px solid #333;
    background:#000;
    color:#fff;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#d4af37;
    color:#000;
    font-weight:900;
    cursor:pointer;
}

/* NOTIFICATION */

#notifyBox{
    position:fixed;
    top:20px;
    right:20px;
    background:#d4af37;
    color:#000;
    padding:15px 18px;
    border-radius:12px;
    display:none;
    z-index:99999;
}

</style>
</head>

<body>

<div class="container">

<!-- HEADER -->
<div class="header">

    <div>
        <div class="page-title">Followup Dashboard</div>
        <div class="welcome">Welcome, <?= $name ?></div>
    </div>

    <div class="header-actions">

        <a href="zone_requests.fu.php" class="btn btn-primary">
            🟡 Requests <?= $pendingRequests ?>
        </a>

        <div class="btn btn-dark">🔔 <?= $notiCount ?></div>

        <button class="btn btn-primary" onclick="openPopup()">+ Add Zone</button>

        <a href="edit_zone.php" class="btn btn-primary">✏️ Edit Zones</a>

        <a href="dashboard.php" class="btn btn-dark">Back</a>

    </div>

</div>

<!-- CARDS -->
<div class="cards">
    <div class="card">Total Zones <?= $totalZones ?></div>
    <div class="card">Branches <?= $totalBranches ?></div>
    <div class="card">Today <?= $todayAdded ?></div>
</div>

<!-- LIVE SEARCH -->
<div class="table-card">

    <div class="section-title">Live Search Zones</div>

    <div class="search-box">
        <input type="text" id="search" placeholder="Search zones...">
    </div>

    <table>
        <thead>
            <tr>
                <th>Branch</th>
                <th>Main Area</th>
                <th>Sub Area</th>
                <th>Zone</th>
            </tr>
        </thead>

        <tbody id="result">
            <tr><td colspan="4">Start typing...</td></tr>
        </tbody>
    </table>

</div>

<!-- REQUESTS -->
<div class="table-card">

    <div class="section-title">Zone Requests</div>

    <table>
        <tbody>
        <?php foreach($recentZones as $zone): ?>
        <tr>
            <td><?= $zone["sub_area"] ?></td>
            <td><?= $zone["main_area"] ?></td>
            <td><?= $zone["branch"] ?></td>
            <td><?= $zone["status"] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>

</div>

<!-- POPUP -->
<div class="popup" id="popup">
<div class="popup-box">

<h3 style="color:#d4af37;margin-bottom:15px;">Add Zone</h3>

<form method="POST">

<select name="branch" required>
    <option value="">Select Branch</option>

    <?php foreach($branches as $b): ?>
        <option value="<?= $b ?>"><?= $b ?></option>
    <?php endforeach; ?>
</select>

<select name="main_area" required>
    <option value="">Select Main Area</option>

    <?php foreach($mainAreas as $m): ?>
        <option value="<?= $m ?>"><?= $m ?></option>
    <?php endforeach; ?>
</select>

<input type="text" name="sub_area" placeholder="Sub Area" required>

<input type="text" name="area" placeholder="Zone" required>

<button type="submit" name="save_zone">Save</button>

</form>

</div>
</div>

<!-- NOTIFICATION -->
<div id="notifyBox"></div>

<script>

/* ================= POPUP ================= */

function openPopup(){
    document.getElementById("popup").classList.add("show");
}

function closePopup(){
    document.getElementById("popup").classList.remove("show");
}

/* ESC + click outside */

document.getElementById("popup").addEventListener("click",function(e){
    if(e.target === this) closePopup();
});

document.addEventListener("keydown",function(e){
    if(e.key === "Escape") closePopup();
});

/* ================= LIVE SEARCH + HIGHLIGHT ================= */

const input = document.getElementById("search");
const result = document.getElementById("result");

let timer;

function hl(text, key){
    if(!key) return text;

    return text.replace(
        new RegExp("(" + key + ")", "gi"),
        `<span class="hl">$1</span>`
    );
}

input.addEventListener("input", function(){

    clearTimeout(timer);

    const val = this.value.trim();

    timer = setTimeout(() => {

        if(val.length === 0){
            result.innerHTML = `<tr><td colspan="4">Start typing...</td></tr>`;
            return;
        }

        result.innerHTML = `<tr><td colspan="4">Loading...</td></tr>`;

        fetch("?ajax=1&search=" + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => {

            if(data.length === 0){
                result.innerHTML = `<tr><td colspan="4">No results</td></tr>`;
                return;
            }

            let html = "";

            data.forEach(r => {

                html += `
                    <tr>
                        <td>${hl(r.branch, val)}</td>
                        <td>${hl(r.main_area, val)}</td>
                        <td>${hl(r.sub_area, val)}</td>
                        <td>${hl(r.area, val)}</td>
                    </tr>
                `;
            });

            result.innerHTML = html;

        });

    }, 250);

});

</script>

</body>
</html>