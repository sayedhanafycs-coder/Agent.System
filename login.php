<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . "/config/db.php";

$error = "";

/* =========================
   LOGIN ATTEMPTS
========================= */

if (!isset($_SESSION["login_attempts"])) {
    $_SESSION["login_attempts"] = 0;
    $_SESSION["last_attempt_time"] = time();
}

$lock_time = 60;

if ($_SESSION["login_attempts"] >= 5) {
    if (time() - $_SESSION["last_attempt_time"] < $lock_time) {
        $error = "⛔ Too many attempts. Try again in 1 minute.";
    } else {
        $_SESSION["login_attempts"] = 0;
    }
}

/* =========================
   LOGIN PROCESS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $error == "") {

    $hr_id    = $_POST["hr_id"] ?? '';
    $password = $_POST["password"] ?? '';

    $sql = "SELECT * FROM users WHERE hr_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . print_r($conn->errorInfo(), true));
    }

    $stmt->execute([$hr_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user["password"]) {

        $_SESSION["login_attempts"] = 0;

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"]    = $user["role"];
        $_SESSION["name"]    = $user["name"];

        if (isset($_POST["remember"])) {
            setcookie("hr_id", $hr_id, time() + (86400 * 30), "/");
        }

        $routes = [
            "admin"    => "admin/dashboard.php",
            "quality"  => "quality/dashboard.php",
            "agent"    => "agent/dashboard.php",
            "followup" => "followup/dashboard.php",
            "manager"  => "manager/dashboard.php"
        ];

        $role = $user["role"];

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

$saved_hr = $_COOKIE["hr_id"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>

<style>
body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg,#0f172a,#1e293b);
    font-family: Arial;
}

.login-box{
    width:380px;
    background: rgba(255,255,255,0.06);
    padding:30px;
    border-radius:20px;
    color:#fff;
}

input{
    width:100%;
    padding:10px;
    margin:10px 0;
}

button{
    width:100%;
    padding:10px;
    background:#fbbf24;
    border:none;
    cursor:pointer;
    font-weight:bold;
}

.error{
    color:red;
    text-align:center;
}
</style>

</head>

<body>

<div class="login-box">

<h2>Login</h2>

<form method="POST">

<input type="text" name="hr_id" placeholder="HR ID"
 value="<?= htmlspecialchars($saved_hr) ?>" required>

<input type="password" name="password" placeholder="Password" required>

<label>
<input type="checkbox" name="remember"> Remember me
</label>

<button type="submit">Login</button>

</form>

<?php if ($error): ?>
    <div class="error"><?= $error ?></div>
<?php endif; ?>

</div>

</body>
</html>
