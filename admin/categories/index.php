<?php

require_once '../../config/database.php';
require_once '../../core/Auth.php';

$auth = new Auth($pdo);
$auth->requireAdmin();


/* =========================
   DELETE CATEGORY
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {

    $categoryId = (int) ($_POST['category_id'] ?? 0);

    if ($categoryId > 0) {

        $stmt = $pdo->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");

        $stmt->execute([$categoryId]);
    }

    header('Location: index.php');
    exit;
}


/* =========================
   FETCH CATEGORIES
========================= */

$stmt = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.image,
        c.status,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.image, c.status
    ORDER BY c.id DESC
");

$categories = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="apple-touch-icon"
          sizes="76x76"
          href="../assets/img/apple-icon.png">

    <link rel="icon"
          type="image/png"
          href="../assets/img/favicon.png">

    <title>Categories - ClothWear</title>


    <!-- Fonts -->

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900">


    <!-- Nucleo Icons -->

    <link href="../assets/css/nucleo-icons.css"
          rel="stylesheet">

    <link href="../assets/css/nucleo-svg.css"
          rel="stylesheet">


    <!-- Font Awesome -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Material Icons -->

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">


    <!-- Material Dashboard CSS -->

    <link id="pagestyle"
          href="../assets/css/material-dashboard.css?v=3.2.0"
          rel="stylesheet">


    <style>

        .search-wrapper {
            width: 180px;
        }

        .search-input {
            width: 100%;
        }


        /* Small screens */

        @media (max-width: 400px) {

            .search-wrapper {
                width: 130px;
                padding-left: 6px !important;
                padding-right: 6px !important;
            }

            .search-input {
                font-size: 13px;
                padding: 6px 4px;
            }

        }


        /* Very small screens */

        @media (max-width: 340px) {

            .search-wrapper {
                width: 115px;
            }

            .search-input {
                font-size: 12px;
                padding: 5px 3px;
            }

            .d-flex.justify-content-between {
                gap: 8px !important;
            }

        }


        @media (max-width: 576px) {

            .category-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px;
            }

            .category-header h5 {
                font-size: 17px;
            }

            .category-header .btn {
                width: 100%;
                text-align: center;
            }

        }


        /* =========================
           CATEGORY IMAGE
        ========================= */

        .category-image {

            width: 50px;
            height: 50px;

            object-fit: cover;

            border-radius: 8px;

        }


        .category-image-placeholder {

            width: 50px;
            height: 50px;

            border-radius: 8px;

            background: #f0f2f5;

            display: flex;
            align-items: center;
            justify-content: center;

        }

    </style>

</head>


<body class="g-sidenav-show bg-gray-100">


    <!-- ADMIN HEADER / NAVBAR -->

    <?php require_once '../../includes/admin-header.php'; ?>


    <!-- ADMIN SIDEBAR -->

    <?php require_once '../../includes/admin-sidenavbar.php'; ?>


    <!-- MAIN CONTENT -->

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">


        <!-- PAGE CONTENT -->

        <div class="container-fluid py-4">


            <!-- PAGE HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">


                <div>

                    <h4 class="fw-bold text-dark mb-0">
                        Categories
                    </h4>

                </div>


                <!-- SEARCH -->

                <div class="d-flex align-items-center gap-3 border rounded ps-4">

                    <input type="text"
                           class="form-control"
                           placeholder="Search categories..."
                           style="width: 220px;">

                </div>


            </div>


            <!-- CATEGORY CARD -->

            <div class="card">


                <!-- CARD HEADER -->

                <div class="card-header pb-0">


                    <div class="category-header d-flex justify-content-between align-items-center">


                        <h5 class="mb-0">
                            Category Management
                        </h5>


                        <!-- ADD CATEGORY -->

                        <a href="create.php"
                           class="btn bg-gradient-dark btn-sm">

                            <i class="fa-solid fa-plus me-1"></i>

                            Add Category

                        </a>


                    </div>

                </div>


                <!-- CATEGORY TABLE -->

                <div class="card-body px-0 pb-2">


                    <div class="table-responsive p-0">


                        <table class="table align-items-center mb-0">


                            <thead>

                                <tr>


                                    <!-- IMAGE -->

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Image
                                    </th>


                                    <!-- CATEGORY NAME -->

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Category Name
                                    </th>


                                    <!-- ITEMS COUNT -->

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Items Count
                                    </th>


                                    <!-- STATUS -->

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Status
                                    </th>


                                    <!-- ACTION -->

                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php if (empty($categories)): ?>


                                    <!-- No Categories -->

                                    <tr>

                                        <td colspan="5"
                                            class="text-center py-4">

                                            <p class="text-sm text-secondary mb-0">
                                                No categories found.
                                            </p>

                                        </td>

                                    </tr>


                                <?php else: ?>


                                    <?php foreach ($categories as $category): ?>

                                        <tr>


                                            <!-- CATEGORY IMAGE -->

                                            <td>

                                                <div class="d-flex px-3 py-1">

                                                    <?php if (!empty($category['image'])): ?>

                                                        <img src="../../public/uploads/categories/<?php echo htmlspecialchars($category['image']); ?>"
                                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                             class="category-image">

                                                    <?php else: ?>

                                                        <div class="category-image-placeholder">

                                                            <i class="fa-solid fa-image text-secondary"></i>

                                                        </div>

                                                    <?php endif; ?>

                                                </div>

                                            </td>


                                            <!-- CATEGORY NAME -->

                                            <td>

                                                <div class="d-flex px-3 py-1">


                                                    <div class="d-flex flex-column justify-content-center">


                                                        <h6 class="mb-0 text-sm">

                                                            <?php echo htmlspecialchars($category['name']); ?>

                                                        </h6>


                                                    </div>

                                                </div>

                                            </td>


                                            <!-- ITEMS COUNT -->

                                            <td>

                                                <p class="text-xs font-weight-bold mb-0">

                                                    <?php

                                                    $count = (int) $category['product_count'];

                                                    echo $count . ' ' .
                                                        ($count === 1 ? 'Product' : 'Products');

                                                    ?>

                                                </p>

                                            </td>


                                            <!-- STATUS -->

                                            <td>

                                                <?php if ((string)$category['status'] === '1'): ?>

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

                                                <a href="edit.php?id=<?php echo (int)$category['id']; ?>"
                                                   class="btn btn-link text-secondary p-0 me-3"
                                                   title="Edit Category">

                                                    <i class="fa-solid fa-pen"></i>

                                                </a>


                                                <!-- DELETE -->

                                                <button type="button"
                                                        class="btn btn-link text-danger p-0"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteCatModal"
                                                        data-category-id="<?php echo (int)$category['id']; ?>">

                                                    <i class="fa-solid fa-trash"></i>

                                                </button>


                                            </td>


                                        </tr>


                                    <?php endforeach; ?>


                                <?php endif; ?>


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
         DELETE CATEGORY MODAL
    ========================== -->

    <div class="modal fade"
         id="deleteCatModal"
         tabindex="-1">


        <div class="modal-dialog modal-dialog-centered modal-sm">


            <div class="modal-content text-center p-3">


                <div class="text-danger fs-1 mb-2">

                    <i class="fa-solid fa-circle-exclamation"></i>

                </div>


                <h6 class="fw-bold">

                    Are you sure?

                </h6>


                <p class="small text-muted mb-3">

                    Do you want to delete this category?

                </p>


                <div class="d-flex justify-content-center gap-2">


                    <!-- CANCEL -->

                    <button type="button"
                            class="btn btn-light btn-sm"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>


                    <!-- DELETE -->

                    <form method="POST"
                          action="index.php"
                          id="deleteCategoryForm">

                        <input type="hidden"
                               name="category_id"
                               id="deleteCategoryId"
                               value="">

                        <button type="submit"
                                name="delete_category"
                                class="btn btn-danger btn-sm">

                            Yes, Delete

                        </button>

                    </form>


                </div>

            </div>

        </div>

    </div>



    <!-- =========================
         JAVASCRIPT
    ========================== -->

    <script src="../assets/js/core/popper.min.js"></script>

    <script src="../assets/js/core/bootstrap.min.js"></script>

    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>

    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>

    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>


    <script>

        const deleteModal = document.getElementById('deleteCatModal');

        const deleteCategoryId = document.getElementById('deleteCategoryId');


        deleteModal.addEventListener('show.bs.modal', function (event) {

            const button = event.relatedTarget;

            const categoryId = button.getAttribute('data-category-id');

            deleteCategoryId.value = categoryId;

        });

    </script>


</body>

</html>