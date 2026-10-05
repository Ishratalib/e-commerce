<?php

$pageTitle = "Order Details";

require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Mailer.php';

$auth = new Auth($pdo);
$auth->requireAdmin();


/* =========================
   GET ORDER ID
========================= */

$orderId = (int) ($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}


/* =========================
   FETCH ORDER
========================= */

$orderStmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        first_name,
        last_name,
        company_name,
        country,
        address,
        address_2,
        city,
        state,
        postcode,
        phone,
        email,
        order_notes,
        order_number,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        transaction_id,
        created_at
    FROM orders
    WHERE id = ?
    LIMIT 1
");

$orderStmt->execute([$orderId]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {
    header('Location: index.php');
    exit;
}


/* =========================
   FETCH ORDER ITEMS
========================= */

$itemStmt = $pdo->prepare("
    SELECT
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        p.name,
        p.image,
        c.name AS category_name
    FROM order_items oi
    LEFT JOIN products p
        ON oi.product_id = p.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$itemStmt->execute([$orderId]);

$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   ORDER DATA
========================= */

$customerName = trim(
    $order['first_name'] . ' ' . $order['last_name']
);

$orderNumber = '#' . $order['order_number'];

$orderDate = date(
    'd M Y',
    strtotime($order['created_at'])
);


/* =========================
   PAYMENT METHOD
========================= */

$paymentMethod = ucfirst(
    $order['payment_method']
);

if ($order['payment_method'] === 'cod') {
    $paymentMethod = 'Cash on Delivery';
}

if ($order['payment_method'] === 'paypal') {
    $paymentMethod = 'PayPal';
}


/* =========================
   PAYMENT / ORDER STATUS
========================= */

$paymentStatus = ucfirst(
    $order['payment_status']
);

$orderStatus = ucfirst(
    $order['order_status']
);


/* =========================
   ADDRESS
========================= */

$shippingAddress = $order['address'];

if (!empty($order['address_2'])) {

    $shippingAddress .= ', ' .
        $order['address_2'];
}

$shippingAddress .= ', ' .
    $order['city'];

$shippingAddress .= ', ' .
    $order['state'];

$shippingAddress .= ', ' .
    $order['postcode'];

$shippingAddress .= ', ' .
    $order['country'];


/* =========================
   TOTALS
========================= */

$grandTotal = (float) $order['total_amount'];

$shipping = 0.00;

$discount = 0.00;

$subtotal = $grandTotal;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">

    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <title>
        Order Details - ClothWear Admin
    </title>


    <!-- Fonts -->

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900">


    <!-- Nucleo Icons -->

    <link href="../assets/css/nucleo-icons.css" rel="stylesheet">

    <link href="../assets/css/nucleo-svg.css" rel="stylesheet">


    <!-- Font Awesome -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Material Icons -->

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">


    <!-- Material Dashboard CSS -->

    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet">


    <!-- =========================
         CUSTOM ORDER DETAIL CSS
    ========================== -->

    <style>
        .order-card {
            border-radius: 16px;
            overflow: hidden;
        }


        .order-card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e9ecef;
        }


        .order-card-header h5 {
            font-weight: 700;
            color: #212529;
        }


        .order-id-info {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            margin-top: 5px;

            padding: 5px 10px;

            border-radius: 6px;

            background-color: #f8f9fa;

            color: #8392ab;

            font-size: 11px;

            font-weight: 600;

        }


        .order-info-box {

            padding: 20px;

            border: 1px solid #e9ecef;

            border-radius: 12px;

            height: 100%;

        }


        .order-info-box h6 {

            font-size: 14px;

            font-weight: 700;

            color: #344767;

            margin-bottom: 15px;

        }


        .info-label {

            display: block;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            color: #8392ab;

            margin-bottom: 4px;

        }


        .info-value {

            font-size: 14px;

            font-weight: 600;

            color: #344767;

            margin-bottom: 14px;

        }


        .order-status-badge {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 6px;

            font-size: 11px;

            font-weight: 700;

        }


        .status-success {

            background-color: #d1e7dd;

            color: #146c43;

        }


        .status-warning {

            background-color: #fff3cd;

            color: #997404;

        }


        .status-info {

            background-color: #cff4fc;

            color: #087990;

        }


        .status-danger {

            background-color: #f8d7da;

            color: #b02a37;

        }


        .order-items-table th {

            font-size: 11px;

            text-transform: uppercase;

            color: #8392ab;

            font-weight: 700;

            border-bottom: 1px solid #e9ecef;

        }


        .order-items-table td {

            padding: 16px 12px;

            vertical-align: middle;

            border-bottom: 1px solid #f0f2f5;

        }


        .product-name {

            font-size: 14px;

            font-weight: 700;

            color: #344767;

            margin-bottom: 3px;

        }


        .product-category {

            font-size: 11px;

            color: #8392ab;

        }


        .order-summary {

            margin-left: auto;

            max-width: 350px;

            padding: 20px;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 12px;

            font-size: 14px;

            color: #67748e;

        }


        .summary-total {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding-top: 15px;

            margin-top: 10px;

            border-top: 1px solid #e9ecef;

            font-size: 16px;

            font-weight: 700;

            color: #212529;

        }


        .order-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            padding-top: 20px;

            margin-top: 10px;

            border-top: 1px solid #e9ecef;

        }


        @media (max-width: 768px) {

            .order-card-header {
                padding: 20px;
            }


            .order-info-box {
                margin-bottom: 15px;
            }


            .order-summary {
                max-width: 100%;
            }


            .order-actions {
                flex-direction: column;
            }


            .order-actions .btn {
                width: 100%;
            }

        }
    </style>

</head>


<body class="g-sidenav-show bg-gray-100">


    <!-- =========================
         ADMIN HEADER
    ========================== -->

    <?php require_once '../../includes/admin-header.php'; ?>


    <!-- =========================
         ADMIN SIDEBAR
    ========================== -->

    <?php require_once '../../includes/admin-sidenavbar.php'; ?>


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">


        <div class="container-fluid py-4">


            <!-- PAGE HEADING -->

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

                <div>

                    <h4 class="fw-bold text-dark mb-1">
                        Order Details
                    </h4>

                    <p class="text-sm text-secondary mb-0">
                        View complete information about this order
                    </p>

                </div>


                <a href="index.php" class="btn btn-outline-secondary btn-sm">

                    <i class="fa-solid fa-arrow-left me-1"></i>

                    Back to Orders

                </a>

            </div>


            <!-- ORDER CARD -->

            <div class="card order-card mb-4">


                <!-- CARD HEADER -->

                <div class="order-card-header">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">


                        <div>

                            <h5 class="mb-1">

                                Order

                                <?php
                                echo htmlspecialchars($orderNumber);
                                ?>

                            </h5>


                            <p class="text-sm text-secondary mb-0">

                                Placed on

                                <?php
                                echo htmlspecialchars($orderDate);
                                ?>

                            </p>


                            <span class="order-id-info">

                                <i class="fa-solid fa-hashtag"></i>

                                Order ID:

                                <?php
                                echo (int) $order['id'];
                                ?>

                            </span>

                        </div>


                        <!-- CURRENT ORDER STATUS -->

                        <div class="d-flex align-items-center flex-wrap justify-content-end gap-2">


                            <?php if ($order['order_status'] === 'delivered'): ?>

                                <span class="order-status-badge status-success">
                                    Delivered
                                </span>


                            <?php elseif ($order['order_status'] === 'processing'): ?>

                                <span class="order-status-badge status-info">
                                    Processing
                                </span>


                            <?php elseif ($order['order_status'] === 'cancelled'): ?>

                                <span class="order-status-badge status-danger">
                                    Cancelled
                                </span>


                            <?php elseif ($order['order_status'] === 'shipped'): ?>

                                <span class="order-status-badge status-warning">
                                    Shipped
                                </span>


                            <?php else: ?>

                                <span class="order-status-badge status-warning">

                                    <?php
                                    echo htmlspecialchars($orderStatus);
                                    ?>

                                </span>

                            <?php endif; ?>


                        </div>

                    </div>

                </div>


                <!-- CARD BODY -->

                <div class="card-body p-4">


                    <!-- CUSTOMER + SHIPPING + PAYMENT -->

                    <div class="row mb-4">


                        <!-- CUSTOMER -->

                        <div class="col-lg-4 col-md-6 mb-3">

                            <div class="order-info-box">


                                <h6>

                                    <i class="fa-solid fa-user me-2"></i>

                                    Customer Information

                                </h6>


                                <span class="info-label">
                                    Name
                                </span>


                                <div class="info-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $customerName
                                    );
                                    ?>

                                </div>


                                <span class="info-label">
                                    Email
                                </span>


                                <div class="info-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $order['email']
                                    );
                                    ?>

                                </div>


                                <span class="info-label">
                                    Phone
                                </span>


                                <div class="info-value mb-0">

                                    <?php
                                    echo htmlspecialchars(
                                        $order['phone']
                                    );
                                    ?>

                                </div>


                            </div>

                        </div>


                        <!-- SHIPPING -->

                        <div class="col-lg-4 col-md-6 mb-3">

                            <div class="order-info-box">


                                <h6>

                                    <i class="fa-solid fa-location-dot me-2"></i>

                                    Shipping Information

                                </h6>


                                <span class="info-label">
                                    Shipping Address
                                </span>


                                <div class="info-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $shippingAddress
                                    );
                                    ?>

                                </div>


                                <span class="info-label">
                                    Delivery Status
                                </span>


                                <div class="info-value mb-0">


                                    <?php if ($order['order_status'] === 'delivered'): ?>

                                        <span class="order-status-badge status-success">
                                            Delivered
                                        </span>


                                    <?php elseif ($order['order_status'] === 'processing'): ?>

                                        <span class="order-status-badge status-info">
                                            Processing
                                        </span>


                                    <?php elseif ($order['order_status'] === 'cancelled'): ?>

                                        <span class="order-status-badge status-danger">
                                            Cancelled
                                        </span>


                                    <?php elseif ($order['order_status'] === 'shipped'): ?>

                                        <span class="order-status-badge status-warning">
                                            Shipped
                                        </span>


                                    <?php else: ?>

                                        <span class="order-status-badge status-warning">

                                            <?php
                                            echo htmlspecialchars(
                                                $orderStatus
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>


                        <!-- PAYMENT -->

                        <div class="col-lg-4 col-md-12 mb-3">

                            <div class="order-info-box">


                                <h6>

                                    <i class="fa-solid fa-credit-card me-2"></i>

                                    Payment Information

                                </h6>


                                <span class="info-label">
                                    Payment Method
                                </span>


                                <div class="info-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $paymentMethod
                                    );
                                    ?>

                                </div>


                                <span class="info-label">
                                    Payment Status
                                </span>


                                <div class="info-value mb-0">


                                    <?php if ($order['payment_status'] === 'paid'): ?>

                                        <span class="order-status-badge status-success">
                                            Paid
                                        </span>


                                    <?php elseif ($order['payment_status'] === 'pending'): ?>

                                        <span class="order-status-badge status-warning">
                                            Pending
                                        </span>


                                    <?php else: ?>

                                        <span class="order-status-badge status-danger">

                                            <?php
                                            echo htmlspecialchars(
                                                $paymentStatus
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ORDER ITEMS -->

                    <div class="mb-4">


                        <h6 class="fw-bold mb-3">
                            Order Items
                        </h6>


                        <div class="table-responsive">


                            <table class="table align-items-center mb-0 order-items-table">


                                <thead>

                                    <tr>

                                        <th>
                                            Product
                                        </th>

                                        <th class="text-center">
                                            Quantity
                                        </th>

                                        <th class="text-end">
                                            Price
                                        </th>

                                        <th class="text-end">
                                            Total
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php if (!empty($orderItems)): ?>


                                        <?php foreach ($orderItems as $item): ?>


                                            <tr>


                                                <!-- PRODUCT -->

                                                <td>

                                                    <div class="product-name">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $item['name'] ?? 'Product unavailable'
                                                        );
                                                        ?>

                                                    </div>


                                                    <div class="product-category">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $item['category_name'] ?? 'N/A'
                                                        );
                                                        ?>

                                                    </div>

                                                </td>


                                                <!-- QUANTITY -->

                                                <td class="text-center">

                                                    <span class="text-sm font-weight-bold">

                                                        <?php
                                                        echo (int) $item['quantity'];
                                                        ?>

                                                    </span>

                                                </td>


                                                <!-- PRICE -->

                                                <td class="text-end">

                                                    <span class="text-sm font-weight-bold">

                                                        $

                                                        <?php
                                                        echo number_format(
                                                            (float) $item['unit_price'],
                                                            2
                                                        );
                                                        ?>

                                                    </span>

                                                </td>


                                                <!-- TOTAL -->

                                                <td class="text-end">

                                                    <span class="text-sm font-weight-bold">

                                                        $

                                                        <?php
                                                        echo number_format(
                                                            (float) $item['subtotal'],
                                                            2
                                                        );
                                                        ?>

                                                    </span>

                                                </td>


                                            </tr>


                                        <?php endforeach; ?>


                                    <?php else: ?>


                                        <tr>

                                            <td colspan="4" class="text-center py-4">

                                                No order items found.

                                            </td>

                                        </tr>


                                    <?php endif; ?>


                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ORDER NOTES -->

                    <?php if (!empty($order['order_notes'])): ?>


                        <div class="order-info-box mb-4">


                            <h6>

                                <i class="fa-solid fa-note-sticky me-2"></i>

                                Order Notes

                            </h6>


                            <div class="info-value mb-0">

                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $order['order_notes']
                                    )
                                );

                                ?>

                            </div>


                        </div>


                    <?php endif; ?>


                    <!-- ORDER SUMMARY -->

                    <div class="order-summary">


                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>


                            <strong>

                                $

                                <?php
                                echo number_format(
                                    $subtotal,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Shipping
                            </span>


                            <strong>

                                $

                                <?php
                                echo number_format(
                                    $shipping,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Discount
                            </span>


                            <strong>

                                -$

                                <?php
                                echo number_format(
                                    $discount,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="summary-total">

                            <span>
                                Grand Total
                            </span>


                            <strong>

                                $

                                <?php
                                echo number_format(
                                    $grandTotal,
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div class="order-actions">


                        <a href="index.php" class="btn btn-light">

                            <i class="fa-solid fa-arrow-left me-1"></i>

                            Back to Orders

                        </a>


                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">

                            <i class="fa-solid fa-print me-1"></i>

                            Print Order

                        </button>


                    </div>


                </div>

            </div>


            <!-- ADMIN FOOTER -->

            <?php require_once '../../includes/admin-footer.php'; ?>


        </div>

    </main>


    <!-- Popper -->

    <script src="../assets/js/core/popper.min.js"></script>


    <!-- Bootstrap -->

    <script src="../assets/js/core/bootstrap.min.js"></script>


    <!-- Perfect Scrollbar -->

    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>


    <!-- Smooth Scrollbar -->

    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>


    <!-- Material Dashboard -->

    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>


</body>

</html>