<?php

require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

/* =========================
   GET ORDER ID
========================= */

$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header('Location: /public/index.php');
    exit;
}

/* =========================
   FETCH ORDER
   Only logged-in user's order
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
        created_at
    FROM orders
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$orderStmt->execute([
    $orderId,
    $_SESSION['user_id']
]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: /public/index.php');
    exit;
}

/* =========================
   CONTINUE SHOPPING
   KEEP SAME ORDER
========================= */

if (
    isset($_GET['continue_shopping']) &&
    (int) $_GET['continue_shopping'] === 1
) {
    $_SESSION['active_order_id'] = $orderId;

    header('Location: /public/products.php');
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
        p.image
    FROM order_items oi
    LEFT JOIN products p
        ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$itemStmt->execute([$orderId]);

$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   CALCULATE ORDER SUBTOTAL
========================= */

$orderSubtotal = 0;

foreach ($orderItems as $item) {
    $orderSubtotal += (float) $item['subtotal'];
}

/* =========================
   GET TOTAL FROM ORDER
========================= */

$totalAmount = (float) $order['total_amount'];

/* =========================
   CALCULATE SHIPPING
========================= */

/*
   Shipping was added into total_amount during checkout.

   Example:
   Subtotal = 1000
   Shipping = 10
   Total    = 1010

   Therefore:
   Shipping = Total - Subtotal
*/

$shippingAmount = $totalAmount - $orderSubtotal;

/* Prevent negative shipping */
if ($shippingAmount < 0) {
    $shippingAmount = 0;
}

/* =========================
   SHIPPING LABEL
========================= */

if ($shippingAmount <= 0) {

    $shippingLabel = 'Free Shipping';

} elseif (abs($shippingAmount - 10) < 0.01) {

    $shippingLabel = 'Standard Shipping';

} elseif (abs($shippingAmount - 20) < 0.01) {

    $shippingLabel = 'Express Shipping';

} else {

    $shippingLabel = 'Shipping';
}

/* =========================
   FORMAT DATA
========================= */

$orderDate = date(
    'M d, Y',
    strtotime($order['created_at'])
);

$paymentMethod = 'Cash on Delivery';

if ($order['payment_method'] === 'stripe') {
    $paymentMethod = 'Stripe';
}

$fullName = trim(
    $order['first_name'] . ' ' . $order['last_name']
);

$billingCityState = trim(
    $order['city'] . ', ' . $order['state']
);

$shippingAddress = $order['address'];

if (!empty($order['address_2'])) {
    $shippingAddress .= ', ' . $order['address_2'];
}

$shippingCityState = trim(
    $order['city'] . ', ' . $order['state']
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    
    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Order Confirmation - Molla</title>

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Demo 4 CSS -->
    <link rel="stylesheet" href="assets/css/demos/demo-4.css">

    <!-- Icons -->
    <link rel="stylesheet" href="assets/css/plugins/animate/animate.min.css">

    <link rel="stylesheet" href="assets/css/plugins/owl-carousel/owl.carousel.css">
    

</head>

<body>

    <div class="page-wrapper">

    
        <!-- ================= HEADER INCLUDE START ================= -->
        <?php include '../includes/header.php'; ?>
        <!-- ================= HEADER INCLUDE END =================== -->


        <main class="main">

            <!-- =========================
         PAGE HEADER
    ========================== -->

            <div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">

                <div class="container">

                    <h1 class="page-title">

                        Order Confirmation

                        <span>Shop</span>

                    </h1>

                </div>

            </div>


            <!-- =========================
         BREADCRUMB
    ========================== -->

            <nav aria-label="breadcrumb" class="breadcrumb-nav">

                <div class="container">

                    <ol class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php">
                                Home
                            </a>

                        </li>

                        <li class="breadcrumb-item">

                            <a href="products.php">
                                Shop
                            </a>

                        </li>

                        <li class="breadcrumb-item active" aria-current="page">

                            Order Confirmation

                        </li>

                    </ol>

                </div>

            </nav>


            <div class="page-content">

                <div class="cart">

                    <div class="container">

                        <div class="row">


                            <!-- =========================
                         LEFT SIDE
                    ========================== -->

                            <div class="col-lg-9">


                                <!-- =========================
                             SUCCESS MESSAGE
                        ========================== -->

                                <div class="text-center p-4 mb-4"
                                    style="background-color: #f9f9f9; border-radius: 4px; border: 1px solid #e0e0e0;">

                                    <div class="mb-2">

                                        <i class="icon-check" style="font-size: 40px; color: #c96;">
                                        </i>

                                    </div>

                                    <h3 class="title mb-1" style="font-size: 22px;">

                                        Thank You For Your Order!

                                    </h3>

                                    <p class="mb-0 text-muted">

                                        Your order has been placed and is currently being processed.

                                    </p>

                                </div>


                                <!-- =========================
                             ORDER ITEMS
                        ========================== -->

                                <table class="table table-cart table-mobile">

                                    <thead>

                                        <tr>

                                            <th>
                                                Product
                                            </th>

                                            <th>
                                                Price
                                            </th>

                                            <th>
                                                Quantity
                                            </th>

                                            <th>
                                                Total
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php if (!empty($orderItems)): ?>

                                            <?php foreach ($orderItems as $item): ?>

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

        // Old single-image products
        $itemImages = [$item['image']];
    }
}

$itemImage = !empty($itemImages)
    ? 'uploads/products/' . $itemImages[0]
    : 'assets/images/products/table/product-1.jpg';

?>

                                                <tr>


                                                    <!-- PRODUCT -->

                                                    <td class="product-col">

                                                        <div class="product">

                                                            <figure class="product-media">

                                                                <a
                                                                    href="product-detail.php?id=<?php echo (int) $item['product_id']; ?>">

                                                                    <img src="<?php echo htmlspecialchars($itemImage); ?>"
                                                                        alt="<?php echo htmlspecialchars($item['name'] ?? 'Product'); ?>">

                                                                </a>

                                                            </figure>


                                                            <h3 class="product-title">

                                                                <a
                                                                    href="product-detail.php?id=<?php echo (int) $item['product_id']; ?>">

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $item['name'] ?? 'Product'
                                                                    );
                                                                    ?>

                                                                </a>

                                                            </h3>

                                                        </div>

                                                    </td>


                                                    <!-- PRICE -->

                                                    <td class="price-col">

                                                        Rs <?php

                                                        echo number_format(
                                                            (float) $item['unit_price'],
                                                            2
                                                        );

                                                        ?>

                                                    </td>


                                                    <!-- QUANTITY -->

                                                    <td class="quantity-col">

                                                        <?php
                                                        echo (int) $item['quantity'];
                                                        ?>

                                                    </td>


                                                    <!-- ITEM TOTAL -->

                                                    <td class="total-col">

                                                        Rs <?php

                                                        echo number_format(
                                                            (float) $item['subtotal'],
                                                            2
                                                        );

                                                        ?>

                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <tr>

                                                <td colspan="4" class="text-center">

                                                    No order items found.

                                                </td>

                                            </tr>

                                        <?php endif; ?>

                                    </tbody>

                                </table>


                                <!-- =========================
                             CUSTOMER DETAILS
                        ========================== -->

                                <div class="row mt-4 mb-4">


                                    <!-- BILLING ADDRESS -->

                                    <div class="col-sm-6 mb-3 mb-sm-0">

                                        <div class="card card-dashboard">

                                            <div class="card-body">

                                                <h3 class="card-title"
                                                    style="font-size: 16px; border-bottom: 1px solid #ebebeb; padding-bottom: 10px;">

                                                    Billing Address

                                                </h3>


                                                <p class="mb-1">

                                                    <strong>

                                                        <?php
                                                        echo htmlspecialchars($fullName);
                                                        ?>

                                                    </strong>

                                                </p>


                                                <?php if (!empty($order['company_name'])): ?>

                                                    <p class="mb-1">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $order['company_name']
                                                        );
                                                        ?>

                                                    </p>

                                                <?php endif; ?>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['address']
                                                    );
                                                    ?>

                                                </p>


                                                <?php if (!empty($order['address_2'])): ?>

                                                    <p class="mb-1">

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $order['address_2']
                                                        );
                                                        ?>

                                                    </p>

                                                <?php endif; ?>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $billingCityState
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['country']
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['postcode']
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['phone']
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-0">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['email']
                                                    );
                                                    ?>

                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- SHIPPING ADDRESS -->

                                    <div class="col-sm-6">

                                        <div class="card card-dashboard">

                                            <div class="card-body">

                                                <h3 class="card-title"
                                                    style="font-size: 16px; border-bottom: 1px solid #ebebeb; padding-bottom: 10px;">

                                                    Shipping Address

                                                </h3>


                                                <p class="mb-1">

                                                    <strong>

                                                        <?php
                                                        echo htmlspecialchars($fullName);
                                                        ?>

                                                    </strong>

                                                </p>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $shippingAddress
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $shippingCityState
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-1">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['country']
                                                    );
                                                    ?>

                                                </p>


                                                <p class="mb-0">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['phone']
                                                    );
                                                    ?>

                                                </p>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =========================
                         RIGHT SIDE
                    ========================== -->

                            <aside class="col-lg-3">

                                <div class="summary summary-cart">


                                    <h3 class="summary-title">

                                        Order Summary

                                    </h3>


                                    <table class="table table-summary">

                                        <tbody>


                                            <!-- ORDER ID -->

                                            <tr class="summary-subtotal">

                                                <td>
                                                    Order ID:
                                                </td>

                                                <td class="text-right">

                                                    #

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $order['order_number']
                                                    );
                                                    ?>

                                                </td>

                                            </tr>


                                            <!-- DATE -->

                                            <tr class="summary-subtotal">

                                                <td>
                                                    Date:
                                                </td>

                                                <td class="text-right">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $orderDate
                                                    );
                                                    ?>

                                                </td>

                                            </tr>


                                            <!-- SUBTOTAL -->

                                            <tr class="summary-subtotal">

                                                <td>
                                                    Subtotal:
                                                </td>

                                                <td class="text-right">

                                                    Rs <?php

                                                    echo number_format(
                                                        $orderSubtotal,
                                                        2
                                                    );

                                                    ?>

                                                </td>

                                            </tr>


                                            <!-- SHIPPING -->

                                            <tr class="summary-shipping">

                                                <td>
                                                    Shipping:
                                                </td>

                                                <td class="text-right">

                                                    <?php if ($shippingAmount <= 0): ?>

                                                        Free Shipping

                                                    <?php else: ?>

                                                        <?php echo htmlspecialchars($shippingLabel); ?>

                                                        <br>

                                                        Rs <?php

                                                        echo number_format(
                                                            $shippingAmount,
                                                            2
                                                        );

                                                        ?>

                                                    <?php endif; ?>

                                                </td>

                                            </tr>


                                            <!-- PAYMENT -->

                                            <tr class="summary-shipping">

                                                <td>
                                                    Payment:
                                                </td>

                                                <td class="text-right">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $paymentMethod
                                                    );
                                                    ?>

                                                </td>

                                            </tr>


                                            <!-- STATUS -->

                                            <tr class="summary-shipping">

                                                <td>
                                                    Status:
                                                </td>

                                                <td class="text-right">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        ucfirst(
                                                            $order['order_status']
                                                        )
                                                    );
                                                    ?>

                                                </td>

                                            </tr>


                                            <!-- TOTAL -->

                                            <tr class="summary-total">

                                                <td>
                                                    Total:
                                                </td>

                                                <td class="text-right">

                                                    Rs <?php

                                                    echo number_format(
                                                        $totalAmount,
                                                        2
                                                    );

                                                    ?>

                                                </td>

                                            </tr>

                                        </tbody>

                                    </table>


                                    <!-- CONTINUE SHOPPING -->

                                    <a href="order-confirmation.php?order_id=<?php echo (int) $orderId; ?>&continue_shopping=1"
                                        class="btn btn-outline-primary-2 btn-order btn-block">

                                        CONTINUE SHOPPING

                                    </a>

                                </div>


                                <!-- BACK TO HOME -->

                                <a href="index.php" class="btn btn-outline-dark-2 btn-block mb-3">

                                    <span>
                                        BACK TO HOME
                                    </span>

                                    <i class="icon-refresh"></i>

                                </a>

                            </aside>

                        </div>

                    </div>

                </div>

        </main>


        <!-- ================= FOOTER INCLUDE START ================= -->
        <?php include '../includes/footer.php'; ?>
        <!-- ================= FOOTER INCLUDE END =================== -->

    </div>


    <!-- SCROLL TOP -->

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

    <script src="assets/js/main.js"></script>


</body>

</html>