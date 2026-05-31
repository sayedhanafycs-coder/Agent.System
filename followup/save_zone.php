<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION["role"])) {
    exit("unauthorized");
}

try {

    $sub_area  = $_POST["sub_area"] ?? null;
    $main_area = $_POST["main_area"] ?? null;
    $area      = $_POST["area"] ?? null;
    $said_by   = $_POST["comment"] ?? null; // نفس input في الفورم لسه اسمه comment
    $branch    = $_POST["branch"] ?? null;

    $created_by = $_SESSION["name"] ?? null;
    $status = "pending";
    $created_at = date("Y-m-d H:i:s");

    // validation
    if (!$sub_area || !$main_area || !$area || !$said_by || !$branch) {
        exit("missing_data");
    }

    $sql = "INSERT INTO zone_requests 
            (sub_area, main_area, area, said_by, branch, status, created_at, created_by)
            VALUES 
            (:sub_area, :main_area, :area, :said_by, :branch, :status, :created_at, :created_by)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":sub_area"    => $sub_area,
        ":main_area"   => $main_area,
        ":area"        => $area,
        ":said_by"     => $said_by,
        ":branch"      => $branch,
        ":status"      => $status,
        ":created_at"  => $created_at,
        ":created_by"  => $created_by
    ]);

    echo "success";

} catch (Exception $e) {
    echo $e->getMessage();
}
?>