<?php

require_once '../config/database.php';


/* =========================
   START SESSION
========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   ADD TO CART
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    /* =========================
       LOGIN CHECK
    ========================= */

    if (!isset($_SESSION['user_id'])) {

        header('Location: /public/login.php');
        exit;
    }


    /* =========================
       GET POST DATA
    ========================= */

    $cartProductId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);


    /* =========================
       VALIDATE DATA
    ========================= */

    if ($cartProductId > 0 && $quantity > 0) {

        /* =========================
           CHECK PRODUCT
        ========================= */

        $cartStmt = $pdo->prepare("
            SELECT
                id,
                stock
            FROM products
            WHERE id = ?
              AND status = 1
            LIMIT 1
        ");

        $cartStmt->execute([$cartProductId]);

        $cartProduct = $cartStmt->fetch(PDO::FETCH_ASSOC);


        /* =========================
           CHECK STOCK
        ========================= */

        if ($cartProduct && (int) $cartProduct['stock'] > 0) {

            /* =========================
               CREATE CART SESSION
            ========================= */

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }


            /* =========================
               EXISTING QUANTITY
            ========================= */

            $existingQuantity =
                (int) ($_SESSION['cart'][$cartProductId] ?? 0);


            /* =========================
               NEW QUANTITY
            ========================= */

            $newQuantity =
                $existingQuantity + $quantity;


            /* =========================
               STOCK LIMIT
            ========================= */

            if ($newQuantity > (int) $cartProduct['stock']) {

                $newQuantity =
                    (int) $cartProduct['stock'];
            }


            /* =========================
               SAVE TO CART
            ========================= */

            $_SESSION['cart'][$cartProductId] =
                $newQuantity;
        }
    }


    /* =========================
       GO TO CART
    ========================= */

    $redirectUrl = $_POST['redirect_url'] ?? $_SERVER['REQUEST_URI'];

    header('Location: ' . $redirectUrl);
    exit;
}


/* =========================
   GET PRODUCT ID
========================= */

$productId = (int) ($_GET['id'] ?? 0);


if ($productId <= 0) {

    header('Location: /public/products.php');
    exit;
}

/* =========================
   FETCH PRODUCT
========================= */

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.stock,
        p.image,
        p.status,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
      AND p.status = 1
    LIMIT 1
");

$stmt->execute([$productId]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================
   PRODUCT NOT FOUND
========================= */

if (!$product) {

    header('Location: products.php');
    exit;
}


/* =========================
   FETCH YOU MAY ALSO LIKE
========================= */

$relatedStmt = $pdo->prepare("
    SELECT
        p.id,
        p.category_id,
        p.name,
        p.price,
        p.stock,
        p.image,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id != ?
      AND p.status = 1
    ORDER BY RAND()
    LIMIT 5
");

$relatedStmt->execute([
    $productId
]);

$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   PRODUCT IMAGES
========================= */

$productImages = [];

if (!empty($product['image'])) {

    $decodedImages = json_decode(
        $product['image'],
        true
    );

    if (is_array($decodedImages)) {

        $productImages = $decodedImages;

    } else {

        // Old single-image products
        $productImages = [$product['image']];
    }
}


/* =========================
   MAIN PRODUCT IMAGE
========================= */

if (!empty($productImages)) {

    $productImage =
        'uploads/products/' . $productImages[0];

} else {

    $productImage =
        'assets/images/products/single/1.jpg';
}


?>

<!DOCTYPE html>
<html lang="en">


<!-- molla/product-gallery.html  22 Nov 2019 10:03:27 GMT -->

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
        .btn-cart span {
            opacity: 1 !important;
            visibility: visible !important;
        }

        .btn-cart:hover span {
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
        }
    </style>


    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">

</head>


<body>

    <div class="page-wrapper">


        <?php include '../includes/header.php'; ?>


        <main class="main">

            <div class="page-content">


                <!-- =====================================================
                 PRODUCT DETAILS TOP
            ====================================================== -->

                <div class="product-details-top">


                    <div class="bg-light pb-5 mb-4">


                        <!-- =========================
                         BREADCRUMB
                    ========================== -->

                        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">

                            <div class="container d-flex align-items-center">


                                <ol class="breadcrumb">


                                    <li class="breadcrumb-item">

                                        <a href="index.php">
                                            Home
                                        </a>

                                    </li>


                                    <li class="breadcrumb-item">

                                        <a href="products.php">
                                            Products
                                        </a>

                                    </li>


                                    <li class="breadcrumb-item active" aria-current="page">

                                        Product Detail

                                    </li>


                                </ol>


                                <nav class="product-pager ml-auto" aria-label="Product">


                                    <a class="product-pager-link product-pager-prev" href="#" aria-label="Previous"
                                        tabindex="-1">

                                        <i class="icon-angle-left"></i>

                                        <span>
                                            Prev
                                        </span>

                                    </a>


                                    <a class="product-pager-link product-pager-next" href="#" aria-label="Next"
                                        tabindex="-1">

                                        <span>
                                            Next
                                        </span>

                                        <i class="icon-angle-right"></i>

                                    </a>


                                </nav>


                            </div>

                        </nav>


                        <!-- =========================
 PRODUCT IMAGE GALLERY
========================== -->

                        <div class="container">

                            <div class="product-gallery-image">

                                <!-- MAIN IMAGE -->

                                <img id="mainProductImage" src="<?= htmlspecialchars($productImage) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>">

                            </div>


                            <!-- =========================
         IMAGE THUMBNAILS
    ========================== -->

                                                    <?php if (count($productImages) > 1): ?>

                                <div class="product-image-thumbnails" style="
                display:flex;
                justify-content:center;
                align-items:center;
                gap:12px;
                margin-top:20px;
                flex-wrap:wrap;
            ">

                                                            <?php foreach ($productImages as $index => $image): ?>

                                        <div class="product-image-thumbnail" style="
                        width:80px;
                        height:80px;
                        cursor:pointer;
                        border:1px solid #ddd;
                        overflow:hidden;
                    " onclick="changeProductImage(
                        '<?= htmlspecialchars(
                            'uploads/products/' . $image,
                            ENT_QUOTES
                        ) ?>',
                        this
                    )">

                                            <img src="<?= htmlspecialchars(
                                                'uploads/products/' . $image
                                            ) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="
                            width:100%;
                            height:100%;
                            object-fit:cover;
                        ">

                                        </div>

                                                            <?php endforeach; ?>

                                </div>

                                                    <?php endif; ?>

                        </div>


                    </div>


                    <!-- =================================================
                     PRODUCT INFORMATION
                ================================================== -->

                    <div class="product-details product-details-centered product-details-separator">


                        <div class="container">


                            <div class="row">


                                <!-- =========================
                                 LEFT SIDE
                            ========================== -->

                                <div class="col-md-6">


                                    <h1 class="product-title">

                                        <?= htmlspecialchars($product['name']) ?>

                                    </h1>


                                    <!-- PRICE -->

                                    <div class="product-price">

                                        Rs
                                        <?= number_format(
                                            (float) $product['price'],
                                            2
                                        ) ?>

                                    </div>


                                    <!-- DESCRIPTION -->

                                    <div class="product-content">

                                        <p>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $product['description']
                                                )
                                            ) ?>

                                        </p>

                                    </div>


                                    <div style="height: 105px;"></div>


                                </div>


                                <!-- =========================
                                 RIGHT SIDE
                            ========================== -->

                                <div class="col-md-6">


                                    <div class="product-details-action">


                                        <!-- =================================================
                                         MAIN ADD TO CART FORM
                                    ================================================== -->

                                        <form method="POST" action="product-detail.php?id=<?= (int) $product['id']; ?>"
                                            class="details-action-col">


                                            <input type="hidden" name="product_id" value="<?= (int) $product['id']; ?>">


                                            <?php if ((int) $product['stock'] > 0): ?>


                                                <!-- QUANTITY -->

                                                <div class="product-details-quantity">


                                                    <input type="number" id="qty" name="quantity" class="form-control"
                                                        value="1" min="1" max="<?= (int) $product['stock']; ?>" step="1"
                                                        data-decimals="0" required>


                                                </div>
                                                <input type="hidden" name="redirect_url"
                                                    value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

                                                <!-- ADD TO CART -->

                                                <button type="submit" name="add_to_cart" class="btn-product btn-cart"
                                                    style="border: 0; background: none;">

                                                    <span>
                                                        add to cart
                                                    </span>

                                                </button>


                                            <?php else: ?>


                                                <!-- OUT OF STOCK -->

                                                <div class="product-details-quantity">

                                                    <span class="text-danger">

                                                        Out of Stock

                                                    </span>

                                                </div>


                                            <?php endif; ?>


                                        </form>


                                    </div>


                                    <!-- =========================
                                     PRODUCT FOOTER
                                ========================== -->

                                    <div class="product-details-footer details-footer-col">


                                        <div class="product-cat">


                                            <span>
                                                Category:
                                            </span>


                                            <?php if (!empty($product['category_name'])): ?>


                                                <a href="#">

                                                    <?= htmlspecialchars(
                                                        $product['category_name']
                                                    ) ?>

                                                </a>


                                            <?php else: ?>


                                                <span>
                                                    Uncategorized
                                                </span>


                                            <?php endif; ?>


                                        </div>

                                    </div>


                                </div>


                            </div>


                        </div>


                    </div>


                </div>


                <!-- =====================================================
                 PRODUCT TABS
            ====================================================== -->

                <div class="container">


                    <div class="product-details-tab">


                        <ul class="nav nav-pills justify-content-center" role="tablist">


                            <li class="nav-item">

                                <a class="nav-link active" id="product-desc-link" data-toggle="tab"
                                    href="#product-desc-tab" role="tab" aria-controls="product-desc-tab"
                                    aria-selected="true">

                                    Description

                                </a>

                            </li>


                            <li class="nav-item">

                                <a class="nav-link" id="product-info-link" data-toggle="tab" href="#product-info-tab"
                                    role="tab" aria-controls="product-info-tab" aria-selected="false">

                                    Additional information

                                </a>

                            </li>


                            <li class="nav-item">

                                <a class="nav-link" id="product-shipping-link" data-toggle="tab"
                                    href="#product-shipping-tab" role="tab" aria-controls="product-shipping-tab"
                                    aria-selected="false">

                                    Shipping & Returns

                                </a>

                            </li>


                        </ul>


                        <div class="tab-content">


                            <!-- =========================
                             DESCRIPTION
                        ========================== -->

                            <div class="tab-pane fade show active" id="product-desc-tab" role="tabpanel"
                                aria-labelledby="product-desc-link">


                                <div class="product-desc-content">


                                    <h3>
                                        Product Information
                                    </h3>


                                    <p>

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $product['description']
                                            )
                                        ) ?>

                                    </p>


                                    <ul>


                                        <li>

                                            Category:

                                            <?= htmlspecialchars(
                                                $product['category_name']
                                                ?? 'Uncategorized'
                                            ) ?>

                                        </li>


                                        <li>

                                            Product:

                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>

                                        </li>


                                        <li>

                                            Price:

                                            Rs
                                            <?= number_format(
                                                (float) $product['price'],
                                                2
                                            ) ?>

                                        </li>


                                    </ul>


                                </div>


                            </div>


                            <!-- =========================
                             ADDITIONAL INFORMATION
                        ========================== -->

                            <div class="tab-pane fade" id="product-info-tab" role="tabpanel"
                                aria-labelledby="product-info-link">


                                <div class="product-desc-content">


                                    <h3>
                                        Additional Information
                                    </h3>


                                    <ul>


                                        <li>

                                            <strong>
                                                Category:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $product['category_name']
                                                ?? 'Uncategorized'
                                            ) ?>

                                        </li>


                                        <li>

                                            <strong>
                                                Product:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>

                                        </li>


                                        <li>

                                            <strong>
                                                Price:
                                            </strong>

                                            Rs
                                            <?= number_format(
                                                (float) $product['price'],
                                                2
                                            ) ?>

                                        </li>


                                        <li>

                                            <strong>
                                                Available Stock:
                                            </strong>

                                            <?= (int) $product['stock'] ?>

                                        </li>


                                        <li>

                                            <strong>
                                                Status:
                                            </strong>


                                            <?php if ((int) $product['stock'] > 0): ?>

                                                Available

                                            <?php else: ?>

                                                Out of Stock

                                            <?php endif; ?>


                                        </li>


                                    </ul>


                                </div>


                            </div>


                            <!-- =========================
                             SHIPPING & RETURNS
                        ========================== -->

                            <div class="tab-pane fade" id="product-shipping-tab" role="tabpanel"
                                aria-labelledby="product-shipping-link">


                                <div class="product-desc-content">


                                    <h3>
                                        Availability & Shipping
                                    </h3>


                                    <?php if ((int) $product['stock'] > 0): ?>


                                        <p>

                                            This product is currently available.

                                            <strong>
                                                <?= (int) $product['stock'] ?>
                                            </strong>

                                            item(s) are available in stock.

                                        </p>


                                    <?php else: ?>


                                        <p class="text-danger">

                                            This product is currently out of stock.

                                        </p>


                                    <?php endif; ?>


                                    <div style="margin-top: 30px;">


                                        <h3>
                                            Returns
                                        </h3>


                                        <p>

                                            Please contact us for return and exchange information
                                            regarding this product.

                                        </p>


                                    </div>


                                </div>


                            </div>


                        </div>


                    </div>


                </div>


            </div>

            <!-- =====================================================
     YOU MAY ALSO LIKE
====================================================== -->

            <?php if (!empty($relatedProducts)): ?>

                <div class="container" style="margin-top: 60px; margin-bottom: 60px;">

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

                                    <a href="product-detail.php?id=<?= (int) $relatedProduct['id']; ?>">

                                        <img src="<?= htmlspecialchars($relatedImage); ?>"
                                            alt="<?= htmlspecialchars($relatedProduct['name']); ?>" class="product-image">

                                    </a>

                                </figure>


                                <div class="product-body">

                                    <div class="product-cat">

                                        <a href="products.php?category=<?= (int) $relatedProduct['category_id']; ?>">

                                            <?= htmlspecialchars(
                                                $relatedProduct['category_name'] ?? 'Uncategorized'
                                            ); ?>

                                        </a>

                                    </div>


                                    <h3 class="product-title">

                                        <a href="product-detail.php?id=<?= (int) $relatedProduct['id']; ?>">

                                            <?= htmlspecialchars($relatedProduct['name']); ?>

                                        </a>

                                    </h3>


                                    <div class="product-price">

                                        Rs
                                        <?= number_format(
                                            (float) $relatedProduct['price'],
                                            2
                                        ); ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>
        </main>


        <?php include '../includes/footer.php'; ?>


    </div>


    <button id="scroll-top" title="Back to Top">

        <i class="icon-arrow-up"></i>

    </button>


    <!-- =========================
     PLUGINS JS FILE
========================= -->

    <script src="assets/js/jquery.min.js"></script>

    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/jquery.hoverIntent.min.js"></script>

    <script src="assets/js/jquery.waypoints.min.js"></script>

    <script src="assets/js/superfish.min.js"></script>

    <script src="assets/js/owl.carousel.min.js"></script>

    <script src="assets/js/bootstrap-input-spinner.js"></script>

    <script src="assets/js/jquery.magnific-popup.min.js"></script>

    <script src="assets/js/main.js"></script>

    <script>

        function changeProductImage(imageUrl, thumbnail) {

            const mainImage =
                document.getElementById('mainProductImage');

            if (mainImage) {

                mainImage.src = imageUrl;

            }


            /* Remove active border from all thumbnails */

            document
                .querySelectorAll('.product-image-thumbnail')
                .forEach(function (item) {

                    item.style.border = '1px solid #ddd';

                });


            /* Highlight selected thumbnail */

            thumbnail.style.border =
                '2px solid #333';

        }

    </script>
</body>


<!-- molla/product-gallery.html  22 Nov 2019 10:03:29 GMT -->

</html>