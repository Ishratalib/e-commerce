<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

/* =========================
   FETCH ACTIVE CATEGORIES
========================= */

$stmt = $pdo->query("
    SELECT
        id,
        name,
        image
    FROM categories
    WHERE status = 1
    ORDER BY id ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as &$category) {

    if (!empty($category['image'])) {
        $category['image_url'] = 'uploads/categories/' . basename($category['image']);
    } else {
        $category['image_url'] = 'assets/images/demos/demo-4/cats/1.png';
    }
}

unset($category);


/* =========================
   FETCH ACTIVE SLIDERS
========================= */

$sliderStmt = $pdo->query("
    SELECT
        id,
        title,
        subtitle,
        description,
        button_text,
        button_link,
        image
    FROM sliders
    WHERE status = 1
    ORDER BY sort_order ASC, id ASC
");

$sliders = $sliderStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   FETCH ACTIVE PRODUCTS (NEW ARRIVALS)
========================= */

$stmt = $pdo->query("
    SELECT
        p.id,
        p.category_id,
        p.name,
        p.price,
        p.image,
        p.stock,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1
    ORDER BY p.created_at DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   FETCH TRENDING PRODUCTS
   MOST SOLD PRODUCTS
========================= */

$trendingStmt = $pdo->query("
    SELECT 
        p.id,
        p.category_id,
        p.name,
        p.price,
        p.image,
        p.stock,
        c.name AS category_name,
        SUM(oi.quantity) AS total_sold
    FROM products p
    INNER JOIN order_items oi
        ON oi.product_id = p.id
    INNER JOIN orders o
        ON o.id = oi.order_id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.status = 1
      AND LOWER(TRIM(o.order_status)) = 'delivered'
    GROUP BY
        p.id,
        p.category_id,
        p.name,
        p.price,
        p.image,
        p.stock,
        c.name
    ORDER BY total_sold DESC
    LIMIT 10
");

$trendingProducts = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);


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


    <!-- =========================
         CUSTOM STYLES
    ========================= -->

    <style>
        .product-image-box {
            width: 100%;
            height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
        }

        .product-image-box .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .category-image-box {
            width: 100%;
            height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
        }

        .category-image-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        /* =========================
           PRODUCT ACTION BUTTONS
        ========================= */

        .product-action {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .product-action form {
            width: 50%;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .product-action .btn-view-product {
            width: 50%;
        }

        .product-action .btn-product {
            position: relative;

            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 100% !important;
            height: 16px;
            margin: 0 !important;
            padding: 0 !important;
            border: 0;
            background: none;
            text-decoration: none;
        }

        /* icon only, hide any text */
        .product-action .btn-product span {
            display: none !important;
        }

        .product-action .btn-cart::after {
            content: "Add to Cart";
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            background: #333;
            color: #fff;
            padding: 5px 8px;
            font-size: 12px;
            white-space: nowrap;
            border-radius: 3px;
            z-index: 10;
        }

        .product-action .btn-cart:hover::after {
            display: block;
        }

        .product-action .btn-view-product::after {
            content: "View Details";
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            background: #333;
            color: #fff;
            padding: 5px 8px;
            font-size: 12px;
            white-space: nowrap;
            border-radius: 3px;
            z-index: 10;
        }

        .product-action .btn-view-product:hover::after {
            display: block;
        }
    </style>

</head>


<body>

    <div class="page-wrapper">


        <?php include '../includes/header.php'; ?>


        <main class="main">


            <!-- =========================
             SLIDER
        ========================= -->

            <?php if (!empty($sliders)): ?>

                <div class="intro-slider-container mb-5">

                    <div class="intro-slider owl-carousel owl-theme owl-nav-inside owl-light" data-toggle="owl"
                        data-owl-options='{
                        "dots": true,
                        "nav": false,
                        "responsive": {
                            "1200": {
                                "nav": true,
                                "dots": false
                            }
                        }
                    }'>

                        <?php foreach ($sliders as $slider): ?>

                            <div class="intro-slide"
                                style="background-image: url('uploads/sliders/<?php echo htmlspecialchars($slider['image']); ?>');">

                                <div class="container intro-content">

                                    <div class="row justify-content-end">

                                        <div class="col-auto col-sm-7 col-md-6 col-lg-5">

                                            <?php if (!empty($slider['subtitle'])): ?>

                                                <h3 class="intro-subtitle text-third">

                                                    <?php echo htmlspecialchars($slider['subtitle']); ?>

                                                </h3>

                                            <?php endif; ?>


                                            <?php if (!empty($slider['title'])): ?>

                                                <h1 class="intro-title">

                                                    <?php echo nl2br(
                                                        htmlspecialchars($slider['title'])
                                                    ); ?>

                                                </h1>

                                            <?php endif; ?>


                                            <?php if (!empty($slider['description'])): ?>

                                                <p class="intro-description">

                                                    <?php echo nl2br(
                                                        htmlspecialchars($slider['description'])
                                                    ); ?>

                                                </p>

                                            <?php endif; ?>


                                            <?php if (!empty($slider['button_text'])): ?>

                                                <a href="<?php echo htmlspecialchars($slider['button_link']); ?>"
                                                    class="btn btn-primary btn-round">

                                                    <span>

                                                        <?php echo htmlspecialchars($slider['button_text']); ?>

                                                    </span>

                                                    <i class="icon-long-arrow-right"></i>

                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <span class="slider-loader"></span>

                </div>

            <?php endif; ?>


            <!-- =========================
             POPULAR CATEGORIES
        ========================= -->

            <div class="container" id="popular-categories">

                <h2 class="title text-center mb-4">

                    Explore Popular Categories

                </h2>


                <div class="cat-blocks-container">

                    <div class="row">

                        <?php foreach ($categories as $category): ?>

                            <div class="col-6 col-sm-4 col-lg-2">

                                <a href="products.php?category=<?php echo (int) $category['id']; ?>" class="cat-block">

                                    <figure>

                                        <div class="category-image-box">

                                            <img src="<?php echo htmlspecialchars($category['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($category['name']); ?>">

                                        </div>

                                    </figure>


                                    <h3 class="cat-block-title">

                                        <?php echo htmlspecialchars($category['name']); ?>

                                    </h3>

                                </a>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


            <div class="mb-4"></div>

            <!-- =========================
     NEW ARRIVALS
========================= -->

            <div class="container new-arrivals" id="new-arrivals">

                <div class="heading heading-flex mb-3">

                    <div class="heading-left">

                        <h2 class="title">
                            New Arrivals
                        </h2>

                    </div>
<div class="heading-right">

    <ul class="nav nav-pills justify-content-center" role="tablist">

        <!-- ALL TAB -->
        <li class="nav-item">

            <a class="nav-link active"
                id="new-all-link"
                data-toggle="tab"
                href="#new-all-tab"
                role="tab">

                All

            </a>

        </li>


        <!-- DYNAMIC CATEGORY TABS -->
        <?php foreach ($categories as $category): ?>

            <li class="nav-item">

                <a class="nav-link"
                    id="new-category-<?php echo (int) $category['id']; ?>-link"
                    data-toggle="tab"
                    href="#new-category-<?php echo (int) $category['id']; ?>-tab"
                    role="tab">

                    <?php echo htmlspecialchars($category['name']); ?>

                </a>

            </li>

        <?php endforeach; ?>

    </ul>

</div>

                </div>


                <div class="tab-content">


                    <!-- =========================
             ALL PRODUCTS
        ========================= -->

                    <div class="tab-pane fade show active" id="new-all-tab" role="tabpanel">

                        <div class="products">

                            <div class="row">

                                <?php if (!empty($products)): ?>

                                    <?php foreach ($products as $product): ?>

                                        <div class="col-6 col-md-4 col-lg-3">

                                            <div class="product product-2">

                                                <figure class="product-media">

                                                    <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                        <div class="product-image-box">

                                                           <?php

$productImages = [];

if (!empty($product['image'])) {

    $decodedImages = json_decode(
        $product['image'],
        true
    );

    if (
        is_array($decodedImages) &&
        !empty($decodedImages)
    ) {

        $productImages = $decodedImages;

    } else {

        // Old single-image products
        $productImages = [$product['image']];
    }
}

$productImage = !empty($productImages)
    ? 'uploads/products/' . $productImages[0]
    : 'assets/images/demos/demo-4/products/product-1.jpg';

?>

<img src="<?= htmlspecialchars($productImage); ?>"
    alt="<?= htmlspecialchars($product['name']); ?>"
    class="product-image">

                                                        </div>

                                                    </a>


                                                    <div class="product-action">

                                                        <?php if ((int) $product['stock'] > 0): ?>

                                                            <form method="POST"
                                                                action="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                                <input type="hidden" name="product_id"
                                                                    value="<?php echo (int) $product['id']; ?>">

                                                                <input type="hidden" name="quantity" value="1">
                                                                <input type="hidden" name="redirect_url"
                                                                    value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                                <button type="submit" name="add_to_cart"
                                                                    class="btn-product btn-cart" title="Add to cart">
                                                                </button>

                                                            </form>

                                                        <?php else: ?>

                                                            <span class="text-danger">
                                                                Out of Stock
                                                            </span>

                                                        <?php endif; ?>


                                                        <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>"
                                                            class="btn-product btn-view-product" title="View product">
                                                            <i class="icon-eye"></i>
                                                        </a>

                                                    </div>

                                                </figure>


                                                <div class="product-body">

                                                    <div class="product-cat">

                                                        <a href="#">
                                                            <?php echo htmlspecialchars(
                                                                $product['category_name'] ?? ''
                                                            ); ?>
                                                        </a>

                                                    </div>


                                                    <h3 class="product-title">

                                                        <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                            <?php echo htmlspecialchars(
                                                                $product['name']
                                                            ); ?>

                                                        </a>

                                                    </h3>


                                                    <div class="product-price">

                                                        Rs
                                                        <?php echo number_format(
                                                            (float) $product['price'],
                                                            2
                                                        ); ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <div class="col-12 text-center">

                                        <p>
                                            No products available.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <!-- =========================
             DYNAMIC CATEGORY TABS
        ========================= -->

                    <?php foreach ($categories as $category): ?>

                        <div class="tab-pane fade" id="new-category-<?php echo (int) $category['id']; ?>-tab"
                            role="tabpanel">

                            <div class="products">

                                <div class="row">

                                    <?php

                                    /*
                                     * Get products belonging
                                     * to this category
                                     */

                                    $categoryProducts = array_filter(
                                        $products,
                                        function ($product) use ($category) {

                                            return (int) $product['category_id']
                                                === (int) $category['id'];

                                        }
                                    );

                                    ?>


                                    <?php if (!empty($categoryProducts)): ?>

                                        <?php foreach ($categoryProducts as $product): ?>

                                            <div class="col-6 col-md-4 col-lg-3">

                                                <div class="product product-2">

                                                    <figure class="product-media">

                                                        <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                            <div class="product-image-box">

                                                               <?php

$productImages = [];

if (!empty($product['image'])) {

    $decodedImages = json_decode(
        $product['image'],
        true
    );

    if (
        is_array($decodedImages) &&
        !empty($decodedImages)
    ) {

        $productImages = $decodedImages;

    } else {

        // Old single-image products
        $productImages = [$product['image']];
    }
}

$productImage = !empty($productImages)
    ? 'uploads/products/' . $productImages[0]
    : 'assets/images/demos/demo-4/products/product-1.jpg';

?>

<img src="<?= htmlspecialchars($productImage); ?>"
    alt="<?= htmlspecialchars($product['name']); ?>"
    class="product-image">

                                                            </div>

                                                        </a>


                                                        <div class="product-action">

                                                            <?php if ((int) $product['stock'] > 0): ?>

                                                                <form method="POST"
                                                                    action="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                                    <input type="hidden" name="product_id"
                                                                        value="<?php echo (int) $product['id']; ?>">

                                                                    <input type="hidden" name="quantity" value="1">
                                                                    <input type="hidden" name="redirect_url"
                                                                        value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                                    <button type="submit" name="add_to_cart"
                                                                        class="btn-product btn-cart" title="Add to cart">
                                                                    </button>

                                                                </form>

                                                            <?php else: ?>

                                                                <span class="text-danger">
                                                                    Out of Stock
                                                                </span>

                                                            <?php endif; ?>

                                                            <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>"
                                                                class="btn-product btn-view-product" title="View product">
                                                                <i class="icon-eye"></i>
                                                            </a>

                                                        </div>

                                                    </figure>


                                                    <div class="product-body">

                                                        <div class="product-cat">

                                                            <a href="#">

                                                                <?php echo htmlspecialchars(
                                                                    $product['category_name'] ?? ''
                                                                ); ?>

                                                            </a>

                                                        </div>


                                                        <h3 class="product-title">

                                                            <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                                <?php echo htmlspecialchars(
                                                                    $product['name']
                                                                ); ?>

                                                            </a>

                                                        </h3>


                                                        <div class="product-price">

                                                            Rs
                                                            <?php echo number_format(
                                                                (float) $product['price'],
                                                                2
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>


                                    <?php else: ?>

                                        <div class="col-12 text-center">

                                            <p>
                                                No products available in
                                                <?php echo htmlspecialchars($category['name']); ?>.
                                            </p>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <div class="mb-3"></div>

            <div class="mb-6"></div>

            <!-- =========================
             TRENDING PRODUCTS
        ========================= -->

            <div class="bg-light pt-5 pb-6">


                <div class="container trending-products">


                    <div class="heading heading-flex mb-3">

                        <div class="heading-left">

                            <h2 class="title">
                                Trending Products
                            </h2>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-12">


                            <div class="owl-carousel owl-full carousel-equal-height carousel-with-shadow"
                                data-toggle="owl" data-owl-options='{
                                "nav": true,
                                "dots": false,
                                "margin": 20,
                                "loop": false,
                                "responsive": {
                                    "0": {
                                        "items": 2
                                    },
                                    "480": {
                                        "items": 2
                                    },
                                    "768": {
                                        "items": 3
                                    },
                                    "992": {
                                        "items": 4
                                    }
                                }
                            }'>


                                <?php foreach ($trendingProducts as $product): ?>


                                    <div class="product product-2">


                                        <figure class="product-media">


                                            <span class="product-label label-circle label-top">
                                                Top
                                            </span>


                                            <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>">


                                                <div class="product-image-box">


                                                    <?php

$productImages = [];

if (!empty($product['image'])) {

    $decodedImages = json_decode(
        $product['image'],
        true
    );

    if (
        is_array($decodedImages) &&
        !empty($decodedImages)
    ) {

        $productImages = $decodedImages;

    } else {

        // Old single-image products
        $productImages = [$product['image']];
    }
}

$productImage = !empty($productImages)
    ? 'uploads/products/' . $productImages[0]
    : 'assets/images/demos/demo-4/products/product-1.jpg';

?>

<img src="<?= htmlspecialchars($productImage); ?>"
    alt="<?= htmlspecialchars($product['name']); ?>"
    class="product-image">


                                                </div>


                                            </a>


                                            <div class="product-action">


                                                <?php if ((int) $product['stock'] > 0): ?>

                                                    <form method="POST"
                                                        action="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                        <input type="hidden" name="product_id"
                                                            value="<?php echo (int) $product['id']; ?>">

                                                        <input type="hidden" name="quantity" value="1">
                                                        <input type="hidden" name="redirect_url"
                                                            value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                        <button type="submit" name="add_to_cart" class="btn-product btn-cart"
                                                            title="Add to cart">
                                                        </button>

                                                    </form>

                                                <?php else: ?>

                                                    <span class="text-danger">
                                                        Out of Stock
                                                    </span>

                                                <?php endif; ?>

                                                <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>"
                                                    class="btn-product btn-view-product" title="View product">
                                                    <i class="icon-eye"></i>
                                                </a>


                                            </div>


                                        </figure>


                                        <div class="product-body">


                                            <div class="product-cat">

                                                <a href="#">

                                                    <?php echo htmlspecialchars(
                                                        $product['category_name'] ?? ''
                                                    ); ?>

                                                </a>

                                            </div>


                                            <h3 class="product-title">

                                                <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>">

                                                    <?php echo htmlspecialchars(
                                                        $product['name']
                                                    ); ?>

                                                </a>

                                            </h3>


                                            <div class="product-price">

                                                Rs
                                                <?php echo number_format(
                                                    (float) $product['price'],
                                                    2
                                                ); ?>

                                            </div>


                                            <!-- Shop Now Button -->

                                            <div class="mt-2">

                                                <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>"
                                                    class="btn btn-primary btn-sm btn-block rounded-0">

                                                    <span>
                                                        Shop Now
                                                    </span>

                                                    <i class="icon-long-arrow-right"></i>

                                                </a>

                                            </div>


                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================
             ICON BOXES
        ========================= -->

            <div class="icon-boxes-container bg-transparent">


                <div class="container">


                    <div class="row">


                        <div class="col-sm-6 col-lg-3">

                            <div class="icon-box icon-box-side">

                                <span class="icon-box-icon text-dark">

                                    <i class="icon-rocket"></i>

                                </span>


                                <div class="icon-box-content">

                                    <h3 class="icon-box-title">
                                        Free Shipping
                                    </h3>

                                    <p>
                                        Orders $50 or more
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="col-sm-6 col-lg-3">

                            <div class="icon-box icon-box-side">

                                <span class="icon-box-icon text-dark">

                                    <i class="icon-rotate-left"></i>

                                </span>


                                <div class="icon-box-content">

                                    <h3 class="icon-box-title">
                                        Free Returns
                                    </h3>

                                    <p>
                                        Within 30 days
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="col-sm-6 col-lg-3">

                            <div class="icon-box icon-box-side">

                                <span class="icon-box-icon text-dark">

                                    <i class="icon-info-circle"></i>

                                </span>


                                <div class="icon-box-content">

                                    <h3 class="icon-box-title">
                                        Get 20% Off 1 Item
                                    </h3>

                                    <p>
                                        When you sign up
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="col-sm-6 col-lg-3">

                            <div class="icon-box icon-box-side">

                                <span class="icon-box-icon text-dark">

                                    <i class="icon-life-bouy"></i>

                                </span>


                                <div class="icon-box-content">

                                    <h3 class="icon-box-title">
                                        We Support
                                    </h3>

                                    <p>
                                        24/7 amazing services
                                    </p>

                                </div>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <div class="mb-5"></div>


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