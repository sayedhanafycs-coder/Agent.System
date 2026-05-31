<?php
session_start();
include "../config/db.php";

/* ================= FILTERS ================= */

$selected_agent = $_GET['agent'] ?? '';
$from_date = $_GET['from'] ?? '';
$to_date   = $_GET['to'] ?? '';

/* ================= EXPORT CSV ================= */

if(isset($_GET['export'])){

    $stmt = $conn->prepare("SELECT * FROM performance_issues");
    $stmt->execute();
    $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="performance_report.csv"');

    $output = fopen("php://output", "w");

    fputcsv($output, ['Agent','Date','Action','Problem','Minutes']);

    foreach($exportData as $row){
        fputcsv($output, [
            $row['agent_name'],
            $row['issue_date'],
            $row['action_type'],
            $row['problem'],
            $row['minutes']
        ]);
    }

    fclose($output);
    exit;
}

/* ================= AGENTS ================= */

$agents = $conn->query("
    SELECT DISTINCT agent_name
    FROM performance_issues
    WHERE agent_name IS NOT NULL
    ORDER BY agent_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ================= QUERY ================= */

$params = [];
$whereParts = [];

if($selected_agent !== '' && $selected_agent !== 'all'){
    $whereParts[] = "agent_name = ?";
    $params[] = $selected_agent;
}

if($from_date !== ''){
    $whereParts[] = "issue_date >= ?";
    $params[] = $from_date;
}

if($to_date !== ''){
    $whereParts[] = "issue_date <= ?";
    $params[] = $to_date;
}

$where = count($whereParts) ? "WHERE ".implode(" AND ", $whereParts) : "";

$stmt = $conn->prepare("
    SELECT *
    FROM performance_issues
    $where
    ORDER BY issue_date ASC
");

$stmt->execute($params);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= CALCULATIONS ================= */

$total_minutes = 0;
$logout = 0;
$coaching = 0;
$tardy = 0;

$trend = [];
$agent_stats = [];

foreach($issues as $row){

    $minutes = (int)($row['minutes'] ?? 0);
    $total_minutes += $minutes;

    $agent = $row['agent_name'] ?? 'Unknown';
    $date  = $row['issue_date'] ?? '';

    $action = strtolower(trim($row['action_type'] ?? ''));
    $problem = strtolower(trim($row['problem'] ?? ''));

    /* ===== ACTION TYPE ===== */
    if($action === 'logout') $logout += $minutes;
    if($action === 'coaching') $coaching += $minutes;
    if($action === 'tardy' || $action === 'tardy login') $tardy += $minutes;

    /* ===== TEXT MATCH ===== */
    if(str_contains($problem,'logout')) $logout += $minutes;
    if(str_contains($problem,'coaching')) $coaching += $minutes;
    if(str_contains($problem,'tardy')) $tardy += $minutes;

    /* ===== TREND ===== */
    if(!isset($trend[$date])) $trend[$date] = 0;
    $trend[$date] += $minutes;

    /* ===== RANKING ===== */
    if(!isset($agent_stats[$agent])){
        $agent_stats[$agent] = 0;
    }
    $agent_stats[$agent] += $minutes;
}

ksort($trend);
arsort($agent_stats);
$ranking = array_slice($agent_stats, 0, 10, true);

/* ================= AI INSIGHT ================= */

$types = [
    'Logout' => $logout,
    'Coaching' => $coaching,
    'Tardy' => $tardy
];

$max_type = '';
$max_val = 0;

foreach($types as $k => $v){
    if($v > $max_val){
        $max_val = $v;
        $max_type = $k;
    }
}

$ai_summary = "Most issue this period is: " . $max_type;

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
    background:#0f172a;
    color:#e5e7eb;
    font-family:Cairo;
    padding:20px;
}

/* FILTER */
select,input{
    padding:10px;
    border-radius:8px;
    margin:5px;
    background:#111827;
    color:#d4af37;
    border:1px solid #333;
}

button,a{
    padding:10px 14px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    margin:5px;
    text-decoration:none;
}

/* GOLD BUTTON */
button{
    background:#d4af37;
    color:#111827;
    font-weight:bold;
}

a{
    background:#374151;
    color:#d4af37;
}

/* CARDS */
.card-container{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
    margin-top:20px;
}

.card{
    background:#111827;
    padding:20px;
    border-radius:15px;
    border:1px solid rgba(212,175,55,.2);
}

.card h2{
    color:#d4af37;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

th,td{
    border:1px solid #2d3748;
    padding:10px;
    text-align:center;
}

th{
    background:#1f2937;
    color:#d4af37;
}

</style>
</head>

<body>

<!-- FILTER -->
<form method="GET">

<select name="agent">
    <option value="all">All Agents</option>
    <?php foreach($agents as $a): ?>
        <option value="<?= $a['agent_name'] ?>" <?= $selected_agent==$a['agent_name']?'selected':'' ?>>
            <?= $a['agent_name'] ?>
        </option>
    <?php endforeach; ?>
</select>

<input type="date" name="from" value="<?= $from_date ?>">
<input type="date" name="to" value="<?= $to_date ?>">

<button type="submit">Filter</button>
<a href="?export=1">Export CSV</a>

</form>

<!-- CARDS -->
<div class="card-container">

<div class="card"><h2>Total Minutes</h2><h1><?= $total_minutes ?></h1></div>
<div class="card"><h2>Logout</h2><h1><?= $logout ?></h1></div>
<div class="card"><h2>Coaching</h2><h1><?= $coaching ?></h1></div>
<div class="card"><h2>Tardy</h2><h1><?= $tardy ?></h1></div>

<div class="card">
    <h2>AI Insight</h2>
    <h3><?= $ai_summary ?></h3>
</div>

</div>

<!-- TREND -->
<h2 style="color:#d4af37;margin-top:30px;">Trend Over Time</h2>
<canvas id="trend" height="80"></canvas>

<!-- RANKING -->
<h2 style="color:#d4af37;margin-top:30px;">Top 10 Agents</h2>

<table>
<tr>
<th>#</th>
<th>Agent</th>
<th>Minutes</th>
</tr>

<?php $i=1; foreach($ranking as $name=>$val): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $name ?></td>
<td><?= $val ?></td>
</tr>
<?php endforeach; ?>

</table>

<!-- DETAILS -->
<h2 style="color:#d4af37;margin-top:30px;">Details</h2>

<table>
<tr>
<th>Agent</th>
<th>Date</th>
<th>Action</th>
<th>Problem</th>
<th>Minutes</th>
</tr>

<?php foreach($issues as $r): ?>
<tr>
<td><?= $r['agent_name'] ?></td>
<td><?= $r['issue_date'] ?></td>
<td><?= $r['action_type'] ?></td>
<td><?= $r['problem'] ?></td>
<td><?= $r['minutes'] ?></td>
</tr>
<?php endforeach; ?>

</table>

<!-- TREND CHART -->
<script>
new Chart(document.getElementById('trend'), {
    type:'line',
    data:{
        labels:<?= json_encode(array_keys($trend)) ?>,
        datasets:[{
            data:<?= json_encode(array_values($trend)) ?>,
            tension:0.3
        }]
    },
    options:{
        plugins:{legend:{display:false}},
        scales:{
            y:{beginAtZero:true}
        }
    }
});
</script>

</body>
</html>