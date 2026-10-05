<?php

require_once '../config/database.php';
require_once '../vendor/autoload.php';
require_once '../core/Mailer.php';
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

$userId = (int) $_SESSION['user_id'];


/* =========================
   STRIPE CONFIG
========================= */

$stripeConfig = require '../config/stripe.php';

\Stripe\Stripe::setApiKey(
    $stripeConfig['secret_key']
);


/* =========================
   GET STRIPE SESSION ID
========================= */

$sessionId = trim(
    $_GET['session_id'] ?? ''
);

if ($sessionId === '') {
    header('Location: /public/checkout.php');
    exit;
}


/* =========================
   GET BILLING DETAILS
========================= */

$billing =
    $_SESSION['stripe_billing_details']
    ?? [];

if (empty($billing)) {

    exit(
        'Billing information is missing.'
    );
}


/* =========================
   RETRIEVE STRIPE SESSION
========================= */

try {

    $stripeSession =
        \Stripe\Checkout\Session::retrieve(
            $sessionId
        );

} catch (\Stripe\Exception\ApiErrorException $e) {

    exit(
        'Stripe error: ' .
        htmlspecialchars($e->getMessage())
    );
}


/* =========================
   VERIFY USER
========================= */

$metadataUserId =
    (int) (
        $stripeSession->metadata->user_id
        ?? 0
    );

if ($metadataUserId !== $userId) {

    exit(
        'Invalid Stripe payment session.'
    );
}


/* =========================
   VERIFY PAYMENT
========================= */

if (
    $stripeSession->payment_status
    !== 'paid'
) {

    header(
        'Location: /public/checkout.php?payment=failed'
    );

    exit;
}


/* =========================
   GET TRANSACTION ID
========================= */

$transactionId =
    (string) (
        $stripeSession->payment_intent
        ?? $stripeSession->id
    );


/* =========================
   PREVENT DUPLICATE ORDER
========================= */

$duplicateStmt = $pdo->prepare("
    SELECT id
    FROM orders
    WHERE transaction_id = ?
    LIMIT 1
");

$duplicateStmt->execute([
    $transactionId
]);

$existingPaidOrder =
    $duplicateStmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($existingPaidOrder) {

    unset($_SESSION['cart']);
    unset($_SESSION['active_order_id']);
    unset($_SESSION['stripe_billing_details']);
    unset($_SESSION['customer_email']);

    header(
        'Location: /public/order-confirmation.php?order_id=' .
        (int) $existingPaidOrder['id']
    );

    exit;
}


/* =========================
   GET CART
========================= */

$cart =
    $_SESSION['cart'] ?? [];

if (empty($cart)) {

    header(
        'Location: /public/cart.php'
    );

    exit;
}


/* =========================
   SHIPPING
========================= */

$shippingOptions = [
    'free' => 0,
    'standard' => 10,
    'express' => 20
];

$selectedShipping =
    $_SESSION['shipping_method']
    ?? 'free';

if (
    !array_key_exists(
        $selectedShipping,
        $shippingOptions
    )
) {

    $selectedShipping = 'free';
}

$shipping =
    $shippingOptions[$selectedShipping];


/* =========================
   CART PRODUCT IDS
========================= */

$cartIds =
    array_keys($cart);

$placeholders =
    implode(
        ',',
        array_fill(
            0,
            count($cartIds),
            '?'
        )
    );


/* =========================
   START TRANSACTION
========================= */

try {

    $pdo->beginTransaction();


    /* =========================
       FETCH PRODUCTS
    ========================= */

    $productStmt = $pdo->prepare("
        SELECT
            id,
            name,
            price,
            stock
        FROM products
        WHERE id IN ($placeholders)
          AND status = 1
        FOR UPDATE
    ");

    $productStmt->execute(
        $cartIds
    );

    $products =
        $productStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    if (empty($products)) {

        throw new Exception(
            'No valid products were found in your cart.'
        );
    }


    /* =========================
       BUILD ORDER ITEMS
    ========================= */

    $orderItems = [];

    $subtotal = 0;


    foreach ($products as $product) {

        $productId =
            (int) $product['id'];

        $quantity =
            (int) (
                $cart[$productId]
                ?? 0
            );

        if ($quantity <= 0) {
            continue;
        }


        /* =========================
           STOCK CHECK
        ========================= */

        if (
            (int) $product['stock']
            < $quantity
        ) {

            throw new Exception(
                'Not enough stock available for ' .
                $product['name'] .
                '.'
            );
        }


        $unitPrice =
            (float) $product['price'];

        $itemSubtotal =
            $unitPrice * $quantity;

        $subtotal +=
            $itemSubtotal;


        $orderItems[] = [

            'product_id' =>
                $productId,

            'quantity' =>
                $quantity,

            'unit_price' =>
                $unitPrice,

            'subtotal' =>
                $itemSubtotal
        ];
    }


    if (empty($orderItems)) {

        throw new Exception(
            'Your cart is empty.'
        );
    }


    /* =========================
       FINAL TOTAL
    ========================= */

    $calculatedTotal =
        $subtotal + $shipping;


    /* =========================
       VERIFY STRIPE AMOUNT
    ========================= */

    $stripeAmount =
        (int) (
            $stripeSession->amount_total
            ?? 0
        );

    $expectedAmount =
        (int) round(
            $calculatedTotal * 100
        );


    if (
        $stripeAmount
        !== $expectedAmount
    ) {

        throw new Exception(
            'Stripe payment amount does not match the order total.'
        );
    }


    /* =========================
       SAVE CUSTOMER DETAILS
    ========================= */

    $customerStmt = $pdo->prepare("
        INSERT INTO customer_details (
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
            email
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            company_name = VALUES(company_name),
            country = VALUES(country),
            address = VALUES(address),
            address_2 = VALUES(address_2),
            city = VALUES(city),
            state = VALUES(state),
            postcode = VALUES(postcode),
            phone = VALUES(phone),
            email = VALUES(email)
    ");

    $customerStmt->execute([

        $userId,

        $billing['first_name'] ?? '',
        $billing['last_name'] ?? '',
        $billing['company_name'] ?? '',
        $billing['country'] ?? '',
        $billing['address'] ?? '',
        $billing['address_2'] ?? '',
        $billing['city'] ?? '',
        $billing['state'] ?? '',
        $billing['postcode'] ?? '',
        $billing['phone'] ?? '',
        $billing['email'] ?? ''
    ]);


    /* =========================
       CHECK ACTIVE ORDER
    ========================= */

    $activeOrderId =
        (int) (
            $_SESSION['active_order_id']
            ?? 0
        );

    $existingOrder = null;


    if ($activeOrderId > 0) {

        $existingOrderStmt = $pdo->prepare("
          SELECT
    id,
    order_number,
    total_amount
FROM orders
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $existingOrderStmt->execute([

            $activeOrderId,
            $userId
        ]);

        $existingOrder =
            $existingOrderStmt->fetch(
                PDO::FETCH_ASSOC
            );
    }


    /* =================================================
       EXISTING ORDER
    ================================================= */

    if ($existingOrder) {

        $orderId =
            (int) $existingOrder['id'];

        $orderNumber =
            $existingOrder['order_number'];

        $oldOrderTotal =
            (float) $existingOrder['total_amount'];

        $newOrderTotal =
            $oldOrderTotal +
            $calculatedTotal;


        /* =========================
           UPDATE EXISTING ORDER
        ========================= */

        $updateOrderStmt = $pdo->prepare("
            UPDATE orders
            SET
                first_name = ?,
                last_name = ?,
                company_name = ?,
                country = ?,
                address = ?,
                address_2 = ?,
                city = ?,
                state = ?,
                postcode = ?,
                phone = ?,
                email = ?,
                order_notes = ?,
                total_amount = ?,
                payment_method = ?,
                payment_status = ?,
                order_status = ?,
                transaction_id = ?
            WHERE id = ?
              AND user_id = ?
        ");

        $updateOrderStmt->execute([

            $billing['first_name'] ?? '',
            $billing['last_name'] ?? '',
            $billing['company_name'] ?? '',
            $billing['country'] ?? '',
            $billing['address'] ?? '',
            $billing['address_2'] ?? '',
            $billing['city'] ?? '',
            $billing['state'] ?? '',
            $billing['postcode'] ?? '',
            $billing['phone'] ?? '',
            $billing['email'] ?? '',
            $billing['order_notes'] ?? '',

            $newOrderTotal,

            'stripe',
            'paid',
            'processing',

            $transactionId,

            $orderId,
            $userId
        ]);


        /* =========================
           INSERT ORDER ITEMS
        ========================= */

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                unit_price,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        /* =========================
           REDUCE STOCK
        ========================= */

        $stockUpdateStmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ?
              AND stock >= ?
        ");


        foreach ($orderItems as $item) {

            $itemStmt->execute([

                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal']
            ]);


            $stockUpdateStmt->execute([

                $item['quantity'],
                $item['product_id'],
                $item['quantity']
            ]);


            if (
                $stockUpdateStmt->rowCount()
                !== 1
            ) {

                throw new Exception(
                    'Unable to update product stock.'
                );
            }
        }


    } else {


        /* =================================================
           CREATE NEW ORDER
        ================================================= */

        $orderNumber =
            'ORD-' .
            date('YmdHis') .
            '-' .
            rand(1000, 9999);


        /* =========================
           INSERT ORDER
        ========================= */

        $orderStmt = $pdo->prepare("
            INSERT INTO orders (
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
                transaction_id
            )
            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
        ");


        $orderStmt->execute([

            $userId,

            $billing['first_name'] ?? '',
            $billing['last_name'] ?? '',
            $billing['company_name'] ?? '',
            $billing['country'] ?? '',
            $billing['address'] ?? '',
            $billing['address_2'] ?? '',
            $billing['city'] ?? '',
            $billing['state'] ?? '',
            $billing['postcode'] ?? '',
            $billing['phone'] ?? '',
            $billing['email'] ?? '',
            $billing['order_notes'] ?? '',

            $orderNumber,

            $calculatedTotal,

            'stripe',
        'paid',
            'processing',

            $transactionId
        ]);


        $orderId =
            (int) $pdo->lastInsertId();


        /* =========================
           INSERT ORDER ITEMS
        ========================= */

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                unit_price,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        /* =========================
           REDUCE STOCK
        ========================= */

        $stockUpdateStmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ?
              AND stock >= ?
        ");


        foreach ($orderItems as $item) {

            $itemStmt->execute([

                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal']
            ]);


            $stockUpdateStmt->execute([

                $item['quantity'],
                $item['product_id'],
                $item['quantity']
            ]);


            if (
                $stockUpdateStmt->rowCount()
                !== 1
            ) {

                throw new Exception(
                    'Unable to update product stock.'
                );
            }
        }
    }


    /* =========================
       COMMIT
    ========================= */

    $pdo->commit();


    /* =========================
       SEND STRIPE ORDER EMAIL
    ========================= */

    $mailer = new Mailer();

    $emailSubject =
        'Stripe Payment Successful - Order ' .
        $orderNumber;

    $emailBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
</head>

<body>

    <h2>Payment Successful!</h2>

    <p>
        Hello ' .
        htmlspecialchars(
            $billing['first_name'] ?? ''
        ) .
    ',
    </p>

    <p>
        Thank you for your order. Your Stripe payment has been
        successfully received.
    </p>

    <p>
        <strong>Order Number:</strong>
        ' .
        htmlspecialchars($orderNumber) .
        '
    </p>

    <p>
        <strong>Total Amount:</strong>
        Rs ' .
        number_format(
            $existingOrder
                ? $newOrderTotal
                : $calculatedTotal,
            2
        ) .
    '
    </p>

    <p>
        <strong>Payment Method:</strong>
        Stripe
    </p>

    <p>
        <strong>Payment Status:</strong>
        Paid
    </p>

    <p>
        <strong>Order Status:</strong>
        Processing
    </p>

    <p>
        We will process your order shortly.
    </p>

    <p>
        Thank you for shopping with us!
    </p>

</body>
</html>
';

    $mailer->send(
        $billing['email'] ?? '',
        $emailSubject,
        $emailBody
    );


    /* =========================
       CLEAR SESSION
    ========================= */

    unset($_SESSION['cart']);

    unset($_SESSION['active_order_id']);

    unset($_SESSION['stripe_billing_details']);

    unset($_SESSION['customer_email']);


    /* =========================
       REDIRECT
    ========================= */

    header(
        'Location: /public/order-confirmation.php?order_id=' .
        (int) $orderId
    );

    exit;


} catch (\Stripe\Exception\ApiErrorException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo '<h2>Stripe Error</h2>';

    echo '<p>';

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo '</p>';

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo '<h2>Payment Error</h2>';

    echo '<p>';

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo '</p>';
}