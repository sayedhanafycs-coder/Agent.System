<?php
session_start();

if ($_SESSION["role"] != "manager") {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$message = "";

/*
=====================================
PLATFORM GROUPING
=====================================
*/

function getPlatformGroup($channel)
{
    $channel = trim($channel);

    if (in_array($channel, ['OD Cash', 'OD Credit'])) {
        return 'Talabat';
    }

    if (in_array($channel, ['Menus OD Cash', 'Menus OD Credit'])) {
        return 'Menus';
    }

    if (in_array($channel, ['BF Cash', 'BF Credit'])) {
        return 'Bread Fast';
    }

    if (in_array($channel, ['HarryApp Cash', 'HarryApp Credit'])) {
        return 'HarryApp';
    }

    if (in_array($channel, ['Hadeer Cash', 'Hadeer Credit'])) {
        return 'Hadeer';
    }

    if (
        strtoupper($channel) == 'CALL CENTER' ||
        strtoupper($channel) == 'CALLCENTER'
    ) {
        return 'Call Center';
    }

    return $channel;
}

/*
=====================================
FORMAT DATE
=====================================
*/

function formatExcelDate($value)
{
    if (empty($value)) {
        return null;
    }

    /*
    EXCEL SERIAL DATE
    */

    if (is_numeric($value)) {

        try {

            return Date::excelToDateTimeObject($value)
                ->format('Y-m-d');

        } catch (Exception $e) {

            return null;
        }
    }

    /*
    NORMAL DATE
    */

    $time = strtotime($value);

    if ($time) {
        return date('Y-m-d', $time);
    }

    return null;
}

/*
=====================================
FORMAT TIME
=====================================
*/

function formatExcelTime($value)
{
    if (empty($value)) {
        return null;
    }

    /*
    EXCEL SERIAL TIME
    */

    if (is_numeric($value)) {

        try {

            return Date::excelToDateTimeObject($value)
                ->format('H:i:s');

        } catch (Exception $e) {

            return null;
        }
    }

    /*
    NORMAL TIME
    */

    $time = strtotime($value);

    if ($time) {
        return date('H:i:s', $time);
    }

    return null;
}

/*
=====================================
UPLOAD EXCEL
=====================================
*/

if (isset($_POST["upload"])) {

    if (!empty($_FILES["excel_file"]["name"])) {

        $fileName = $_FILES["excel_file"]["tmp_name"];

        try {

            $spreadsheet = IOFactory::load($fileName);

            $sheet = $spreadsheet
                ->getActiveSheet()
                ->toArray();

            $totalInserted = 0;

            /*
            =====================================
            PREPARE INSERT QUERY
            =====================================
            */

            $insertSql = "
                INSERT INTO sales_data (

                    sales_channel,
                    platform_group,
                    order_number,
                    customer_name,
                    phone,
                    store,
                    employee_name,
                    zone_name,
                    order_date,
                    order_time,
                    total_amount,
                    discount_amount,
                    phone_ext,
                    chains,
                    void_by

                )

                VALUES (

                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?

                )
            ";

            $insertStmt = $conn->prepare($insertSql);

            /*
            =====================================
            LOOP ROWS
            =====================================
            */

            foreach ($sheet as $index => $row) {

                /*
                SKIP HEADER
                */

                if ($index == 0) {
                    continue;
                }

                /*
                CLEAN DATA
                */

                $sales_channel =
                    trim($row[0] ?? '');

                $platform_group =
                    getPlatformGroup($sales_channel);

                $order_number =
                    trim($row[1] ?? '');

                $customer_name =
                    trim($row[2] ?? '');

                $phone =
                    trim($row[3] ?? '');

                $store =
                    trim($row[4] ?? '');

                $employee_name =
                    trim($row[5] ?? '');

                $zone_name =
                    trim($row[6] ?? '');

                $order_date =
                    formatExcelDate($row[7] ?? '');

                $order_time =
                    formatExcelTime($row[8] ?? '');

                $total_amount =
                    is_numeric($row[9] ?? null)
                    ? (float)$row[9]
                    : 0;

                $discount_amount =
                    is_numeric($row[10] ?? null)
                    ? (float)$row[10]
                    : 0;

                $phone_ext =
                    trim($row[11] ?? '');

                $chains =
                    trim($row[12] ?? '');

                $void_by =
                    trim($row[13] ?? '');

                /*
                SKIP EMPTY ORDER
                */

                if (empty($order_number)) {
                    continue;
                }

                /*
                =====================================
                CHECK DUPLICATE
                =====================================
                */

                $checkSql = "
                    SELECT id
                    FROM sales_data
                    WHERE order_number = ?
                ";

                $checkStmt = $conn->prepare($checkSql);

                $checkStmt->execute([
                    $order_number
                ]);

                if ($checkStmt->fetch()) {
                    continue;
                }

                /*
                =====================================
                INSERT DATA
                =====================================
                */

                $insertStmt->execute([

                    $sales_channel,
                    $platform_group,
                    $order_number,
                    $customer_name,
                    $phone,
                    $store,
                    $employee_name,
                    $zone_name,
                    $order_date,
                    $order_time,
                    $total_amount,
                    $discount_amount,
                    $phone_ext,
                    $chains,
                    $void_by
                ]);

                $totalInserted++;
            }

            /*
            =====================================
            SAVE REPORT
            =====================================
            */

            $reportSql = "
                INSERT INTO uploaded_reports (

                    file_name,
                    uploaded_by,
                    total_rows,
                    uploaded_at

                )

                VALUES (

                    ?, ?, ?, GETDATE()

                )
            ";

            $reportStmt = $conn->prepare($reportSql);

            $reportStmt->execute([

                $_FILES["excel_file"]["name"],

                $_SESSION["name"],

                $totalInserted
            ]);

            $message =
                "تم رفع $totalInserted أوردر بنجاح ✅";

        } catch (Exception $e) {

            $message =
                "حدث خطأ أثناء رفع الملف : "
                . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>
    Upload Sales
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet"
>

<style>

body{
    background:#f5f7fb;
    font-family:'Cairo',sans-serif;
}

.upload-box{
    max-width:750px;
    margin:80px auto;
    background:white;
    padding:45px;
    border-radius:25px;
    box-shadow:0 5px 25px rgba(0,0,0,0.08);
}

.title{
    font-size:32px;
    font-weight:bold;
    margin-bottom:35px;
    text-align:center;
}

.upload-icon{
    text-align:center;
    font-size:70px;
    color:#198754;
    margin-bottom:25px;
}

.btn-upload{
    width:100%;
    height:60px;
    font-size:20px;
    border-radius:14px;
}

</style>

</head>

<body>

<div class="container">

    <div class="upload-box">

        <div class="upload-icon">

            <i class="fa-solid fa-file-excel"></i>

        </div>

        <div class="title">

            رفع ملف المبيعات

        </div>

        <?php if($message): ?>

            <div class="alert alert-info text-center">

                <?= $message ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="mb-4">

                <label class="form-label">

                    اختر ملف Excel

                </label>

                <input
                    type="file"
                    name="excel_file"
                    class="form-control form-control-lg"
                    accept=".xlsx,.xls"
                    required
                >

            </div>

            <button
                type="submit"
                name="upload"
                class="btn btn-success btn-upload"
            >

                <i class="fa-solid fa-upload"></i>

                رفع البيانات

            </button>

        </form>

    </div>

</div>

</body>
</html>