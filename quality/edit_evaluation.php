<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "quality") {
    header("Location: ../login.php");
    exit();
}

/* =========================
   DELETE EVALUATION
========================= */

if(isset($_GET["delete"])){

    $delete_id = intval($_GET["delete"]);

    $stmt = $conn->prepare("
        DELETE FROM evaluation_details
        WHERE evaluation_id=?
    ");

    $stmt->execute([$delete_id]);

    $stmt = $conn->prepare("
        DELETE FROM evaluations
        WHERE id=?
    ");

    $stmt->execute([$delete_id]);

    header("Location: edit_evaluation.php?deleted=1");
    exit();
}

/* =========================
   GET AGENTS
========================= */

$agents = $conn->query("
    SELECT id,name
    FROM users
    WHERE role='agent'
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SEARCH
========================= */

$search_phone = $_GET["phone"] ?? "";
$search_agent = $_GET["agent"] ?? "";

$searched = isset($_GET["search"]);

$evaluations = [];

if($searched){

    $where = [];
    $params = [];

    if($search_phone != ""){
        $where[] = "e.phone LIKE ?";
        $params[] = "%$search_phone%";
    }

    if($search_agent != ""){
        $where[] = "u.id = ?";
        $params[] = $search_agent;
    }

    $where_sql = "";

    if(count($where) > 0){
        $where_sql = "WHERE " . implode(" AND ",$where);
    }

    $sql = "
    SELECT TOP 50
        e.*,
        u.name AS agent_name
    FROM evaluations e
    LEFT JOIN users u
    ON e.user_id = u.id
    $where_sql
    ORDER BY e.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   LOAD EVALUATION
========================= */

$evaluation_id = $_GET["edit"] ?? 0;

$evaluation = null;
$details = [];

if($evaluation_id){

    $stmt = $conn->prepare("
        SELECT e.*,u.name AS agent_name
        FROM evaluations e
        LEFT JOIN users u
        ON e.user_id = u.id
        WHERE e.id=?
    ");

    $stmt->execute([$evaluation_id]);

    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $conn->prepare("
        SELECT *
        FROM evaluation_details
        WHERE evaluation_id=?
        ORDER BY id ASC
    ");

    $stmt2->execute([$evaluation_id]);

    $details = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   SAVE
========================= */

if(isset($_POST["save"])){

    $evaluation_id = $_POST["evaluation_id"];

    $scores = $_POST["score"];
    $comments = $_POST["comment"];
    $detail_ids = $_POST["detail_id"];

    $main_comment = trim($_POST["main_comment"]);

    $total = 0;

    for($i=0; $i<count($detail_ids); $i++){

        $detail_id = $detail_ids[$i];
        $score = intval($scores[$i]);

        $comment = trim($comments[$i] ?? '');

        $total += $score;

        $stmt = $conn->prepare("
            UPDATE evaluation_details
            SET
                score=?,
                comment=?
            WHERE id=?
        ");

        $stmt->execute([
            $score,
            $comment,
            $detail_id
        ]);
    }

    $stmt = $conn->prepare("
        UPDATE evaluations
        SET
            total_score=?,
            comment=?
        WHERE id=?
    ");

    $stmt->execute([
        $total,
        $main_comment,
        $evaluation_id
    ]);

    header("Location: edit_evaluation.php?edit=".$evaluation_id."&saved=1&search=1&agent=".$search_agent."&phone=".$search_phone);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Evaluation</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Cairo',sans-serif;
}

body{
    background:#071120;
    color:#fff;
    overflow:hidden;
    font-size:13px;
}

/* ================= CONTAINER ================= */

.container{
    display:grid;
    grid-template-columns:350px 1fr;
    height:100vh;
}

/* ================= LEFT SIDE ================= */

.left-side{
    background:linear-gradient(180deg,#081120,#09162a);
    border-right:1px solid rgba(255,255,255,.05);
    padding:18px;
    overflow:auto;
}

.page-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
}

.page-title-left{
    display:flex;
    align-items:center;
    gap:10px;
    color:#f4c430;
    font-size:18px;
    font-weight:900;
}

.page-title i{
    font-size:22px;
}

/* ================= BACK BTN ================= */

.back-btn{
    background:#101d35;
    color:#fff;
    text-decoration:none;
    padding:10px 14px;
    border-radius:12px;
    font-size:12px;
    font-weight:700;
    transition:.3s;
}

.back-btn:hover{
    background:#f4c430;
    color:#000;
}

/* ================= SEARCH ================= */

.search-box{
    margin-bottom:20px;
}

.search-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:12px;
}

.search-grid input,
.search-grid select{
    width:100%;
    background:#0d1b33;
    border:1px solid rgba(255,255,255,.08);
    height:46px;
    border-radius:12px;
    padding:0 14px;
    color:#fff;
    outline:none;
    font-size:13px;
}

.search-btn{
    height:46px;
    border:none;
    border-radius:12px;
    background:#f4c430;
    color:#000;
    font-weight:900;
    cursor:pointer;
    font-size:14px;
    transition:.3s;
}

.search-btn:hover{
    transform:translateY(-2px);
}

/* ================= CALLS ================= */

.calls-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

.calls-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:18px;
    font-weight:900;
}

.total-calls{
    background:#101d35;
    padding:6px 12px;
    border-radius:30px;
    font-size:11px;
}

/* ================= CALL CARD ================= */

.call-card{
    background:linear-gradient(90deg,#0a1324,#08101f);
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    padding:14px;
    margin-bottom:12px;
    transition:.3s;
}

.call-card:hover{
    border-color:#f4c430;
}

.call-card.active{
    border:1px solid #f4c430;
    box-shadow:0 0 15px rgba(244,196,48,.12);
}

.agent-row{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:10px;
}

.avatar{
    width:45px;
    height:45px;
    border-radius:50%;
    background:linear-gradient(135deg,#f4c430,#c58b00);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    font-weight:900;
    color:#fff;
}

.agent-name{
    font-size:16px;
    font-weight:800;
}

.call-info{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:7px;
    color:#dbe5ff;
    font-size:12px;
}

.call-info i{
    width:15px;
    color:#ff4da6;
}

.call-actions{
    display:flex;
    gap:10px;
    margin-top:14px;
}

.edit-btn,
.delete-btn{
    flex:1;
    text-align:center;
    text-decoration:none;
    padding:10px;
    border-radius:10px;
    font-weight:800;
    font-size:12px;
}

.edit-btn{
    background:#f4c430;
    color:#000;
}

.delete-btn{
    background:#dc2626;
    color:#fff;
}

/* ================= RIGHT SIDE ================= */

.right-side{
    padding:18px;
    overflow:auto;
}

/* ================= INFO BOX ================= */

.info-box{
    background:linear-gradient(90deg,#081120,#07101d);
    border:1px solid rgba(255,255,255,.05);
    border-radius:20px;
    padding:18px;
    margin-bottom:18px;
}

.info-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:22px;
    font-weight:900;
    margin-bottom:18px;
}

.info-title i{
    color:#3ba3ff;
}

.call-grid{
    display:grid;
    grid-template-columns:1fr 1fr 1fr 1fr 140px;
    gap:14px;
    align-items:center;
}

.call-item{
    background:#0d1728;
    border:1px solid rgba(255,255,255,.05);
    border-radius:16px;
    padding:15px;
}

.call-item-label{
    color:#8da2c7;
    margin-bottom:8px;
    font-size:11px;
}

.call-item-value{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:15px;
    font-weight:700;
}

.call-item-value i{
    color:#c86dff;
}

.score-box{
    background:#0d1728;
    border:1px solid rgba(255,255,255,.05);
    border-radius:16px;
    text-align:center;
    padding:20px 10px;
}

.score-number{
    font-size:38px;
    font-weight:900;
    color:#f4c430;
    line-height:1;
}

.score-total{
    margin-top:8px;
    color:#fff;
    font-size:12px;
}

/* ================= MAIN COMMENT ================= */

.main-comment{
    margin-top:18px;
}

.main-comment label{
    display:block;
    margin-bottom:10px;
    font-weight:700;
    font-size:13px;
}

.main-comment textarea{
    width:100%;
    min-height:90px;
    background:#091424;
    border:1px solid rgba(255,255,255,.08);
    border-radius:16px;
    padding:15px;
    color:#fff;
    resize:none;
    outline:none;
    font-size:13px;
}

/* ================= QUESTIONS ================= */

.questions-box{
    background:linear-gradient(90deg,#081120,#07101d);
    border:1px solid rgba(255,255,255,.05);
    border-radius:20px;
    padding:18px;
}

.questions-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:18px;
}

.questions-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:22px;
    font-weight:900;
}

.questions-title i{
    color:#f4c430;
}

.legend{
    display:flex;
    gap:18px;
    font-size:12px;
}

.legend span{
    display:flex;
    align-items:center;
    gap:7px;
}

.dot{
    width:11px;
    height:11px;
    border-radius:50%;
}

.green{background:#42d66a;}
.yellow{background:#f4c430;}
.red{background:#ff4d4d;}

/* ================= QUESTION CARD ================= */

.question-card{
    background:#0b1628;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:16px;
    margin-bottom:14px;
}

.question-top{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:16px;
}

.q-number{
    min-width:38px;
    height:38px;
    border-radius:50%;
    border:2px solid #f4c430;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#f4c430;
    font-weight:900;
    font-size:12px;
}

.question-text{
    font-size:15px;
    font-weight:700;
}

.question-grid{
    display:grid;
    grid-template-columns:140px 1fr;
    gap:14px;
}

.question-grid label{
    display:block;
    margin-bottom:6px;
    font-size:12px;
    color:#c8d3ea;
}

.question-grid select,
.question-grid textarea{
    width:100%;
    background:#091424;
    border:1px solid rgba(255,255,255,.08);
    border-radius:12px;
    padding:12px;
    color:#fff;
    outline:none;
}

.question-grid select{
    height:48px;
    font-size:14px;
}

.question-grid textarea{
    min-height:80px;
    resize:none;
    font-size:13px;
}

/* ================= SAVE ================= */

.save-btn{
    width:100%;
    height:54px;
    border:none;
    border-radius:14px;
    background:#f4c430;
    color:#000;
    font-size:18px;
    font-weight:900;
    margin-top:18px;
    cursor:pointer;
}

/* ================= EMPTY ================= */

.empty{
    height:80vh;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#8ea0c0;
    font-size:18px;
    font-weight:700;
    text-align:center;
    padding:20px;
}

/* ================= ALERT ================= */

.alert{
    background:#16a34a;
    color:#fff;
    padding:14px;
    border-radius:14px;
    margin-bottom:16px;
    font-weight:700;
}

.deleted{
    background:#dc2626;
}

/* ================= MOBILE ================= */

@media(max-width:1200px){

    .container{
        grid-template-columns:1fr;
    }

    .call-grid{
        grid-template-columns:1fr;
    }

    .question-grid{
        grid-template-columns:1fr;
    }

}

</style>
</head>

<body>

<div class="container">

    <!-- LEFT SIDE -->

    <div class="left-side">

        <div class="page-title">

            <div class="page-title-left">
                <i class="fa fa-pen-to-square"></i>
                Edit Evaluation
            </div>

            <a href="dashboard.php" class="back-btn">
                <i class="fa fa-arrow-left"></i>
                Dashboard
            </a>

        </div>

        <?php if(isset($_GET["saved"])): ?>

            <div class="alert">
                ✅ Evaluation Updated Successfully
            </div>

        <?php endif; ?>

        <?php if(isset($_GET["deleted"])): ?>

            <div class="alert deleted">
                ❌ Evaluation Deleted Successfully
            </div>

        <?php endif; ?>

        <form class="search-box" method="GET">

            <div class="search-grid">

                <select name="agent">

                    <option value="">
                        All Agents
                    </option>

                    <?php foreach($agents as $agent): ?>

                        <option value="<?= $agent["id"] ?>"
                        <?= ($search_agent == $agent["id"]) ? "selected" : "" ?>>

                            <?= $agent["name"] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <input type="text"
                       name="phone"
                       placeholder="Search by phone..."
                       value="<?= htmlspecialchars($search_phone) ?>">

                <button class="search-btn" name="search" value="1">
                    <i class="fa fa-magnifying-glass"></i>
                    Search
                </button>

            </div>

        </form>

        <div class="calls-header">

            <div class="calls-title">
                <i class="fa fa-phone"></i>
                Calls
            </div>

            <div class="total-calls">
                <?= count($evaluations) ?>
            </div>

        </div>

        <?php if($searched): ?>

            <?php foreach($evaluations as $call): ?>

                <div class="call-card <?= ($evaluation_id == $call["id"]) ? "active" : "" ?>">

                    <div class="agent-row">

                        <div class="avatar">
                            <?= strtoupper(substr($call["agent_name"],0,1)) ?>
                        </div>

                        <div class="agent-name">
                            <?= $call["agent_name"] ?>
                        </div>

                    </div>

                    <div class="call-info">
                        <i class="fa fa-phone"></i>
                        <?= $call["phone"] ?>
                    </div>

                    <div class="call-info">
                        <i class="fa fa-calendar"></i>
                        <?= $call["call_date"] ?>
                    </div>

                    <div class="call-info">
                        <i class="fa fa-headset"></i>
                        <?= $call["call_type"] ?>
                    </div>

                    <div class="call-actions">

                        <a class="edit-btn"
                           href="?search=1&agent=<?= $search_agent ?>&phone=<?= $search_phone ?>&edit=<?= $call["id"] ?>">

                            Edit

                        </a>

                        <a class="delete-btn"
                           onclick="return confirm('Delete this evaluation?')"
                           href="?delete=<?= $call["id"] ?>">

                            Delete

                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <!-- RIGHT SIDE -->

    <div class="right-side">

        <?php if($evaluation): ?>

            <form method="POST">

                <input type="hidden"
                       name="evaluation_id"
                       value="<?= $evaluation["id"] ?>">

                <!-- INFO -->

                <div class="info-box">

                    <div class="info-title">
                        <i class="fa fa-circle-info"></i>
                        Call Information
                    </div>

                    <div class="call-grid">

                        <div class="call-item">

                            <div class="call-item-label">
                                Agent
                            </div>

                            <div class="call-item-value">
                                <i class="fa fa-user"></i>
                                <?= $evaluation["agent_name"] ?>
                            </div>

                        </div>

                        <div class="call-item">

                            <div class="call-item-label">
                                Phone
                            </div>

                            <div class="call-item-value">
                                <i class="fa fa-phone"></i>
                                <?= $evaluation["phone"] ?>
                            </div>

                        </div>

                        <div class="call-item">

                            <div class="call-item-label">
                                Call Type
                            </div>

                            <div class="call-item-value">
                                <i class="fa fa-headset"></i>
                                <?= $evaluation["call_type"] ?>
                            </div>

                        </div>

                        <div class="call-item">

                            <div class="call-item-label">
                                Date
                            </div>

                            <div class="call-item-value">
                                <i class="fa fa-calendar"></i>
                                <?= $evaluation["call_date"] ?>
                            </div>

                        </div>

                        <div class="score-box">

                            <div class="score-number">
                                <?= $evaluation["total_score"] ?>
                            </div>

                            <div class="score-total">
                                Total
                            </div>

                        </div>

                    </div>

                    <div class="main-comment">

                        <label>
                            Main Comment
                        </label>

                        <textarea
                            name="main_comment"
                            placeholder="Write overall evaluation comment here..."><?= htmlspecialchars($evaluation["comment"] ?? '') ?></textarea>

                    </div>

                </div>

                <!-- QUESTIONS -->

                <div class="questions-box">

                    <div class="questions-header">

                        <div class="questions-title">
                            <i class="fa fa-clipboard-check"></i>
                            Evaluation Questions
                        </div>

                        <div class="legend">

                            <span>
                                <div class="dot green"></div>
                                10
                            </span>

                            <span>
                                <div class="dot yellow"></div>
                                5
                            </span>

                            <span>
                                <div class="dot red"></div>
                                0
                            </span>

                        </div>

                    </div>

                    <?php foreach($details as $index => $d): ?>

                        <?php

                        $currentScore = intval($d["score"]);

                        $scoreOptions = [0,5];

                        if($currentScore == 10){
                            $scoreOptions = [0,5,10];
                        }

                        ?>

                        <div class="question-card">

                            <div class="question-top">

                                <div class="q-number">
                                    Q<?= $index + 1 ?>
                                </div>

                                <div class="question-text">
                                    <?= htmlspecialchars($d["question"]) ?>
                                </div>

                            </div>

                            <input type="hidden"
                                   name="detail_id[]"
                                   value="<?= $d["id"] ?>">

                            <div class="question-grid">

                                <div>

                                    <label>
                                        Score
                                    </label>

                                    <select name="score[]">

                                        <?php foreach($scoreOptions as $s): ?>

                                            <option value="<?= $s ?>"
                                            <?= ($currentScore == $s) ? "selected" : "" ?>>

                                                <?= $s ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <div>

                                    <label>
                                        Comment
                                    </label>

                                    <textarea
                                        name="comment[]"
                                        placeholder="No Comment"><?= htmlspecialchars($d["comment"] ?? '') ?></textarea>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                    <button class="save-btn" name="save">
                        <i class="fa fa-floppy-disk"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        <?php else: ?>

            <div class="empty">

                <?php if(!$searched): ?>

                    🔍 Search For Calls First

                <?php else: ?>

                    No Evaluation Selected

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>