<?php
session_start();
include "../config/db.php";

if ($_SESSION['role'] != 'manager') {
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
SELECT
    ed.question,
    ed.score,
    ed.comment
FROM evaluation_details ed
INNER JOIN evaluations e
    ON e.id = ed.evaluation_id
WHERE e.id = ?
ORDER BY ed.id ASC
");

$stmt->execute([$id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detailsWrapper">

    <?php if(count($details) > 0): ?>

        <?php foreach($details as $d): ?>

            <div class="detailCard">

                <div class="question">
                    <?= htmlspecialchars($d['question']) ?>
                </div>

                <div class="scoreBox">
                    Score : <?= $d['score'] ?>
                </div>

                <div class="commentBox">
                    <?= $d['comment'] ? htmlspecialchars($d['comment']) : 'No Comment' ?>
                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="emptyBox">
            No Details Found
        </div>

    <?php endif; ?>

</div>

<style>

.detailsWrapper{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.detailCard{
    background:#111827;
</style>