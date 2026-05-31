<!-- GLOBAL NOTIFICATION -->

<div id="globalNotification" class="global-notification">

    <div class="global-title">
        Notification
    </div>

    <div id="globalText"></div>

    <button onclick="closeGlobalNotification()" class="global-btn">
        OK
    </button>

</div>

<audio id="globalSound" loop>
    <source src="../assets/notification.mp3">
</audio>

<style>

.global-notification{

    position:fixed;
    top:25px;
    right:25px;
    width:340px;
    background:#111827;
    color:white;
    border-left:5px solid #d4af37;
    border-radius:18px;
    padding:18px;
    z-index:999999;

    transform:translateX(450px);
    opacity:0;

    transition:.4s;
}

.global-notification.show{

    transform:translateX(0);
    opacity:1;
}

.global-title{

    font-size:18px;
    font-weight:900;
    color:#d4af37;
    margin-bottom:10px;
}

.global-btn{

    width:100%;
    margin-top:14px;
    border:none;
    background:#d4af37;
    color:#111827;
    padding:10px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
}

</style>

<script>

let globalSound =
    document.getElementById("globalSound");

let globalUnlocked = false;

document.addEventListener("click", () => {

    globalSound.play()
    .then(() => {

        globalSound.pause();
        globalSound.currentTime = 0;

        globalUnlocked = true;

    }).catch(()=>{});

}, { once:true });

function showGlobalNotification(message){

    document.getElementById("globalText")
        .innerHTML = message;

    document.getElementById("globalNotification")
        .classList.add("show");

    if(globalUnlocked){

        globalSound.pause();

        globalSound.currentTime = 0;

        globalSound.play().catch(()=>{});
    }
}

function closeGlobalNotification(){

    document.getElementById("globalNotification")
        .classList.remove("show");

    globalSound.pause();

    globalSound.currentTime = 0;
}

/* CHECK EVERY 5 SECONDS */

setInterval(() => {

    fetch("../agent/check_prayer_status.php")

    .then(res => res.json())

    .then(data => {

        if(!data.success) return;

        let msg = "";

        if(data.status == "approved"){

            msg =
            "✅ Your prayer request for " +
            data.prayer +
            " at " +
            data.time +
            " was APPROVED";
        }

        if(data.status == "rejected"){

            msg =
            "❌ Your prayer request for " +
            data.prayer +
            " at " +
            data.time +
            " was REJECTED";
        }

        showGlobalNotification(msg);

    });

}, 5000);

</script>