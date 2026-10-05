<?php

$pageTitle = "Reports";

require_once '../config/database.php';

function getCount($pdo, $query)
{
    $result = $pdo->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| SUMMARY DATA
|--------------------------------------------------------------------------
*/

/* Total Users */

$totalUsers = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'customer'"
);


/* Total Categories */

$totalCategories = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM categories"
);


/* Total Products */

$totalProducts = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM products"
);


/* Total Orders */

$totalOrders = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM orders"
);


/*
|--------------------------------------------------------------------------
| ORDER STATUS
|--------------------------------------------------------------------------
*/

/* Delivered Orders */

$deliveredOrders = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE LOWER(TRIM(order_status)) = 'delivered'"
);


/* Processing Orders */

$processingOrders = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE LOWER(TRIM(order_status)) = 'processing'"
);


/* Cancelled Orders */

$cancelledOrders = getCount(
    $pdo,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE LOWER(TRIM(order_status)) = 'cancelled'"
);


/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
|
| Sirf DELIVERED orders ki total_amount sales mein include hogi.
| Processing, Shipped aur Cancelled orders include nahi honge.
|
*/

$totalSales = 0;

$salesQuery = "
    SELECT COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders
    WHERE LOWER(TRIM(order_status)) = 'delivered'
";

$salesResult = $pdo->query($salesQuery);

if ($salesResult) {

    $salesRow = $salesResult->fetch(PDO::FETCH_ASSOC);

    $totalSales = (float) ($salesRow['total_sales'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| SALES SUMMARY
|--------------------------------------------------------------------------
|
| Tabs:
| Today   = Today's delivered orders
| Weekly  = Current week + previous 7 weeks
| Monthly = Current month + previous 5 months
| Yearly  = Current year + previous 4 years
|
| Orders = Delivered orders count
| Sales  = Delivered orders total_amount
|
*/

$period = $_GET['period'] ?? 'today';

if (!in_array($period, ['today', 'weekly', 'monthly', 'yearly'], true)) {
    $period = 'today';
}

$salesSummary = [];


/*
|--------------------------------------------------------------------------
| TODAY SALES
|--------------------------------------------------------------------------
*/

if ($period === 'today') {

    $todayQuery = "
        SELECT
            DATE(created_at) AS period_key,

            DATE_FORMAT(
                created_at,
                '%d %M %Y'
            ) AS period_name,

            COUNT(*) AS orders,

            COALESCE(
                SUM(total_amount),
                0
            ) AS sales

        FROM orders

        WHERE DATE(created_at) = CURDATE()

        AND LOWER(TRIM(order_status)) = 'delivered'

        GROUP BY DATE(created_at)

        ORDER BY period_key DESC
    ";

    $todayResult = $pdo->query($todayQuery);

    if ($todayResult) {

        while ($row = $todayResult->fetch(PDO::FETCH_ASSOC)) {

            $salesSummary[] = $row;
        }
    }
}


/*
|--------------------------------------------------------------------------
| WEEKLY SALES
|--------------------------------------------------------------------------
|
| Current week + previous 7 weeks
| Week Monday se start hota hai.
|
*/

if ($period === 'weekly') {

    $weeklyQuery = "
        SELECT

            YEARWEEK(created_at, 1) AS period_key,

            CONCAT(

                DATE_FORMAT(
                    DATE_SUB(
                        DATE(created_at),
                        INTERVAL WEEKDAY(created_at) DAY
                    ),
                    '%d %b %Y'
                ),

                ' - ',

                DATE_FORMAT(
                    DATE_ADD(
                        DATE(created_at),
                        INTERVAL (6 - WEEKDAY(created_at)) DAY
                    ),
                    '%d %b %Y'
                )

            ) AS period_name,

            COUNT(*) AS orders,

            COALESCE(
                SUM(total_amount),
                0
            ) AS sales

        FROM orders

        WHERE created_at >= DATE_SUB(
            DATE_SUB(
                CURDATE(),
                INTERVAL WEEKDAY(CURDATE()) DAY
            ),
            INTERVAL 7 WEEK
        )

        AND LOWER(TRIM(order_status)) = 'delivered'

        GROUP BY
            YEARWEEK(created_at, 1),

            DATE_SUB(
                DATE(created_at),
                INTERVAL WEEKDAY(created_at) DAY
            )

        ORDER BY period_key DESC
    ";

    $weeklyResult = $pdo->query($weeklyQuery);

    if ($weeklyResult) {

        while ($row = $weeklyResult->fetch(PDO::FETCH_ASSOC)) {

            $salesSummary[] = $row;
        }
    }
}


/*
|--------------------------------------------------------------------------
| MONTHLY SALES
|--------------------------------------------------------------------------
|
| Current month + previous 5 months
|
*/

if ($period === 'monthly') {

    $monthlyQuery = "
        SELECT

            DATE_FORMAT(
                created_at,
                '%Y-%m'
            ) AS period_key,

            DATE_FORMAT(
                created_at,
                '%M %Y'
            ) AS period_name,

            COUNT(*) AS orders,

            COALESCE(
                SUM(total_amount),
                0
            ) AS sales

        FROM orders

        WHERE created_at >= DATE_SUB(
            DATE_FORMAT(
                CURDATE(),
                '%Y-%m-01'
            ),
            INTERVAL 5 MONTH
        )

        AND LOWER(TRIM(order_status)) = 'delivered'

        GROUP BY
            DATE_FORMAT(
                created_at,
                '%Y-%m'
            ),

            DATE_FORMAT(
                created_at,
                '%M %Y'
            )

        ORDER BY period_key DESC
    ";

    $monthlyResult = $pdo->query($monthlyQuery);

    if ($monthlyResult) {

        while ($row = $monthlyResult->fetch(PDO::FETCH_ASSOC)) {

            $salesSummary[] = $row;
        }
    }
}


/*
|--------------------------------------------------------------------------
| YEARLY SALES
|--------------------------------------------------------------------------
|
| Current year + previous 4 years
|
*/

if ($period === 'yearly') {

    $yearlyQuery = "
        SELECT

            YEAR(created_at) AS period_key,

            YEAR(created_at) AS period_name,

            COUNT(*) AS orders,

            COALESCE(
                SUM(total_amount),
                0
            ) AS sales

        FROM orders

        WHERE created_at >= DATE_SUB(
            DATE_FORMAT(
                CURDATE(),
                '%Y-01-01'
            ),
            INTERVAL 4 YEAR
        )

        AND LOWER(TRIM(order_status)) = 'delivered'

        GROUP BY YEAR(created_at)

        ORDER BY period_key DESC
    ";

    $yearlyResult = $pdo->query($yearlyQuery);

    if ($yearlyResult) {

        while ($row = $yearlyResult->fetch(PDO::FETCH_ASSOC)) {

            $salesSummary[] = $row;
        }
    }
}


/*
|--------------------------------------------------------------------------
| MOST SOLD PRODUCTS
|--------------------------------------------------------------------------
|
| Sirf DELIVERED orders ke products count honge.
|
| Product jis quantity mein sab se zyada sell hua hai
| woh list mein sab se upar show hoga.
|
*/

$mostSoldProducts = [];

$mostSoldProductsQuery = "
    SELECT

        p.id,
        p.name,
        p.price,

        c.name AS category_name,

        COALESCE(
            SUM(oi.quantity),
            0
        ) AS units_sold

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    INNER JOIN products p
        ON p.id = oi.product_id

    LEFT JOIN categories c
        ON c.id = p.category_id

    WHERE LOWER(TRIM(o.order_status)) = 'delivered'

    GROUP BY
        p.id,
        p.name,
        p.price,
        c.name

    ORDER BY
        units_sold DESC,
        p.name ASC
";

$mostSoldProductsResult = $pdo->query($mostSoldProductsQuery);

if ($mostSoldProductsResult) {

    while ($row = $mostSoldProductsResult->fetch(PDO::FETCH_ASSOC)) {

        $mostSoldProducts[] = $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">

    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <title>
        Reports - ClothWear Admin
    </title>


    <!-- Fonts -->

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900">


    <!-- Nucleo Icons -->

    <link href="assets/css/nucleo-icons.css" rel="stylesheet">

    <link href="assets/css/nucleo-svg.css" rel="stylesheet">


    <!-- Font Awesome -->

    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous">
    </script>


    <!-- Material Icons -->

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">


    <!-- Material Dashboard -->

    <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet">


    <style>
        /*
        |--------------------------------------------------------------------------
        | Scrollbar Fix
        |--------------------------------------------------------------------------
        */

        html,
        body {
            min-height: 100%;
            height: auto;
            overflow-x: hidden;
            overflow-y: auto;
        }


        .main-content {
            min-height: 100vh !important;
            height: auto !important;
            max-height: none !important;
        }


        .container-fluid {
            min-height: auto;
        }


        /*
        |--------------------------------------------------------------------------
        | Report Icons
        |--------------------------------------------------------------------------
        */

        .report-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
        }


        .report-icon .material-symbols-rounded {
            font-size: 23px;
            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | Sales Summary Tabs
        |--------------------------------------------------------------------------
        */

        .sales-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }


        .sales-tab {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            color: #67748e;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }


        .sales-tab:hover {
            color: #344767;
            background: #e9ecef;
        }


        .sales-tab.active {
            color: #fff;
            background: #344767;
        }


        /*
        |--------------------------------------------------------------------------
        | Most Sold Products
        |--------------------------------------------------------------------------
        */

        .product-rank {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            font-size: 13px;
            font-weight: 700;
        }
    </style>

</head>


<body class="g-sidenav-show bg-gray-100">


    <!-- Admin Header -->

    <?php require_once '../includes/admin-header.php'; ?>


    <!-- Admin Sidebar -->

    <?php require_once '../includes/admin-sidenavbar.php'; ?>


    <!-- Main Content -->

    <main class="main-content position-relative border-radius-lg">

        <div class="container-fluid py-4">


            <!-- ==============================
                 PAGE HEADER
            =============================== -->

            <div class="row">

                <div class="col-12">

                    <div class="mb-4">

                        <h4 class="font-weight-bolder mb-1">
                            Analytics & Reports
                        </h4>

                        <p class="text-sm text-secondary mb-0">
                            Overview of your ClothWear store performance
                        </p>

                    </div>

                </div>

            </div>


            <!-- ==============================
                 MAIN SUMMARY CARDS
            =============================== -->

            <div class="row">


                <!-- TOTAL SALES -->

                <div class="col-xl-3 col-sm-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="row">

                                <div class="col-8">

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Total Sales
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        Rs<?= number_format($totalSales, 2) ?>
                                    </h5>

                                </div>


                                <div class="col-4 text-end">

                                    <div class="report-icon">

                                        <span class="material-symbols-rounded">
                                            payments
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TOTAL ORDERS -->

                <div class="col-xl-3 col-sm-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="row">

                                <div class="col-8">

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Total Orders
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($totalOrders) ?>
                                    </h5>

                                </div>


                                <div class="col-4 text-end">

                                    <div class="report-icon">

                                        <span class="material-symbols-rounded">
                                            shopping_cart
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TOTAL USERS -->

                <div class="col-xl-3 col-sm-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="row">

                                <div class="col-8">

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Total Users
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($totalUsers) ?>
                                    </h5>

                                </div>


                                <div class="col-4 text-end">

                                    <div class="report-icon">

                                        <span class="material-symbols-rounded">
                                            group
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TOTAL PRODUCTS -->

                <div class="col-xl-3 col-sm-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="row">

                                <div class="col-8">

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Total Products
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($totalProducts) ?>
                                    </h5>

                                </div>


                                <div class="col-4 text-end">

                                    <div class="report-icon">

                                        <span class="material-symbols-rounded">
                                            inventory_2
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==============================
                 ORDER STATUS CARDS
            =============================== -->

            <div class="row">


                <!-- DELIVERED -->

                <div class="col-xl-3 col-md-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Delivered Orders
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($deliveredOrders) ?>
                                    </h5>

                                </div>

                                <i class="material-symbols-rounded text-success">
                                    check_circle
                                </i>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PROCESSING -->

                <div class="col-xl-3 col-md-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Processing Orders
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($processingOrders) ?>
                                    </h5>

                                </div>

                                <i class="material-symbols-rounded text-warning">
                                    pending
                                </i>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CANCELLED -->

                <div class="col-xl-3 col-md-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Cancelled Orders
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($cancelledOrders) ?>
                                    </h5>

                                </div>

                                <i class="material-symbols-rounded text-danger">
                                    cancel
                                </i>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CATEGORIES -->

                <div class="col-xl-3 col-md-6 mb-4">

                    <div class="card">

                        <div class="card-body p-3">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">
                                        Categories
                                    </p>

                                    <h5 class="font-weight-bolder mb-0">
                                        <?= number_format($totalCategories) ?>
                                    </h5>

                                </div>

                                <i class="material-symbols-rounded text-info">
                                    category
                                </i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==============================
                 SALES SUMMARY
            =============================== -->

            <div class="row">

                <div class="col-12">

                    <div class="card mb-4">


                        <div class="card-header pb-0">

                            <h6 class="mb-1">
                                Sales Summary
                            </h6>

                            <p class="text-sm text-secondary mb-3">
                                Sales overview and Order Summary
                            </p>


                            <!-- SALES TABS -->

                            <div class="sales-tabs">

                                <a href="?period=today" class="sales-tab <?= $period === 'today' ? 'active' : '' ?>">
                                    Today
                                </a>


                                <a href="?period=weekly" class="sales-tab <?= $period === 'weekly' ? 'active' : '' ?>">
                                    Weekly
                                </a>


                                <a href="?period=monthly"
                                    class="sales-tab <?= $period === 'monthly' ? 'active' : '' ?>">
                                    Monthly
                                </a>


                                <a href="?period=yearly" class="sales-tab <?= $period === 'yearly' ? 'active' : '' ?>">
                                    Yearly
                                </a>

                            </div>

                        </div>


                        <div class="card-body px-0 pt-0 pb-2">

                            <div class="table-responsive p-0">

                                <table class="table align-items-center mb-0">


                                    <thead>

                                        <tr>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">

                                                <?= $period === 'today'
                                                    ? 'Day'
                                                    : ($period === 'weekly'
                                                        ? 'Week'
                                                        : ($period === 'monthly'
                                                            ? 'Month'
                                                            : 'Year')) ?>

                                            </th>


                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">

                                                Orders

                                            </th>


                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">

                                                Sales

                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php if (!empty($salesSummary)): ?>

                                            <?php foreach ($salesSummary as $periodData): ?>

                                                <tr>

                                                    <!-- PERIOD -->

                                                    <td>

                                                        <div class="d-flex px-3 py-1">

                                                            <div class="d-flex flex-column justify-content-center">

                                                                <h6 class="mb-0 text-sm">

                                                                    <?= htmlspecialchars(
                                                                        $periodData['period_name']
                                                                    ) ?>

                                                                </h6>

                                                            </div>

                                                        </div>

                                                    </td>


                                                    <!-- ORDERS -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            <?= number_format(
                                                                (int) ($periodData['orders'] ?? 0)
                                                            ) ?>

                                                        </p>

                                                    </td>


                                                    <!-- SALES -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            Rs <?= number_format(
                                                                (float) ($periodData['sales'] ?? 0),
                                                                2
                                                            ) ?>

                                                        </p>

                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>


                                        <?php else: ?>

                                            <tr>

                                                <td colspan="3">

                                                    <div class="text-center py-4">

                                                        <p class="text-sm text-secondary mb-0">

                                                            No delivered orders available.

                                                        </p>

                                                    </div>

                                                </td>

                                            </tr>

                                        <?php endif; ?>

                                    </tbody>


                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==============================
                 MOST SOLD PRODUCTS
            =============================== -->

            <div class="row">

                <div class="col-12">

                    <div class="card mb-4">


                        <!-- SECTION HEADER -->

                        <div class="card-header pb-0">

                            <h6 class="mb-1">
                                Most Sold Products
                                        </h6>

                        </div>


                        <!-- PRODUCT TABLE -->

                        <div class="card-body px-0 pt-0 pb-2">

                            <div class="table-responsive p-0">

                                <table class="table align-items-center mb-0">


                                    <thead>

                                        <tr>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">
                                                #
                                            </th>


                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Product
                                            </th>


                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Category
                                            </th>


                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Price
                                            </th>


                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Units Sold
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php if (!empty($mostSoldProducts)): ?>

                                            <?php
                                            $rank = 1;
                                            ?>

                                            <?php foreach ($mostSoldProducts as $product): ?>

                                                <tr>


                                                    <!-- RANK -->

                                                    <td>

                                                        <div class="d-flex px-3 py-1">

                                                            <div class="product-rank">

                                                                <?= $rank ?>

                                                            </div>

                                                        </div>

                                                    </td>


                                                    <!-- PRODUCT -->

                                                    <td>

                                                        <div class="d-flex px-3 py-1">

                                                            <div class="d-flex flex-column justify-content-center">

                                                                <h6 class="mb-0 text-sm">

                                                                    <?= htmlspecialchars(
                                                                        $product['name']
                                                                    ) ?>

                                                                </h6>

                                                            </div>

                                                        </div>

                                                    </td>


                                                    <!-- CATEGORY -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            <?= htmlspecialchars(
                                                                $product['category_name']
                                                                ?? 'Uncategorized'
                                                            ) ?>

                                                        </p>

                                                    </td>


                                                    <!-- PRICE -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            Rs <?= number_format(
                                                                (float) ($product['price'] ?? 0),
                                                                2
                                                            ) ?>

                                                        </p>

                                                    </td>


                                                    <!-- UNITS SOLD -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            <?= number_format(
                                                                (int) ($product['units_sold'] ?? 0)
                                                            ) ?>

                                                        </p>

                                                    </td>


                                                </tr>


                                                <?php
                                                $rank++;
                                                ?>

                                            <?php endforeach; ?>


                                        <?php else: ?>

                                            <tr>

                                                <td colspan="5">

                                                    <div class="text-center py-4">

                                                        <p class="text-sm text-secondary mb-0">

                                                            No sold products available.

                                                        </p>

                                                    </div>

                                                </td>

                                            </tr>

                                        <?php endif; ?>

                                    </tbody>


                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Admin Footer -->

            <?php require_once '../includes/admin-footer.php'; ?>


        </div>

    </main>


    <!-- Scripts -->

    <script src="assets/js/core/popper.min.js"></script>

    <script src="assets/js/core/bootstrap.min.js"></script>

    <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>

    <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>

    <script src="assets/js/material-dashboard.min.js?v=3.2.0"></script>


</body>

</html>