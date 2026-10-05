<?php

require_once '../config/database.php';

/* =========================
   START SESSION
========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];


/* =========================
   FETCH USER ORDERS
   LATEST FIRST
========================= */

$orderStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
");

$orderStmt->execute([$userId]);

$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   FETCH ORDER ITEMS
========================= */

$orderItems = [];

if (!empty($orders)) {

    $orderIds = array_column($orders, 'id');

    $placeholders = implode(
        ',',
        array_fill(0, count($orderIds), '?')
    );

    $itemStmt = $pdo->prepare("
        SELECT
            oi.id,
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,
            p.name,
            p.image
        FROM order_items oi
        LEFT JOIN products p
            ON p.id = oi.product_id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.id ASC
    ");

    $itemStmt->execute($orderIds);

    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {

        $orderId = (int) $item['order_id'];

        if (!isset($orderItems[$orderId])) {
            $orderItems[$orderId] = [];
        }

        $orderItems[$orderId][] = $item;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Molla - Bootstrap eCommerce Template</title>

    <meta name="keywords" content="HTML5 Template">

    <meta name="description" content="Molla - Bootstrap eCommerce Template">

    <meta name="author" content="p-themes">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/favicon-16x16.png">

    <link rel="manifest" href="assets/images/icons/site.html">

    <link rel="mask-icon" href="assets/images/icons/safari-pinned-tab.svg" color="#666666">

    <link rel="shortcut icon" href="assets/images/icons/favicon.ico">

    <meta name="apple-mobile-web-app-title" content="Molla">

    <meta name="application-name" content="Molla">

    <meta name="msapplication-TileColor" content="#cc9966">

    <meta name="msapplication-config" content="assets/images/icons/browserconfig.xml">

    <meta name="theme-color" content="#ffffff">


    <!-- Line Awesome -->
    <link rel="stylesheet" href="assets/vendor/line-awesome/line-awesome/line-awesome/css/line-awesome.min.css">


    <!-- Plugins CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <link rel="stylesheet" href="assets/css/plugins/owl-carousel/owl.carousel.css">

    <link rel="stylesheet" href="assets/css/plugins/magnific-popup/magnific-popup.css">

    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">


    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet" href="assets/css/skins/skin-demo-4.css">

    <link rel="stylesheet" href="assets/css/demos/demo-4.css">


    <style>
        /* =========================================================
           MY ORDERS PAGE CUSTOM STYLING
           ========================================================= */

        .my-orders-page {
            padding: 55px 0 80px;
            background: #fff;
        }

        .my-orders-heading {
            margin-bottom: 35px;
        }

        .my-orders-heading h2 {
            font-size: 30px;
            font-weight: 500;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .my-orders-heading p {
            color: #777;
            margin: 0;
            font-size: 14px;
        }

        .my-order-card {
            border: 1px solid #ebebeb;
            background: #fff;
            margin-bottom: 25px;
            transition: box-shadow .2s ease;
        }

        .my-order-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, .06);
        }

        .my-order-header {
            padding: 22px 25px;
            background: #fafafa;
            border-bottom: 1px solid #ebebeb;
        }

        .my-order-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .my-order-number {
            margin: 0 0 5px;
            font-size: 18px;
            font-weight: 500;
            color: #333;
        }

        .my-order-date {
            margin: 0;
            color: #888;
            font-size: 13px;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            padding: 7px 13px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .4px;
            border-radius: 2px;
        }

        .order-status.processing {
            background: #fff4d6;
            color: #9a6b00;
        }

        .order-status.shipped {
            background: #e7f3ff;
            color: #1671b8;
        }

        .order-status.delivered {
            background: #e6f7ed;
            color: #287a4b;
        }

        .order-status.cancelled {
            background: #fde9e9;
            color: #b33a3a;
        }

        .my-order-body {
            padding: 0 25px;
        }

        .my-order-product {
            display: flex;
            align-items: center;
            padding: 22px 0;
            border-bottom: 1px solid #eeeeee;
        }

        .my-order-product:last-child {
            border-bottom: 0;
        }

        .my-order-product-image {
            width: 82px;
            height: 82px;
            flex: 0 0 82px;
            margin-right: 20px;
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .my-order-product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .my-order-product-info {
            flex: 1;
            min-width: 0;
        }

        .my-order-product-name {
            margin: 0 0 7px;
            font-size: 15px;
            font-weight: 500;
            color: #333;
        }

        .my-order-product-name a {
            color: inherit;
        }

        .my-order-product-meta {
            margin: 0;
            color: #888;
            font-size: 13px;
        }

        .my-order-product-price {
            width: 130px;
            text-align: right;
            flex-shrink: 0;
        }

        .my-order-product-price span {
            display: block;
            color: #999;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .my-order-product-price strong {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .my-order-footer {
            padding: 20px 25px;
            border-top: 1px solid #ebebeb;
            background: #fff;
        }

        .my-order-footer-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .my-order-payment {
            color: #777;
            font-size: 13px;
        }

        .my-order-payment strong {
            color: #333;
            font-weight: 500;
        }

        .my-order-total {
            text-align: right;
        }

        .my-order-total-label {
            display: block;
            color: #888;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .my-order-total-price {
            font-size: 19px;
            font-weight: 500;
            color: #333;
        }

        .my-order-actions {
            margin-top: 18px;
            text-align: right;
        }

        .my-order-details-btn {
            min-width: 145px;
        }

        .my-orders-empty {
            text-align: center;
            border: 1px solid #ebebeb;
            padding: 70px 30px;
        }

        .my-orders-empty-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            border: 1px solid #e5e5e5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .my-orders-empty-icon i {
            font-size: 28px;
            color: #777;
        }

        .my-orders-empty h3 {
            font-size: 22px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .my-orders-empty p {
            color: #888;
            margin-bottom: 25px;
        }

        @media (max-width: 767px) {
            .my-orders-page {
                padding: 35px 0 55px;
            }

            .my-order-header-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .my-order-product {
                flex-wrap: wrap;
            }

            .my-order-product-info {
                width: calc(100% - 100px);
            }

            .my-order-product-price {
                width: 100%;
                text-align: left;
                padding-left: 100px;
                margin-top: 10px;
            }

            .my-order-footer-grid {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .my-order-total {
                text-align: left;
            }

            .my-order-actions {
                text-align: left;
            }

            .my-order-details-btn {
                width: 100%;
            }
        }
    </style>

</head>

<body>

    <div class="page-wrapper">

        <?php include '../includes/header.php'; ?>

        <main class="main">

            <div class="page-header text-center">
                <div class="container">
                    <h1 class="page-title">My Orders</h1>
                </div>
            </div>

            <nav class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">My Orders</li>
                    </ol>
                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <div class="my-orders-page">

                        <div class="my-orders-heading">
                            <h2>My Orders</h2>
                            <p>View your recent orders, products and order status.</p>
                        </div>

                        <?php if (empty($orders)): ?>

                            <div class="my-orders-empty">
                                <div class="my-orders-empty-icon">
                                    <i class="icon-shopping-cart"></i>
                                </div>
                                <h3>You haven't placed any orders yet</h3>
                                <p>Start shopping and your orders will appear here.</p>
                                <a href="products.php" class="btn btn-outline-primary-2">
                                    <span>START SHOPPING</span>
                                    <i class="icon-long-arrow-right"></i>
                                </a>
                            </div>

                        <?php else: ?>

                            <?php foreach ($orders as $order): ?>

                                <?php
                                $orderId = (int) $order['id'];
                                $status = strtolower(trim($order['order_status'] ?? 'processing'));
                                if (!in_array($status, ['processing', 'shipped', 'delivered', 'cancelled'], true)) {
                                    $status = 'processing';
                                }
                                ?>

                                <div class="my-order-card">

                                    <div class="my-order-header">
                                        <div class="my-order-header-inner">
                                            <div>
                                                <h3 class="my-order-number">
                                                    Order #<?= htmlspecialchars($order['order_number']); ?>
                                                </h3>
                                                <p class="my-order-date">
                                                    Placed on <?= date('F d, Y', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                            <div>
                                                <span class="order-status <?= $status; ?>">
                                                    <?= ucfirst($status); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="my-order-body">
                                        <?php if (!empty($orderItems[$orderId])): ?>
                                            <?php foreach ($orderItems[$orderId] as $item): ?>
                                                <div class="my-order-product">

                                                    <div class="my-order-product-image">
                                                     <?php

$itemImages = [];

if (!empty($item['image'])) {

    $decodedItemImages = json_decode(
        $item['image'],
        true
    );

    if (
        is_array($decodedItemImages) &&
        !empty($decodedItemImages)
    ) {

        $itemImages = $decodedItemImages;

    } else {

        // Old single-image products/orders
        $itemImages = [$item['image']];
    }
}

$itemImage = !empty($itemImages)
    ? 'uploads/products/' . $itemImages[0]
    : 'assets/images/products/product-1.jpg';

?>

<a href="product-detail.php?id=<?= (int) $item['product_id']; ?>">
    <img
        src="<?= htmlspecialchars($itemImage); ?>"
        alt="<?= htmlspecialchars($item['name'] ?? 'Product'); ?>"
    >
</a>
                                                    </div>

                                                    <div class="my-order-product-info">
                                                        <h4 class="my-order-product-name">
                                                            <a href="product-detail.php?id=<?= (int) $item['product_id']; ?>">
                                                                <?= htmlspecialchars($item['name'] ?? 'Product'); ?>
                                                            </a>
                                                        </h4>
                                                        <p class="my-order-product-meta">
                                                            Quantity: <?= (int) $item['quantity']; ?>
                                                        </p>
                                                    </div>

                                                    <div class="my-order-product-price">
                                                        <span>Price</span>
                                                        <strong>Rs <?= number_format((float) $item['unit_price'], 2); ?></strong>
                                                    </div>

                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="my-order-footer">
                                        <div class="my-order-footer-grid">
                                            <div class="my-order-payment">
                                                <strong>Payment:</strong>
                                                <?= htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?>
                                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                                <strong>Payment Status:</strong>
                                                <?= htmlspecialchars(ucfirst($order['payment_status'] ?? 'N/A')); ?>
                                            </div>

                                            <div class="my-order-total">
                                                <span class="my-order-total-label">Order Total</span>
                                                <span class="my-order-total-price">
                                                    Rs <?= number_format((float) $order['total_amount'], 2); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="my-order-actions">
                                            <a href="order-confirmation.php?order_id=<?= $orderId; ?>"
                                                class="btn btn-outline-primary-2 my-order-details-btn">
                                                <span>VIEW DETAILS</span>
                                                <i class="icon-long-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <button id="scroll-top" title="Back to Top">
        <i class="icon-arrow-up"></i>
    </button>

    <!-- Plugins JS -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/bootstrap-input-spinner.js"></script>
    <script src="assets/js/jquery.plugin.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/jquery.countdown.min.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/demos/demo-4.js"></script>

</body>

</html>