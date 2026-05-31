<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "agent") {
    header("Location: ../login.php");
    exit();
}

$agent_name = $_SESSION["name"];

$error = "";

/* ===============================
   SEND REQUEST
================================ */

if(isset($_POST['send_request'])){

    $order_number = trim($_POST['order_number']);
    $reason = trim($_POST['reason']);
    $notes = trim($_POST['notes']);

    if($order_number != '' && $reason != ''){

        $stmt = $conn->prepare("
            INSERT INTO cancel_orders
            (agent_name, order_number, reason, notes)
            VALUES (?,?,?,?)
        ");

        $stmt->execute([
            $agent_name,
            $order_number,
            $reason,
            $notes
        ]);

        // 🔥 GET LAST REQUEST ID
        $request_id = $conn->lastInsertId();

        // 🔥 OPEN CHAT DIRECTLY
        header("Location: cancel_order_chat.php?id=".$request_id);
        exit();

    }else{

        $error = "Please fill required fields";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>Cancel Order</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

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
    padding:30px;
}

/* ================= CONTAINER ================= */

.container{
    max-width:700px;
    margin:auto;
}

/* ================= CARD ================= */

.card{
    background:#111827;
    border:1px solid rgba(212,175,55,.2);
    border-radius:20px;
    padding:30px;
    box-shadow:0 0 25px rgba(0,0,0,.25);
}

/* ================= TITLE ================= */

.title{
    font-size:32px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:10px;
}

.sub{
    color:#94a3b8;
    margin-bottom:25px;
    font-size:14px;
}

/* ================= ALERT ================= */

.error{
    background:#7f1d1d;
    color:#fca5a5;
    padding:14px;
    border-radius:12px;
    margin-bottom:20px;
    text-align:center;
    font-weight:bold;
}

/* ================= FORM ================= */

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    color:#d1d5db;
    font-weight:600;
}

input,
textarea{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#1f2937;
    color:white;
    font-size:14px;
    transition:.3s;
}

textarea{
    min-height:130px;
    resize:none;
}

input:focus,
textarea:focus{
    outline:none;
    border:1px solid #d4af37;
    box-shadow:0 0 10px rgba(212,175,55,.2);
}

/* ================= BUTTON ================= */

button{
    width:100%;
    padding:15px;
    border:none;
    border-radius:14px;
    background:#d4af37;
    color:#000;
    font-weight:900;
    cursor:pointer;
    transition:.3s;
    font-size:15px;
}

button:hover{
    background:#facc15;
    transform:translateY(-2px);
}

/* ================= MOBILE ================= */

@media(max-width:768px){

    body{
        padding:15px;
    }

    .card{
        padding:20px;
    }

    .title{
        font-size:26px;
    }

}

</style>

</head>

<script>

/* ===============================
   REQUEST PERMISSION
================================ */

if (Notification.permission !== "granted") {
    Notification.requestPermission();
}

/* ===============================
   CHECK NOTIFICATIONS
================================ */

setInterval(() => {

    fetch("check_notifications.php")
    .then(res => res.json())
    .then(data => {

        if(data.status){

            let message =
                "Order #" + data.order_number +
                " was " + data.status;

            /* ===============================
               POPUP ALERT
            ================================ */

            alert(message);

            /* ===============================
               WINDOWS NOTIFICATION
            ================================ */

            if(Notification.permission === "granted"){

                new Notification(
                    "Cancel Order Update",
                    {
                        body: message,
                        icon: "https://cdn-icons-png.flaticon.com/512/1827/1827349.png"
                    }
                );
            }
        }

    });

}, 3000);


</script>

<?php include "../includes/global_notifications.php"; ?>

<body>

<div class="container">

    <div class="card">

        <div class="title">
            Cancel Order Request
        </div>

        <div class="sub">
            Send cancellation request to Follow Up Team
        </div>

        <?php if($error != ''): ?>

            <div class="error">
                <?= $error ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <!-- ORDER NUMBER -->

            <div class="form-group">

                <label>
                    Order Number
                </label>

                <input
                    type="text"
                    name="order_number"
                    placeholder="Enter order number..."
                    required
                >

            </div>

            <!-- REASON -->

            <div class="form-group">

                <label>
                    Reason
                </label>

                <input
                    type="text"
                    name="reason"
                    placeholder="Cancellation reason..."
                    required
                >

            </div>

            <!-- NOTES -->

            <div class="form-group">

                <label>
                    Notes
                </label>

                <textarea
                    name="notes"
                    placeholder="Write additional notes..."
                ></textarea>

            </div>

            <!-- BUTTON -->

            <button
                type="submit"
                name="send_request"
            >
                Send Request
            </button>

        </form>

    </div>

</div>

</body>
</html>