<?php
include __DIR__ . "/config/db.php";

$orderData = null;

if(isset($_GET['order_number']) && $_GET['order_number'] != '') {

    $order_number = $_GET['order_number'];

    $stmt = $conn->prepare("
        SELECT *
        FROM order1
        WHERE order_number = ?
    ");

    $stmt->execute([$order_number]);

    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Order Search</title>

<style>
body{
    font-family:Arial;
    background:#0f172a;
    color:white;
    margin:0;
}

.container{
    padding:30px;
}

.box{
    background:rgba(255,255,255,0.08);
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;
}

input{
    padding:10px;
    width:250px;
    border-radius:8px;
    border:none;
}

button{
    padding:10px 15px;
    border:none;
    border-radius:8px;
    background:#3b82f6;
    color:white;
    cursor:pointer;
}

table{
    width:100%;
    margin-top:20px;
    border-collapse:collapse;
    background:rgba(255,255,255,0.05);
}

td,th{
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

th{
    text-align:left;
    background:rgba(255,255,255,0.1);
}
</style>

</head>

<body>

<div class="container">

<!-- SEARCH BOX -->
<div class="box">
<h2>Search Order</h2>

<form method="GET">
<input type="text" name="order_number" placeholder="Enter Order Number">
<button type="submit">Search</button>
</form>
</div>

<!-- RESULT -->
<?php if($orderData){ ?>

<div class="box">
<h2>Order Details</h2>

<table>
<?php foreach($orderData as $key => $value){ ?>
<tr>
<th><?= $key ?></th>
<td><?= $value ?></td>
</tr>
<?php } ?>
</table>

</div>

<?php } elseif(isset($_GET['order_number'])) { ?>

<div class="box">
<h2>No Order Found</h2>
<p>Try another order number</p>
</div>

<?php } ?>

</div>

</body>
</html>