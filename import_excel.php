<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

include "config/db.php";

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = "uploads/زونات الفروع يوم 07-05-2026.xlsx";

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$reader->setReadEmptyCells(false);

$spreadsheet = $reader->load($file);

foreach ($spreadsheet->getAllSheets() as $sheet) {

    $highestRow = $sheet->getHighestDataRow();

    $current_main_area = "";

    for ($row = 1; $row <= $highestRow; $row++) {

        $rowData = $sheet->rangeToArray(
            'A' . $row . ':' . $sheet->getHighestDataColumn() . $row,
            null,
            true,
            true,
            false
        );

        if (!isset($rowData[0])) continue;

        $branch    = trim((string)($rowData[0][0] ?? ""));
        $main_area = trim((string)($rowData[0][1] ?? ""));
        $sub_area  = trim((string)($rowData[0][2] ?? ""));
        $area      = trim((string)($rowData[0][3] ?? ""));
        $comment   = trim((string)($rowData[0][4] ?? ""));

        // 🔥 استبعاد الهيدر تلقائيًا مهما كان مكانه
        if (
            strtolower($branch) == "branch" &&
            strtolower($main_area) == "main_area"
        ) {
            continue;
        }

        // تجاهل الصفوف الفاضية
        if ($branch == "" && $sub_area == "") {
            continue;
        }

        // تحديث main_area
        if ($main_area != "") {
            $current_main_area = $main_area;
        }

        // لو مفيش sub_area نتخطى
        if ($sub_area == "") {
            continue;
        }

        // منع التكرار
        $check = $conn->prepare("
            SELECT COUNT(*) 
            FROM zone_map
            WHERE branch = ?
            AND main_area = ?
            AND sub_area = ?
        ");

        $check->execute([
            $branch,
            $current_main_area,
            $sub_area
        ]);

        if ($check->fetchColumn() > 0) {
            continue;
        }

        // الإدخال النهائي
        $stmt = $conn->prepare("
            INSERT INTO zone_map
            (branch, main_area, sub_area, area, comment)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $branch,
            $current_main_area,
            $sub_area,
            $area,
            $comment
        ]);
    }
}

$spreadsheet->__destruct();

echo "DONE IMPORT SUCCESSFULLY";
?>