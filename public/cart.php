<?php

require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   CART SESSION
========================= */

$cart = $_SESSION['cart'] ?? [];
/* =========================
   SHIPPING OPTIONS
========================= */

$shippingOptions = [
    'free' => 0,
    'standard' => 10,
    'express' => 20
];


/* =========================
   SAVE SHIPPING METHOD
========================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['shipping_method'])
) {

    $shippingMethod = $_POST['shipping_method'];

    if (array_key_exists($shippingMethod, $shippingOptions)) {

        $_SESSION['shipping_method'] = $shippingMethod;
    }

    header('Location: cart.php');
    exit;
}


/* =========================
   SELECTED SHIPPING
========================= */

$selectedShipping = $_SESSION['shipping_method'] ?? 'free';

if (!array_key_exists($selectedShipping, $shippingOptions)) {

    $selectedShipping = 'free';
}

$shipping = $shippingOptions[$selectedShipping];

/* =========================
   REMOVE ITEM
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {

    $removeId = (int) $_POST['remove_item'];

    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }

    header('Location: cart.php');
    exit;
}


/* =========================
   UPDATE CART
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {

    $quantities = $_POST['quantity'] ?? [];

    foreach ($quantities as $productId => $quantity) {

        $productId = (int) $productId;
        $quantity = (int) $quantity;

        if ($productId <= 0) {
            continue;
        }


        /* Remove if quantity is 0 or less */

        if ($quantity <= 0) {

            unset($_SESSION['cart'][$productId]);

            continue;
        }


        /* =========================
           CHECK PRODUCT STOCK
        ========================= */

        $stockStmt = $pdo->prepare("
            SELECT stock
            FROM products
            WHERE id = ?
              AND status = 1
            LIMIT 1
        ");

        $stockStmt->execute([$productId]);

        $stock = $stockStmt->fetchColumn();


        if ($stock !== false) {

            /* Do not allow quantity above stock */

            $quantity = min($quantity, (int) $stock);

            $_SESSION['cart'][$productId] = $quantity;

        } else {

            /* Product no longer exists / inactive */

            unset($_SESSION['cart'][$productId]);
        }
    }

    header('Location: cart.php');
    exit;
}


/* =========================
   FETCH CART PRODUCTS
========================= */

$cartItems = [];

if (!empty($_SESSION['cart'])) {

    $productIds = array_keys($_SESSION['cart']);

    /*
     * Create ?, ?, ? placeholders
     */
    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );


    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.id IN ($placeholders)
          AND p.status = 1
    ");

    $stmt->execute($productIds);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($products as $product) {

        $productId = (int) $product['id'];

        $quantity = (int) (
            $_SESSION['cart'][$productId] ?? 0
        );


        if ($quantity <= 0) {
            continue;
        }


        /* =========================
           CHECK STOCK AGAIN
        ========================= */

        if ($quantity > (int) $product['stock']) {

            $quantity = (int) $product['stock'];

            $_SESSION['cart'][$productId] = $quantity;
        }


        /* If stock became 0 */

        if ($quantity <= 0) {

            unset($_SESSION['cart'][$productId]);

            continue;
        }


        /* Add quantity */

        $product['quantity'] = $quantity;


        /* Calculate subtotal */

        $product['subtotal'] =
            (float) $product['price'] * $quantity;


        $cartItems[] = $product;
    }
}


/* =========================
   CART TOTAL
========================= */

$subtotal = 0;

foreach ($cartItems as $item) {

    $subtotal += (float) $item['subtotal'];
}

/* =========================
   SHIPPING + CART TOTAL
========================= */

$shipping = $shippingOptions[$selectedShipping];

$total = $subtotal + $shipping;


?>


<!DOCTYPE html>
<html lang="en">


<!-- molla/cart.php  22 Nov 2019 09:55:06 GMT -->

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible"
          content="IE=edge">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Molla - Bootstrap eCommerce Template</title>

    <meta name="keywords"
          content="HTML5 Template">

    <meta name="description"
          content="Molla - Bootstrap eCommerce Template">

    <meta name="author"
          content="p-themes">


    <!-- Favicon -->

    <link rel="apple-touch-icon"
          sizes="180x180"
          href="assets/images/icons/apple-touch-icon.png">

    <link rel="icon"
          type="image/png"
          sizes="32x32"
          href="assets/images/icons/favicon-32x32.png">

    <link rel="icon"
          type="image/png"
          sizes="16x16"
          href="assets/images/icons/favicon-16x16.png">

    <link rel="manifest"
          href="assets/images/icons/site.html">

    <link rel="mask-icon"
          href="assets/images/icons/safari-pinned-tab.svg"
          color="#666666">

    <link rel="shortcut icon"
          href="assets/images/icons/favicon.ico">

    <meta name="apple-mobile-web-app-title"
          content="Molla">

    <meta name="application-name"
          content="Molla">

    <meta name="msapplication-TileColor"
          content="#cc9966">

    <meta name="msapplication-config"
          content="assets/images/icons/browserconfig.xml">

    <meta name="theme-color"
          content="#ffffff">


    <!-- Plugins CSS File -->

    <link rel="stylesheet"
          href="assets/css/bootstrap.min.css">


    <!-- Main CSS File -->

    <link rel="stylesheet"
          href="assets/css/style.css">

</head>


<body>

<div class="page-wrapper">


    <?php include '../includes/header.php'; ?>


    <main class="main">


        <!-- =========================
             PAGE HEADER
        ========================= -->

        <div class="page-header text-center"
             style="background-image: url('assets/images/page-header-bg.jpg')">

            <div class="container">

                <h1 class="page-title">
                    Shopping Cart
                    <span>Shop</span>
                </h1>

            </div>

        </div>


        <!-- =========================
             BREADCRUMB
        ========================= -->

        <nav aria-label="breadcrumb"
             class="breadcrumb-nav">

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

                    <li class="breadcrumb-item active"
                        aria-current="page">

                        Shopping Cart

                    </li>

                </ol>

            </div>

        </nav>


        <!-- =========================
             PAGE CONTENT
        ========================= -->

        <div class="page-content">

            <div class="cart">

                <div class="container">

                    <div class="row">


                        <!-- =========================
                             CART PRODUCTS
                        ========================= -->

                        <div class="col-lg-9">


                            <!--
                                This form handles
                                UPDATE CART
                            -->

                            <form method="POST"
                                  action="cart.php">


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

                                            <th></th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                    <?php if (!empty($cartItems)): ?>


                                        <?php foreach ($cartItems as $item): ?>


                                            <tr>


                                                <!-- =================
                                                     PRODUCT
                                                ================== -->

                                                <td class="product-col">

                                                    <div class="product">


                                                        <figure class="product-media">

                                                            <a href="product-detail.php?id=<?= (int) $item['id']; ?>">


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
    : 'assets/images/products/product-1.jpg';

?>

<img
    src="<?= htmlspecialchars($itemImage); ?>"
    alt="<?= htmlspecialchars($item['name']); ?>"
>


                                                            </a>

                                                        </figure>


                                                        <h3 class="product-title">

                                                            <a href="product-detail.php?id=<?= (int) $item['id']; ?>">

                                                                <?= htmlspecialchars($item['name']); ?>

                                                            </a>

                                                        </h3>


                                                    </div>

                                                </td>


                                                <!-- =================
                                                     PRICE
                                                ================== -->

                                                <td class="price-col">

                                                    Rs
                                                    <?= number_format(
                                                        (float) $item['price'],
                                                        2
                                                    ); ?>

                                                </td>


                                                <!-- =================
                                                     QUANTITY
                                                ================== -->

                                                <td class="quantity-col">

                                                    <div class="cart-product-quantity">

                                                        <input
                                                            type="number"
                                                            name="quantity[<?= (int) $item['id']; ?>]"
                                                            class="form-control"
                                                            value="<?= (int) $item['quantity']; ?>"
                                                            min="1"
                                                            max="<?= (int) $item['stock']; ?>"
                                                            step="1"
                                                            data-decimals="0"
                                                            required
                                                        >

                                                    </div>

                                                </td>


                                                <!-- =================
                                                     TOTAL
                                                ================== -->

                                                <td class="total-col">

                                                    Rs
                                                    <?= number_format(
                                                        (float) $item['subtotal'],
                                                        2
                                                    ); ?>

                                                </td>


                                                <!-- =================
                                                     REMOVE
                                                ================== -->

                                                <td class="remove-col">


                                                    <button
                                                        type="submit"
                                                        name="remove_item"
                                                        value="<?= (int) $item['id']; ?>"
                                                        class="btn-remove"
                                                        title="Remove Product"
                                                    >

                                                        <i class="icon-close"></i>

                                                    </button>


                                                </td>


                                            </tr>


                                        <?php endforeach; ?>


                                    <?php else: ?>


                                        <!-- =================
                                             EMPTY CART
                                        ================== -->

                                        <tr>

                                            <td colspan="5"
                                                class="text-center">

                                                <div style="padding: 40px 0;">

                                                    <h3>
                                                        Your cart is empty.
                                                    </h3>

                                                    <p>
                                                        Add some products to your cart first.
                                                    </p>

                                                    <a href="products.php"
                                                       class="btn btn-outline-primary-2">

                                                        <span>
                                                            CONTINUE SHOPPING
                                                        </span>

                                                        <i class="icon-long-arrow-right"></i>

                                                    </a>

                                                </div>

                                            </td>

                                        </tr>


                                    <?php endif; ?>


                                    </tbody>


                                </table>


                                <!-- =========================
                                     CART BOTTOM
                                ========================= -->

                                <div class="cart-bottom">



                                    <!-- UPDATE CART -->

                                    <?php if (!empty($cartItems)): ?>

                                        <button
                                            type="submit"
                                            name="update_cart"
                                            class="btn btn-outline-dark-2"
                                        >

                                            <span>
                                                UPDATE CART
                                            </span>

                                            <i class="icon-refresh"></i>

                                        </button>

                                    <?php endif; ?>


                                </div>


                            </form>


                        </div>


                        <!-- =========================
                             CART SUMMARY
                        ========================= -->

                        <aside class="col-lg-3">


                            <div class="summary summary-cart">


                                <h3 class="summary-title">
                                    Cart Total
                                </h3>


                                <table class="table table-summary">


                                    <tbody>


                                        <!-- SUBTOTAL -->

                                        <tr class="summary-subtotal">

                                            <td>
                                                Subtotal:
                                            </td>

                                            <td>

                                                Rs
                                                <?= number_format(
                                                    $subtotal,
                                                    2
                                                ); ?>

                                            </td>

                                        </tr>

<!-- =========================
     SHIPPING
========================= -->

<tr class="summary-shipping">

    <td>
        Shipping:
    </td>

    <td>
        &nbsp;
    </td>

</tr>


<!-- FREE SHIPPING -->

<tr class="summary-shipping-row">

    <td>

        <div class="custom-control custom-radio">

            <input
                type="radio"
                id="free-shipping"
                name="shipping_method"
                value="free"
                class="custom-control-input shipping-option"
                data-price="0"
                <?= $selectedShipping === 'free' ? 'checked' : ''; ?>
            >

            <label
                class="custom-control-label"
                for="free-shipping"
            >
                Free Shipping
            </label>

        </div>

    </td>

    <td>
        Rs 0.00
    </td>

</tr>


<!-- STANDARD SHIPPING -->

<tr class="summary-shipping-row">

    <td>

        <div class="custom-control custom-radio">

            <input
                type="radio"
                id="standard-shipping"
                name="shipping_method"
                value="standard"
                class="custom-control-input shipping-option"
                data-price="10"
                <?= $selectedShipping === 'standard' ? 'checked' : ''; ?>
            >

            <label
                class="custom-control-label"
                for="standard-shipping"
            >
                Standard:
            </label>

        </div>

    </td>

    <td>
        Rs 10.00
    </td>

</tr>


<!-- EXPRESS SHIPPING -->

<tr class="summary-shipping-row">

    <td>

        <div class="custom-control custom-radio">

            <input
                type="radio"
                id="express-shipping"
                name="shipping_method"
                value="express"
                class="custom-control-input shipping-option"
                data-price="20"
                <?= $selectedShipping === 'express' ? 'checked' : ''; ?>
            >

            <label
                class="custom-control-label"
                for="express-shipping"
            >
                Express:
            </label>

        </div>

    </td>

    <td>
        Rs 20.00
    </td>

</tr>


                                        <!-- ADDRESS -->

                                        <tr class="summary-shipping-estimate">

                                            <td>

                                                Estimate for Your Country
                                                <br>

                                                <a href="#">
                                                    Change address
                                                </a>

                                            </td>

                                            <td>
                                                &nbsp;
                                            </td>

                                        </tr>


                                        <!-- TOTAL -->

                                    <tr class="summary-total">

    <td>
        Total:
    </td>

    <td>

        Rs
        <span id="cart-total">
            <?= number_format($total, 2); ?>
        </span>

    </td>

</tr>


                                    </tbody>

                                </table>


                                <!-- CHECKOUT -->

                                <a
                                    href="checkout.php"
                                    class="btn btn-outline-primary-2 btn-order btn-block"
                                >

                                    PROCEED TO CHECKOUT

                                </a>


                            </div>


                            <!-- CONTINUE SHOPPING -->

                            <a
                                href="products.php"
                                class="btn btn-outline-dark-2 btn-block mb-3"
                            >

                                <span>
                                    CONTINUE SHOPPING
                                </span>

                                <i class="icon-refresh"></i>

                            </a>


                        </aside>


                    </div>

                </div>

            </div>

        </div>

    </main>


    <?php include '../includes/footer.php'; ?>


</div>


<button
    id="scroll-top"
    title="Back to Top"
>

    <i class="icon-arrow-up"></i>

</button>


<!-- =========================
     PLUGINS JS
========================= -->

<script src="assets/js/jquery.min.js"></script>

<script src="assets/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/jquery.hoverIntent.min.js"></script>

<script src="assets/js/jquery.waypoints.min.js"></script>

<script src="assets/js/superfish.min.js"></script>

<script src="assets/js/owl.carousel.min.js"></script>

<script src="assets/js/bootstrap-input-spinner.js"></script>

<script src="assets/js/main.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const shippingOptions = document.querySelectorAll('.shipping-option');

    const subtotal = <?= json_encode((float) $subtotal); ?>;

    const totalElement = document.getElementById('cart-total');


    shippingOptions.forEach(function (option) {

        option.addEventListener('change', function () {

            const shippingPrice = parseFloat(
                this.getAttribute('data-price')
            ) || 0;


            const total = subtotal + shippingPrice;


            totalElement.textContent = total.toFixed(2);


            /* =========================
               SAVE SHIPPING IN SESSION
            ========================= */

            const formData = new FormData();

            formData.append(
                'shipping_method',
                this.value
            );


            fetch('cart.php', {

                method: 'POST',
                body: formData

            }).catch(function (error) {

                console.error(
                    'Shipping update failed:',
                    error
                );

            });

        });

    });

});

</script>
</body>


<!-- molla/cart.php  22 Nov 2019 09:55:06 GMT -->

</html>