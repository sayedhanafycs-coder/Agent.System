<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "quality") {
    header("Location: ../login.php");
    exit();
}

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
   SAVE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id   = $_POST["user_id"];
    $scores    = $_POST["scores"];
    $comments  = $_POST["comments"] ?? [];

    $call_date = $_POST["call_date"];
    $phone     = $_POST["phone"];
    $order_id  = $_POST["order_id"];
    $call_type = $_POST["call_type"];

    $total = array_sum($scores);

    $stmt = $conn->prepare("
        INSERT INTO evaluations
        (user_id, evaluator_id, total_score, call_date, phone, order_id, call_type)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $_SESSION["user_id"],
        $total,
        $call_date,
        $phone,
        $order_id,
        $call_type
    ]);

    $evaluation_id = $conn->lastInsertId();

    foreach ($scores as $code => $score) {

        $comment = $comments[$code] ?? null;

        if ($score == 10) {
            $comment = null;
        }

        if ($comment !== null && trim($comment) == "") {
            $comment = null;
        }

        $stmt = $conn->prepare("
            INSERT INTO evaluation_details
            (evaluation_id, question, score, comment)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $evaluation_id,
            $code,
            $score,
            $comment
        ]);
    }

    header("Location: view_evaluation.php?id=".$evaluation_id);
    exit();
}

/* =========================
   AGENTS
========================= */

$users = $conn->query("
    SELECT id, name
    FROM [quality_system].[dbo].[users]
    WHERE role = 'agent'
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   STATS
========================= */

$stats = [];

$q = $conn->query("
    SELECT 
        user_id,
        COUNT(*) as calls,
        AVG(total_score) as avg_score
    FROM evaluations
    GROUP BY user_id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($q as $row) {

    $stats[$row["user_id"]] = [
        "calls" => $row["calls"],
        "avg"   => round($row["avg_score"],1) . "%"
    ];
}

/* =========================
   QUESTIONS WITH 10
========================= */

$with_10 = ["Q1","Q2","Q4","Q5","Q6","Q7","Q8","Q13","Q16","Q20"];

?>

<!DOCTYPE html>
<html lang="ar">

<head>

<meta charset="UTF-8">
<title>Evaluate Call</title>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    direction:rtl;
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(180deg,#243247,#1e293b);
    color:#e2e8f0;
}

.container{
    width:95%;
    max-width:1450px;
    margin:25px auto;
}

.page-title{
    font-size:28px;
    margin-bottom:20px;
    color:#fff;
    font-weight:700;
}

.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.sections-grid{
    align-items:start;
    margin-top:18px;
}

.sections-grid .section{
    height:fit-content;
}

.section{
    background:#111827;
    border:1px solid #2d3748;
    border-radius:20px;
    padding:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.35);
}

.sections-grid .section{
    border:1px solid #7c5c12;
    box-shadow:
    0 0 0 1px rgba(212,175,55,.15),
    0 10px 25px rgba(0,0,0,.35);
}

.section h3{
    margin-top:0;
    margin-bottom:18px;
    font-size:18px;
    color:#f8fafc;
    font-weight:700;
}

input,
select,
textarea{
    width:100%;
    background:#0f172a;
    border:1px solid #334155;
    color:#fff;
    border-radius:12px;
    padding:12px;
    margin-bottom:12px;
    outline:none;
    font-size:14px;
}

input:focus,
select:focus,
textarea:focus{
    border-color:#d4af37;
}

textarea{
    resize:none;
    height:70px;
}

.mini-progress{
    margin-top:18px;
    background:#0f172a;
    padding:14px;
    border-radius:16px;
    border:1px solid #1e293b;
}

.progress-info{
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
    font-size:13px;
    font-weight:600;
}

.progress-bar{
    height:10px;
    background:#1e293b;
    border-radius:30px;
    overflow:hidden;
}

.progress-fill{
    height:100%;
    width:0%;
    background:#d4af37;
    transition:.3s ease;
}

.agent-stats{
    margin-top:15px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.stat-box{
    background:#0f172a;
    border:1px solid #1e293b;
    border-radius:14px;
    padding:12px;
    text-align:center;
}

.stat-title{
    font-size:12px;
    color:#94a3b8;
    margin-bottom:6px;
}

.stat-value{
    font-size:20px;
    font-weight:bold;
    color:#f8fafc;
}

.question{
    background:#0f172a;
    border:1px solid #1e293b;
    border-radius:14px;
    padding:15px;
    margin-bottom:12px;
    transition:.2s;
}

.question:hover{
    border-color:#d4af37;
}

.question-title-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:12px;
}

.question-title{
    line-height:1.9;
    font-size:15px;
    font-weight:600;
    color:#f1f5f9;
}

.comment-toggle{
    width:38px;
    height:38px;
    border:none;
    border-radius:12px;
    background:#1e293b;
    color:#fff;
    cursor:pointer;
    font-size:16px;
    transition:.2s;
    flex-shrink:0;
}

.comment-toggle:hover{
    background:#d4af37;
    color:#000;
    transform:scale(1.05);
}

.radio-group{
    display:flex;
    gap:8px;
    align-items:center;
}

.radio-group input{
    display:none;
}

.radio{
    width:38px;
    height:38px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    cursor:pointer;
    font-size:13px;
    font-weight:700;
    transition:.2s;
    opacity:.45;
    border:1px solid transparent;
}

.red{
    background:#7f1d1d;
    color:#fecaca;
}

.yellow{
    background:#78350f;
    color:#fde68a;
}

.green{
    background:#14532d;
    color:#bbf7d0;
}

.radio-group input:checked + .radio{
    opacity:1;
    transform:scale(1.08);
    border-color:#fff;
}

.comment-box{
    display:none;
    margin-top:12px;
}

.save-btn{
    width:420px;
    border:none;
    background:linear-gradient(135deg,#8b6914,#d4af37);
    color:#fff;
    padding:18px;
    border-radius:18px;
    font-size:20px;
    font-weight:800;
    margin:30px auto 0;
    cursor:pointer;
    transition:.3s;
    display:block;
}

.save-btn:hover{
    transform:translateY(-2px);
}

.grade{
    font-weight:bold;
}

@media(max-width:992px){

.grid,
.sections-grid{
    grid-template-columns:1fr;
}

.save-btn{
    width:100%;
}

.agent-stats{
    grid-template-columns:1fr;
}

}

.home-btn{
    display:inline-block;
    margin-bottom:20px;
    background:#1e293b;
    color:#fff;
    padding:12px 18px;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
    transition:.2s;
    border:1px solid #334155;
}

.home-btn:hover{
    background:#d4af37;
    color:#000;
    transform:translateY(-2px);
}

</style>

</head>

<body>

<div class="container">

<h1 class="page-title">📞 تقييم مكالمة</h1>

<a href="dashboard.php" class="home-btn">
🏠 الرجوع للداش بورد
</a>

<form method="POST">

<div class="grid">

<div class="section">

<h3>👤 بيانات الموظف</h3>

<select name="user_id" id="agentSelect" required>

<option value="" selected disabled>
-- اختر الايجنت --
</option>

<?php foreach($users as $u): ?>

<option value="<?= $u['id'] ?>">
<?= $u['name'] ?>
</option>

<?php endforeach; ?>

</select>

<div class="mini-progress">

<div class="progress-info">
<div>📊 <span id="percent">0%</span></div>
<div>Score: <span id="score">0</span></div>
<div class="grade" id="grade">❌ ضعيف</div>
</div>

<div class="progress-bar">
<div class="progress-fill" id="progressFill"></div>
</div>

</div>

<div class="agent-stats">

<div class="stat-box">
<div class="stat-title">📞 المكالمات المسموعة</div>
<div class="stat-value" id="callsCount">0</div>
</div>

<div class="stat-box">
<div class="stat-title">⭐ Average Score</div>
<div class="stat-value" id="avgScore">0%</div>
</div>

</div>

</div>

<div class="section">

<h3>📋 تفاصيل المكالمة</h3>

<input type="date" name="call_date" required>

<input type="text" name="phone" placeholder="رقم الموبايل" required>

<select name="order_id">

<option value="">اختر الاسبوع</option>
<option value="الاسبوع الاول">الاسبوع الاول</option>
<option value="الاسبوع الثاني">الاسبوع الثاني</option>
<option value="الاسبوع الثالث">الاسبوع الثالث</option>
<option value="الاسبوع الرابع">الاسبوع الرابع</option>

</select>

<select name="call_type">
<option>Order</option>
<option>Inquiry</option>
<option>Complaint</option>
</select>

</div>

</div>

<div class="grid sections-grid">

<div class="section">

<h3>🛒 القسم الأول / خطوات أخذ الطلب</h3>

<?php foreach(array_slice($questions,0,9) as $code=>$text): ?>

<div class="question">

<div class="question-title-row">

<div class="question-title">
<?= $text ?>
</div>

<button
type="button"
class="comment-toggle"
onclick="toggleCommentBox(this)"
>
💬
</button>

</div>

<div class="radio-group">

<?php if(in_array($code,$with_10)): ?>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="0" checked>
<span class="radio red">0</span>
</label>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="5">
<span class="radio yellow">5</span>
</label>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="10">
<span class="radio green">10</span>
</label>

<?php else: ?>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="0" checked>
<span class="radio red">0</span>
</label>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="5">
<span class="radio green">5</span>
</label>

<?php endif; ?>

</div>

<div class="comment-box">
<textarea
name="comments[<?= $code ?>]"
placeholder="اكتب الملاحظة هنا..."
></textarea>
</div>

</div>

<?php endforeach; ?>

</div>

<div class="section">

<h3>📞 القسم الثاني / آداب الهاتف ومهارات الاتصال</h3>

<?php foreach(array_slice($questions,9,11) as $code=>$text): ?>

<div class="question">

<div class="question-title-row">

<div class="question-title">
<?= $text ?>
</div>

<button
type="button"
class="comment-toggle"
onclick="toggleCommentBox(this)"
>
💬
</button>

</div>

<div class="radio-group">

<?php if(in_array($code,$with_10)): ?>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="0" checked>
<span class="radio red">0</span>
</label>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="5">
<span class="radio yellow">5</span>
</label>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="10">
<span class="radio green">10</span>
</label>

<?php else: ?>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="0" checked>
<span class="radio red">0</span>
</label>

<label>
<input type="radio" name="scores[<?= $code ?>]" value="5">
<span class="radio green">5</span>
</label>

<?php endif; ?>

</div>

<div class="comment-box">
<textarea
name="comments[<?= $code ?>]"
placeholder="اكتب الملاحظة هنا..."
></textarea>
</div>

</div>

<?php endforeach; ?>

</div>

</div>

<button type="submit" class="save-btn">
💾 حفظ التقييم
</button>

</form>

</div>

<script>

const maxScore = 150;

function calc(){

    let total = 0;

    document.querySelectorAll("input[type='radio']:checked").forEach(el=>{

        total += parseInt(el.value) || 0;

    });

    let percent = ((total / maxScore) * 100).toFixed(1);

    document.getElementById("score").innerText = total;
    document.getElementById("percent").innerText = percent + "%";

    let bar = document.getElementById("progressFill");

    bar.style.width = percent + "%";

    let grade = "";
    let color = "";

    if(percent >= 97.6){

        grade = "🔥 فاق التوقعات";
        color = "#22c55e";

    }else if(percent >= 95){

        grade = "⭐ ممتاز";
        color = "#3b82f6";

    }else if(percent >= 90){

        grade = "⚠️ جيد";
        color = "#f59e0b";

    }else{

        grade = "❌ ضعيف";
        color = "#ef4444";
    }

    bar.style.background = color;

    document.getElementById("grade").innerText = grade;
}

calc();

document.querySelectorAll("input[type='radio']").forEach(el=>{

    el.addEventListener("change", function(){

        calc();

    });

});

function toggleCommentBox(button){

    const question = button.closest(".question");

    const commentBox = question.querySelector(".comment-box");

    if(commentBox.style.display === "block"){

        commentBox.style.display = "none";

    }else{

        commentBox.style.display = "block";
    }
}

/* =========================
   AGENT STATS
========================= */

const stats = <?= json_encode($stats, JSON_UNESCAPED_UNICODE) ?>;

const select = document.getElementById("agentSelect");

function updateAgentStats(){

    let id = select.value;

    if(!id){

        document.getElementById("callsCount").innerText = 0;
        document.getElementById("avgScore").innerText = "0%";
        return;
    }

    if(stats[id]){

        document.getElementById("callsCount").innerText = stats[id].calls;
        document.getElementById("avgScore").innerText = stats[id].avg;

    }else{

        document.getElementById("callsCount").innerText = 0;
        document.getElementById("avgScore").innerText = "0%";
    }
}

select.addEventListener("change", updateAgentStats);

</script>

</body>
</html>