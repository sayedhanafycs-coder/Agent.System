<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION["role"])){
    header("Location: ../login.php");
    exit();
}

$user_name = $_SESSION["name"];
$user_role = $_SESSION["role"];

$request_id = (int)($_GET['id'] ?? 0);

if($request_id <= 0){
    exit("Invalid Request");
}

/* ===============================
   SEND MESSAGE (AJAX)
================================ */

if(isset($_POST['ajax_send'])){

    $message = trim($_POST['message']);

    if($message != ''){

        $stmt = $conn->prepare("
            INSERT INTO cancel_order_chat
            (request_id,sender_name,sender_role,message)
            VALUES (?,?,?,?)
        ");

        $stmt->execute([
            $request_id,
            $user_name,
            $user_role,
            $message
        ]);
    }

    exit("success");
}

/* ===============================
   LOAD CHAT (AJAX)
================================ */

if(isset($_GET['load_chat'])){

    $stmt = $conn->prepare("
        SELECT *
        FROM cancel_order_chat
        WHERE request_id=?
        ORDER BY id ASC
    ");

    $stmt->execute([$request_id]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($messages as $msg){

        $class = $msg['sender_role'];

        echo '
        <div class="message '.$class.'">

            <div class="sender">
                '.htmlspecialchars($msg['sender_name']).'
            </div>

            <div>
                '.nl2br(htmlspecialchars($msg['message'])).'
            </div>

            <div class="time">
                '.date("h:i A",strtotime($msg['created_at'])).'
            </div>

        </div>';
    }

    exit();
}

/* ===============================
   END CHAT
================================ */

if(isset($_GET['end_chat'])){

    if($user_role == 'agent' || $user_role == 'followup'){

        $stmt = $conn->prepare("
            UPDATE cancel_orders
            SET status='closed'
            WHERE id=?
        ");

        $stmt->execute([$request_id]);

        exit("success");
    }

    exit("denied");
}

/* ===============================
   GET REQUEST
================================ */

$stmt = $conn->prepare("
    SELECT *
    FROM cancel_orders
    WHERE id=?
");

$stmt->execute([$request_id]);

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$request){
    exit("Request not found");
}

$isClosed = ($request['status'] == 'closed');

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Cancel Order Chat</title>

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
    padding:25px;
}

/* ================= HEADER ================= */

.top-box{
    background:#111827;
    border-radius:20px;
    padding:20px;
    margin-bottom:20px;
    border:1px solid rgba(212,175,55,.2);
}


.title{
    color:#d4af37;
    font-size:28px;
    font-weight:900;
    margin-bottom:10px;
}

.info{
    color:#cbd5e1;
    line-height:2;
}

/* ================= CHAT ================= */

.chat-box{
    background:#111827;
    border-radius:20px;
    padding:20px;
    height:500px;
    overflow-y:auto;
    border:1px solid rgba(212,175,55,.2);
    margin-bottom:20px;
    scroll-behavior:smooth;
}

/* ================= MESSAGE ================= */

.message{
    margin-bottom:15px;
    max-width:75%;
    padding:15px;
    border-radius:16px;
    line-height:1.7;
    animation:fade .2s ease;
}

@keyframes fade{
    from{
        opacity:0;
        transform:translateY(10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.agent{
    background:#1e293b;
    margin-right:auto;
}

.fu{
    background:#d4af37;
    color:#000;
    margin-left:auto;
}

.manager{
    background:#d4af37;
    color:#000;
    margin-left:auto;
}

.sender{
    font-weight:bold;
    margin-bottom:6px;
}

.time{
    font-size:11px;
    opacity:.7;
    margin-top:8px;
}

/* ================= FORM ================= */

.send-box{
    display:flex;
    gap:10px;
}

.send-box input{
    flex:1;
    padding:15px;
    border:none;
    border-radius:14px;
    background:#1f2937;
    color:white;
    font-size:14px;
}

.send-box input:focus{
    outline:none;
    border:1px solid #d4af37;
}

.send-box button{
    padding:15px 25px;
    border:none;
    border-radius:14px;
    background:#d4af37;
    color:black;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}

.send-box button:hover{
    background:#facc15;
}

/* ================= MOBILE ================= */

@media(max-width:768px){

    body{
        padding:15px;
    }

    .chat-box{
        height:420px;
    }

    .message{
        max-width:90%;
    }

}

</style>

</head>

<body>

<!-- ================= REQUEST INFO ================= -->

<div class="top-box">

    <div class="title">
        Order #<?= htmlspecialchars($request['order_number']) ?>
    </div>

    <div class="info">

        <strong>Agent:</strong>
        <?= htmlspecialchars($request['agent_name']) ?>

        <br>

        <strong>Reason:</strong>
        <?= htmlspecialchars($request['reason']) ?>

        <br>

        <strong>Status:</strong>
        <?= ucfirst($request['status']) ?>

    </div>

</div>



<!-- ================= CHAT ================= -->

<div
    class="chat-box"
    id="chatBox"
></div>

<!-- ================= SEND ================= -->

<div class="send-box">

    <input
        type="text"
        id="message"
        placeholder="Type your message..."
    >

    <button onclick="sendMessage()">
        Send
    </button>

</div>

<script>

function endChat(){

    if(!confirm("Are you sure you want to close this chat?")){
        return;
    }

    fetch(
        "cancel_order_chat.php?id=<?= $request_id ?>&end_chat=1"
    )
    .then(res => res.text())
    .then(() => {

        <?php if($user_role == 'followup'): ?>

        window.location.href =
            "../followup/cancel_order_monitor.php";

        <?php else: ?>

        window.location.href =
            "cancel_order.php";

        <?php endif; ?>

    });

}

const chatBox = document.getElementById("chatBox");

function loadChat(){

    fetch("cancel_order_chat.php?id=<?= $request_id ?>&load_chat=1")
    .then(res => res.text())
    .then(data => {

        let isBottom =
            chatBox.scrollTop + chatBox.clientHeight >=
            chatBox.scrollHeight - 50;

        chatBox.innerHTML = data;

        if(isBottom){
            chatBox.scrollTop = chatBox.scrollHeight;
        }

    });

}

function sendMessage(){
<?php if($isClosed): ?>
return;
<?php endif; ?>
    let message = document.getElementById("message").value;

    if(message.trim() == ''){
        return;
    }

    let formData = new FormData();

    formData.append("ajax_send",1);
    formData.append("message",message);

    fetch("cancel_order_chat.php?id=<?= $request_id ?>",{
        method:"POST",
        body:formData
    })
    .then(res => res.text())
    .then(() => {

        document.getElementById("message").value = '';

        loadChat();

        setTimeout(() => {
            chatBox.scrollTop = chatBox.scrollHeight;
        },100);

    });

}

/* ENTER SEND */

document
.getElementById("message")
.addEventListener("keypress",function(e){

    if(e.key === "Enter"){

        e.preventDefault();

        sendMessage();

    }

});

/* AUTO LOAD */

loadChat();

setInterval(loadChat,2000);

</script>

</body>
</html>


<?php if($user_role == 'agent' || $user_role == 'followup'): ?>

<div style="margin-bottom:15px;text-align:right">

    <?php if(!$isClosed): ?>

    <button
        onclick="endChat()"
        style="
            background:#dc2626;
            color:white;
            border:none;
            padding:12px 20px;
            border-radius:12px;
            cursor:pointer;
            font-weight:bold;
        "
    >
        End Chat
    </button>

    <?php endif; ?>

</div>

<?php endif; ?>