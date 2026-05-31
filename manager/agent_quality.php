<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "manager") {
    header("Location: ../login.php");
    exit();
}

/* ======================================
   FILTER DATE RANGE
====================================== */

$date_from = isset($_GET['date_from'])
    ? $_GET['date_from']
    : date("Y-m-d", strtotime("-7 days"));

$date_to = isset($_GET['date_to'])
    ? $_GET['date_to']
    : date("Y-m-d");

/* ======================================
   TOTAL EVALUATIONS
====================================== */

$totalStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM evaluations
    WHERE CAST(created_at AS DATE)
    BETWEEN ? AND ?
");

$totalStmt->execute([$date_from, $date_to]);

$totalEvaluations =
    $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

/* ======================================
   AVERAGE PERCENTAGE
====================================== */

$scoreStmt = $conn->prepare("
    SELECT SUM(total_score) AS total_score
    FROM evaluations
    WHERE CAST(created_at AS DATE)
    BETWEEN ? AND ?
");

$scoreStmt->execute([$date_from, $date_to]);

$totalScore =
    $scoreStmt->fetch(PDO::FETCH_ASSOC)['total_score'];

$totalPossible =
    $totalEvaluations * 145;

$avgPercentage = 0;

if($totalPossible > 0){

    $avgPercentage =
        round(($totalScore / $totalPossible) * 100,1);
}

/* ======================================
   ZERO CALLS
====================================== */

$zeroStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM evaluations
    WHERE total_score = 0
    AND CAST(created_at AS DATE)
    BETWEEN ? AND ?
");

$zeroStmt->execute([$date_from, $date_to]);

$zeroCalls =
    $zeroStmt->fetch(PDO::FETCH_ASSOC)['total'];

/* ======================================
   ZERO CALLS DETAILS
====================================== */

$zeroDetailsStmt = $conn->prepare("
SELECT
    u.name,
    COUNT(*) total_zero

FROM evaluations e

INNER JOIN users u
ON e.user_id = u.id

WHERE e.total_score = 0
AND CAST(e.created_at AS DATE)
BETWEEN ? AND ?

GROUP BY u.name

ORDER BY total_zero DESC
");

$zeroDetailsStmt->execute([$date_from, $date_to]);

$zeroDetails =
    $zeroDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================
   BEST AGENT
====================================== */

$bestStmt = $conn->prepare("
SELECT TOP 1
    u.name,
    AVG(e.total_score) avg_score

FROM evaluations e

INNER JOIN users u
ON e.user_id = u.id

WHERE CAST(e.created_at AS DATE)
BETWEEN ? AND ?

GROUP BY u.name

ORDER BY avg_score DESC
");

$bestStmt->execute([$date_from, $date_to]);

$bestAgent =
    $bestStmt->fetch(PDO::FETCH_ASSOC);

/* ======================================
   WORST AGENT
====================================== */

$worstStmt = $conn->prepare("
SELECT TOP 1
    u.name,
    AVG(e.total_score) avg_score

FROM evaluations e

INNER JOIN users u
ON e.user_id = u.id

WHERE CAST(e.created_at AS DATE)
BETWEEN ? AND ?

GROUP BY u.name

ORDER BY avg_score ASC
");

$worstStmt->execute([$date_from, $date_to]);

$worstAgent =
    $worstStmt->fetch(PDO::FETCH_ASSOC);

/* ======================================
   MOST COMMON MISTAKES
====================================== */

$mistakesStmt = $conn->prepare("
SELECT
    comment,
    COUNT(*) total

FROM evaluation_details

WHERE comment IS NOT NULL
AND comment != ''

GROUP BY comment

ORDER BY total DESC
");

$mistakesStmt->execute();

$mistakes =
    $mistakesStmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================
   AGENT RANKING
====================================== */

$rankingStmt = $conn->prepare("
SELECT
    u.id,

    u.name AS agent_name,

    COUNT(e.id) AS total_calls,

    CAST(
        ROUND(
            (
                SUM(e.total_score) * 100.0
            ) / (COUNT(e.id) * 145),
        1)
    AS DECIMAL(10,1)) AS avg_score,

    SUM(
        CASE
            WHEN e.total_score = 0
            THEN 1
            ELSE 0
        END
    ) AS zero_calls

FROM evaluations e

INNER JOIN users u
ON e.user_id = u.id

WHERE CAST(e.created_at AS DATE)
BETWEEN ? AND ?

GROUP BY u.id, u.name

ORDER BY avg_score DESC
");

$rankingStmt->execute([$date_from, $date_to]);

$ranking =
    $rankingStmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================
   GET ALL EVALUATIONS
====================================== */

$evalStmt = $conn->prepare("
SELECT
    e.*,

    agent.name AS agent_name,

    evaluator.name AS evaluator_name

FROM evaluations e

LEFT JOIN users agent
ON e.user_id = agent.id

LEFT JOIN users evaluator
ON e.evaluator_id = evaluator.id

WHERE CAST(e.created_at AS DATE)
BETWEEN ? AND ?

ORDER BY e.created_at DESC
");

$evalStmt->execute([$date_from, $date_to]);

$evaluations =
    $evalStmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================
   GROUP CALLS BY AGENT
====================================== */

$agentCalls = [];

foreach($evaluations as $e){

    $agentCalls[$e['user_id']][] = $e;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Agent Quality Dashboard</title>

<link
href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap"
rel="stylesheet"
>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    flex-wrap:wrap;
    gap:15px;
}

.page-title{
    font-size:34px;
    font-weight:900;
    color:#d4af37;
}

.filter-box{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.filter-box input{
    background:#111827;
    border:none;
    padding:12px;
    color:white;
    border-radius:12px;
}

.filter-box button{
    background:#d4af37;
    border:none;
    padding:12px 18px;
    border-radius:12px;
    font-weight:900;
    cursor:pointer;
}

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:18px;
    margin-bottom:25px;
}

.card{
    background:#111827;
    border-radius:20px;
    padding:22px;
    border:1px solid rgba(212,175,55,.15);
    transition:.3s;
}

.card:hover{
    transform:translateY(-4px);
}

.card-title{
    color:#94a3b8;
    font-size:14px;
    margin-bottom:10px;
}

.card-number{
    font-size:34px;
    font-weight:900;
    color:#d4af37;
}

.grid{
    display:grid;
    grid-template-columns:400px 1fr;
    gap:22px;
}

.panel{
    background:#111827;
    border-radius:22px;
    border:1px solid rgba(212,175,55,.15);
    overflow:hidden;
}

.panel-header{
    background:#1f2937;
    padding:18px;
    font-size:20px;
    font-weight:900;
    color:#d4af37;
}

.panel-body{
    padding:18px;
}

.agent-row{
    background:#0f172a;
    border-radius:14px;
    padding:16px;
    margin-bottom:14px;
    cursor:pointer;
    transition:.3s;
}

.agent-row:hover{
    background:#172033;
}

.agent-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.agent-name{
    font-size:18px;
    font-weight:900;
}

.score{
    color:#d4af37;
    font-size:20px;
    font-weight:900;
}

.small{
    margin-top:8px;
    color:#94a3b8;
    font-size:13px;
}

.table-box{
    overflow:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f2937;
    color:#d4af37;
    padding:14px;
    font-size:14px;
}

td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,.05);
    font-size:14px;
}

.low-score{
    color:#ef4444;
    font-weight:bold;
}

.high-score{
    color:#4ade80;
    font-weight:bold;
}

.view-btn{
    background:#2563eb;
    color:white;
    border:none;
    padding:8px 12px;
    border-radius:10px;
    cursor:pointer;
    font-size:12px;
    font-weight:800;
}

.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.7);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:99999;
}

.modal-content{
    width:95%;
    max-width:1200px;
    max-height:90vh;
    overflow:auto;
    background:#111827;
    border-radius:22px;
    padding:25px;
}

.small-modal{
    max-width:700px !important;
}

.close-btn{
    float:right;
    background:#dc2626;
    border:none;
    color:white;
    padding:8px 14px;
    border-radius:10px;
    cursor:pointer;
}

.modal-title{
    font-size:28px;
    color:#d4af37;
    margin-bottom:20px;
    font-weight:900;
}

.mistakes-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.mistake-item{
    background:#0f172a;
    padding:14px;
    border-radius:14px;
}

@media(max-width:1000px){

    .grid{
        grid-template-columns:1fr;
    }

    .mistakes-grid{
        grid-template-columns:1fr;
    }
}

</style>

</head>

<body>

<div class="header">

    <div class="page-title">
        Agent Quality Dashboard
    </div>

    <form class="filter-box" method="GET">

        <input
            type="date"
            name="date_from"
            value="<?= $date_from ?>"
        >

        <input
            type="date"
            name="date_to"
            value="<?= $date_to ?>"
        >

        <button type="submit">
            Filter
        </button>

        <a
            href="export_quality_excel.php?date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
            style="
                background:#16a34a;
                padding:12px 18px;
                border-radius:12px;
                color:white;
                text-decoration:none;
                font-weight:900;
            "
        >
            Export Excel
        </a>

        <a
            href="dashboard.php"
            style="
                background:#334155;
                padding:12px 18px;
                border-radius:12px;
                color:white;
                text-decoration:none;
                font-weight:900;
            "
        >
            Back To Dashboard
        </a>

    </form>

</div>

<!-- STATS -->

<div class="stats">

    <div class="card">

        <div class="card-title">
            Total Evaluations
        </div>

        <div class="card-number">
            <?= $totalEvaluations ?>
        </div>

    </div>

    <div class="card">

        <div class="card-title">
            Average Score
        </div>

        <div class="card-number">
            <?= $avgPercentage ?>%
        </div>

    </div>

    <div
        class="card"
        onclick="openZeroModal()"
        style="cursor:pointer;"
    >

        <div class="card-title">
            Zero Calls
        </div>

        <div class="card-number">
            <?= $zeroCalls ?>
        </div>

    </div>

    <div class="card">

        <div class="card-title">
            Best Agent
        </div>

        <div
            class="card-number"
            style="font-size:22px;"
        >
            <?= $bestAgent['name'] ?? '-' ?>
        </div>

    </div>

    <div class="card">

        <div class="card-title">
            Worst Agent
        </div>

        <div
            class="card-number"
            style="font-size:22px;"
        >
            <?= $worstAgent['name'] ?? '-' ?>
        </div>

    </div>

    <div
        class="card"
        onclick="openMistakesModal()"
        style="cursor:pointer;"
    >

        <div class="card-title">
            Most Common Mistake
        </div>

        <div class="card-number">
            <?= count($mistakes) ?>
        </div>

    </div>

</div>

<div class="grid">

    <!-- LEFT -->

    <div class="panel">

        <div
            class="panel-header"
            onclick="toggleAgentsPanel()"
            style="
                cursor:pointer;
                display:flex;
                justify-content:space-between;
            "
        >

            <span>Agents Weekly Overview</span>

            <span id="toggleIcon">+</span>

        </div>

        <div
            class="panel-body"
            id="agentsPanel"
            style="display:none;"
        >

            <?php foreach($ranking as $r): ?>

            <div
                class="agent-row"
                onclick="openAgentCalls(<?= $r['id'] ?>)"
            >

                <div class="agent-top">

                    <div class="agent-name">
                        <?= $r['agent_name'] ?>
                    </div>

                    <div class="score">
                        <?= $r['avg_score'] ?>%
                    </div>

                </div>

                <div class="small">

                    Calls:
                    <?= $r['total_calls'] ?>

                    |

                    Zero Calls:
                    <?= $r['zero_calls'] ?>

                </div>

            </div>

            <?php endforeach; ?>

        </div>

    </div>

    <!-- RIGHT -->

    <div class="panel">

        <div class="panel-header">
            Quality Performance Chart
        </div>

        <div class="panel-body">

            <canvas id="scoreChart"></canvas>

        </div>

    </div>

</div>

<!-- CALLS MODAL -->

<div id="callsModal" class="modal">

    <div class="modal-content">

        <button
            class="close-btn"
            onclick="closeModal()"
        >
            X
        </button>

        <div
            class="modal-title"
            id="modalTitle"
        >
            Agent Calls
        </div>

        <div class="table-box">

            <table>

                <thead>

                <tr>

                    <th>Evaluator</th>
                    <th>Score</th>
                    <th>Order</th>
                    <th>Phone</th>
                    <th>Call Type</th>
                    <th>Date</th>
                    <th>Details</th>

                </tr>

                </thead>

                <tbody id="callsBody"></tbody>

            </table>

        </div>

    </div>

</div>

<!-- MISTAKES MODAL -->

<div id="mistakesModal" class="modal">

    <div class="modal-content small-modal">

        <button
            class="close-btn"
            onclick="closeMistakesModal()"
        >
            X
        </button>

        <div class="modal-title">
            Most Common Mistakes
        </div>

        <div class="mistakes-grid">

        <?php foreach($mistakes as $m): ?>

            <div class="mistake-item">

                <div
                    style="
                        font-weight:800;
                        margin-bottom:8px;
                    "
                >
                    <?= htmlspecialchars($m['comment']) ?>
                </div>

                <div style="color:#94a3b8;">

                    Repeated:
                    <?= $m['total'] ?>

                </div>

            </div>

        <?php endforeach; ?>

        </div>

    </div>

</div>

<!-- ZERO MODAL -->

<div id="zeroModal" class="modal">

    <div class="modal-content small-modal">

        <button
            class="close-btn"
            onclick="closeZeroModal()"
        >
            X
        </button>

        <div class="modal-title">
            Zero Calls Details
        </div>

        <?php foreach($zeroDetails as $z): ?>

        <div
            style="
                background:#0f172a;
                padding:14px;
                border-radius:14px;
                margin-bottom:10px;
            "
        >

            <div
                style="
                    font-size:18px;
                    font-weight:900;
                    margin-bottom:6px;
                "
            >
                <?= $z['name'] ?>
            </div>

            <div style="color:#ef4444;font-weight:800;">

                Zero Calls:
                <?= $z['total_zero'] ?>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

</div>

<script>

let callsData =
<?= json_encode($agentCalls) ?>;

/* ======================================
   OPEN AGENT CALLS
====================================== */

function openAgentCalls(agentId){

    let body =
        document.getElementById("callsBody");

    body.innerHTML = "";

    let calls = callsData[agentId];

    if(!calls || calls.length === 0){

        body.innerHTML = `
            <tr>
                <td colspan="7">
                    No calls found
                </td>
            </tr>
        `;

    }else{

        document.getElementById("modalTitle")
        .innerHTML =
            calls[0].agent_name + " Calls";

        calls.forEach(call => {

            let percentage =
                parseFloat(
                    ((call.total_score / 145) * 100)
                    .toFixed(1)
                );

            body.innerHTML += `

            <tr>

                <td>${call.evaluator_name ?? '-'}</td>

                <td>

                    <span class="${
                        percentage < 50
                        ? 'low-score'
                        : 'high-score'
                    }">

                        ${percentage}%

                    </span>

                </td>

                <td>${call.order_id ?? '-'}</td>

                <td>${call.phone ?? '-'}</td>

                <td>${call.call_type ?? '-'}</td>

                <td>${call.created_at}</td>

                <td>

                    <button
                        class="view-btn"
                        onclick="loadDetails(${call.id})"
                    >
                        View
                    </button>

                </td>

            </tr>
            `;
        });
    }

    document.getElementById("callsModal")
        .style.display = "flex";
}

/* ======================================
   CLOSE MODAL
====================================== */

function closeModal(){

    document.getElementById("callsModal")
        .style.display = "none";
}

/* ======================================
   LOAD DETAILS
====================================== */

function loadDetails(id){

    fetch("get_quality_details.php?id="+id)

    .then(res => res.text())

    .then(data => {

        let win =
            window.open("", "", "width=700,height=800");

        win.document.write(`
            <html>
            <head>

            <title>Evaluation Details</title>

            <style>

            body{
                background:#0f172a;
                color:white;
                font-family:Cairo;
                padding:20px;
            }

            </style>

            </head>

            <body>

            ${data}

            </body>

            </html>
        `);
    });
}

/* ======================================
   CHART
====================================== */

const ctx =
document.getElementById('scoreChart');

new Chart(ctx, {

    type: 'bar',

    data: {

        labels: [

            <?php
            foreach($ranking as $r){
                echo "'" . $r['agent_name'] . "',";
            }
            ?>

        ],

        datasets: [{

            label: 'Average %',

            data: [

                <?php
                foreach($ranking as $r){
                    echo $r['avg_score'] . ",";
                }
                ?>

            ]

        }]
    }
});

/* ======================================
   TOGGLE PANEL
====================================== */

function toggleAgentsPanel(){

    let panel =
        document.getElementById("agentsPanel");

    let icon =
        document.getElementById("toggleIcon");

    if(panel.style.display === "none"){

        panel.style.display = "block";

        icon.innerHTML = "-";

    }else{

        panel.style.display = "none";

        icon.innerHTML = "+";
    }
}

/* ======================================
   MISTAKES MODAL
====================================== */

function openMistakesModal(){

    document.getElementById("mistakesModal")
        .style.display = "flex";
}

function closeMistakesModal(){

    document.getElementById("mistakesModal")
        .style.display = "none";
}

/* ======================================
   ZERO MODAL
====================================== */

function openZeroModal(){

    document.getElementById("zeroModal")
        .style.display = "flex";
}

function closeZeroModal(){

    document.getElementById("zeroModal")
        .style.display = "none";
}

</script>

</body>
</html>