<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$cartCount = array_sum($_SESSION['cart'] ?? []);
$currentPage = basename($_SERVER['PHP_SELF']);
$latestOrderId = null;

if ($isLoggedIn) {
    $orderStmt = $pdo->prepare("
        SELECT id
        FROM orders
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $orderStmt->execute([$_SESSION['user_id']]);
    $latestOrderId = $orderStmt->fetchColumn();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['header_remove_item'])) {

    $removeProductId = (int) $_POST['header_remove_item'];

    if ($removeProductId > 0 && isset($_SESSION['cart'][$removeProductId])) {
        unset($_SESSION['cart'][$removeProductId]);
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}
$headerCartItems = [];
$headerCartTotal = 0;

if (!empty($_SESSION['cart'])) {

    $cartIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

    $stmt = $pdo->prepare("
        SELECT id, name, price, image
        FROM products
        WHERE id IN ($placeholders)
          AND status = 1
    ");

    $stmt->execute($cartIds);

    /* IMPORTANT:
       Do NOT use $products here.
       $products belongs to the main page.
    */
    $headerProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($headerProducts as $headerProduct) {

        $productId = (int) $headerProduct['id'];

        $quantity = (int) ($_SESSION['cart'][$productId] ?? 0);

        if ($quantity <= 0) {
            continue;
        }

        $itemTotal = (float) $headerProduct['price'] * $quantity;

        $headerProduct['quantity'] = $quantity;
        $headerProduct['item_total'] = $itemTotal;

        $headerCartItems[] = $headerProduct;

        $headerCartTotal += $itemTotal;
    }
}
?>

<style>
    /* =========================================================
   CART
   ========================================================= */

    .header-middle .cart-dropdown {
        margin: 0 !important;
        padding: 0 !important;

        flex-shrink: 0;
    }

    .header-middle .cart-dropdown>a {
        margin: 0 !important;
    }


    /* =========================================================
   REMOVE OLD MOLLA DESKTOP SEARCH AREA
   ========================================================= */

    .header-middle .header-center {
        display: none !important;
    }
</style>


<header class="header header-intro-clearance header-4">

    <!-- =====================================================
         HEADER TOP
         ===================================================== -->

    <div class="header-top">

        <div class="container">

            <div class="header-left">

                <a href="tel:#">
                    <i class="icon-phone"></i>
                    Call: +0123 456 789
                </a>

            </div>


            <div class="header-right">

                <ul class="top-menu">

                    <li>

                        <a href="#">
                            Links
                        </a>

                        <ul>

                            <!-- Currency -->
                            <li>

                                <div class="header-dropdown">

                                    <a href="#">
                                        USD
                                    </a>

                                    <div class="header-menu">

                                        <ul>
                                            <li>
                                                <a href="#">EUR</a>
                                            </li>

                                            <li>
                                                <a href="#">USD</a>
                                            </li>
                                        </ul>

                                    </div>

                                </div>

                            </li>


                            <!-- Language -->
                            <li>

                                <div class="header-dropdown">

                                    <a href="#">
                                        English
                                    </a>

                                    <div class="header-menu">

                                        <ul>

                                            <li>
                                                <a href="#">English</a>
                                            </li>

                                            <li>
                                                <a href="#">French</a>
                                            </li>

                                            <li>
                                                <a href="#">Spanish</a>
                                            </li>

                                        </ul>

                                    </div>

                                </div>

                            </li>


                            <!-- Login -->
                            <li>
                                <?php if ($isLoggedIn): ?>
                                    <a href="logout.php">
                                        Sign Out
                                    </a>

                                <?php else: ?>
                                    <a href="login.php">
                                        Sign In/ Sign Up
                                    </a>
                                <?php endif; ?>
                            </li>

                        </ul>

                    </li>

                </ul>

            </div>

        </div>

    </div>


    <!-- =====================================================
         HEADER MIDDLE
         ===================================================== -->

    <div class="header-middle">

        <div class="container">


            <!-- ================= LEFT ================= -->

            <div class="header-left">

                <!-- Mobile Menu -->
                <button class="mobile-menu-toggler" type="button">

                    <span class="sr-only">
                        Toggle mobile menu
                    </span>

                    <i class="icon-bars"></i>

                </button>


                <!-- Logo -->
                <a href="index.php" class="logo">

                    <img src="assets/images/demos/demo-4/logo.png" alt="Molla Logo" width="105" height="25">

                </a>

            </div>


            <!-- =================================================
                 RIGHT = SEARCH + CART
                 ================================================= -->

            <div class="header-right">

                <!-- ================= CART ================= -->

                <div class="dropdown cart-dropdown">

                    <a href="cart.php" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false" data-display="static">

                        <div class="icon">

                            <i class="icon-shopping-cart"></i>

                            <span class="cart-count">
                                <?= $cartCount; ?>
                            </span>

                        </div>

                        <p>
                            Cart
                        </p>

                    </a>


                    <!-- Cart Dropdown -->

                    <div class="dropdown-menu dropdown-menu-right">

                        <div class="dropdown-cart-products">

                            <?php if (!empty($headerCartItems)): ?>

                                <?php foreach ($headerCartItems as $item): ?>

                                    <div class="product">

                                        <div class="product-cart-details">

                                            <h4 class="product-title">

                                                <a href="product-detail.php?id=<?= (int) $item['id']; ?>">

                                                    <?= htmlspecialchars($item['name']); ?>

                                                </a>

                                            </h4>

                                            <span class="cart-product-info">

                                                <span class="cart-product-qty">
                                                    <?= (int) $item['quantity']; ?>
                                                </span>

                                                x Rs <?= number_format((float) $item['price'], 2); ?>

                                            </span>

                                        </div>

                                        <figure class="product-image-container">

                                            <a href="product-detail.php?id=<?= (int) $item['id']; ?>" class="product-image">

                                                <?php

                                                $headerItemImages = [];

                                                if (!empty($item['image'])) {

                                                    $decodedHeaderImages = json_decode(
                                                        $item['image'],
                                                        true
                                                    );

                                                    if (
                                                        is_array($decodedHeaderImages) &&
                                                        !empty($decodedHeaderImages)
                                                    ) {

                                                        $headerItemImages = $decodedHeaderImages;

                                                    } else {

                                                        // Old single-image products
                                                        $headerItemImages = [$item['image']];
                                                    }
                                                }

                                                $headerItemImage = !empty($headerItemImages)
                                                    ? 'uploads/products/' . $headerItemImages[0]
                                                    : 'assets/images/products/cart/product-1.jpg';

                                                ?>

                                                <img src="<?= htmlspecialchars($headerItemImage); ?>"
                                                    alt="<?= htmlspecialchars($item['name']); ?>">

                                            </a>

                                        </figure>


                                        <form method="POST" style="display:inline;">

                                            <input type="hidden" name="header_remove_item" value="<?= (int) $item['id']; ?>">

                                            <button type="submit" class="btn-remove" title="Remove Product"
                                                style="border:0; background:none; padding:0;">
                                                <i class="icon-close"></i>
                                            </button>

                                        </form>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <p class="text-center p-3 mb-0">
                                    Your cart is empty.
                                </p>

                            <?php endif; ?>

                        </div>


                        <!-- Cart Total -->

                        <div class="dropdown-cart-total">

                            <span>
                                Total
                            </span>

                            <span class="cart-total-price">
                                Rs <?= number_format($headerCartTotal, 2); ?>
                            </span>
                        </div>


                        <!-- Cart Buttons -->

                        <div class="dropdown-cart-action">

                            <a href="cart.php" class="btn btn-primary">
                                View Cart
                            </a>


                            <a href="checkout.php" class="btn btn-outline-primary-2">

                                <span>
                                    Checkout
                                </span>

                                <i class="icon-long-arrow-right"></i>

                            </a>

                        </div>

                    </div>

                </div>

                <!-- ================= END CART ================= -->


            </div>

            <!-- ================= END HEADER RIGHT ================= -->

        </div>

    </div>

    <!-- =====================================================
         END HEADER MIDDLE
         ===================================================== -->



    <!-- =====================================================
         HEADER BOTTOM
         ===================================================== -->

    <div class="header-bottom sticky-header">

        <div class="container">


            <!-- ================= CATEGORIES ================= -->

            <div class="header-left">

                <div class="dropdown category-dropdown">

                    <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false" data-display="static" title="Browse Categories">

                        <i class="icon-bars"></i>

                        Browse Categories

                        <i class="icon-angle-down"></i>

                    </a>


                    <div class="dropdown-menu">

                        <nav class="side-nav">

                            <ul class="menu-vertical sf-arrows">

                                <li class="item-lead">
                                    <a href="products.php">
                                        All Products
                                    </a>
                                </li>

                                <li>
                                    <a href="index.php#popular-categories">
                                        Categories
                                    </a>
                                </li>

                            </ul>

                        </nav>

                    </div>

                </div>

            </div>


            <!-- ================= MAIN NAV ================= -->

            <div class="header-center">

                <nav class="main-nav">

                    <ul class="menu sf-arrows">

                        <!-- HOME -->

                        <li class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">

                            <a href="index.php">
                                Home
                            </a>

                        </li>


                        <!-- SHOP -->

                        <li class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">

                            <a href="products.php" class="sf-with-ul">
                                Shop
                            </a>


                            <div class="megamenu megamenu-md">

                                <div class="row no-gutters">


                                    <!-- Shop Menu -->

                                    <div class="col-md-8 shop-menu-content">

                                        <div class="menu-col">

                                            <div class="row">


                                                <!-- Column 1 -->

                                                <div class="col-md-6">

                                                    <div class="menu-title">
                                                        Shop
                                                    </div>
                                                    <ul>

                                                        <li>
                                                            <a href="products.php">
                                                                All Products
                                                            </a>
                                                        </li>

                                                        <?php
                                                        $headerCategoryStmt = $pdo->query("
        SELECT id, name
        FROM categories
        WHERE status = 1
        ORDER BY id ASC
    ");

                                                        $headerCategories = $headerCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
                                                        ?>

                                                        <?php foreach ($headerCategories as $headerCategory): ?>

                                                            <li>
                                                                <a
                                                                    href="products.php?category=<?= (int) $headerCategory['id']; ?>">
                                                                    <?= htmlspecialchars($headerCategory['name']); ?>
                                                                </a>
                                                            </li>

                                                        <?php endforeach; ?>

                                                    </ul>


                                                    <div class="menu-title">
                                                        Shopping
                                                    </div>

                                                    <ul>

                                                        <li>
                                                            <a href="cart.php">
                                                                Cart
                                                            </a>
                                                        </li>

                                                        <li>
                                                            <a href="checkout.php">
                                                                Checkout
                                                            </a>
                                                        </li>

                                                    </ul>

                                                </div>


                                                <!-- Column 2 -->

                                                <div class="col-md-6">

                                                    <div class="menu-title">
                                                        Order
                                                    </div>

                                                    <ul>

                                                        <?php if ($latestOrderId): ?>
                                                            <li>
                                                                <a
                                                                    href="order-confirmation.php?order_id=<?= (int) $latestOrderId; ?>">
                                                                    Order Confirmation
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>

                                                    </ul>


                                                    <div class="menu-title">
                                                        Account
                                                    </div>

                                                    <ul>
                                                        <?php if ($isLoggedIn): ?>

                                                            <li>
                                                                <a href="logout.php">
                                                                    Sign Out
                                                                </a>
                                                            </li>

                                                        <?php else: ?>

                                                            <li>
                                                                <a href="login.php">
                                                                    Sign In
                                                                </a>
                                                            </li>

                                                            <li>
                                                                <a href="register.php">
                                                                    Register
                                                                </a>
                                                            </li>

                                                        <?php endif; ?>
                                                    </ul>

                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- Banner -->

                                    <div class="col-md-4 shop-menu-banner">

                                        <div class="banner banner-overlay">

                                            <a href="products.php" class="banner banner-menu">

                                                <img src="assets/images/menu/banner-1.jpg" alt="Banner">


                                                <div class="banner-content banner-content-top">

                                                    <div class="banner-title text-white">

                                                        Last
                                                        <br>

                                                        Chance

                                                        <br>

                                                        <span>
                                                            <strong>
                                                                Sale
                                                            </strong>
                                                        </span>

                                                    </div>

                                                </div>

                                            </a>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </li>
                        <?php if ($isLoggedIn): ?>

                            <!-- MY ORDERS -->
                            <li class="<?= $currentPage === 'my-orders.php' ? 'active' : '' ?>">
                                <a href="my-orders.php">My Orders</a>
                            </li>

                        <?php endif; ?>



                    </ul>

                </nav>

            </div>


        </div>

    </div>

    <!-- =====================================================
         END HEADER BOTTOM
         ===================================================== -->

</header>



<!-- =========================================================
     MOBILE MENU OVERLAY
     ========================================================= -->

<div class="mobile-menu-overlay"></div>


<!-- =========================================================
     MOBILE MENU
     ========================================================= -->

<div class="mobile-menu-container mobile-menu-light">

    <div class="mobile-menu-wrapper">


        <!-- Close -->

        <span class="mobile-menu-close">

            <i class="icon-close"></i>

        </span>


        <!-- Mobile Search -->

        <form action="products.php" method="get" class="mobile-search">

            <label for="mobile-search" class="sr-only">
                Search
            </label>


            <input type="search" class="form-control" name="q" id="mobile-search" placeholder="Search in..." required>


            <button class="btn btn-primary" type="submit">

                <i class="icon-search"></i>

            </button>

        </form>


        <!-- Mobile Tabs -->

        <ul class="nav nav-pills-mobile nav-border-anim" role="tablist">

            <li class="nav-item">

                <a class="nav-link active" id="mobile-menu-link" data-toggle="tab" href="#mobile-menu-tab" role="tab"
                    aria-controls="mobile-menu-tab" aria-selected="true">
                    Menu
                </a>

            </li>


            <li class="nav-item">

                <a class="nav-link" id="mobile-cats-link" data-toggle="tab" href="#mobile-cats-tab" role="tab"
                    aria-controls="mobile-cats-tab" aria-selected="false">
                    Categories
                </a>

            </li>

        </ul>


        <!-- Mobile Tab Content -->

        <div class="tab-content">


            <!-- ================= MOBILE MENU TAB ================= -->

            <div class="tab-pane fade show active" id="mobile-menu-tab" role="tabpanel"
                aria-labelledby="mobile-menu-link">

                <nav class="mobile-nav">

                    <ul class="mobile-menu">


                        <li class="active">

                            <a href="index.php">
                                Home
                            </a>

                        </li>


                        <li>

                            <a href="products.php">
                                Shop
                            </a>


                            <ul>

                                <li>
                                    <a href="products.php">
                                        All Products
                                    </a>
                                </li>

                                <li>
                                    <a href="cart.php">
                                        Cart
                                    </a>
                                </li>

                                <li>
                                    <a href="checkout.php">
                                        Checkout
                                    </a>
                                </li>
                                <?php if ($latestOrderId): ?>
                                    <li>
                                        <a href="order-confirmation.php?order_id=<?= (int) $latestOrderId; ?>">
                                            Order Confirmation
                                        </a>
                                    </li>
                                <?php endif; ?>



                            </ul>

                        </li>


                        <?php if ($isLoggedIn): ?>

                            <li>

                                <a href="logout.php">
                                    Sign Out
                                </a>

                            </li>

                        <?php else: ?>

                            <li>

                                <a href="login.php">
                                    Login
                                </a>

                            </li>


                            <li>

                                <a href="login.php">
                                    Register
                                </a>

                            </li>

                        <?php endif; ?>

                    </ul>

                </nav>

            </div>


            <!-- ================= MOBILE CATEGORIES TAB ================= -->

            <div class="tab-pane fade" id="mobile-cats-tab" role="tabpanel" aria-labelledby="mobile-cats-link">

                <nav class="mobile-cats-nav">

                    <ul class="mobile-cats-menu">


                        <li>

                            <a class="mobile-cats-lead" href="products.php">
                                All Products
                            </a>

                        </li>


                        <li>

                            <a href="products.php">
                                Categories
                            </a>

                        </li>


                    </ul>

                </nav>

            </div>


        </div>


        <!-- ================= SOCIAL ICONS ================= -->

        <div class="social-icons">


            <a href="#" class="social-icon" target="_blank" title="Facebook">
                <i class="icon-facebook-f"></i>
            </a>


            <a href="#" class="social-icon" target="_blank" title="Twitter">
                <i class="icon-twitter"></i>
            </a>


            <a href="#" class="social-icon" target="_blank" title="Instagram">
                <i class="icon-instagram"></i>
            </a>


            <a href="#" class="social-icon" target="_blank" title="Youtube">
                <i class="icon-youtube"></i>
            </a>


        </div>


    </div>

</div>



<!-- =========================================================
     SIGN IN / REGISTER MODAL
     ========================================================= -->

<div class="modal fade" id="signin-modal" tabindex="-1" role="dialog" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" role="document">

        <div class="modal-content">

            <div class="modal-body">


                <!-- Close -->

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    <span aria-hidden="true">

                        <i class="icon-close"></i>

                    </span>

                </button>


                <div class="form-box">

                    <div class="form-tab">


                        <!-- Tabs -->

                        <ul class="nav nav-pills nav-fill nav-border-anim" role="tablist">

                            <li class="nav-item">

                                <a class="nav-link active" id="signin-tab" data-toggle="tab" href="#signin" role="tab"
                                    aria-controls="signin" aria-selected="true">
                                    Sign In
                                </a>

                            </li>


                            <li class="nav-item">

                                <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab"
                                    aria-controls="register" aria-selected="false">
                                    Register
                                </a>

                            </li>

                        </ul>


                        <!-- Tab Content -->

                        <div class="tab-content" id="tab-content-5">


                            <!-- ================= SIGN IN ================= -->

                            <div class="tab-pane fade show active" id="signin" role="tabpanel"
                                aria-labelledby="signin-tab">

                                <form action="login.php" method="post">


                                    <div class="form-group">

                                        <label for="singin-email">
                                            Username or email address *
                                        </label>

                                        <input type="text" class="form-control" id="singin-email" name="email" required>

                                    </div>


                                    <div class="form-group">

                                        <label for="singin-password">
                                            Password *
                                        </label>

                                        <input type="password" class="form-control" id="singin-password" name="password"
                                            required>

                                    </div>


                                    <div class="form-footer">


                                        <button type="submit" class="btn btn-outline-primary-2">

                                            <span>
                                                LOG IN
                                            </span>

                                            <i class="icon-long-arrow-right"></i>

                                        </button>


                                        <div class="custom-control custom-checkbox">

                                            <input type="checkbox" class="custom-control-input" id="signin-remember"
                                                name="remember">


                                            <label class="custom-control-label" for="signin-remember">Remember Me
                                            </label>

                                        </div>


                                        <a href="#" class="forgot-link">
                                            Forgot Your Password?
                                        </a>


                                    </div>

                                </form>

                            </div>


                            <!-- ================= REGISTER ================= -->

                            <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">

                                <form action="login.php" method="post">


                                    <div class="form-group">

                                        <label for="register-email">
                                            Your email address *
                                        </label>

                                        <input type="email" class="form-control" id="register-email" name="email"
                                            required>

                                    </div>


                                    <div class="form-group">

                                        <label for="register-password">
                                            Password *
                                        </label>

                                        <input type="password" class="form-control" id="register-password"
                                            name="password" required>

                                    </div>


                                    <div class="form-footer">


                                        <button type="submit" class="btn btn-outline-primary-2">

                                            <span>
                                                SIGN UP
                                            </span>

                                            <i class="icon-long-arrow-right"></i>

                                        </button>


                                        <div class="custom-control custom-checkbox">

                                            <input type="checkbox" class="custom-control-input" id="register-policy"
                                                required>


                                            <label class="custom-control-label" for="register-policy">

                                                I agree to the

                                                <a href="#">
                                                    privacy policy
                                                </a>

                                                *

                                            </label>

                                        </div>


                                    </div>

                                </form>

                            </div>


                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>