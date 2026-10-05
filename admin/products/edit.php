<?php

require_once '../../config/database.php';
require_once '../../core/Auth.php';

$auth = new Auth($pdo);
$auth->requireAdmin();

$pageTitle = "Edit Product";

/* =========================
   GET PRODUCT ID
========================= */

$productId = (int) ($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: index.php');
    exit;
}


/* =========================
   FETCH PRODUCT
========================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        category_id,
        name,
        slug,
        description,
        price,
        stock,
        image,
        status
    FROM products
    WHERE id = ?
");

$stmt->execute([$productId]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================
   PRODUCT NOT FOUND
========================= */

if (!$product) {
    header('Location: index.php');
    exit;
}


/* =========================
   FETCH CATEGORIES
========================= */

$stmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 1
    ORDER BY name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   SET FORM VALUES
========================= */

$productName = $product['name'];
$productCategory = (string) $product['category_id'];
$productPrice = $product['price'];
$productStock = $product['stock'];
$productStatus = (int) $product['status'];
$productDescription = $product['description'] ?? '';
$productImage = $product['image'] ?? '';

$productImages = [];

if (!empty($productImage)) {
    $decodedImages = json_decode($productImage, true);

    if (is_array($decodedImages)) {
        $productImages = $decodedImages;
    } else {
        // Old single-image products
        $productImages = [$productImage];
    }
}

/* =========================
   UPDATE PRODUCT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {

    $productName = trim($_POST['product_name'] ?? '');
    $productCategory = (int) ($_POST['category'] ?? 0);
    $productPrice = (float) ($_POST['price'] ?? 0);
    $productStock = (int) ($_POST['stock'] ?? 0);
    $productStatus = ($_POST['status'] ?? 'inactive') === 'active' ? 1 : 0;
    $productDescription = trim($_POST['description'] ?? '');


    /* =========================
       BASIC VALIDATION
    ========================= */

    if (
        $productName === '' ||
        $productCategory <= 0 ||
        $productPrice < 0 ||
        $productStock < 0
    ) {
        $error = "Please fill all required fields correctly.";
    } else {

        /* =========================
           CHECK CATEGORY
        ========================= */

        $categoryCheck = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            AND status = 1
        ");

        $categoryCheck->execute([$productCategory]);

        if (!$categoryCheck->fetch()) {

            $error = "Selected category is invalid.";

        } else {

            /* =========================
               GENERATE SLUG
            ========================= */

            $slug = strtolower(trim($productName));

            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

            $slug = trim($slug, '-');


            /* =========================
               MAKE SLUG UNIQUE
            ========================= */

            $slugCheck = $pdo->prepare("
                SELECT id
                FROM products
                WHERE slug = ?
                AND id != ?
            ");

            $slugCheck->execute([
                $slug,
                $productId
            ]);

            if ($slugCheck->fetch()) {

                $slug .= '-' . $productId;
            }

/* =========================
   IMAGE HANDLING
========================= */

$newImages = $productImages;


/* =========================
   DELETE SELECTED IMAGES
========================= */

$deleteImages = $_POST['delete_images'] ?? [];

if (!is_array($deleteImages)) {
    $deleteImages = [];
}


/* =========================
   PRODUCT IMAGE DIRECTORY
========================= */

$uploadDirectory =
    '../../public/uploads/products/' . $productId . '/';

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}


/* =========================
   REMOVE SELECTED IMAGES
========================= */

if (!empty($deleteImages)) {

    foreach ($deleteImages as $imageToDelete) {

        $imageToDelete = basename($imageToDelete);

        $imagePath =
            $productId . '/' . $imageToDelete;

        $imageIndex = array_search(
            $imagePath,
            $newImages,
            true
        );

        if ($imageIndex !== false) {

            $filePath =
                $uploadDirectory . $imageToDelete;

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            unset($newImages[$imageIndex]);
        }
    }

    $newImages = array_values($newImages);
}


/* =========================
   UPLOAD NEW IMAGES
========================= */

if (
    isset($_FILES['images']) &&
    isset($_FILES['images']['name']) &&
    is_array($_FILES['images']['name'])
) {

    $files = $_FILES['images'];

    $fileCount = count($files['name']);

    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $maxFileSize = 5 * 1024 * 1024;


    /* =========================
       COUNT NEW FILES
    ========================== */

    $newFileCount = 0;

    for ($i = 0; $i < $fileCount; $i++) {

        if ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            $newFileCount++;
        }
    }


    /* =========================
       MAXIMUM 5 IMAGES
    ========================== */

    if ((count($newImages) + $newFileCount) > 5) {

        $error = "A product can have a maximum of 5 images.";

    } else {

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);


        for ($i = 0; $i < $fileCount; $i++) {

            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }


            /* =========================
               UPLOAD ERROR
            ========================== */

            if ($files['error'][$i] !== UPLOAD_ERR_OK) {

                $error = "There was an error uploading an image.";
                break;
            }


            /* =========================
               FILE SIZE
            ========================== */

            if ($files['size'][$i] > $maxFileSize) {

                $error = "Each image must be less than 5 MB.";
                break;
            }


            /* =========================
               MIME TYPE
            ========================== */

            $mimeType = finfo_file(
                $fileInfo,
                $files['tmp_name'][$i]
            );


            if (!in_array($mimeType, $allowedTypes, true)) {

                $error = "Only JPG, PNG and WEBP images are allowed.";
                break;
            }


            /* =========================
               FILE EXTENSION
            ========================== */

            $extension = $extensions[$mimeType];


            /* =========================
               CREATE IMAGE NAME
            ========================== */

          $imageNumber = 1;

do {

    $imageName =
        'image' . $imageNumber . '.' . $extension;

    $uploadPath =
        $uploadDirectory . $imageName;

    $imageNumber++;

} while (file_exists($uploadPath));


            /* =========================
               MOVE IMAGE
            ========================== */

            if (
                move_uploaded_file(
                    $files['tmp_name'][$i],
                    $uploadPath
                )
            ) {

                $newImages[] =
                    $productId . '/' . $imageName;

            } else {

                $error = "Failed to upload an image.";
                break;
            }
        }


        finfo_close($fileInfo);
    }
}


/* =========================
   CONVERT IMAGES TO JSON
========================= */

if (!isset($error)) {

    $newImageJson = json_encode(
        array_values($newImages),
        JSON_UNESCAPED_SLASHES
    );
}
            /* =========================
               UPDATE DATABASE
            ========================= */

            if (!isset($error)) {

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET
                        category_id = ?,
                        name = ?,
                        slug = ?,
                        description = ?,
                        price = ?,
                        stock = ?,
                        image = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $productCategory,
                    $productName,
                    $slug,
                    $productDescription,
                    $productPrice,
                    $productStock,
                    $newImageJson,
                    $productStatus,
                    $productId
                ]);
/* =========================
   SUCCESS MESSAGE
========================= */

if (!empty($deleteImages) && isset($_FILES['images'])) {

    $uploadedCount = 0;

    if (
        isset($_FILES['images']['name']) &&
        is_array($_FILES['images']['name'])
    ) {
        foreach ($_FILES['images']['name'] as $fileName) {
            if (!empty($fileName)) {
                $uploadedCount++;
            }
        }
    }

    if ($uploadedCount > 0) {
        $success = "Product images have been updated.";
    } else {
        $success = "Product image has been removed.";
    }

} elseif (!empty($deleteImages)) {

    $success = "Product image has been removed.";

} else {

    $uploadedCount = 0;

    if (
        isset($_FILES['images']['name']) &&
        is_array($_FILES['images']['name'])
    ) {
        foreach ($_FILES['images']['name'] as $fileName) {
            if (!empty($fileName)) {
                $uploadedCount++;
            }
        }
    }

    if ($uploadedCount > 0) {
        $success = "Product images have been updated.";
    } else {
        $success = "Product has been updated successfully.";
    }
}


/* =========================
   UPDATE CURRENT PAGE DATA
========================= */

$productImages = array_values($newImages);
$productImage = $newImageJson;
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link
    rel="apple-touch-icon"
    sizes="76x76"
    href="../assets/img/apple-icon.png"
>

<link
    rel="icon"
    type="image/png"
    href="../assets/img/favicon.png"
>

<title>Edit Product - ClothWear</title>


<!-- Fonts -->

<link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900"
>


<!-- Nucleo Icons -->

<link
    href="../assets/css/nucleo-icons.css"
    rel="stylesheet"
>

<link
    href="../assets/css/nucleo-svg.css"
    rel="stylesheet"
>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<!-- Material Icons -->

<link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0"
>


<!-- Material Dashboard CSS -->

<link
    id="pagestyle"
    href="../assets/css/material-dashboard.css?v=3.2.0"
    rel="stylesheet"
>


<!-- =========================
     CUSTOM PRODUCT FORM CSS
========================== -->

<style>

    /* =========================
       FORM CARD
    ========================== */

    .product-form-card {
        border-radius: 16px;
        overflow: hidden;
    }


    /* =========================
       CARD HEADER
    ========================== */

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
    ========================== */

    .product-form-body {
        padding: 30px;
    }


    /* =========================
       FORM FIELD
    ========================== */

    .product-field {
        margin-bottom: 28px;
    }


    /* =========================
       LABEL
    ========================== */

    .product-field label {
        display: flex;
        align-items: center;
        gap: 8px;

        margin-bottom: 10px;

        font-size: 14px;
        font-weight: 700;

        color: #344767;
    }


    /* Label Icon */

    .product-field label i {
        font-size: 14px;
         color: #242426;
    }


    /* =========================
       INPUT / SELECT / TEXTAREA
    ========================== */

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


    /* Placeholder */

    .product-input::placeholder {
        color: #adb5bd;
    }


    /* =========================
       INPUT FOCUS
    ========================== */

    .product-input:focus {

        border-color: #2e37a4;

        box-shadow:
            0 0 0 3px rgba(46, 55, 164, 0.10);

        outline: none;
    }


    /* =========================
       SELECT
    ========================== */

    select.product-input {
        cursor: pointer;
    }


    /* =========================
       TEXTAREA
    ========================== */

    textarea.product-input {
        min-height: 130px;
        resize: vertical;
    }


    /* =========================
       FILE INPUT
    ========================== */

    input[type="file"].product-input {
        padding: 10px 12px;
        cursor: pointer;
    }


    /* =========================
       HELPER TEXT
    ========================== */

    .field-help {
        display: block;

        margin-top: 7px;

        font-size: 12px;

        color: #8392ab;
    }


    /* =========================
       PRODUCT ID INFO
    ========================== */

    .product-id-info {

        display: inline-flex;

        align-items: center;

        gap: 6px;

        margin-top: 5px;

        padding: 5px 10px;

        border-radius: 6px;

        background-color: #f8f9fa;

        color: #8392ab;

        font-size: 11px;

        font-weight: 600;
    }


    /* =========================
       BUTTON AREA
    ========================== */

    .product-form-actions {

        display: flex;

        justify-content: flex-end;

        gap: 10px;

        padding-top: 20px;

        margin-top: 5px;

        border-top: 1px solid #e9ecef;
    }


    /* =========================
       UPDATE BUTTON
    ========================== */

    .product-form-actions .update-btn {

        min-width: 160px;

        padding: 11px 18px;

        font-weight: 600;
    }


    /* =========================
       CANCEL BUTTON
    ========================== */

    .product-form-actions .cancel-btn {

        min-width: 100px;

        padding: 11px 18px;

        font-weight: 600;
    }


    /* =========================
       MOBILE
    ========================== */

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

</head>

<body class="g-sidenav-show bg-gray-100">

<!-- =========================
     ADMIN HEADER
========================== -->

<?php require_once '../../includes/admin-header.php'; ?>


<!-- =========================
     ADMIN SIDEBAR
========================== -->

<?php require_once '../../includes/admin-sidenavbar.php'; ?>


<!-- =========================
     MAIN CONTENT
========================== -->

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">


    <div class="container-fluid py-4">


        <!-- =========================
             PAGE HEADER
        ========================== -->

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

            <div>

                <h4 class="fw-bold text-dark mb-1">
                    Edit Product
                </h4>

                <p class="text-sm text-secondary mb-0">
                    Update the product information
                </p>

            </div>


            <!-- BACK BUTTON -->

            <a
                href="index.php"
                class="btn btn-outline-secondary btn-sm"
            >

                <i class="fa-solid fa-arrow-left me-1"></i>

                Back to Products

            </a>

        </div>


        <!-- =========================
             PRODUCT FORM
        ========================== -->

        <div class="row">

            <div class="col-lg-10 col-md-11 mx-auto">


                <!-- FORM CARD -->

                <div class="card product-form-card">


                    <!-- =========================
                         CARD HEADER
                    ========================== -->

                    <div class="product-form-header">

                        <h5 class="mb-1">
                            Product Information
                        </h5>

                        <p class="text-sm">
                            Update the details of this product.
                        </p>


                        <!-- PRODUCT ID -->

                        <span class="product-id-info">

                            <i class="fa-solid fa-hashtag"></i>

                            Product ID:
                            <?php echo htmlspecialchars($productId); ?>

                        </span>

                    </div>


                    <!-- =========================
                         FORM BODY
                    ========================== -->

                    <div class="product-form-body">


                        <form
                            method="POST"
                            action=""
                            enctype="multipart/form-data"
                        >
<?php if (isset($error)): ?>

    <div class="alert alert-danger text-sm mb-4">
        <?php echo htmlspecialchars($error); ?>
    </div>

<?php endif; ?>
<?php if (isset($success)): ?>

    <div
        id="successToast"
        style="
            position:fixed;
            top:25px;
            right:25px;
            z-index:9999;
            background:#ffffff;
            color:#344767;
            padding:15px 20px;
            border-radius:10px;
            box-shadow:0 8px 25px rgba(0,0,0,0.15);
            border-left:4px solid #2e7d32;
            min-width:280px;
            font-size:14px;
            font-weight:600;
        "
    >

        <i
            class="fa-solid fa-circle-check"
            style="color:#2e7d32; margin-right:8px;"
        ></i>

        <?php echo htmlspecialchars($success); ?>

    </div>

<?php endif; ?>

                            <!-- Hidden Product ID -->

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php echo htmlspecialchars($productId); ?>"
                            >


                            <!-- =========================
                                 PRODUCT NAME
                            ========================== -->

                            <div class="product-field">

                                <label for="product_name">

                                    <i class="fa-solid fa-box"></i>

                                    Product Name

                                </label>


                                <input
                                    type="text"
                                    id="product_name"
                                    name="product_name"
                                    class="product-input"
                                    value="<?php echo htmlspecialchars($productName); ?>"
                                    placeholder="Enter product name"
                                    required
                                >


                                <span class="field-help">
                                    Update the name of this product.
                                </span>

                            </div>


                            <!-- =========================
                                 CATEGORY + PRICE
                            ========================== -->

                            <div class="row">


                                <!-- CATEGORY -->

                                <div class="col-md-6">

                                    <div class="product-field">

                                        <label for="category">

                                            <i class="fa-solid fa-layer-group"></i>

                                            Category

                                        </label>

<select
    id="category"
    name="category"
    class="product-input"
    required
>

    <option value="">
        Select Category
    </option>

    <?php foreach ($categories as $category): ?>

        <option
            value="<?php echo (int) $category['id']; ?>"
            <?php echo (
                $productCategory == $category['id']
            ) ? 'selected' : ''; ?>
        >
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


                                        <input
                                            type="number"
                                            id="price"
                                            name="price"
                                            class="product-input"
                                            value="<?php echo htmlspecialchars($productPrice); ?>"
                                            placeholder="Enter price"
                                            min="0"
                                            step="0.01"
                                            required
                                        >


                                        <span class="field-help">
                                            Update the product price.
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <!-- =========================
                                 STOCK + STATUS
                            ========================== -->

                            <div class="row">


                                <!-- STOCK -->

                                <div class="col-md-6">

                                    <div class="product-field">

                                        <label for="stock">

                                            <i class="fa-solid fa-boxes-stacked"></i>

                                            Stock Quantity

                                        </label>


                                        <input
                                            type="number"
                                            id="stock"
                                            name="stock"
                                            class="product-input"
                                            value="<?php echo htmlspecialchars($productStock); ?>"
                                            placeholder="Enter stock quantity"
                                            min="0"
                                            required
                                        >


                                        <span class="field-help">
                                            Update the available quantity.
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


                                        <select
                                            id="status"
                                            name="status"
                                            class="product-input"
                                            required
                                        >

                                         <option
    value="active"
    <?php echo ($productStatus === 1) ? "selected" : ""; ?>
>
    Active
</option>


                   <option
    value="inactive"
    <?php echo ($productStatus === 0) ? "selected" : ""; ?>
>
    Inactive
</option>

                                        </select>


                                        <span class="field-help">
                                            Change whether this product is currently active.
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <!-- =========================
                                 DESCRIPTION
                            ========================== -->

                            <div class="product-field">

                                <label for="description">

                                    <i class="fa-solid fa-align-left"></i>

                                    Product Description

                                </label>


                                <textarea
                                    id="description"
                                    name="description"
                                    class="product-input"
                                    rows="5"
                                    placeholder="Enter product description"
                                ><?php echo htmlspecialchars($productDescription); ?></textarea>


                                <span class="field-help">
                                    Update the product description.
                                </span>

                            </div>

<!-- =========================
     PRODUCT IMAGES
========================== -->

<div class="product-field">

    <label for="images">

        <i class="fa-solid fa-images"></i>

        Product Images

    </label>


    <!-- CURRENT IMAGES -->

    <?php if (!empty($productImages)): ?>

        <div class="mt-3 mb-3">

            <p class="text-xs text-secondary mb-2">
                Current Images
            </p>


            <div
                style="
                    display:flex;
                    flex-wrap:wrap;
                    gap:15px;
                "
            >

                <?php foreach ($productImages as $image): ?>

                    <?php
                    $imageFileName = basename($image);
                    ?>

                    <div
                        style="
                            position:relative;
                            width:110px;
                        "
                    >

                        <img
                            src="../../public/uploads/products/<?php echo htmlspecialchars($image); ?>"
                            alt="<?php echo htmlspecialchars($productName); ?>"
                            width="110"
                            height="110"
                            style="
                                object-fit:cover;
                                border-radius:10px;
                                border:1px solid #e1e5ea;
                            "
                        >


                        <label
                            style="
                                display:flex;
                                align-items:center;
                                gap:5px;
                                margin-top:7px;
                                font-size:12px;
                                color:#67748e;
                                cursor:pointer;
                            "
                        >

                            <input
                                type="checkbox"
                                name="delete_images[]"
                                value="<?php echo htmlspecialchars($imageFileName); ?>"
                            >

                            Remove

                        </label>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- NEW IMAGES -->

    <input
        type="file"
        id="images"
        name="images[]"
        class="product-input"
        accept="image/jpeg,image/png,image/webp"
        multiple
    >


    <span class="field-help">

        Upload new JPG, PNG or WEBP images.
        You can have a maximum of 5 images per product.
        Maximum 5 MB per image.

    </span>

</div>
  


                 <!-- =========================
     BUTTONS
========================== -->

<div class="product-form-actions">

    <!-- CANCEL -->

    <a
        href="index.php"
        class="btn btn-light cancel-btn"
    >

        <i class="fa-solid fa-xmark me-1"></i>

        Cancel

    </a>


    <!-- UPDATE -->

    <button
        type="submit"
        name="update_product"
        class="btn bg-gradient-dark update-btn"
        id="updateProductBtn"
    >

        <span id="updateButtonContent">

            <i class="fa-solid fa-pen-to-square me-1"></i>

            Update Product

        </span>


        <span
            id="updateButtonLoading"
            style="display:none;"
        >

            <i class="fa-solid fa-spinner fa-spin me-1"></i>

            Updating...

        </span>

    </button>

</div>

                        </form>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================
             ADMIN FOOTER
        ========================== -->

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
<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('form');
    const button = document.getElementById('updateProductBtn');

    const buttonContent =
        document.getElementById('updateButtonContent');

    const buttonLoading =
        document.getElementById('updateButtonLoading');


    if (form && button) {

        form.addEventListener('submit', function () {

            button.disabled = true;

            buttonContent.style.display = 'none';

            buttonLoading.style.display = 'inline-block';

        });

    }


    /* =========================
       HIDE SUCCESS TOAST
    ========================== */

    const successToast =
        document.getElementById('successToast');

    if (successToast) {

        setTimeout(function () {

            successToast.style.transition =
                'opacity 0.4s ease';

            successToast.style.opacity = '0';

            setTimeout(function () {

                successToast.remove();

            }, 400);

        }, 3500);

    }

});

</script>
</body>

</html>
