<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

include "../config/db.php";

$id = $_GET["id"];

$stmt = $conn->prepare("SELECT * FROM evaluations WHERE id=?");
$stmt->execute([$id]);
$e = $stmt->fetch(PDO::FETCH_ASSOC);

$html = "
<h2>QA Report</h2>
<p>Score: {$e['total_score']}</p>
<p>Phone: {$e['phone']}</p>
<p>Date: {$e['call_date']}</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("evaluation.pdf");
?>