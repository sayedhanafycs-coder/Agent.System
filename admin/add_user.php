<?php
include "../config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name     = $_POST["name"];
    $hr_id    = $_POST["hr_id"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role     = $_POST["role"];
    $shift    = $_POST["shift"];

    $sql = "INSERT INTO users (name, hr_id, password, role, shift)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$name, $hr_id, $password, $role, $shift])) {
        $message = "✅ تم إضافة الموظف بنجاح";
    } else {
        $message = "❌ حصل خطأ";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Add User</title>
<style>
body { font-family: Arial; direction: rtl; background:#f4f4f4; padding:20px;}
form { background:#fff; padding:20px; border-radius:10px; width:400px;}
input, select { width:100%; padding:10px; margin:10px 0;}
button { padding:10px; background:#28a745; color:#fff; border:none;}
</style>
</head>

<body>

<h2>إضافة موظف</h2>

<form method="POST">
    <input type="text" name="name" placeholder="اسم الموظف" required>
    <input type="text" name="hr_id" placeholder="HR ID" required>
    <input type="password" name="password" placeholder="الباسورد" required>

    <select name="role">
        <option value="agent">Agent</option>
        <option value="quality">Quality</option>
        <option value="admin">Admin</option>
    </select>

    <select name="shift">
        <option>Morning</option>
        <option>Evening</option>
        <option>Night</option>
    </select>

    <button type="submit">إضافة</button>
</form>

<p><?php echo $message; ?></p>

</body>
</html>