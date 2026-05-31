<?php

session_start();
include "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "agent") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["id"])) {
    die("Invalid Evaluation ID");
}

$id = intval($_GET["id"]);
$user_id = $_SESSION["user_id"];

/* =========================
   GET EVALUATION
========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM evaluations
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([$id, $user_id]);

$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    die("Evaluation not found");
}

/* =========================
   DETAILS
========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM evaluation_details
    WHERE evaluation_id = ?
");

$stmt->execute([$id]);

$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   QUESTIONS
========================= */

$questions = [

"Q1"=>"هل قام بالقاء تحية بداية المكالمة بطريقة صحيحة ؟",
"Q2"=>"هل تم مراجعة البيانات ؟",
"Q3"=>"هل تم معرفة راى العميل فى اخر طلب ؟",
"Q4"=>"هل قام المتلقى باقتراح العرض القائم ان وجد ؟",
"Q5"=>"هل قام المتلقى باقتراح اصناف جانيبه ؟",
"Q6"=>"هل تم اعادة الطلب على العميل ؟",
"Q7"=>"هل تم ابلاغ العميل باجمالى قيمة الطلب ؟",
"Q8"=>"هل تم ابلاغ العميل بزمن التوصيل ؟",
"Q9"=>"هل تم انهاء المكالمه بالطريقه الصحيحه وشكر العميل ؟",

"Q10"=>"هل كان المتلقى فى حالة استعداد لتلقى الطلب؟",
"Q11"=>"هل قام بتحية العميل ؟",
"Q12"=>"هل كان المتلقى واضح فى حديثه واسلوبه مهذب؟",
"Q13"=>"هل كان المتلقى معتدل فى نبرة الصوت ؟",
"Q14"=>"هل قام المتلقى باستخدام اسم العميل بطريقه صحيحه ؟",
"Q15"=>"هل قام المتلقى بفهم احتياجات العميل واظهار الرغبه فى المساعدة ؟",
"Q16"=>"هل قام المتلقى بتجنب الرتابه والملل فى المكالمة ؟",
"Q17"=>"هل تم استئذان العميل لوضعه على الـ Hold او تحويله مع شرح السبب ؟",
"Q18"=>"هل كان المتلقى يستمع للعميل بدقه ؟",
"Q19"=>"هل قام المتلقى بأخذ بيانات و ملاحظات العميل والطلب بالسرعة المطلوبة ؟",
"Q20"=>"هل متلقي الطلب علي دراية كاملة بالمنتجات ومكوناتها ؟",

];

/* =========================
   SCORE CALCULATION
========================= */

$max_score = 145;

$total_score = $evaluation["total_score"];

$percentage = round(($total_score / $max_score) * 100, 1);

?>

<!DOCTYPE html>
<html lang="ar">

<head>

<meta charset="UTF-8">
<title>عرض التقييم</title>

<style>

body{
    margin:0;
    background:#182235;
    color:#fff;
    direction:rtl;
    font-family:'Segoe UI';
}

.container{
    width:95%;
    max-width:1200px;
    margin:30px auto;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.title{
    font-size:28px;
    font-weight:bold;
}

.btn{
    background:#d4af37;
    color:#fff;
    text-decoration:none;
    padding:10px 18px;
    border-radius:12px;
    font-weight:bold;
    transition:.2s;
}

.btn:hover{
    opacity:.85;
}

.score-box{
    background:#111827;
    padding:30px;
    border-radius:20px;
    margin-bottom:25px;
    border:1px solid #374151;
}

.score-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}

.score-card{
    background:#1f2937;
    padding:25px;
    border-radius:18px;
    text-align:center;
    border:1px solid #374151;
}

.score-title{
    color:#9ca3af;
    margin-bottom:15px;
    font-size:15px;
}

.score{
    font-size:48px;
    font-weight:bold;
    color:#d4af37;
}

.table{
    width:100%;
    border-collapse:collapse;
    overflow:hidden;
    border-radius:18px;
}

.table th{
    background:#374151;
    padding:15px;
}

.table td{
    background:#1f2937;
    padding:15px;
    border-bottom:1px solid #374151;
}

.good{
    color:#22c55e;
    font-weight:bold;
    font-size:18px;
}

.bad{
    color:#ef4444;
    font-weight:bold;
    font-size:18px;
}

@media(max-width:768px){

.score-grid{
    grid-template-columns:1fr;
}

.table{
    display:block;
    overflow-x:auto;
}

}

</style>

</head>

<body>

<div class="container">

<div class="header">

<div class="title">
📋 تفاصيل التقييم
</div>

<a href="quality.php" class="btn">
⬅ رجوع
</a>

</div>

<!-- SCORE BOX -->

<div class="score-box">

<div class="score-grid">

<div class="score-card">

<div class="score-title">
🎯 الدرجة النهائية
</div>

<div class="score">
<?= $total_score ?> / <?= $max_score ?>
</div>

</div>

<div class="score-card">

<div class="score-title">
📊 النسبة المئوية
</div>

<div class="score">
<?= $percentage ?>%
</div>

</div>

</div>

</div>

<!-- TABLE -->

<table class="table">

<tr>
<th>السؤال</th>
<th>الدرجة</th>
<th>الملاحظة</th>
</tr>

<?php foreach($details as $d): ?>

<tr>

<td>
<?= $questions[$d["question"]] ?>
</td>

<td>

<?php if($d["score"] >= 5): ?>

<span class="good">
<?= $d["score"] ?>
</span>

<?php else: ?>

<span class="bad">
<?= $d["score"] ?>
</span>

<?php endif; ?>

</td>

<td>

<?= $d["comment"] ?: "-" ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

</body>
</html>