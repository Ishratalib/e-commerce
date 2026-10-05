<?php

require_once '../../config/database.php';
require_once '../../core/Auth.php';

$auth = new Auth($pdo);
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {

    $name = trim($_POST['name'] ?? '');
    $status = $_POST['status'] ?? '1';

    if ($name !== '' && in_array($status, ['1', '0'], true)) {

        /* =========================
           CREATE SLUG
        ========================= */

        $slug = strtolower(trim($name));

        // Replace spaces with hyphens
        $slug = preg_replace('/\s+/', '-', $slug);

        // Remove anything except letters, numbers and hyphens
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);


        /* =========================
           IMAGE UPLOAD
        ========================= */

        $imageName = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $uploadDir = '../../public/uploads/categories/';

            // Create unique filename
            $extension = strtolower(
                pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)
            );

            $imageName = $slug . '-' . time() . '.' . $extension;

            $uploadPath = $uploadDir . $imageName;

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $uploadPath
            );
        }


        /* =========================
           INSERT CATEGORY
        ========================= */

        $stmt = $pdo->prepare("
            INSERT INTO categories (name, slug, image, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $slug,
            $imageName,
            $status
        ]);

        header('Location: index.php');
        exit;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">

    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <title>Create Category - ClothWear</title>


    <!-- Fonts -->

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900">


    <!-- Nucleo Icons -->

    <link href="../assets/css/nucleo-icons.css" rel="stylesheet">

    <link href="../assets/css/nucleo-svg.css" rel="stylesheet">


    <!-- Font Awesome -->

    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>


    <!-- Material Icons -->

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">


    <!-- Material Dashboard CSS -->

    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet">


    <!-- CUSTOM CATEGORY FORM CSS -->

    <style>
        .category-form-card {
            border-radius: 16px;
            overflow: hidden;
        }

        .category-form-header {
            padding: 28px 30px 18px;
            border-bottom: 1px solid #e9ecef;
        }

        .category-form-header h5 {
            font-weight: 700;
            color: #212529;
        }

        .category-form-header p {
            margin-bottom: 0;
            color: #67748e;
        }

        .category-form-body {
            padding: 30px;
        }

        .category-field {
            margin-bottom: 28px;
        }

        .category-field label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #344767;
        }

        .category-field label i {
            font-size: 14px;
            color: #2e37a4;
        }

        .category-input {
            width: 100%;
            min-height: 48px;
            padding: 12px 15px;
            border: 1px solid #d8dce3;
            border-radius: 8px;
            background-color: #ffffff;
            font-size: 14px;
            color: #344767;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .category-input::placeholder {
            color: #adb5bd;
        }

        .category-input:focus {
            border-color: #2e37a4;
            box-shadow: 0 0 0 3px rgba(46, 55, 164, 0.10);
            outline: none;
        }

        select.category-input {
            cursor: pointer;
        }

        .field-help {
            display: block;
            margin-top: 7px;
            font-size: 12px;
            color: #8392ab;
        }

        .category-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            margin-top: 5px;
            border-top: 1px solid #e9ecef;
        }

        .category-form-actions .create-btn {
            min-width: 160px;
            padding: 11px 18px;
            font-weight: 600;
        }

        .category-form-actions .cancel-btn {
            min-width: 100px;
            padding: 11px 18px;
            font-weight: 600;
        }

        @media (max-width: 576px) {

            .category-form-body {
                padding: 20px;
            }

            .category-form-header {
                padding: 22px 20px 16px;
            }

            .category-form-actions {
                flex-direction: column-reverse;
            }

            .category-form-actions .btn {
                width: 100%;
            }

        }
    </style>

</head>


<body class="g-sidenav-show bg-gray-100">


    <?php require_once '../../includes/admin-header.php'; ?>

    <?php require_once '../../includes/admin-sidenavbar.php'; ?>


    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">


        <div class="container-fluid py-4">


            <!-- PAGE HEADER -->

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

                <div>

                    <h4 class="fw-bold text-dark mb-1">
                        Create Category
                    </h4>

                    <p class="text-sm text-secondary mb-0">
                        Add a new product category
                    </p>

                </div>


                <a href="index.php" class="btn btn-outline-secondary btn-sm">

                    <i class="fa-solid fa-arrow-left me-1"></i>

                    Back to Categories

                </a>

            </div>


            <!-- CATEGORY FORM -->

            <div class="row">

                <div class="col-lg-8 col-md-10 mx-auto">


                    <div class="card category-form-card">


                        <div class="category-form-header">

                            <h5 class="mb-1">
                                Category Information
                            </h5>

                            <p class="text-sm">
                                Enter the details for the new product category.
                            </p>

                        </div>


                        <div class="category-form-body">


                            <form method="POST" action="" enctype="multipart/form-data">

                                <!-- CATEGORY NAME -->

                                <div class="category-field">

                                    <label for="categoryName">

                                        <i class="fa-solid fa-tag"></i>

                                        Category Name

                                    </label>


                                    <input type="text" id="categoryName" name="name" class="category-input"
                                        placeholder="e.g. Footwear, Shirts, Accessories" required>


                                    <span class="field-help">
                                        Enter a unique name for this product category.
                                    </span>

                                </div>

                                <!-- CATEGORY IMAGE -->

                                <div class="category-field">

                                    <label for="categoryImage">

                                        <i class="fa-solid fa-image"></i>

                                        Category Image

                                    </label>


                                    <input type="file" id="categoryImage" name="image" class="category-input"
                                        accept="image/jpeg,image/png,image/webp" required>


                                    <span class="field-help">
                                        Upload a JPG, PNG or WebP image for this category.
                                    </span>

                                </div>
                                <!-- STATUS -->

                                <div class="category-field">

                                    <label for="categoryStatus">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Category Status

                                    </label>


                                    <select id="categoryStatus" name="status" class="category-input" required>

                                        <option value="1">
                                            Active
                                        </option>

                                        <option value="0">
                                            Inactive
                                        </option>

                                    </select>


                                    <span class="field-help">
                                        Inactive categories will not be available for new products.
                                    </span>

                                </div>


                                <!-- BUTTONS -->

                                <div class="category-form-actions">


                                    <a href="index.php" class="btn btn-light cancel-btn">

                                        <i class="fa-solid fa-xmark me-1"></i>

                                        Cancel

                                    </a>


                                    <button type="submit" name="save_category" class="btn bg-gradient-dark create-btn">

                                        <i class="fa-solid fa-plus me-1"></i>

                                        Create Category

                                    </button>


                                </div>


                            </form>

                        </div>

                    </div>

                </div>

            </div>


            <?php require_once '../../includes/admin-footer.php'; ?>


        </div>

    </main>


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


</body>

</html>