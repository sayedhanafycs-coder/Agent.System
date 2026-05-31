<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION["role"])) {
    header("Location: ../login.php");
    exit();
}

$branches = $conn->query("
    SELECT DISTINCT branch
    FROM zone_map
    ORDER BY branch ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Zone Map</title>

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
    background:linear-gradient(180deg,#0b0b0f,#111827);
    color:#e5e7eb;
    padding:30px;
}

/* ================= CONTAINER ================= */

.container{
    max-width:1300px;
    margin:auto;
}

/* ================= HEADER ================= */

.top-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    flex-wrap:wrap;
    gap:15px;
}

.page-title{
    font-size:32px;
    font-weight:700;
    color:#d4af37;
}

/* ================= BUTTONS ================= */

.actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.btn{
    padding:12px 20px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-size:15px;
    font-weight:600;
    transition:.3s;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.btn-add{
    background:#d4af37;
    color:#111;
}

.btn-add:hover{
    background:#f5d76e;
    transform:translateY(-2px);
}


/* ================= SEARCH ================= */

.search-card{
    background:#111827;
    padding:20px;
    border-radius:18px;
    border:1px solid rgba(212,175,55,.15);
    margin-bottom:25px;
}

.search-box,
.search-card{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}

input,
select{
    width:100%;
    padding:14px;
    border-radius:12px;
    border:1px solid rgba(212,175,55,.2);
    background:#0b0b0f;
    color:#fff;
    outline:none;
    font-size:15px;
    transition:.3s;
}

input:focus,
select:focus{
    border-color:#d4af37;
    box-shadow:0 0 0 2px rgba(212,175,55,.15);
}

/* ================= TABLE ================= */

.table-card{
    background:#111827;
    border-radius:18px;
    overflow:hidden;
    border:1px solid rgba(212,175,55,.15);
    box-shadow:0 10px 30px rgba(0,0,0,.3);
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#0f172a;
    color:#d4af37;
    padding:18px;
    font-size:15px;
    letter-spacing:.5px;
}

td{
    padding:16px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,.05);
    font-size:14px;
    color:#e5e7eb;
}

tr:hover{
    background:#0b0b0f;
    transition:.2s;
}

.empty-row td{
    padding:40px;
    color:#94a3b8;
}

/* ================= BADGE ================= */

.badge{
    background:rgba(212,175,55,.15);
    color:#d4af37;
    padding:6px 12px;
    border-radius:30px;
    font-size:13px;
    font-weight:600;
}

/* ================= MARK ================= */

mark{
    background:#d4af37;
    color:#000;
    padding:2px 5px;
    border-radius:4px;
}

/* ================= POPUP ================= */

.popup{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:999;
}

.popup-content{
    width:95%;
    max-width:500px;
    background:#111827;
    border-radius:20px;
    padding:25px;
    border:1px solid rgba(212,175,55,.2);
    box-shadow:0 20px 60px rgba(0,0,0,.5);
    animation:pop .25s ease;
}

@keyframes pop{
    from{transform:scale(.9);opacity:0;}
    to{transform:scale(1);opacity:1;}
}

.popup-title{
    font-size:24px;
    margin-bottom:20px;
    color:#d4af37;
    font-weight:700;
}

.popup-actions{
    display:flex;
    gap:10px;
    margin-top:20px;
}

.save-btn{
    flex:1;
    background:#d4af37;
    color:#111;
    border:none;
    padding:14px;
    border-radius:12px;
    font-weight:800;
    cursor:pointer;
}

.close-btn{
    flex:1;
    background:#1f2937;
    color:#fff;
    border:none;
    padding:14px;
    border-radius:12px;
    cursor:pointer;
}

/* ================= MESSAGES ================= */

.success-msg{
    background:#14532d;
    color:#bbf7d0;
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
    display:none;
}

.error-msg{
    background:#7f1d1d;
    color:#fecaca;
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
    display:none;
}

/* ================= RESPONSIVE ================= */

@media(max-width:768px){
    body{padding:15px;}
    .top-header{flex-direction:column;align-items:flex-start;}
}

</style>
</head>

<body>

<div class="container">

    <div class="top-header">

        <h1 class="page-title">
            Zone Map
        </h1>

        <div class="actions">

            <button class="btn btn-add" onclick="openPopup()">
                + إضافة زون جديدة
            </button>

                     </div>

    </div>

    <div class="search-card">

        <div class="search-box">

            <div class="input-group">

                <select id="branch">
                    <option value="">All Branches</option>

                    <?php foreach ($branches as $b): ?>

                        <option value="<?= $b["branch"] ?>">
                            <?= $b["branch"] ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="input-group">

                <input
                    type="text"
                    id="search"
                    placeholder="ابحث عن منطقة أو زون..."
                >

            </div>

        </div>

    </div>

    <div class="table-card">

        <table>

            <thead>
                <tr>
                    <th>Sub Area</th>
                    <th>Zone</th>
                    <th>Branch</th>
                </tr>
            </thead>

            <tbody id="results">

                <tr class="empty-row">
                    <td colspan="3">
                        ابدأ البحث لعرض النتائج
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

<!-- =========================
     POPUP
========================= -->

<div class="popup" id="popup">

    <div class="popup-content">

        <button class="popup-x" onclick="closePopup()">
            ✕
        </button>

        <div class="popup-title">
            إضافة زون جديدة
        </div>

        <div class="success-msg" id="successMsg">
            تم إرسال الطلب للفولو اب بنجاح 🎉
        </div>

        <div class="error-msg" id="errorMsg"></div>

        <form id="zoneForm">

            <div class="form-group">
                <label>اسم الشارع</label>
                <input type="text" name="sub_area" required>
            </div>

            <div class="form-group">
                <label>اسم المنطقة</label>
                <input type="text" name="main_area" required>
            </div>

            <div class="form-group">
                <label>الزون</label>
                <input type="text" name="area" required>
            </div>

            <div class="form-group">
                <label>مين بلغك بالزون</label>
                <input type="text" name="comment" required>
            </div>

            <div class="form-group">
                <label>الفرع</label>

                <select name="branch" required>

                    <?php foreach ($branches as $b): ?>

                        <option value="<?= $b["branch"] ?>">
                            <?= $b["branch"] ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="popup-actions">

                <button type="submit" class="save-btn">
                    حفظ
                </button>

                <button type="button" class="close-btn" onclick="closePopup()">
                    إغلاق
                </button>

            </div>

        </form>

    </div>

</div>

<script>

let search = document.getElementById("search");
let branch = document.getElementById("branch");
let results = document.getElementById("results");

let timer = null;

/* =========================
   FETCH DATA
========================= */

function fetchData() {

    let q = search.value;
    let b = branch.value;

    fetch(`search_zone.php?search=${encodeURIComponent(q)}&branch=${encodeURIComponent(b)}`)

    .then(res => res.json())

    .then(data => {

        results.innerHTML = "";

        if (data.length === 0) {

            results.innerHTML = `
                <tr class="empty-row">
                    <td colspan="3">
                        لا توجد نتائج
                    </td>
                </tr>
            `;

            return;
        }

        data.forEach(row => {

            results.innerHTML += `
                <tr>

                    <td>
                        ${highlight(row.sub_area, q)}
                    </td>

                    <td>
                        <span class="badge">
                            ${highlight(row.main_area, q)}
                        </span>
                    </td>

                    <td>
                        ${row.branch}
                    </td>

                </tr>
            `;
        });

    })

    .catch(err => {
        console.log(err);
    });

}

/* =========================
   HIGHLIGHT
========================= */

function highlight(text, keyword) {

    if (!keyword) return text;

    let regex = new RegExp(`(${keyword})`, "gi");

    return text.replace(regex, "<mark>$1</mark>");
}

/* =========================
   SEARCH EVENTS
========================= */

search.addEventListener("input", function () {

    clearTimeout(timer);

    timer = setTimeout(fetchData, 300);

});

branch.addEventListener("change", fetchData);

/* =========================
   POPUP
========================= */

function openPopup(){

    document.getElementById("popup").style.display = "flex";

    document.body.style.overflow = "hidden";
}

function closePopup(){

    document.getElementById("popup").style.display = "none";

    document.body.style.overflow = "auto";

    document.getElementById("successMsg").style.display = "none";

    document.getElementById("errorMsg").style.display = "none";
}

/* اغلاق لما يدوس برا */

window.addEventListener("click", function(e){

    let popup = document.getElementById("popup");

    if(e.target === popup){
        closePopup();
    }

});

/* اغلاق بـ ESC */

document.addEventListener("keydown", function(e){

    if(e.key === "Escape"){
        closePopup();
    }

});

/* =========================
   SAVE ZONE
========================= */

document.getElementById("zoneForm")
.addEventListener("submit", function(e){

    e.preventDefault();

    let formData = new FormData(this);

    fetch("../followup/save_zone.php",{
        method:"POST",
        body:formData
    })

    .then(res => res.text())

    .then(data => {

        if(data.trim() === "success"){

            document.getElementById("successMsg").style.display = "block";

            document.getElementById("errorMsg").style.display = "none";

            this.reset();

            fetchData();

            setTimeout(() => {

                closePopup();

            },1500);

        }else{

            document.getElementById("errorMsg").innerText = data;

            document.getElementById("errorMsg").style.display = "block";

            document.getElementById("successMsg").style.display = "none";

        }

    });

});

</script>

</body>
</html>