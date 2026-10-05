<?php

require_once '../config/database.php';
require_once '../vendor/autoload.php';

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

\Stripe\Stripe::setApiKey($stripeConfig['secret_key']);


/* =========================
   GET CART
========================= */

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: /public/cart.php');
    exit;
}


/* =========================
   FETCH CART PRODUCTS
========================= */

$cartIds = array_keys($cart);

$placeholders = implode(
    ',',
    array_fill(0, count($cartIds), '?')
);

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        price,
        stock
    FROM products
    WHERE id IN ($placeholders)
      AND status = 1
");

$stmt->execute($cartIds);

$cartProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   SHIPPING
========================= */

$shippingOptions = [
    'free' => 0,
    'standard' => 10,
    'express' => 20
];

$selectedShipping = $_SESSION['shipping_method'] ?? 'free';

if (!array_key_exists($selectedShipping, $shippingOptions)) {
    $selectedShipping = 'free';
}

$shipping = $shippingOptions[$selectedShipping];


/* =========================
   BUILD STRIPE LINE ITEMS
========================= */

$lineItems = [];

$subtotal = 0;

foreach ($cartProducts as $product) {

    $productId = (int) $product['id'];

    $quantity = (int) ($cart[$productId] ?? 0);

    if ($quantity <= 0) {
        continue;
    }


    /* =========================
       STOCK CHECK
    ========================= */

    if ((int) $product['stock'] < $quantity) {

        exit(
            'Not enough stock available for ' .
            htmlspecialchars($product['name'])
        );
    }


    $price = (float) $product['price'];

    $itemTotal = $price * $quantity;

    $subtotal += $itemTotal;


    /*
     * Stripe expects the amount
     * in the smallest currency unit.
     *
     * For example:
     * Rs 100.00 = 10000
     */

    $unitAmount = (int) round($price * 100);


    $lineItems[] = [
        'price_data' => [
            'currency' => 'pkr',

            'product_data' => [
                'name' => $product['name'],
            ],

            'unit_amount' => $unitAmount,
        ],

        'quantity' => $quantity,
    ];
}


/* =========================
   ADD SHIPPING
========================= */

if ($shipping > 0) {

    $lineItems[] = [
        'price_data' => [
            'currency' => 'pkr',

            'product_data' => [
                'name' => 'Shipping - ' . ucfirst($selectedShipping),
            ],

            'unit_amount' => (int) round($shipping * 100),
        ],

        'quantity' => 1,
    ];
}


/* =========================
   CHECK TOTAL
========================= */

$total = $subtotal + $shipping;

if ($total <= 0) {
    header('Location: /public/cart.php');
    exit;
}


/* =========================
   CREATE STRIPE SESSION
========================= */

try {

    $session = \Stripe\Checkout\Session::create([

        'mode' => 'payment',

        'line_items' => $lineItems,

        'success_url' =>
            'http://ecommerce.local/public/stripe-success.php?session_id={CHECKOUT_SESSION_ID}',

        'cancel_url' =>
            'http://ecommerce.local/public/checkout.php?payment=cancelled',

        'customer_email' =>
            $_SESSION['customer_email'] ?? null,

        'metadata' => [
            'user_id' => (string) $userId,
        ],
    ]);


    /* =========================
       REDIRECT TO STRIPE
    ========================= */

    header(
        'Location: ' . $session->url
    );

    exit;


} catch (\Stripe\Exception\ApiErrorException $e) {

    echo '<h2>Stripe Payment Error</h2>';

    echo '<p>';

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo '</p>';

} catch (Exception $e) {

    echo '<h2>Payment Error</h2>';

    echo '<p>';

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo '</p>';
}