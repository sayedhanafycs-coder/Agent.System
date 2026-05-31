<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "quality") {
    header("Location: ../login.php");
    exit();
}

$evaluation_id = $_GET["id"] ?? 0;

/* =======================
   MAIN EVALUATION
======================= */
$stmt = $conn->prepare("
SELECT e.*, u.name AS agent_name
FROM evaluations e
JOIN users u ON e.user_id = u.id
WHERE e.id = ?
");
$stmt->execute([$evaluation_id]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    die("Evaluation not found");
}

/* =======================
   DETAILS
======================= */
$stmt = $conn->prepare("
SELECT question, score
FROM evaluation_details
WHERE evaluation_id = ?
");
$stmt->execute([$evaluation_id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scores = array_column($details, 'score', 'question');


/* =======================
   QUESTIONS MAP (ARABIC)
======================= */
$questions = [

"Q1" => "هل قام بالقاء تحية بداية المكالمة بطريقة صحيحة ؟",
"Q2" => "هل تم مراجعة البيانات ؟",
"Q3" => "هل تم معرفة رأي العميل فى اخر طلب ؟",
"Q4" => "هل قام المتلقى باقتراح العرض القائم ان وجد ؟",
"Q5" => "هل قام المتلقى باقتراح اصناف جانبية ؟",
"Q6" => "هل تم اعادة الطلب على العميل ؟",
"Q7" => "هل تم ابلاغ العميل باجمالى قيمة الطلب ؟",
"Q8" => "هل تم ابلاغ العميل بزمن التوصيل ؟",
"Q9" => "هل تم انهاء المكالمة وشكر العميل ؟",
"Q10" => "هل لديه معرفة كاملة بالمنتجات ؟",

"Q11" => "هل كان المتلقى مستعد لتلقي الطلب ؟",
"Q12" => "هل قام بتحية العميل ؟",
"Q13" => "هل كان واضح ومهذب ؟",
"Q14" => "هل استخدم اسم العميل بشكل صحيح ؟",
"Q15" => "هل فهم احتياجات العميل ؟",
"Q16" => "هل استأذن للـ Hold أو التحويل ؟",
"Q17" => "هل استمع بدقة ؟",
"Q18" => "هل سجل البيانات بسرعة ؟",
"Q19" => "هل التزم بالـ Script ؟",

"Q20" => "هل كان هناك تفاعل أثناء المكالمة ؟",
"Q21" => "هل نبرة الصوت معتدلة ؟",
"Q22" => "هل تم تجنب الرتابة والملل ؟"
];

/* =======================
   SECTION SCORES
======================= */
$section1 = 0;
for ($i=1;$i<=10;$i++) $section1 += $scores["Q$i"] ?? 0;

$section2 = 0;
for ($i=11;$i<=19;$i++) $section2 += $scores["Q$i"] ?? 0;

$section3 = 0;
for ($i=20;$i<=22;$i++) $section3 += $scores["Q$i"] ?? 0;

/* =======================
   PERCENTAGE (145)
======================= */
$max_score = 145;
$percentage = ($evaluation["total_score"] / $max_score) * 100;
$percentage = round($percentage, 2);

/* =======================
   GRADE
======================= */
if ($percentage < 90) {
    $grade = "❌ ضعيف";
} elseif ($percentage < 95) {
    $grade = "⚠️ جيد";
} elseif ($percentage < 97.5) {
    $grade = "⭐ ممتاز";
} else {
    $grade = "🔥 فاق التوقعات";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Review Evaluation</title>

<style>
body{
    font-family: Arial;
    direction: rtl;
    background:#f4f6f9;
    padding:20px;
}

.container{
    background:#fff;
    padding:20px;
    border-radius:10px;
    width:900px;
    margin:auto;
}

h2{
    background:#007bff;
    color:#fff;
    padding:10px;
    border-radius:5px;
}

.box{
    background:#f8f9fa;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
}

.section{
    margin-top:20px;
    padding:15px;
    background:#f1f1f1;
    border-radius:10px;
}

.item{
    display:flex;
    justify-content:space-between;
    padding:6px 0;
    border-bottom:1px solid #ddd;
}

.score{
    font-weight:bold;
}

.total{
    font-size:20px;
    color:green;
    font-weight:bold;
}

.grade{
    font-size:18px;
    font-weight:bold;
    margin-top:5px;
}

.btn{
    display:inline-block;
    padding:10px 15px;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:10px;
}

.new{
    background:#28a745;
}

.pdf{
    background:#dc3545;
}
</style>

</head>

<body>

<div class="container">

<h2>📊 Review Evaluation</h2>

<!-- BUTTONS -->
<a href="evaluate.php" class="btn new">➕ تقييم جديد</a>
<a href="export_pdf.php?id=<?= $evaluation_id ?>" class="btn pdf">📄 Export PDF</a>

<!-- INFO -->
<div class="box">
👤 Agent: <?= $evaluation["agent_name"] ?><br>
📅 Date: <?= $evaluation["call_date"] ?><br>
📞 Phone: <?= $evaluation["phone"] ?><br>
🧾 Order: <?= $evaluation["order_id"] ?><br>
📌 Type: <?= $evaluation["call_type"] ?><br>
</div>

<!-- SCORE -->
<div class="box">
🧮 Total Score: <?= $evaluation["total_score"] ?> / 145<br>
📊 Percentage: <?= $percentage ?>%<br>
<div class="grade">🏆 Grade: <?= $grade ?></div>
</div>

<!-- SECTION 1 -->
<div class="section">
<h3>📌 القسم الأول - خطوات أخذ الطلب</h3>

<?php for($i=1;$i<=10;$i++): 
$q = "Q".$i;
?>
<div class="item">
    <span><?= $questions[$q] ?? $q ?></span>
    <span class="score"><?= $scores[$q] ?? 0 ?></span>
</div>
<?php endfor; ?>

<div class="grade">Section Score: <?= $section1 ?> / 85</div>
</div>

<!-- SECTION 2 -->
<div class="section">
<h3>📌 القسم الثاني - آداب الهاتف</h3>

<?php for($i=11;$i<=19;$i++): 
$q = "Q".$i;
?>
<div class="item">
    <span><?= $questions[$q] ?? $q ?></span>
    <span class="score"><?= $scores[$q] ?? 0 ?></span>
</div>
<?php endfor; ?>

<div class="grade">Section Score: <?= $section2 ?> / 45</div>
</div>

<!-- SECTION 3 -->
<div class="section">
<h3>📌 القسم الثالث - نبرة الصوت</h3>

<?php for($i=20;$i<=22;$i++): 
$q = "Q".$i;
?>
<div class="item">
    <span><?= $questions[$q] ?? $q ?></span>
    <span class="score"><?= $scores[$q] ?? 0 ?></span>
</div>
<?php endfor; ?>

<div class="grade">Section Score: <?= $section3 ?> / 15</div>
</div>

</div>

</body>
</html>