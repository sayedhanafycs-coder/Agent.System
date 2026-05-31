<?php
session_start();

if ($_SESSION["role"] != "manager") {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

/*
=====================================
TOTAL SALES
=====================================
*/

$totalSalesQuery = "
    SELECT ISNULL(SUM(total_amount),0) AS total_sales
    FROM sales_data
";

$totalSalesStmt = $conn->prepare($totalSalesQuery);
$totalSalesStmt->execute();

$totalSales = $totalSalesStmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

/*
=====================================
TOTAL ORDERS
=====================================
*/

$totalOrdersQuery = "
    SELECT COUNT(*) AS total_orders
    FROM sales_data
";

$totalOrdersStmt = $conn->prepare($totalOrdersQuery);
$totalOrdersStmt->execute();

$totalOrders = $totalOrdersStmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

/*
=====================================
AVERAGE ORDER
=====================================
*/

$avgQuery = "
    SELECT ISNULL(AVG(total_amount),0) AS avg_order
    FROM sales_data
";

$avgStmt = $conn->prepare($avgQuery);
$avgStmt->execute();

$avgOrder = round(
    $avgStmt->fetch(PDO::FETCH_ASSOC)['avg_order'],
    2
);

/*
=====================================
BEST PLATFORM
=====================================
*/

$platformQuery = "
    SELECT TOP 1
        platform_group,
        SUM(total_amount) AS total
    FROM sales_data
    GROUP BY platform_group
    ORDER BY total DESC
";

$platformStmt = $conn->prepare($platformQuery);
$platformStmt->execute();

$bestPlatform = $platformStmt->fetch(PDO::FETCH_ASSOC);

/*
=====================================
CHART - PLATFORM SALES
=====================================
*/

$chartQuery = "
    SELECT
        platform_group,
        SUM(total_amount) AS total
    FROM sales_data
    GROUP BY platform_group
";

$chartStmt = $conn->prepare($chartQuery);
$chartStmt->execute();

$platformLabels = [];
$platformTotals = [];

while ($row = $chartStmt->fetch(PDO::FETCH_ASSOC)) {

    $platformLabels[] = $row['platform_group'];

    $platformTotals[] = (float)$row['total'];
}

/*
=====================================
CHART - TOP STORES
=====================================
*/

$storesQuery = "
    SELECT TOP 10
        store,
        SUM(total_amount) AS total
    FROM sales_data
    GROUP BY store
    ORDER BY total DESC
";

$storesStmt = $conn->prepare($storesQuery);
$storesStmt->execute();

$storeLabels = [];
$storeTotals = [];

while ($row = $storesStmt->fetch(PDO::FETCH_ASSOC)) {

    $storeLabels[] = $row['store'];

    $storeTotals[] = (float)$row['total'];
}

/*
=====================================
SALES TREND
=====================================
*/

$trendQuery = "
    SELECT
        order_date,
        SUM(total_amount) AS total
    FROM sales_data
    GROUP BY order_date
    ORDER BY order_date
";

$trendStmt = $conn->prepare($trendQuery);
$trendStmt->execute();

$trendDates = [];
$trendTotals = [];

while ($row = $trendStmt->fetch(PDO::FETCH_ASSOC)) {

    $trendDates[] = date(
        'Y-m-d',
        strtotime($row['order_date'])
    );

    $trendTotals[] = (float)$row['total'];
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>Sales Analytics Dashboard</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet"
>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>

body{
    background:#f4f7fc;
    font-family:'Cairo',sans-serif;
}

.dashboard-title{
    font-size:34px;
    font-weight:bold;
    margin-bottom:30px;
}

.card-box{
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    transition:0.3s;
}

.card-box:hover{
    transform:translateY(-5px);
}

.card-icon{
    font-size:35px;
    margin-bottom:15px;
}

.card-title{
    color:#888;
    font-size:15px;
}

.card-value{
    font-size:32px;
    font-weight:bold;
}

.chart-box{
    background:white;
    padding:20px;
    border-radius:20px;
    margin-top:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.filter-box{
    background:white;
    padding:20px;
    border-radius:20px;
    margin-bottom:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.table th{
    background:#f8f9fa;
}

</style>

</head>

<body>

<div class="container-fluid p-4">

    <div class="dashboard-title">
        Sales Analytics Dashboard
    </div>

    <!-- FILTERS -->

    <div class="filter-box">

        <div class="row g-3">

            <div class="col-md-3">

                <label class="form-label">
                    من تاريخ
                </label>

                <input
                    type="date"
                    id="from_date"
                    class="form-control"
                >

            </div>

            <div class="col-md-3">

                <label class="form-label">
                    إلى تاريخ
                </label>

                <input
                    type="date"
                    id="to_date"
                    class="form-control"
                >

            </div>

            <div class="col-md-3">

                <label class="form-label">
                    الفرع
                </label>

                <select
                    id="store_filter"
                    class="form-select"
                >

                    <option value="">
                        كل الفروع
                    </option>

                    <?php

                    $storesQuery = "
                        SELECT DISTINCT store
                        FROM sales_data
                        ORDER BY store
                    ";

                    $storesStmt = $conn->prepare($storesQuery);
                    $storesStmt->execute();

                    while($store = $storesStmt->fetch(PDO::FETCH_ASSOC)){

                        echo '
                            <option value="'.$store['store'].'">
                                '.$store['store'].'
                            </option>
                        ';
                    }

                    ?>

                </select>

            </div>

            <div class="col-md-3">

                <label class="form-label">
                    المنصة
                </label>

                <select
                    id="platform_filter"
                    class="form-select"
                >

                    <option value="">
                        كل المنصات
                    </option>

                    <?php

                    $platformsQuery = "
                        SELECT DISTINCT platform_group
                        FROM sales_data
                        ORDER BY platform_group
                    ";

                    $platformsStmt = $conn->prepare($platformsQuery);
                    $platformsStmt->execute();

                    while($platform = $platformsStmt->fetch(PDO::FETCH_ASSOC)){

                        echo '
                            <option value="'.$platform['platform_group'].'">
                                '.$platform['platform_group'].'
                            </option>
                        ';
                    }

                    ?>

                </select>

            </div>

        </div>

    </div>

    <!-- CARDS -->

    <div class="row g-4">

        <div class="col-md-3">

            <div class="card-box">

                <div class="card-icon text-success">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>

                <div class="card-title">
                    إجمالي المبيعات
                </div>

                <div class="card-value" id="total_sales">
                    <?= number_format($totalSales,2) ?>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card-box">

                <div class="card-icon text-primary">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>

                <div class="card-title">
                    عدد الأوردرات
                </div>

                <div class="card-value" id="total_orders">
                    <?= number_format($totalOrders) ?>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card-box">

                <div class="card-icon text-warning">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div class="card-title">
                    متوسط الأوردر
                </div>

                <div class="card-value" id="avg_order">
                    <?= number_format($avgOrder,2) ?>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="card-box">

                <div class="card-icon text-danger">
                    <i class="fa-solid fa-trophy"></i>
                </div>

                <div class="card-title">
                    أفضل منصة
                </div>

                <div
                    class="card-value"
                    id="best_platform"
                    style="font-size:24px"
                >
                    <?= $bestPlatform['platform_group'] ?? '-' ?>
                </div>

            </div>

        </div>

    </div>

    <!-- CHARTS -->

    <div class="row mt-4 g-4">

        <div class="col-lg-6">

            <div class="chart-box">

                <h4 class="mb-4">
                    المبيعات حسب المنصة
                </h4>

                <div id="platformChart"></div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="chart-box">

                <h4 class="mb-4">
                    أعلى الفروع مبيعًا
                </h4>

                <div id="storesChart"></div>

            </div>

        </div>

    </div>

    <!-- TREND -->

    <div class="row mt-4">

        <div class="col-12">

            <div class="chart-box">

                <h4 class="mb-4">
                    حركة المبيعات
                </h4>

                <div id="trendChart"></div>

            </div>

        </div>

    </div>

    <!-- EMPLOYEES -->

    <div class="row mt-4">

        <div class="col-12">

            <div class="chart-box">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h4>
                        Top Employees Ranking
                    </h4>

                    <span class="badge bg-primary">
                        Live Ranking
                    </span>

                </div>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>

                            <tr>

                                <th>#</th>
                                <th>الموظف</th>
                                <th>عدد الأوردرات</th>
                                <th>إجمالي المبيعات</th>
                                <th>متوسط الأوردر</th>

                            </tr>

                        </thead>

                        <tbody id="employeesTable">

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

let platformChart;
let storesChart;
let trendChart;

/*
=====================================
INIT CHARTS
=====================================
*/

function initCharts(data){

    /*
    PLATFORM CHART
    */

    platformChart = new ApexCharts(
        document.querySelector("#platformChart"),
        {

            series:data.platform_totals,

            chart:{
                type:'donut',
                height:350
            },

            labels:data.platform_labels
        }
    );

    platformChart.render();

    /*
    STORES CHART
    */

    storesChart = new ApexCharts(
        document.querySelector("#storesChart"),
        {

            series:[{
                name:'Sales',
                data:data.store_totals
            }],

            chart:{
                type:'bar',
                height:350
            },

            xaxis:{
                categories:data.store_labels
            }
        }
    );

    storesChart.render();

    /*
    TREND CHART
    */

    trendChart = new ApexCharts(
        document.querySelector("#trendChart"),
        {

            series:[{
                name:'Sales',
                data:data.trend_totals
            }],

            chart:{
                type:'line',
                height:400
            },

            stroke:{
                curve:'smooth'
            },

            xaxis:{
                categories:data.trend_dates
            }
        }
    );

    trendChart.render();
}

/*
=====================================
UPDATE CHARTS
=====================================
*/

function updateCharts(data){

    platformChart.updateOptions({

        labels:data.platform_labels

    });

    platformChart.updateSeries(
        data.platform_totals
    );

    storesChart.updateOptions({

        xaxis:{
            categories:data.store_labels
        }

    });

    storesChart.updateSeries([{

        data:data.store_totals

    }]);

    trendChart.updateOptions({

        xaxis:{
            categories:data.trend_dates
        }

    });

    trendChart.updateSeries([{

        data:data.trend_totals

    }]);
}

/*
=====================================
LOAD DASHBOARD
=====================================
*/

function loadDashboardData(){

    $.ajax({

        url:'ajax/filter_dashboard.php',

        type:'POST',

        data:{
            from_date:$('#from_date').val(),
            to_date:$('#to_date').val(),
            store:$('#store_filter').val(),
            platform:$('#platform_filter').val()
        },

        success:function(response){

            let data = JSON.parse(response);

            /*
            UPDATE CARDS
            */

            $('#total_sales').html(
                data.total_sales
            );

            $('#total_orders').html(
                data.total_orders
            );

            $('#avg_order').html(
                data.avg_order
            );

            $('#best_platform').html(
                data.best_platform
            );

            /*
            EMPLOYEES TABLE
            */

            let table = '';

            data.employees.forEach((emp,index)=>{

                table += `

                    <tr>

                        <td>${index + 1}</td>

                        <td>${emp.employee}</td>

                        <td>${emp.orders}</td>

                        <td>${emp.sales}</td>

                        <td>${emp.avg}</td>

                    </tr>
                `;
            });

            $('#employeesTable').html(table);

            /*
            UPDATE CHARTS
            */

            if(!platformChart){

                initCharts(data);

            }else{

                updateCharts(data);
            }
        }
    });
}

/*
=====================================
FILTER CHANGE
=====================================
*/

$('#from_date,#to_date,#store_filter,#platform_filter')
.on('change',function(){

    loadDashboardData();

});

/*
=====================================
FIRST LOAD
=====================================
*/

loadDashboardData();

</script>

</body>
</html>