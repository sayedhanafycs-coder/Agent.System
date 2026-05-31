<?php
session_start();
include "config/db.php";

$error = "";

/* =========================
   LOGIN ATTEMPTS (basic security)
========================= */

if (!isset($_SESSION["login_attempts"])) {
    $_SESSION["login_attempts"] = 0;
    $_SESSION["last_attempt_time"] = time();
}

$lock_time = 60; // lock for 60 seconds after 5 fails

if ($_SESSION["login_attempts"] >= 5) {
    if (time() - $_SESSION["last_attempt_time"] < $lock_time) {
        $error = "⛔ Too many attempts. Try again in 1 minute.";
    } else {
        $_SESSION["login_attempts"] = 0;
    }
}

/* =========================
   LOGIN
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $error == "") {

    $hr_id    = $_POST["hr_id"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE hr_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$hr_id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user["password"]) {

        $_SESSION["login_attempts"] = 0;

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"]    = $user["role"];
        $_SESSION["name"]    = $user["name"];

        /* remember me */
        if (isset($_POST["remember"])) {
            setcookie("hr_id", $hr_id, time() + (86400 * 30), "/");
        }

$role = $user["role"];

/* =========================
   ROLE BASED REDIRECT
========================= */

$routes = [
    "admin"    => "admin/dashboard.php",
    "quality"  => "quality/dashboard.php",
    "agent"    => "agent/dashboard.php",
    "followup" => "followup/dashboard.php",
    "manager"  => "manager/dashboard.php"
];

if (isset($routes[$role])) {
    header("Location: " . $routes[$role]);
    exit();
} else {
    header("Location: login.php");
    exit();
}

    } else {
        $_SESSION["login_attempts"]++;
        $_SESSION["last_attempt_time"] = time();
        $error = "❌ Invalid credentials";
    }
}

/* cookie preload */
$saved_hr = $_COOKIE["hr_id"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: 'Segoe UI', sans-serif;
}

body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg,#0f172a,#1e293b);
}

/* container */
.login-box{
    width:380px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(18px);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:20px;
    padding:30px;
    box-shadow:0 20px 60px rgba(0,0,0,0.6);
    animation: fadeIn 0.7s ease;
}

/* logo */
.logo{
    text-align:center;
    margin-bottom:15px;
}

.logo img{
    width:70px;
}

/* title */
h2{
    color:#fff;
    text-align:center;
    margin-bottom:20px;
}

/* input */
input{
    width:100%;
    padding:12px 15px;
    margin-bottom:12px;
    border-radius:12px;
    border:1px solid rgba(255,255,255,0.1);
    background:rgba(255,255,255,0.07);
    color:#fff;
    outline:none;
}

input:focus{
    border-color:#fbbf24;
}

/* password wrapper */
.pass-box{
    position:relative;
}

.eye{
    position:absolute;
    right:10px;
    top:10px;
    cursor:pointer;
    color:#ccc;
}

/* remember */
.remember{
    color:#cbd5e1;
    font-size:13px;
    margin-bottom:10px;
}

/* button */
button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:12px;
    background: linear-gradient(90deg,#d4af37,#fbbf24);
    color:#111;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    transform:scale(1.03);
}

/* error */
.error{
    margin-top:10px;
    text-align:center;
    color:#ef4444;
    font-size:13px;
}

/* footer */
.footer{
    margin-top:15px;
    text-align:center;
    color:#94a3b8;
    font-size:12px;
}

/* animation */
@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}

</style>

</head>

<body>

<div class="login-box">

    <!-- LOGO -->
    <div class="logo">
        <img src="https://img.icons8.com/fluency/96/security-shield-green.png">
    </div>

    <h2>Welcome Back</h2>

    <form method="POST">

        <input type="text" name="hr_id" placeholder="HR ID"
               value="<?= htmlspecialchars($saved_hr) ?>" required>

        <!-- PASSWORD WITH EYE -->
        <div class="pass-box">
            <input type="password" id="password" name="password" placeholder="Password" required>
            <span class="eye" onclick="togglePass()">👁</span>
        </div>

        <!-- REMEMBER -->
        <div class="remember">
            <label>
                <input type="checkbox" name="remember">
                Remember me
            </label>
        </div>

        <button type="submit" id="loginBtn">Sign In</button>

        <?php if($error != ""): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

    </form>

    <div class="footer">
        Quality Management System © <?= date("Y") ?>
    </div>

</div>

<script>

/* =========================
   SHOW / HIDE PASSWORD
========================= */

function togglePass(){
    let pass = document.getElementById("password");
    pass.type = pass.type === "password" ? "text" : "password";
}

/* =========================
   LOADING EFFECT
========================= */

const form = document.querySelector("form");
const btn = document.getElementById("loginBtn");

form.addEventListener("submit", function(){
    btn.innerHTML = "Logging in...";
    btn.disabled = true;
});

</script>

</body>
</html>