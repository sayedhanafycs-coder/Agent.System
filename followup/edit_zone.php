<?php 
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "followup") {
    header("Location: ../login.php");
    exit();
}

/* DATA */
$zones = $conn->query("SELECT * FROM zone_map ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$branches = $conn->query("SELECT DISTINCT branch FROM zone_map")->fetchAll(PDO::FETCH_COLUMN);
$mainAreas = $conn->query("SELECT DISTINCT main_area FROM zone_map")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Zone Control</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>

body{
    margin:0;
    font-family:'Cairo',sans-serif;
    background:linear-gradient(135deg,#0b0b0f,#1a1a1f);
    color:#fff;
    padding:30px;
}

.container{max-width:1200px;margin:auto;}

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

.filters{
    display:flex;
    gap:10px;
    margin-bottom:15px;
}

select{
    padding:10px;
    border-radius:10px;
    background:#111;
    color:#fff;
    border:1px solid #333;
}

.table-card{
    background:rgba(255,255,255,0.03);
    border-radius:16px;
    overflow:hidden;
}

table{width:100%;border-collapse:collapse;}

th{
    background:#111;
    color:#d4af37;
    padding:15px;
}

td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,0.05);
}

.btn{
    padding:10px 14px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-weight:700;
}

.btn-gold{background:#d4af37;color:#000;}
.btn-edit{background:#2ecc71;color:#000;}
.btn-del{background:#e74c3c;color:#fff;}

/* POPUP */
.popup{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.8);
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.popup-box{
    background:#111;
    padding:20px;
    border-radius:12px;
    width:360px;
}

input,select{
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    border:1px solid #333;
    background:#000;
    color:#fff;
}

</style>
</head>

<body>

<div class="container">

<div class="header">
    <div class="title">ZONE CONTROL PANEL</div>
    <button class="btn btn-gold" onclick="openAdd()">+ Add Zone</button>
</div>

<div class="filters">

<select id="filterBranch">
    <option value="">Branch</option>
    <?php foreach($branches as $b): ?>
        <option value="<?= $b ?>"><?= $b ?></option>
    <?php endforeach; ?>
</select>

<select id="filterZone">
    <option value="">Zone</option>
    <?php foreach($mainAreas as $m): ?>
        <option value="<?= $m ?>"><?= $m ?></option>
    <?php endforeach; ?>
</select>

</div>

<div class="table-card">
<table>
<thead>
<tr>
    <th>Street</th>
    <th>Zone</th>
    <th>Branch</th>
    <th>Actions</th>
</tr>
</thead>

<tbody id="zoneTableBody"></tbody>
</table>
</div>

</div>

<!-- EDIT POPUP -->
<div class="popup" id="editPopup">
<div class="popup-box">

<h3 style="color:#d4af37">Edit Zone</h3>

<form id="editForm">

<input type="hidden" name="id" id="edit_id">

<input name="sub_area" id="edit_sub" placeholder="Sub Area">
<input name="main_area" id="edit_main" placeholder="Zone">
<input name="branch" id="edit_branch" placeholder="Branch">

<button class="btn btn-gold">Save</button>

</form>

</div>
</div>

<!-- ADD POPUP -->
<div class="popup" id="addPopup">
<div class="popup-box">

<h3 style="color:#d4af37">Add Zone</h3>

<form id="addForm">

<input name="sub_area" placeholder="Sub Area">

<select name="main_area">
<?php foreach($mainAreas as $m): ?>
<option value="<?= $m ?>"><?= $m ?></option>
<?php endforeach; ?>
</select>

<select name="branch">
<?php foreach($branches as $b): ?>
<option value="<?= $b ?>"><?= $b ?></option>
<?php endforeach; ?>
</select>

<button class="btn btn-gold">Add</button>

</form>

</div>
</div>

<script>

const ZONES = <?= json_encode($zones) ?>;

const branchSelect = document.getElementById("filterBranch");
const zoneSelect = document.getElementById("filterZone");
const body = document.getElementById("zoneTableBody");

/* RENDER */
function renderTable(data){
    body.innerHTML = "";

    data.forEach(z=>{
        let tr = document.createElement("tr");

        tr.innerHTML = `
            <td>${z.sub_area}</td>
            <td>${z.main_area}</td>
            <td>${z.branch}</td>
            <td>
                <button class="btn btn-edit" onclick='openEdit(${JSON.stringify(z)})'>Edit</button>
                <button class="btn btn-del" onclick="deleteZone(${z.id})">Delete</button>
            </td>
        `;

        body.appendChild(tr);
    });
}

/* ✅ FIXED: update zones based on branch */
function updateZoneOptions(){

    let branch = branchSelect.value;

    let filtered = branch 
        ? ZONES.filter(z => z.branch === branch)
        : [];

    let uniqueZones = [...new Set(filtered.map(z => z.main_area))];

    zoneSelect.innerHTML = `<option value="">Zone</option>`;

    uniqueZones.forEach(z=>{
        let opt = document.createElement("option");
        opt.value = z;
        opt.textContent = z;
        zoneSelect.appendChild(opt);
    });
}

/* FILTER */
function filter(){

    let b = branchSelect.value;
    let z = zoneSelect.value;

    if(!b || !z){
        body.innerHTML = "";
        return;
    }

    renderTable(ZONES.filter(x=>x.branch==b && x.main_area==z));
}

/* EVENTS */
branchSelect.addEventListener("change", ()=>{
    zoneSelect.value = "";
    updateZoneOptions();
    body.innerHTML = "";
});

zoneSelect.addEventListener("change", filter);

/* INIT */
updateZoneOptions();

/* EDIT */
function openEdit(z){
    editPopup.style.display="flex";
    edit_id.value=z.id;
    edit_sub.value=z.sub_area;
    edit_main.value=z.main_area;
    edit_branch.value=z.branch;
}

/* DELETE */
function deleteZone(id){
    if(!confirm("Delete?")) return;

    fetch("delete_zone.php?id="+id)
    .then(r=>r.json())
    .then(d=>{
        if(d.success) location.reload();
    });
}

/* ADD */
function openAdd(){
    addPopup.style.display="flex";
}

/* CLOSE POPUP */
document.querySelectorAll(".popup").forEach(p=>{
    p.onclick=e=>{
        if(e.target===p) p.style.display="none";
    }
});

/* ESC CLOSE */
document.addEventListener("keydown",e=>{
    if(e.key==="Escape"){
        document.querySelectorAll(".popup").forEach(p=>p.style.display="none");
    }
});

/* SAVE EDIT */
editForm.onsubmit=e=>{
    e.preventDefault();

    fetch("update_zone.php",{
        method:"POST",
        body:new FormData(editForm)
    }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload();
    });
};

/* SAVE ADD */
addForm.onsubmit=e=>{
    e.preventDefault();

    fetch("add_zone.php",{
        method:"POST",
        body:new FormData(addForm)
    }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload();
    });
};

</script>

</body>
</html>