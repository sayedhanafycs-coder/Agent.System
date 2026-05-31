<?php

$host = "mysql.railway.internal";
$port = "3306";
$user = "root";
$password = "VNfigPOSHCaOXBSuKUbuXwCxQKsKuAUL";
$dbname = "railway";

try {
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}