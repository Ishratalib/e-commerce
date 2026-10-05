<?php

$pageTitle = "Orders";

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth($pdo);
$auth->requireAdmin();

$stripeConfig = require '../../config/stripe.php';

\Stripe\Stripe::setApiKey(
    $stripeConfig['secret_key']
);


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function normalizeStatus($value)
{
    return strtolower(trim((string) $value));
}


/*
|--------------------------------------------------------------------------
| UPDATE ORDER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = normalizeStatus($_POST['value'] ?? '');

    if ($orderId > 0) {

        /*
        |--------------------------------------------------------------------------
        | FETCH CURRENT ORDER
        |--------------------------------------------------------------------------
        */

        $currentStmt = $pdo->prepare("
            SELECT
                id,
                payment_method,
                payment_status,
                order_status,
                transaction_id
            FROM orders
            WHERE id = ?
            LIMIT 1
        ");

        $currentStmt->execute([$orderId]);

        $currentOrder = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if ($currentOrder) {

            $paymentMethod = normalizeStatus(
                $currentOrder['payment_method']
            );

            $currentPaymentStatus = normalizeStatus(
                $currentOrder['payment_status']
            );

            $currentOrderStatus = normalizeStatus(
                $currentOrder['order_status']
            );

            $transactionId = trim(
                (string) ($currentOrder['transaction_id'] ?? '')
            );


            /*
            |--------------------------------------------------------------------------
            | FINAL ORDER STATES
            |--------------------------------------------------------------------------
            */

            $finalOrderStates = [
                'delivered',
                'cancelled'
            ];


            if (!in_array($currentOrderStatus, $finalOrderStates, true)) {


                /*
                |--------------------------------------------------------------------------
                | PAYMENT STATUS UPDATE
                |--------------------------------------------------------------------------
                */

                if ($field === 'payment_status') {

                    $allowedPaymentStatuses = [
                        'pending',
                        'paid'
                    ];


                    if (
                        in_array($value, $allowedPaymentStatuses, true)
                    ) {


                        /*
                        |--------------------------------------------------------------------------
                        | STRIPE PAYMENT
                        |--------------------------------------------------------------------------
                        |
                        | Stripe payment is controlled by Stripe.
                        | Admin should not manually change Stripe payment status.
                        |
                        */

                        if ($paymentMethod === 'stripe') {

                            // Do nothing for Stripe payment status.


                        /*
                        |--------------------------------------------------------------------------
                        | COD PAYMENT PAID
                        |--------------------------------------------------------------------------
                        */

                        } elseif (
                            $paymentMethod === 'cod'
                            && $value === 'paid'
                        ) {

                            $stmt = $pdo->prepare("
                                UPDATE orders
                                SET
                                    payment_status = 'paid',
                                    order_status = 'delivered'
                                WHERE id = ?
                            ");

                            $stmt->execute([
                                $orderId
                            ]);


                        /*
                        |--------------------------------------------------------------------------
                        | COD PAYMENT PENDING
                        |--------------------------------------------------------------------------
                        */

                        } elseif (
                            $paymentMethod === 'cod'
                            && $value === 'pending'
                        ) {

                            if ($currentOrderStatus === 'processing') {

                                $stmt = $pdo->prepare("
                                    UPDATE orders
                                    SET payment_status = 'pending'
                                    WHERE id = ?
                                ");

                                $stmt->execute([
                                    $orderId
                                ]);
                            }
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | ORDER STATUS UPDATE
                |--------------------------------------------------------------------------
                */

                if ($field === 'order_status') {


                    /*
                    |--------------------------------------------------------------------------
                    | PROCESSING
                    |--------------------------------------------------------------------------
                    */

                    if ($currentOrderStatus === 'processing') {


                        /*
                        | PROCESSING -> SHIPPED
                        */

                        if ($value === 'shipped') {

                            $stmt = $pdo->prepare("
                                UPDATE orders
                                SET order_status = 'shipped'
                                WHERE id = ?
                            ");

                            $stmt->execute([
                                $orderId
                            ]);


                        /*
                        | PROCESSING -> CANCELLED
                        */

                        } elseif ($value === 'cancelled') {


                            /*
                            |--------------------------------------------------------------------------
                            | STRIPE REFUND
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $paymentMethod === 'stripe'
                                && $currentPaymentStatus === 'paid'
                            ) {

                                if ($transactionId === '') {

                                    $_SESSION['order_update_error'] =
                                        'Stripe transaction ID is missing. Refund cannot be processed.';

                                } else {

                                    try {

                                        $refund = \Stripe\Refund::create([
                                            'payment_intent' => $transactionId
                                        ]);


                                        if ($refund->status === 'succeeded') {

                                            $stmt = $pdo->prepare("
                                                UPDATE orders
                                                SET
                                                    order_status = 'cancelled',
                                                    payment_status = 'cancelled'
                                                WHERE id = ?
                                            ");

                                            $stmt->execute([
                                                $orderId
                                            ]);

                                        } else {

                                            $_SESSION['order_update_error'] =
                                                'Stripe refund could not be completed.';
                                        }

                                    } catch (\Stripe\Exception\ApiErrorException $e) {

                                        $_SESSION['order_update_error'] =
                                            'Stripe refund failed: ' .
                                            $e->getMessage();
                                    }
                                }


                            /*
                            |--------------------------------------------------------------------------
                            | COD / OTHER PAYMENT CANCEL
                            |--------------------------------------------------------------------------
                            */

                            } else {

                                $stmt = $pdo->prepare("
                                    UPDATE orders
                                    SET order_status = 'cancelled'
                                    WHERE id = ?
                                ");

                                $stmt->execute([
                                    $orderId
                                ]);
                            }
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SHIPPED
                    |--------------------------------------------------------------------------
                    */

                    elseif ($currentOrderStatus === 'shipped') {


                        /*
                        | SHIPPED -> DELIVERED
                        */

                        if ($value === 'delivered') {


                            /*
                            |--------------------------------------------------------------------------
                            | COD DELIVERED
                            |--------------------------------------------------------------------------
                            */

                            if ($paymentMethod === 'cod') {

                                $stmt = $pdo->prepare("
                                    UPDATE orders
                                    SET
                                        order_status = 'delivered',
                                        payment_status = 'paid'
                                    WHERE id = ?
                                ");


                            } else {

                                $stmt = $pdo->prepare("
                                    UPDATE orders
                                    SET order_status = 'delivered'
                                    WHERE id = ?
                                ");
                            }


                            $stmt->execute([
                                $orderId
                            ]);


                        /*
                        | SHIPPED -> CANCELLED
                        */

                        } elseif ($value === 'cancelled') {


                            /*
                            |--------------------------------------------------------------------------
                            | STRIPE REFUND
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $paymentMethod === 'stripe'
                                && $currentPaymentStatus === 'paid'
                            ) {

                                if ($transactionId === '') {

                                    $_SESSION['order_update_error'] =
                                        'Stripe transaction ID is missing. Refund cannot be processed.';

                                } else {

                                    try {

                                        $refund = \Stripe\Refund::create([
                                            'payment_intent' => $transactionId
                                        ]);


                                        if ($refund->status === 'succeeded') {

                                            $stmt = $pdo->prepare("
                                                UPDATE orders
                                                SET
                                                    order_status = 'cancelled',
                                                    payment_status = 'cancelled'
                                                WHERE id = ?
                                            ");

                                            $stmt->execute([
                                                $orderId
                                            ]);

                                        } else {

                                            $_SESSION['order_update_error'] =
                                                'Stripe refund could not be completed.';
                                        }

                                    } catch (\Stripe\Exception\ApiErrorException $e) {

                                        $_SESSION['order_update_error'] =
                                            'Stripe refund failed: ' .
                                            $e->getMessage();
                                    }
                                }


                            /*
                            |--------------------------------------------------------------------------
                            | COD / OTHER PAYMENT CANCEL
                            |--------------------------------------------------------------------------
                            */

                            } else {

                                $stmt = $pdo->prepare("
                                    UPDATE orders
                                    SET order_status = 'cancelled'
                                    WHERE id = ?
                                ");

                                $stmt->execute([
                                    $orderId
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PREVENT FORM RESUBMISSION
    |--------------------------------------------------------------------------
    */

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH ORDERS
|--------------------------------------------------------------------------
*/

$orderStmt = $pdo->query("
    SELECT
        id,
        order_number,
        first_name,
        last_name,
        email,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    ORDER BY created_at DESC
");

$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">

    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <title>Orders - ClothWear Admin</title>


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


    <style>

        /*
        |--------------------------------------------------------------------------
        | DROPDOWNS
        |--------------------------------------------------------------------------
        */

        .order-status-form,
        .payment-status-form {
            margin: 0;
        }


        .order-status-select,
        .payment-status-select {

            min-width: 125px;

            border: none;

            border-radius: 6px;

            padding: 6px 30px 6px 10px;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            outline: none;

        }


        .payment-status-select {
            min-width: 115px;
        }


        .order-status-select:focus,
        .payment-status-select:focus {
            box-shadow: none;
        }


        /*
        |--------------------------------------------------------------------------
        | PAYMENT COLORS
        |--------------------------------------------------------------------------
        */

        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }


        .payment-paid {
            background: #d1e7dd;
            color: #0f5132;
        }


        /*
        |--------------------------------------------------------------------------
        | ORDER COLORS
        |--------------------------------------------------------------------------
        */

        .status-processing {
            background: #cff4fc;
            color: #055160;
        }


        .status-shipped {
            background: #cfe2ff;
            color: #084298;
        }


        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }


        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }


        /*
        |--------------------------------------------------------------------------
        | FINAL STATUS BADGES
        |--------------------------------------------------------------------------
        */

        .final-status-badge {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 6px;

            font-size: 12px;

            font-weight: 600;

        }


        @media (max-width: 768px) {

            .order-status-select,
            .payment-status-select {
                min-width: 105px;
            }

        }

    </style>

</head>


<body class="g-sidenav-show bg-gray-100">


    <!-- ADMIN HEADER -->

    <?php require_once '../../includes/admin-header.php'; ?>


    <!-- ADMIN SIDEBAR -->

    <?php require_once '../../includes/admin-sidenavbar.php'; ?>


    <!-- MAIN CONTENT -->

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">


        <div class="container-fluid py-4">


            <!-- PAGE HEADING -->

            <div class="row">

                <div class="col-12">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <div>

                            <h4 class="font-weight-bolder mb-0">
                                Orders Management
                            </h4>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ORDERS TABLE -->

            <div class="row">

                <div class="col-12">

                    <div class="card mb-4">


                        <!-- CARD HEADER -->

                        <div class="card-header pb-0">

                            <div class="d-flex justify-content-between align-items-center">

                                <h6>
                                    Orders
                                </h6>

                                <span class="text-sm text-secondary">

                                    <?php echo count($orders); ?>
                                    order(s)

                                </span>

                            </div>

                        </div>


                        <!-- TABLE -->

                        <div class="card-body px-0 pt-0 pb-2">

                            <div class="table-responsive p-0">

                                <table class="table align-items-center mb-0">


                                    <thead>

                                        <tr>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Order
                                            </th>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                                Customer
                                            </th>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Total
                                            </th>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Payment
                                            </th>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Order Status
                                            </th>

                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Date
                                            </th>

                                            <th class="text-secondary opacity-7">
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php if (!empty($orders)): ?>


                                            <?php foreach ($orders as $order): ?>


                                                <?php

                                                $customerName = trim(
                                                    $order['first_name'] . ' ' . $order['last_name']
                                                );

                                                $paymentMethod = normalizeStatus(
                                                    $order['payment_method']
                                                );

                                                $paymentStatus = normalizeStatus(
                                                    $order['payment_status']
                                                );

                                                $orderStatus = normalizeStatus(
                                                    $order['order_status']
                                                );

                                                ?>


                                                <tr>


                                                    <!-- ORDER -->

                                                    <td>

                                                        <div class="d-flex px-3 py-1">

                                                            <div class="d-flex flex-column justify-content-center">

                                                                <h6 class="mb-0 text-sm">

                                                                    #

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $order['order_number']
                                                                    );
                                                                    ?>

                                                                </h6>

                                                            </div>

                                                        </div>

                                                    </td>


                                                    <!-- CUSTOMER -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $customerName
                                                            );
                                                            ?>

                                                        </p>

                                                        <p class="text-xs text-secondary mb-0">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $order['email']
                                                            );
                                                            ?>

                                                        </p>

                                                    </td>


                                                    <!-- TOTAL -->

                                                    <td>

                                                        <p class="text-sm font-weight-bold mb-0">

                                                            Rs.
                                                            <?php
                                                            echo number_format(
                                                                (float) $order['total_amount'],
                                                                2
                                                            );
                                                            ?>

                                                        </p>

                                                    </td>


                                                    <!-- PAYMENT -->

                                                    <td>

                                                        <?php if ($orderStatus === 'cancelled'): ?>

                                                            <span class="final-status-badge payment-paid">

                                                                <?php
                                                                echo ucfirst(
                                                                    $paymentStatus ?: 'Cancelled'
                                                                );
                                                                ?>

                                                            </span>


                                                        <?php elseif ($orderStatus === 'delivered'): ?>

                                                            <span class="final-status-badge payment-paid">

                                                                <?php
                                                                echo ucfirst(
                                                                    $paymentStatus ?: 'Paid'
                                                                );
                                                                ?>

                                                            </span>


                                                        <?php elseif ($paymentMethod === 'stripe'): ?>

                                                            <span class="final-status-badge
                                                                <?php
                                                                echo $paymentStatus === 'paid'
                                                                    ? 'payment-paid'
                                                                    : 'payment-pending';
                                                                ?>">

                                                                <?php
                                                                echo ucfirst(
                                                                    $paymentStatus ?: 'Pending'
                                                                );
                                                                ?>

                                                            </span>


                                                        <?php else: ?>

                                                            <form method="POST" class="payment-status-form">

                                                                <input
                                                                    type="hidden"
                                                                    name="order_id"
                                                                    value="<?php echo (int) $order['id']; ?>"
                                                                >

                                                                <input
                                                                    type="hidden"
                                                                    name="field"
                                                                    value="payment_status"
                                                                >


                                                                <select
                                                                    name="value"
                                                                    class="payment-status-select
                                                                        <?php
                                                                        echo $paymentStatus === 'paid'
                                                                            ? 'payment-paid'
                                                                            : 'payment-pending';
                                                                        ?>"
                                                                    onchange="this.form.submit()"
                                                                >


                                                                    <option
                                                                        value="pending"
                                                                        <?php
                                                                        echo $paymentStatus === 'pending'
                                                                            ? 'selected'
                                                                            : '';
                                                                        ?>
                                                                    >
                                                                        Pending
                                                                    </option>


                                                                    <option
                                                                        value="paid"
                                                                        <?php
                                                                        echo $paymentStatus === 'paid'
                                                                            ? 'selected'
                                                                            : '';
                                                                        ?>
                                                                    >
                                                                        Paid
                                                                    </option>


                                                                </select>

                                                            </form>

                                                        <?php endif; ?>

                                                    </td>


                                                    <!-- ORDER STATUS -->

                                                    <td>


                                                        <?php if ($orderStatus === 'delivered'): ?>

                                                            <span class="final-status-badge status-delivered">
                                                                Delivered
                                                            </span>


                                                        <?php elseif ($orderStatus === 'cancelled'): ?>

                                                            <span class="final-status-badge status-cancelled">
                                                                Cancelled
                                                            </span>


                                                        <?php elseif ($orderStatus === 'processing'): ?>

                                                            <form
                                                                method="POST"
                                                                class="order-status-form"
                                                            >

                                                                <input
                                                                    type="hidden"
                                                                    name="order_id"
                                                                    value="<?php echo (int) $order['id']; ?>"
                                                                >

                                                                <input
                                                                    type="hidden"
                                                                    name="field"
                                                                    value="order_status"
                                                                >


                                                                <select
                                                                    name="value"
                                                                    class="order-status-select status-processing"
                                                                    onchange="this.form.submit()"
                                                                >


                                                                    <option
                                                                        value="processing"
                                                                        selected
                                                                    >
                                                                        Processing
                                                                    </option>


                                                                    <option value="shipped">
                                                                        Shipped
                                                                    </option>


                                                                    <option value="cancelled">
                                                                        Cancelled
                                                                    </option>


                                                                </select>

                                                            </form>


                                                        <?php elseif ($orderStatus === 'shipped'): ?>

                                                            <form
                                                                method="POST"
                                                                class="order-status-form"
                                                            >

                                                                <input
                                                                    type="hidden"
                                                                    name="order_id"
                                                                    value="<?php echo (int) $order['id']; ?>"
                                                                >

                                                                <input
                                                                    type="hidden"
                                                                    name="field"
                                                                    value="order_status"
                                                                >


                                                                <select
                                                                    name="value"
                                                                    class="order-status-select status-shipped"
                                                                    onchange="this.form.submit()"
                                                                >


                                                                    <option
                                                                        value="shipped"
                                                                        selected
                                                                    >
                                                                        Shipped
                                                                    </option>


                                                                    <option value="delivered">
                                                                        Delivered
                                                                    </option>


                                                                    <option value="cancelled">
                                                                        Cancelled
                                                                    </option>


                                                                </select>

                                                            </form>

                                                        <?php endif; ?>


                                                    </td>


                                                    <!-- DATE -->

                                                    <td>

                                                        <span class="text-secondary text-xs font-weight-bold">

                                                            <?php

                                                            echo date(
                                                                'd M Y',
                                                                strtotime($order['created_at'])
                                                            );

                                                            ?>

                                                        </span>

                                                    </td>


                                                    <!-- VIEW -->

                                                    <td class="align-middle">

                                                        <a
                                                            href="detail.php?id=<?php echo (int) $order['id']; ?>"
                                                            class="btn btn-link text-secondary mb-0"
                                                            title="View Order"
                                                        >

                                                            <i class="fa fa-eye text-xs"></i>

                                                        </a>

                                                    </td>


                                                </tr>


                                            <?php endforeach; ?>


                                        <?php else: ?>


                                            <tr>

                                                <td
                                                    colspan="7"
                                                    class="text-center py-5"
                                                >

                                                    <p class="text-secondary mb-0">

                                                        No orders found.

                                                    </p>

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


            <!-- ADMIN FOOTER -->

            <?php require_once '../../includes/admin-footer.php'; ?>


        </div>

    </main>


    <!-- SCRIPTS -->

    <script src="../assets/js/core/popper.min.js"></script>

    <script src="../assets/js/core/bootstrap.min.js"></script>

    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>

    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>

    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>


</body>

</html>