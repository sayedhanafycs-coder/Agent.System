<?php
include __DIR__ . "/config/db.php";

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

/* ================= TOTAL ORDERS ================= */
$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM order1
WHERE CAST(order_date AS DATE) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$total = $stmt->fetchColumn();

/* ================= ORDERS TYPES ================= */
$stmt = $conn->prepare("
SELECT ord_type, COUNT(*) as c
FROM order1
WHERE CAST(order_date AS DATE) BETWEEN ? AND ?
GROUP BY ord_type
");
$stmt->execute([$from, $to]);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach($types as $t){
    $data[$t['ord_type']] = $t['c'];
}

$orders = $data['orders'] ?? 0;
$menus  = $data['menus'] ?? 0;
$call   = $data['call center'] ?? 0;

/* ================= BRANCH ================= */
$stmt = $conn->prepare("
SELECT Store_desc, COUNT(*) as c
FROM order1
WHERE CAST(order_date AS DATE) BETWEEN ? AND ?
GROUP BY Store_desc
ORDER BY c DESC
");
$stmt->execute([$from, $to]);
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Advanced Analysis</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    margin:0;
    font-family:Arial;
    background: linear-gradient(135deg,#0f172a,#1e293b);
    color:white;
}

.container{padding:20px}

/* FILTER */
.filter{
    background:rgba(255,255,255,0.08);
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}

/* CARDS */
.cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
}

.card{
    background:rgba(255,255,255,0.08);
    padding:20px;
    border-radius:15px;
    backdrop-filter: blur(10px);
    box-shadow:0 10px 25px rgba(0,0,0,0.3);
    transition:0.3s;
}

.card:hover{
    transform:translateY(-5px);
}

.card h2{margin:0;font-size:14px;opacity:0.7}
.card h1{margin:10px 0;font-size:28px}

/* TABLE */
table{
    width:100%;
    margin-top:20px;
    border-collapse:collapse;
    background:rgba(255,255,255,0.05);
}

th,td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

th{
    background:rgba(255,255,255,0.1);
}
</style>
</head>

<body>

<div class="container">

<!-- FILTER -->
<div class="filter">
<form method="GET">
From: <input type="date" name="from" value="<?= $from ?>">
To: <input type="date" name="to" value="<?= $to ?>">
<button>Analyze</button>
</form>
</div>

<!-- CARDS -->
<div class="cards">

<div class="card">
<h2>Total Orders</h2>
<h1><?= $total ?></h1>
</div>

<div class="card">
<h2>Orders</h2>
<h1><?= $orders ?></h1>
</div>

<div class="card">
<h2>Menus</h2>
<h1><?= $menus ?></h1>
</div>

<div class="card">
<h2>Call Center</h2>
<h1><?= $call ?></h1>
</div>

</div>

<!-- CHART -->
<div class="card" style="margin-top:20px;">
<canvas id="chart"></canvas>
</div>

<script>
new Chart(document.getElementById('chart'),{
    type:'doughnut',
    data:{
        labels:['Orders','Menus','Call Center'],
        datasets:[{
            data:[<?= $orders ?>,<?= $menus ?>,<?= $call ?>],
            backgroundColor:['#3b82f6','#22c55e','#f97316']
        }]
    }
});
</script>

<!-- BRANCH TABLE -->
<h2 style="margin-top:30px;">Branches Performance</h2>

<table>
<tr>
<th>Branch</th>
<th>Orders</th>
</tr>

<?php foreach($branches as $b){ ?>
<tr>
<td><?= $b['Store_desc'] ?></td>
<td><?= $b['c'] ?></td>
</tr>
<?php } ?>

</table>

</div>

</body>
</html>