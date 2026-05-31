<?php
session_start();
include "../config/db.php";

if ($_SESSION["role"] != "followup") {
    exit("Access denied");
}

$user = $_SESSION["name"];
$id = $_POST["id"] ?? null;

if (!$id) {

    $stmt = $conn->prepare("
        INSERT INTO zone_map (sub_area, main_area, area, branch, created_at)
        VALUES (:sub_area, :main_area, :area, :branch, GETDATE())
    ");

    $stmt->execute([
        ":sub_area"  => $_POST["sub_area"],
        ":main_area" => $_POST["main_area"],
        ":area"      => $_POST["area"],
        ":branch"    => $_POST["branch"]
    ]);

} else {

    $stmt = $conn->prepare("
        UPDATE zone_map
        SET sub_area=:sub_area,
            main_area=:main_area,
            area=:area,
            branch=:branch
        WHERE id=:id
    ");

    $stmt->execute([
        ":sub_area"  => $_POST["sub_area"],
        ":main_area" => $_POST["main_area"],
        ":area"      => $_POST["area"],
        ":branch"    => $_POST["branch"],
        ":id"        => $id
    ]);

}

/* LOG WHO EDITED */
$log = $conn->prepare("
    INSERT INTO notifications (user_role, message, is_read, created_at)
    VALUES ('fu', :msg, 0, GETDATE())
");

$log->execute([
    ":msg" => $user . " modified zone ID: " . ($id ?? 'NEW')
]);

header("Location: edit_zone.php");
exit();