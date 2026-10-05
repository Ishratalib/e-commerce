<?php

require_once '../../config/database.php';
require_once '../../core/Auth.php';

$auth = new Auth($pdo);
$auth->requireAdmin();

$pageTitle = "Create Product";

/* =========================
   GET ACTIVE CATEGORIES
========================= */

$categoryStmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 1
    ORDER BY name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   CREATE PRODUCT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {

    $productName = trim($_POST['product_name'] ?? '');
    $categoryId = (int) ($_POST['category'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $description = trim($_POST['description'] ?? '');


    /* =========================
       VALIDATION
    ========================= */

    if ($productName === '') {
        die('Product name is required.');
    }

    if ($categoryId <= 0) {
        die('Please select a category.');
    }

    if ($price < 0) {
        die('Price cannot be negative.');
    }

    if ($stock < 0) {
        die('Stock cannot be negative.');
    }


    /* =========================
       CONVERT STATUS
    ========================= */

    $statusValue = ($status === 'active') ? 1 : 0;


    /* =========================
       GENERATE SLUG
    ========================= */

    $slug = strtolower(trim($productName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    $originalSlug = $slug;
    $counter = 1;

    while (true) {

        $slugCheck = $pdo->prepare("
            SELECT id
            FROM products
            WHERE slug = ?
            LIMIT 1
        ");

        $slugCheck->execute([$slug]);

        if (!$slugCheck->fetch()) {
            break;
        }

        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }


    /* =========================
       INSERT PRODUCT
    ========================= */

    $stmt = $pdo->prepare("
        INSERT INTO products
        (
            category_id,
            name,
            slug,
            description,
            price,
            stock,
            image,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $categoryId,
        $productName,
        $slug,
        $description,
        $price,
        $stock,
        null,
        $statusValue
    ]);


    /* =========================
       GET NEW PRODUCT ID
    ========================= */

    $productId = (int) $pdo->lastInsertId();


    /* =========================
       CREATE PRODUCT IMAGE FOLDER
    ========================= */

    $productUploadDirectory =
        '../../public/uploads/products/' . $productId . '/';

    if (!is_dir($productUploadDirectory)) {
        mkdir($productUploadDirectory, 0755, true);
    }


    /* =========================
       UPLOAD PRODUCT IMAGES
    ========================= */

    $uploadedImages = [];

    if (
        isset($_FILES['images']) &&
        isset($_FILES['images']['name']) &&
        is_array($_FILES['images']['name'])
    ) {

        $files = $_FILES['images'];

        $fileCount = count($files['name']);

        /* Maximum 5 images */

        if ($fileCount > 5) {
            die('You can upload a maximum of 5 images.');
        }


        /* Allowed MIME types */

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];


        /* File extensions */

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];


        /* Maximum 5 MB per image */

        $maxFileSize = 5 * 1024 * 1024;


        /* Open file information */

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);


        for ($i = 0; $i < $fileCount; $i++) {

            /* Skip empty file */

            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }


            /* Check upload error */

            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }


            /* Check file size */

            if ($files['size'][$i] > $maxFileSize) {

                finfo_close($fileInfo);

                die('Each image must be less than 5 MB.');
            }


            /* Detect actual MIME type */

            $mimeType = finfo_file(
                $fileInfo,
                $files['tmp_name'][$i]
            );


            /* Validate MIME type */

            if (!in_array($mimeType, $allowedTypes, true)) {

                finfo_close($fileInfo);

                die('Only JPG, PNG and WEBP images are allowed.');
            }


            /* Get extension */

            $extension = $extensions[$mimeType];


            /* Create image name */

            $imageNumber = count($uploadedImages) + 1;

            $imageName =
                'image' . $imageNumber . '.' . $extension;


            /* Full upload path */

            $uploadPath =
                $productUploadDirectory . $imageName;


            /* Move uploaded image */

            if (
                move_uploaded_file(
                    $files['tmp_name'][$i],
                    $uploadPath
                )
            ) {

                /*
                 * Save relative path
                 *
                 * Example:
                 * 30/image1.jpg
                 */

                $uploadedImages[] =
                    $productId . '/' . $imageName;
            }
        }


        finfo_close($fileInfo);
    }


    /* =========================
       SAVE IMAGES AS JSON
    ========================= */

    $imageJson = json_encode(
        $uploadedImages,
        JSON_UNESCAPED_SLASHES
    );


    /* =========================
       UPDATE PRODUCT IMAGES
    ========================= */

    $updateImages = $pdo->prepare("
        UPDATE products
        SET image = ?
        WHERE id = ?
    ");

    $updateImages->execute([
        $imageJson,
        $productId
    ]);


    /* =========================
       REDIRECT
    ========================= */

    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    ```
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">

    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <title>Create Product - ClothWear</title>


    <!-- Fonts -->

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900">


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


    <!-- Custom Product Form CSS -->

    <style>
        /* =========================
       FORM CARD
    ========================= */

        .product-form-card {
            border-radius: 16px;
            overflow: hidden;
        }


        /* =========================
       CARD HEADER
    ========================= */

        .product-form-header {
            padding: 28px 30px 18px;
            border-bottom: 1px solid #e9ecef;
        }

        .product-form-header h5 {
            font-weight: 700;
            color: #212529;
        }

        .product-form-header p {
            margin-bottom: 0;
            color: #67748e;
        }


        /* =========================
       FORM BODY
    ========================= */

        .product-form-body {
            padding: 30px;
        }


        /* =========================
       FORM FIELD
    ========================= */

        .product-field {
            margin-bottom: 28px;
        }


        /* =========================
       LABEL
    ========================= */

        .product-field label {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 10px;

            font-size: 14px;
            font-weight: 700;

            color: #344767;
        }


        .product-field label i {
            font-size: 14px;
            color: #242426;
        }


        /* =========================
       INPUT / SELECT / TEXTAREA
    ========================= */

        .product-input {
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


        /* =========================
       PLACEHOLDER
    ========================= */

        .product-input::placeholder {
            color: #adb5bd;
        }


        /* =========================
       INPUT FOCUS
    ========================= */

        .product-input:focus {

            border-color: #2e37a4;

            box-shadow:
                0 0 0 3px rgba(46, 55, 164, 0.10);

            outline: none;
        }


        /* =========================
       SELECT
    ========================= */

        select.product-input {
            cursor: pointer;
        }


        /* =========================
       TEXTAREA
    ========================= */

        textarea.product-input {
            min-height: 130px;
            resize: vertical;
        }


        /* =========================
       FILE INPUT
    ========================= */

        input[type="file"].product-input {
            padding: 10px 12px;
            cursor: pointer;
        }


        /* =========================
       HELPER TEXT
    ========================= */

        .field-help {
            display: block;

            margin-top: 7px;

            font-size: 12px;

            color: #8392ab;
        }


        /* =========================
       BUTTON AREA
    ========================= */

        .product-form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            padding-top: 20px;

            margin-top: 5px;

            border-top: 1px solid #e9ecef;
        }


        /* =========================
       CREATE BUTTON
    ========================= */

        .product-form-actions .create-btn {

            min-width: 160px;

            padding: 11px 18px;

            font-weight: 600;
        }


        /* =========================
       CANCEL BUTTON
    ========================= */

        .product-form-actions .cancel-btn {

            min-width: 100px;

            padding: 11px 18px;

            font-weight: 600;
        }


        /* =========================
       MOBILE
    ========================= */

        @media (max-width: 576px) {

            .product-form-body {
                padding: 20px;
            }

            .product-form-header {
                padding: 22px 20px 16px;
            }

            .product-form-actions {
                flex-direction: column-reverse;
            }

            .product-form-actions .btn {
                width: 100%;
            }

        }
    </style>
    ```

</head>

<body class="g-sidenav-show bg-gray-100">

    ```
    <!-- ADMIN HEADER -->

    <?php require_once '../../includes/admin-header.php'; ?>


    <!-- ADMIN SIDEBAR -->

    <?php require_once '../../includes/admin-sidenavbar.php'; ?>


    <!-- MAIN CONTENT -->

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">


        <div class="container-fluid py-4">


            <!-- PAGE HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

                <div>

                    <h4 class="fw-bold text-dark mb-1">
                        Create Product
                    </h4>

                    <p class="text-sm text-secondary mb-0">
                        Add a new product to your inventory
                    </p>

                </div>


                <!-- BACK BUTTON -->

                <a href="index.php" class="btn btn-outline-secondary btn-sm">

                    <i class="fa-solid fa-arrow-left me-1"></i>

                    Back to Products

                </a>

            </div>


            <!-- PRODUCT FORM -->

            <div class="row">

                <div class="col-lg-10 col-md-11 mx-auto">


                    <div class="card product-form-card">


                        <!-- CARD HEADER -->

                        <div class="product-form-header">

                            <h5 class="mb-1">
                                Product Information
                            </h5>

                            <p class="text-sm">
                                Enter the details of the new product.
                            </p>

                        </div>


                        <!-- FORM BODY -->

                        <div class="product-form-body">


                            <form action="" method="POST" enctype="multipart/form-data">


                                <!-- PRODUCT NAME -->

                                <div class="product-field">

                                    <label for="product_name">

                                        <i class="fa-solid fa-box"></i>

                                        Product Name

                                    </label>


                                    <input type="text" id="product_name" name="product_name" class="product-input"
                                        placeholder="Enter product name" required>


                                    <span class="field-help">
                                        Enter the name of the product.
                                    </span>

                                </div>


                                <!-- CATEGORY + PRICE -->

                                <div class="row">


                                    <!-- CATEGORY -->

                                    <div class="col-md-6">

                                        <div class="product-field">

                                            <label for="category">

                                                <i class="fa-solid fa-layer-group"></i>

                                                Category

                                            </label>


                                            <select id="category" name="category" class="product-input" required>

                                                <option value="">
                                                    Select Category
                                                </option>


                                                <?php foreach ($categories as $category): ?>

                                                    <option value="<?php echo (int) $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>


                                            <span class="field-help">
                                                Select the category for this product.
                                            </span>

                                        </div>

                                    </div>


                                    <!-- PRICE -->

                                    <div class="col-md-6">

                                        <div class="product-field">

                                            <label for="price">

                                                <i class="fa-solid fa-money-bill"></i>

                                                Price

                                            </label>


                                            <input type="number" id="price" name="price" class="product-input"
                                                placeholder="Enter price" min="0" step="0.01" required>


                                            <span class="field-help">
                                                Enter the product price.
                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <!-- STOCK + STATUS -->

                                <div class="row">


                                    <!-- STOCK -->

                                    <div class="col-md-6">

                                        <div class="product-field">

                                            <label for="stock">

                                                <i class="fa-solid fa-boxes-stacked"></i>

                                                Stock Quantity

                                            </label>


                                            <input type="number" id="stock" name="stock" class="product-input"
                                                placeholder="Enter stock quantity" min="0" required>


                                            <span class="field-help">
                                                Enter the available quantity.
                                            </span>

                                        </div>

                                    </div>


                                    <!-- STATUS -->

                                    <div class="col-md-6">

                                        <div class="product-field">

                                            <label for="status">

                                                <i class="fa-solid fa-circle-check"></i>

                                                Product Status

                                            </label>


                                            <select id="status" name="status" class="product-input" required>

                                                <option value="active">
                                                    Active
                                                </option>

                                                <option value="inactive">
                                                    Inactive
                                                </option>

                                            </select>


                                            <span class="field-help">
                                                Choose whether this product is active.
                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <!-- DESCRIPTION -->

                                <div class="product-field">

                                    <label for="description">

                                        <i class="fa-solid fa-align-left"></i>

                                        Product Description

                                    </label>


                                    <textarea id="description" name="description" class="product-input" rows="5"
                                        placeholder="Enter product description"></textarea>


                                    <span class="field-help">
                                        Add a short description of the product.
                                    </span>

                                </div>


                                <!-- PRODUCT IMAGES -->

                                <div class="product-field">

                                    <label for="images">

                                        <i class="fa-solid fa-images"></i>

                                        Product Images

                                    </label>


                                    <input type="file" id="images" name="images[]" class="product-input"
                                        accept="image/jpeg,image/png,image/webp" multiple>


                                    <span class="field-help">
                                        Upload up to 5 JPG, PNG or WEBP images. Maximum 5 MB per image.
                                    </span>

                                </div>


                                <!-- BUTTONS -->

                                <div class="product-form-actions">


                                    <!-- CANCEL -->

                                    <a href="index.php" class="btn btn-light cancel-btn">

                                        <i class="fa-solid fa-xmark me-1"></i>

                                        Cancel

                                    </a>


                                    <!-- CREATE -->

                                    <button type="submit" name="create_product" class="btn bg-gradient-dark create-btn">

                                        <i class="fa-solid fa-plus me-1"></i>

                                        Create Product

                                    </button>


                                </div>


                            </form>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ADMIN FOOTER -->

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
    ```

</body>

</html>