<?php

require_once '../config/database.php';


/* =========================
   START SESSION
========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   GET CATEGORY ID
========================= */

$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);

if (!$categoryId) {
    $categoryId = 0;
}


/* =========================
   FETCH PRODUCTS
========================= */

if ($categoryId > 0) {

    /*
     * IMPORTANT:
     * Category page only shows products
     * belonging to this exact category.
     */

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.category_id,
            p.name,
            p.price,
            p.stock,
            p.image,
            p.status,
            c.name AS category_name
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        WHERE p.category_id = ?
          AND p.status = 1
          AND c.status = 1
        ORDER BY p.id DESC
    ");

    $stmt->execute([$categoryId]);

} else {

    /*
     * All products page
     */

    $stmt = $pdo->query("
        SELECT
            p.id,
            p.category_id,
            p.name,
            p.price,
            p.stock,
            p.image,
            p.status,
            c.name AS category_name
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        WHERE p.status = 1
          AND c.status = 1
        ORDER BY p.id DESC
    ");
}


$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   FETCH YOU MAY ALSO LIKE
========================= */

if ($categoryId > 0) {

    $relatedStmt = $pdo->prepare("
        SELECT
            p.id,
            p.category_id,
            p.name,
            p.price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        WHERE p.status = 1
          AND c.status = 1
          AND p.category_id != ?
        ORDER BY RAND()
        LIMIT 5
    ");

    $relatedStmt->execute([$categoryId]);

} else {

    $relatedStmt = $pdo->query("
        SELECT
            p.id,
            p.category_id,
            p.name,
            p.price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        WHERE p.status = 1
          AND c.status = 1
        ORDER BY RAND()
        LIMIT 5
    ");
}

$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   CATEGORY NAME
========================= */

$categoryName = 'All Products';

if ($categoryId > 0) {

    $categoryStmt = $pdo->prepare("
        SELECT
            id,
            name
        FROM categories
        WHERE id = ?
          AND status = 1
        LIMIT 1
    ");

    $categoryStmt->execute([$categoryId]);

    $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {

        $categoryName = $category['name'];

    } else {

        $categoryId = 0;
        $categoryName = 'All Products';

    }
}


?>

<!DOCTYPE html>
<html lang="en">


<!-- molla/product-detail.php  22 Nov 2019 09:54:50 GMT -->

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


    <!-- Plugins CSS File -->

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <link rel="stylesheet" href="assets/css/plugins/owl-carousel/owl.carousel.css">

    <link rel="stylesheet" href="assets/css/plugins/magnific-popup/magnific-popup.css">


    <!-- Main CSS File -->

    <link rel="stylesheet" href="assets/css/style.css">


    <style>
        .product-action .btn-cart:hover,
        .product-action .btn-cart:focus {
            color: inherit !important;
            font-weight: 700 !important;
        }

        .product-action .btn-cart:hover span,
        .product-action .btn-cart:focus span {
            color: inherit !important;
            font-weight: 700 !important;
        }

        .product-action .btn-cart:hover i,
        .product-action .btn-cart:focus i {
            color: inherit !important;
        }
    </style>

    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">

</head>


<body>

    <div class="page-wrapper">

        <?php include '../includes/header.php'; ?>


        <main class="main">


            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">

                <div class="container d-flex align-items-center">

                    <ol class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php">
                                Home
                            </a>

                        </li>


                        <li class="breadcrumb-item active" aria-current="page">

                            Products

                        </li>


                        <li class="breadcrumb-item">

                            <a href="product-detail.php?id=<?= !empty($products) ? (int) $products[0]['id'] : 0 ?>">

                                Product Detail

                            </a>

                        </li>

                    </ol>


                    <nav class="product-pager ml-auto" aria-label="Product">

                        <a class="product-pager-link product-pager-prev" href="#" aria-label="Previous" tabindex="-1">

                            <i class="icon-angle-left"></i>

                            <span>
                                Prev
                            </span>

                        </a>


                        <a class="product-pager-link product-pager-next" href="#" aria-label="Next" tabindex="-1">

                            <span>
                                Next
                            </span>

                            <i class="icon-angle-right"></i>

                        </a>

                    </nav>

                </div>

            </nav>


            <div class="page-content">

                <div class="container">

                    <div class="page-content">

                        <div class="container">


                            <!-- Category Heading -->

                            <div class="heading heading-center mb-3">

                                <h2 class="title">

                                    <?= htmlspecialchars($categoryName) ?>

                                </h2>

                            </div>


                            <div class="products">

                                <div class="row">


                                    <?php if (!empty($products)): ?>


                                        <?php foreach ($products as $product): ?>


                                            <div class="col-6 col-md-4 col-lg-3">

                                                <div class="product product-7 text-center">


                                                    <figure class="product-media">


                                                        <!-- =========================
                                                             STOCK STATUS
                                                        ========================== -->

                                                        <?php if ((int) ($product['stock'] ?? 0) > 0): ?>

                                                            <span class="product-label label-new">

                                                                Available

                                                            </span>

                                                        <?php else: ?>

                                                            <span class="product-label label-out">

                                                                Out of Stock

                                                            </span>

                                                        <?php endif; ?>


                                                        <!-- =========================
                                                             PRODUCT IMAGE
                                                        ========================== -->

                                                        <a href="product-detail.php?id=<?= (int) $product['id'] ?>">

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
                                                                : 'assets/images/products/product-1.jpg';

                                                            ?>

                                                            <img src="<?= htmlspecialchars($productImage) ?>"
                                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                                class="product-image">

                                                        </a>


                                                        <!-- =========================
                                                             ADD TO CART
                                                        ========================== -->

                                                        <div class="product-action">


                                                            <?php if ((int) $product['stock'] > 0): ?>


                                                                <form method="POST"
                                                                    action="product-detail.php?id=<?= (int) $product['id'] ?>"
                                                                    style="display: inline;">


                                                                    <input type="hidden" name="product_id"
                                                                        value="<?= (int) $product['id'] ?>">


                                                                    <input type="hidden" name="quantity" value="1">

                                                                    <input type="hidden" name="redirect_url"
                                                                        value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

                                                                    <button type="submit" name="add_to_cart"
                                                                        class="btn-product btn-cart"
                                                                        style="border: 0; background: none; padding: 0;">

                                                                        <span>
                                                                            add to cart
                                                                        </span>

                                                                    </button>


                                                                </form>


                                                            <?php else: ?>


                                                                <span class="text-danger">

                                                                    Out of Stock

                                                                </span>


                                                            <?php endif; ?>


                                                        </div>


                                                    </figure>


                                                    <!-- =========================
                                                         PRODUCT BODY
                                                    ========================== -->

                                                    <div class="product-body">


                                                        <div class="product-cat">


                                                            <a href="#">

                                                                <?= htmlspecialchars(
                                                                    $product['category_name'] ?? 'Uncategorized'
                                                                ) ?>


                                                            </a>


                                                        </div>


                                                        <h3 class="product-title">


                                                            <a href="product-detail.php?id=<?= (int) $product['id'] ?>">


                                                                <?= htmlspecialchars(
                                                                    $product['name']
                                                                ) ?>


                                                            </a>


                                                        </h3>


                                                        <div class="product-price">

                                                            Rs

                                                            <?= number_format(
                                                                (float) $product['price'],
                                                                2
                                                            ) ?>


                                                        </div>

                                                    </div>


                                                </div>

                                            </div>


                                        <?php endforeach; ?>


                                    <?php else: ?>


                                        <div class="col-12">


                                            <div class="alert alert-info text-center">

                                                No products found in this category.

                                            </div>


                                        </div>


                                    <?php endif; ?>


                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- =========================
                         YOU MAY ALSO LIKE
                    ========================= -->

                    <?php if (!empty($relatedProducts)): ?>


                        <h2 class="title text-center mb-4">

                            You May Also Like

                        </h2>


                        <div class="owl-carousel owl-simple carousel-equal-height carousel-with-shadow" data-toggle="owl"
                            data-owl-options='{
                                "nav": false,
                                "dots": true,
                                "margin": 20,
                                "loop": false,
                                "responsive": {
                                    "0": {
                                        "items": 1
                                    },
                                    "480": {
                                        "items": 2
                                    },
                                    "768": {
                                        "items": 3
                                    },
                                    "992": {
                                        "items": 4
                                    },
                                    "1200": {
                                        "items": 4,
                                        "nav": true,
                                        "dots": false
                                    }
                                }
                            }'>


                            <?php foreach ($relatedProducts as $relatedProduct): ?>


                                <?php

                                $relatedImages = [];

                                if (!empty($relatedProduct['image'])) {

                                    $decodedRelatedImages = json_decode(
                                        $relatedProduct['image'],
                                        true
                                    );

                                    if (
                                        is_array($decodedRelatedImages) &&
                                        !empty($decodedRelatedImages)
                                    ) {

                                        $relatedImages = $decodedRelatedImages;

                                    } else {

                                        // Old single-image products
                                        $relatedImages = [$relatedProduct['image']];
                                    }
                                }

                                $relatedImage = !empty($relatedImages)
                                    ? 'uploads/products/' . $relatedImages[0]
                                    : 'assets/images/products/product-1.jpg';

                                ?>


                                <div class="product product-7 text-center">


                                    <figure class="product-media">


                                        <?php if ((int) $relatedProduct['stock'] <= 0): ?>


                                            <span class="product-label label-out">

                                                Out of Stock

                                            </span>


                                        <?php endif; ?>


                                        <a href="product-detail.php?id=<?= (int) $relatedProduct['id'] ?>">


                                            <img src="<?= htmlspecialchars($relatedImage) ?>"
                                                alt="<?= htmlspecialchars($relatedProduct['name']) ?>" class="product-image">


                                        </a>


                                        <div class="product-action">


                                            <?php if ((int) $relatedProduct['stock'] > 0): ?>


                                                <form method="POST"
                                                    action="product-detail.php?id=<?= (int) $relatedProduct['id'] ?>"
                                                    style="display:inline;">


                                                    <input type="hidden" name="product_id"
                                                        value="<?= (int) $relatedProduct['id'] ?>">


                                                    <input type="hidden" name="quantity" value="1">

                                                    <input type="hidden" name="redirect_url"
                                                        value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">


                                                    <button type="submit" name="add_to_cart" class="btn-product btn-cart"
                                                        style="border:0;background:none;padding:0;">


                                                        <span>
                                                            add to cart
                                                        </span>


                                                    </button>


                                                </form>


                                            <?php else: ?>


                                                <span class="text-danger">

                                                    Out of Stock

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                    </figure>


                                    <div class="product-body">


                                        <div class="product-cat">


                                            <a href="products.php?category=<?= (int) $relatedProduct['category_id'] ?>">


                                                <?= htmlspecialchars(
                                                    $relatedProduct['category_name'] ?? 'Uncategorized'
                                                ) ?>


                                            </a>


                                        </div>


                                        <h3 class="product-title">


                                            <a href="product-detail.php?id=<?= (int) $relatedProduct['id'] ?>">


                                                <?= htmlspecialchars(
                                                    $relatedProduct['name']
                                                ) ?>


                                            </a>


                                        </h3>


                                        <div class="product-price">

                                            Rs

                                            <?= number_format(
                                                (float) $relatedProduct['price'],
                                                2
                                            ) ?>


                                        </div>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </div>

            </div>


        </main>


        <?php include '../includes/footer.php'; ?>


    </div>


    <button id="scroll-top" title="Back to Top">

        <i class="icon-arrow-up"></i>

    </button>


    <!-- Plugins JS File -->

    <script src="assets/js/jquery.min.js"></script>

    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/jquery.hoverIntent.min.js"></script>

    <script src="assets/js/jquery.waypoints.min.js"></script>

    <script src="assets/js/superfish.min.js"></script>

    <script src="assets/js/owl.carousel.min.js"></script>

    <script src="assets/js/bootstrap-input-spinner.js"></script>

    <script src="assets/js/jquery.elevateZoom.min.js"></script>

    <script src="assets/js/bootstrap-input-spinner.js"></script>

    <script src="assets/js/jquery.magnific-popup.min.js"></script>


    <!-- Main JS File -->

    <script src="assets/js/main.js"></script>

</body>


<!-- molla/product-detail.php  22 Nov 2019 09:55:05 GMT -->

</html>