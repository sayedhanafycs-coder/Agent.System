<?php

include "../../config/db.php";

$where = " WHERE 1=1 ";

$params = [];

/*
=====================================
DATE FILTER
=====================================
*/

if (!empty($_POST['from_date'])) {

    $where .= " AND order_date >= ? ";
    $params[] = $_POST['from_date'];
}

if (!empty($_POST['to_date'])) {

    $where .= " AND order_date <= ? ";
    $params[] = $_POST['to_date'];
}

/*
=====================================
STORE FILTER
=====================================
*/

if (!empty($_POST['store'])) {

    $where .= " AND store = ? ";
    $params[] = $_POST['store'];
}

/*
=====================================
PLATFORM FILTER
=====================================
*/

if (!empty($_POST['platform'])) {

    $where .= " AND platform_group = ? ";
    $params[] = $_POST['platform'];
}

/*
=====================================
TOTAL SALES
=====================================
*/

$totalSalesSql = "
    SELECT
        ISNULL(SUM(total_amount),0) AS total_sales
    FROM sales_data
    $where
";

$stmt = sqlsrv_query($conn, $totalSalesSql, $params);

$totalSales = sqlsrv_fetch_array(
    $stmt,
    SQLSRV_FETCH_ASSOC
);

/*
=====================================
TOTAL ORDERS
=====================================
*/

$totalOrdersSql = "
    SELECT
        COUNT(*) AS total_orders
    FROM sales_data
    $where
";

$stmt2 = sqlsrv_query(
    $conn,
    $totalOrdersSql,
    $params
);

$totalOrders = sqlsrv_fetch_array(
    $stmt2,
    SQLSRV_FETCH_ASSOC
);

/*
=====================================
AVG ORDER
=====================================
*/

$avgSql = "
    SELECT
        ISNULL(AVG(total_amount),0) AS avg_order
    FROM sales_data
    $where
";

$stmt3 = sqlsrv_query(
    $conn,
    $avgSql,
    $params
);

$avgOrder = sqlsrv_fetch_array(
    $stmt3,
    SQLSRV_FETCH_ASSOC
);

/*
=====================================
BEST PLATFORM
=====================================
*/

$platformSql = "
    SELECT TOP 1
        platform_group,
        SUM(total_amount) AS total
    FROM sales_data
    $where
    GROUP BY platform_group
    ORDER BY total DESC
";

$stmt4 = sqlsrv_query(
    $conn,
    $platformSql,
    $params
);

$platform = sqlsrv_fetch_array(
    $stmt4,
    SQLSRV_FETCH_ASSOC
);

/*
=====================================
PLATFORM CHART
=====================================
*/

$chartSql = "
    SELECT
        platform_group,
        SUM(total_amount) AS total
    FROM sales_data
    $where
    GROUP BY platform_group
";

$chartStmt = sqlsrv_query(
    $conn,
    $chartSql,
    $params
);

$platformLabels = [];
$platformTotals = [];

while ($row = sqlsrv_fetch_array(
    $chartStmt,
    SQLSRV_FETCH_ASSOC
)) {

    $platformLabels[] = $row['platform_group'];

    $platformTotals[] = (float)$row['total'];
}

/*
=====================================
STORES CHART
=====================================
*/

$storesSql = "
    SELECT TOP 10
        store,
        SUM(total_amount) AS total
    FROM sales_data
    $where
    GROUP BY store
    ORDER BY total DESC
";

$storesStmt = sqlsrv_query(
    $conn,
    $storesSql,
    $params
);

$storeLabels = [];
$storeTotals = [];

while ($row = sqlsrv_fetch_array(
    $storesStmt,
    SQLSRV_FETCH_ASSOC
)) {

    $storeLabels[] = $row['store'];

    $storeTotals[] = (float)$row['total'];
}

/*
=====================================
TREND CHART
=====================================
*/

$trendSql = "
    SELECT
        order_date,
        SUM(total_amount) AS total
    FROM sales_data
    $where
    GROUP BY order_date
    ORDER BY order_date
";

$trendStmt = sqlsrv_query(
    $conn,
    $trendSql,
    $params
);

$trendDates = [];
$trendTotals = [];

while ($row = sqlsrv_fetch_array(
    $trendStmt,
    SQLSRV_FETCH_ASSOC
)) {

    $trendDates[] =
        $row['order_date']->format('Y-m-d');

    $trendTotals[] =
        (float)$row['total'];
}

/*
=====================================
TOP EMPLOYEES
=====================================
*/

$employeesSql = "
    SELECT TOP 10
        employee_name,
        COUNT(*) AS orders_count,
        SUM(total_amount) AS total_sales,
        AVG(total_amount) AS avg_order
    FROM sales_data
    $where
    GROUP BY employee_name
    ORDER BY total_sales DESC
";

$employeesStmt = sqlsrv_query(
    $conn,
    $employeesSql,
    $params
);

$employees = [];

while ($row = sqlsrv_fetch_array(
    $employeesStmt,
    SQLSRV_FETCH_ASSOC
)) {

    $employees[] = [

        "employee" =>
            $row['employee_name'],

        "orders" =>
            number_format($row['orders_count']),

        "sales" =>
            number_format($row['total_sales'], 2),

        "avg" =>
            number_format($row['avg_order'], 2)
    ];
}

/*
=====================================
RETURN JSON
=====================================
*/

echo json_encode([

    /*
    CARDS
    */

    "total_sales" =>
        number_format(
            $totalSales['total_sales'],
            2
        ),

    "total_orders" =>
        number_format(
            $totalOrders['total_orders']
        ),

    "avg_order" =>
        number_format(
            $avgOrder['avg_order'],
            2
        ),

    "best_platform" =>
        $platform['platform_group'] ?? '-',

    /*
    PLATFORM CHART
    */

    "platform_labels" =>
        $platformLabels,

    "platform_totals" =>
        $platformTotals,

    /*
    STORES CHART
    */

    "store_labels" =>
        $storeLabels,

    "store_totals" =>
        $storeTotals,

    /*
    TREND CHART
    */

    "trend_dates" =>
        $trendDates,

    "trend_totals" =>
        $trendTotals,

    /*
    TOP EMPLOYEES
    */

    "employees" =>
        $employees

]);