<?php

require_once '../../config/database.php';
require_once '../../core/Auth.php';

$auth = new Auth($pdo);
$auth->requireAdmin();

$pageTitle = "Products";

/* Fetch products with category name */
$stmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.price,
        p.stock,
        p.image,
        p.status,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {

    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($productId > 0) {

        /*
        |------------------------------------------------------------------
        | DELETE PRODUCT IMAGES FOLDER
        |------------------------------------------------------------------
        */

        $productDirectory =
            '../../public/uploads/products/' . $productId;

        if (is_dir($productDirectory)) {

            $files = scandir($productDirectory);

            foreach ($files as $file) {

                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $productDirectory . '/' . $file;

                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }

            rmdir($productDirectory);
        }


        /*
        |------------------------------------------------------------------
        | DELETE PRODUCT FROM DATABASE
        |------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            DELETE FROM products
            WHERE id = ?
        ");

        $stmt->execute([$productId]);
    }

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <title>Products - ClothWear</title>

    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />

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
</head>

<body class="g-sidenav-show  bg-gray-100">

    <!-- ADMIN HEADER / NAVBAR -->
    <?php require_once '../../includes/admin-header.php'; ?>


    <!-- ADMIN SIDEBAR -->
    <?php require_once '../../includes/admin-sidenavbar.php'; ?>


    <!-- MAIN CONTENT -->
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">

        <div class="container-fluid py-4">


            <!-- =========================
             PAGE HEADER
        ========================== -->

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

                <div>
                    <h4 class="fw-bold text-dark mb-0">
                        Products
                    </h4>

                </div>


                <div class="d-flex align-items-center gap-3 border rounded ps-4">

                    <input type="text" class="form-control" placeholder="Search products..." style="width: 220px;">

                </div>

            </div>


            <!-- =========================
             PRODUCT CARD
        ========================== -->

            <div class="card">


                <!-- CARD HEADER -->

                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <h5 class="mb-0">
                            Product Inventory
                        </h5>


                        <a href="/admin/products/create.php" class="btn bg-gradient-dark btn-sm">

                            <i class="fa-solid fa-plus me-1"></i>

                            Add Product

                        </a>

                    </div>

                </div>


                <!-- =========================
                 PRODUCT TABLE
            ========================== -->

                <div class="card-body px-0 pb-2">

                    <div class="table-responsive p-0">

                        <table class="table align-items-center mb-0">

                            <thead>

                                <tr>

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Product
                                    </th>

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Category
                                    </th>

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Price
                                    </th>

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Stock
                                    </th>

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Status
                                    </th>

                                    <th
                                        class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <!-- =========================
                                 EXAMPLE PRODUCT
                            ========================== -->

                                <?php foreach ($products as $product): ?>

                                    <tr>

                                        <!-- PRODUCT -->

                           <td>

    <div class="d-flex px-3 py-1 align-items-center">

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
            ? '/public/uploads/products/' . $productImages[0]
            : '../assets/img/small-logos/logo-spotify.svg';

        ?>

        <img
            src="<?php echo htmlspecialchars($productImage); ?>"
            class="avatar avatar-sm me-3"
            alt="Product"
        >

        <div class="d-flex flex-column justify-content-center">

            <h6 class="mb-0 text-sm">
                <?php echo htmlspecialchars($product['name']); ?>
            </h6>

            <p class="text-xs text-secondary mb-0">
                Product ID: <?php echo (int) $product['id']; ?>
            </p>

        </div>

    </div>

</td>            


                        <!-- CATEGORY -->

                        <td>

                            <span class="badge bg-gradient-secondary">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                            </span>

                        </td>


                        <!-- PRICE -->

                        <td>

                            <p class="text-xs font-weight-bold mb-0">
                                $<?php echo number_format((float) $product['price'], 2); ?>
                            </p>

                        </td>


                        <!-- STOCK -->

                        <td>

                            <p class="text-xs font-weight-bold mb-0">
                                <?php echo (int) $product['stock']; ?>
                            </p>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php if ((int) $product['status'] === 1): ?>

                                <span class="badge bg-gradient-success">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="badge bg-gradient-secondary">
                                    Inactive
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- ACTION -->

                        <td class="text-end pe-4">


                            <!-- EDIT -->

                            <a href="/admin/products/edit.php?id=<?php echo (int) $product['id']; ?>"
                                class="btn btn-link text-secondary p-0 me-3" title="Edit">

                                <i class="fa-solid fa-pen"></i>

                            </a>


                            <!-- DELETE -->

                            <button type="button" class="btn btn-link text-danger p-0" data-bs-toggle="modal"
                                data-bs-target="#deleteProductModal" data-product-id="<?php echo (int) $product['id']; ?>"
                                title="Delete">

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </td>

                        </tr>

                    <?php endforeach; ?>


                    </tbody>

                    </table>

                </div>

            </div>

        </div>

        <!-- ADMIN FOOTER -->
        <?php require_once '../../includes/admin-footer.php'; ?>
        </div>

    </main>


    <!-- =========================
     DELETE PRODUCT MODAL
========================== -->

    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-sm">

            <div class="modal-content text-center p-3">


                <div class="text-danger fs-1 mb-2">

                    <i class="fa-solid fa-circle-exclamation"></i>

                </div>


                <h6 class="fw-bold">
                    Are you sure?
                </h6>


                <p class="small text-muted mb-3">
                    This product will be permanently deleted.
                </p>


                <div class="d-flex justify-content-center gap-2">


                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                        Cancel
                    </button>


                    <form method="POST" action="index.php" id="deleteProductForm">

                        <input type="hidden" name="product_id" id="deleteProductId" value="">

                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm">
                            Yes, Delete
                        </button>

                    </form>


                </div>

            </div>

        </div>

    </div>


    <!-- Popper -->
    <script src="../assets/js/core/popper.min.js"></script>

    <!-- Bootstrap -->
    <script src="../assets/js/core/bootstrap.min.js"></script>

    <!-- Perfect Scrollbar -->
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>

    <!-- Smooth Scrollbar -->
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>

    <!-- Material Dashboard -->
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const deleteModal = document.getElementById('deleteProductModal');
            const deleteProductId = document.getElementById('deleteProductId');

            if (deleteModal && deleteProductId) {

                deleteModal.addEventListener('show.bs.modal', function (event) {

                    const button = event.relatedTarget;

                    const productId = button.getAttribute('data-product-id');

                    deleteProductId.value = productId;
                });
            }

        });
    </script>
</body>

</html>