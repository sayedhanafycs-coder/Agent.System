<?php

$host = "mysql.railway.internal";
$user = "root";
$password = "VNfigPOSHCaOXBSuKUbuXwCxQKsKuAUL";
$dbname = "railway";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
