<?php

session_start();
include "config/db.php";

/* =========================
   SET OFFLINE
========================= */

if(isset($_SESSION["user_id"])){

    $stmt = $conn->prepare("
        UPDATE users
        SET
            is_online = 0,
            status = 'offline'
        WHERE id = ?
    ");

    $stmt->execute([$_SESSION["user_id"]]);
}

/* =========================
   DESTROY SESSION
========================= */

session_unset();

session_destroy();

header("Location: login.php");
exit();

?>