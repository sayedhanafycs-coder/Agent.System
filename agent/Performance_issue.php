<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "agent") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];
$today = date("Y-m-d");

/* ================= SAVE ISSUE ================= */

if(isset($_POST['save_issue'])){

    $agent_name = $name;

    $date = $_POST['date'];
    $day = $_POST['day'];
    $action = $_POST['action'];
    $problem = $_POST['problem'];
    $time_from = $_POST['time_from'];
    $time_to = $_POST['time_to'];
    $minutes = $_POST['minutes'];

    $sql = "INSERT INTO performance_issues
    (agent_name, issue_date, day_name, action_type, problem, time_from, time_to, minutes)
    VALUES
    (?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $agent_name,
        $date,
        $day,
        $action,
        $problem,
        $time_from,
        $time_to,
        $minutes
    ]);

    echo json_encode(["success"=>true, "msg"=>"Saved"]);
    exit();
}

/* ================= GET DATA ================= */

if(isset($_GET['load'])){

    $from = $_GET['from'];
    $to = $_GET['to'];

    $sql = "SELECT * FROM performance_issues
            WHERE agent_name = ?
            AND issue_date BETWEEN ? AND ?
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);

    $stmt->execute([$name,$from,$to]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<title>Performance Issue</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

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
    color:#fff;
    min-height:100vh;
    padding:20px;
}

/* ================= MAIN ================= */

.main{
    width:100%;
}

.top-title{
    font-size:30px;
    font-weight:900;
    margin-bottom:5px;
}

.sub{
    color:#94a3b8;
    font-size:13px;
    margin-bottom:20px;
}

/* ================= SECTION ================= */

.section{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(212,175,55,.12);
    border-radius:18px;
    padding:20px;
    margin-bottom:22px;
}

.section-title{
    font-size:22px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:15px;
}

/* ================= FORM ================= */

.row{
    display:flex;
    gap:12px;
    margin-bottom:12px;
}

input,select{
    flex:1;
    padding:12px;
    border-radius:12px;
    border:1px solid rgba(212,175,55,.2);
    background:#111827;
    color:#fff;
    outline:none;
    transition:.2s;
}

input:focus,
select:focus{
    border-color:#d4af37;
}

button{
    padding:12px;
    background:#d4af37;
    border:none;
    border-radius:12px;
    font-weight:900;
    cursor:pointer;
    transition:.2s;
    color:#000;
}

button:hover{
    opacity:.9;
}

.save-btn{
    width:100%;
}

.filter-btn{
    width:220px;
}

/* ================= TABLE ================= */

.table-card{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(212,175,55,.12);
    border-radius:16px;
    overflow:hidden;
    margin-top:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#111827;
    color:#d4af37;
    padding:14px;
    font-size:13px;
}

td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,.05);
    font-size:13px;
}

/* ================= SUMMARY ================= */

.summary-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
    margin-top:20px;
}

.summary-card{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(212,175,55,.12);
    border-radius:16px;
    padding:20px;
}

.summary-card i{
    color:#d4af37;
    font-size:22px;
    margin-bottom:12px;
}

.summary-number{
    font-size:34px;
    font-weight:900;
    color:#d4af37;
}

.summary-title{
    color:#cbd5e1;
    font-size:13px;
    margin-top:5px;
}

/* ================= MOBILE ================= */

@media(max-width:900px){

    .row{
        flex-direction:column;
    }

    .filter-btn{
        width:100%;
    }

}

html, body {
    height: 100%;
    overflow: hidden;
}

.main {
    height: 100vh;
    overflow-y: auto;
    padding-right: 10px;
}

/* ================= POPUP ================= */

.popup {
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,.6);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.popup-box {
    background:#111827;
    border-radius:14px;
    padding:20px;
    text-align:center;
    color:#fff;
    border:2px solid #d4af37;
    min-width:250px;
}

.popup-box button {
    margin-top:10px;
    padding:8px 15px;
    border:none;
    background:#d4af37;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
}

.hidden { display:none; }

</style>
</head>

<body>

<div class="main">

    <div class="top-title">Performance Issue</div>
    <div class="sub">Track Coaching, Logout and Tardy Login professionally</div>

    <!-- ================= FORM ================= -->

    <div class="section">

        <div class="section-title">Today Issues</div>

        <form id="issueForm">

            <div class="row">
                <input type="date" name="date" value="<?= $today ?>" required>

                <select name="day" required>
                    <option value="">Select Day</option>
                    <option>Sunday</option>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                    <option>Saturday</option>
                </select>
            </div>

            <div class="row">
                <select name="action" id="action" required>
                    <option value="">Select Action</option>
                    <option value="Tardy">Tardy Login</option>
                    <option value="Coaching">Coaching</option>
                    <option value="Logout">Logout</option>
                </select>

                <select name="problem" id="problem" required>
                    <option value="">Select Problem</option>
                </select>
            </div>

            <div class="row">
                <select name="time_from" required>
                <?php
                $start = strtotime("09:00");
                $end = strtotime("02:00 +1 day");
                for ($t = $start; $t <= $end; $t += 60) {
                    echo "<option>" . date("H:i", $t) . "</option>";
                }
                ?>
                </select>

                <select name="time_to" required>
                <?php
                $start = strtotime("09:00");
                $end = strtotime("02:00 +1 day");
                for ($t = $start; $t <= $end; $t += 60) {
                    echo "<option>" . date("H:i", $t) . "</option>";
                }
                ?>
                </select>
            </div>

            <button type="submit" class="save-btn">Save Issue</button>

        </form>
    </div>

    <!-- ================= REPORT ================= -->

    <div class="section">

        <div class="section-title">Reports & Totals</div>

        <div class="row">
            <input type="date" id="fromDate" value="<?= $today ?>">
            <input type="date" id="toDate" value="<?= $today ?>">
            <button class="filter-btn" onclick="loadIssues()">Show My Numbers</button>
        </div>

        <div class="summary-grid">

            <div class="summary-card">
                <i class="fa fa-user-group"></i>
                <div class="summary-number" id="coachingMinutes">0</div>
                <div class="summary-title">Coaching Minutes</div>
            </div>

            <div class="summary-card">
                <i class="fa fa-right-from-bracket"></i>
                <div class="summary-number" id="logoutMinutes">0</div>
                <div class="summary-title">Logout Minutes</div>
            </div>

            <div class="summary-card">
                <i class="fa fa-clock"></i>
                <div class="summary-number" id="tardyMinutes">0</div>
                <div class="summary-title">Tardy Login Minutes</div>
            </div>

        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Action</th>
                        <th>Problem</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Minutes</th>
                    </tr>
                </thead>

                <tbody id="issueTable"></tbody>

            </table>
        </div>

    </div>

</div>

<!-- ================= POPUP ================= -->

<div id="popup" class="popup hidden">
    <div class="popup-box">
        <p id="popupMsg"></p>
        <button onclick="closePopup()">OK</button>
    </div>
</div>

<script>

/* ================= POPUP ================= */

function showPopup(msg, success=true){
    document.getElementById("popupMsg").innerText = msg;

    let box = document.querySelector(".popup-box");
    box.style.borderColor = success ? "#4CAF50" : "#ff4d4d";

    document.getElementById("popup").classList.remove("hidden");
}

function closePopup(){
    document.getElementById("popup").classList.add("hidden");
}

/* ================= PROBLEMS ================= */

const tardyProblems = ["PC Shortage"];

const coachingProblems = [
    "Low Performance",
    "Quality Issue",
    "Wrong Action",
    "Attendance",
    "Behavior"
];

const logoutProblems = [
    "System Issue",
    "Electricity",
    "Internet",
    "Personal Issue",
    "Emergency"
];

const actionSelect = document.getElementById("action");
const problemSelect = document.getElementById("problem");

actionSelect.addEventListener("change", ()=>{

    let value = actionSelect.value;

    problemSelect.innerHTML = `<option value="">Select Problem</option>`;

    let arr = [];

    if(value === "Tardy") arr = tardyProblems;
    if(value === "Coaching") arr = coachingProblems;
    if(value === "Logout") arr = logoutProblems;

    arr.forEach(p=>{
        let option = document.createElement("option");
        option.value = p;
        option.textContent = p;
        problemSelect.appendChild(option);
    });

});

/* ================= SAVE ================= */

document.getElementById("issueForm").onsubmit = function(e){

    e.preventDefault();

    const form = new FormData(this);

    let from = form.get("time_from");
    let to = form.get("time_to");

    let minutes = calculateMinutes(from,to);

    form.append("minutes",minutes);
    form.append("save_issue","1");

    fetch("",{
        method:"POST",
        body:form
    })
    .then(res => res.json())
    .then(data => {

        if(data.success){
            showPopup("Issue Saved Successfully ✔", true);
            loadIssues();
            this.reset();
        } else {
            showPopup("Save Failed ❌ " + (data.msg || ""), false);
        }

    })
    .catch(()=>{
        showPopup("Network Error", false);
    });

};

/* ================= LOAD ================= */

function loadIssues(){

    let from = document.getElementById("fromDate").value;
    let to = document.getElementById("toDate").value;

    fetch(`?load=1&from=${from}&to=${to}`)
    .then(res=>res.json())
    .then(data=>{

        let tbody = document.getElementById("issueTable");
        tbody.innerHTML = "";

        let coaching = 0, logout = 0, tardy = 0;

        data.forEach(item=>{

            let tr = document.createElement("tr");

            tr.innerHTML = `
                <td>${item.issue_date}</td>
                <td>${item.day_name}</td>
                <td>${item.action_type}</td>
                <td>${item.problem}</td>
                <td>${item.time_from}</td>
                <td>${item.time_to}</td>
                <td>${item.minutes} Min</td>
            `;

            tbody.appendChild(tr);

            if(item.action_type === "Coaching") coaching += parseInt(item.minutes);
            if(item.action_type === "Logout") logout += parseInt(item.minutes);
            if(item.action_type === "Tardy") tardy += parseInt(item.minutes);

        });

        document.getElementById("coachingMinutes").innerText = coaching;
        document.getElementById("logoutMinutes").innerText = logout;
        document.getElementById("tardyMinutes").innerText = tardy;

    });

}

/* ================= TIME ================= */

function calculateMinutes(start,end){

    let s = start.split(":");
    let e = end.split(":");

    let startMinutes = parseInt(s[0])*60 + parseInt(s[1]);
    let endMinutes = parseInt(e[0])*60 + parseInt(e[1]);

    if(endMinutes < startMinutes){
        endMinutes += 24*60;
    }

    return endMinutes - startMinutes;
}

loadIssues();

</script>

</body>
</html>