<?php

require_once '../config/database.php';

/* =========================
   VARIABLES
========================= */

$registerSuccess = '';
$loginSuccess = '';

$registerNameError = '';
$registerEmailError = '';
$registerPasswordError = '';
$registerConfirmPasswordError = '';
$registerPolicyError = '';

$loginEmailError = '';
$loginPasswordError = '';

/* =========================
   CHECK REGISTRATION REDIRECT
========================= */

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $loginSuccess = 'Account created successfully. You can now login.';
}

/* =========================
   REGISTER
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $policy = isset($_POST['policy']);

    /* Name */

    if ($name === '') {
        $registerNameError = 'Name is required.';
    } elseif (strlen($name) < 3) {
        $registerNameError = 'Name must be at least 3 characters long.';
    } elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $registerNameError = 'Name can only contain letters and spaces.';
    }

    /* Email */

    if ($email === '') {
        $registerEmailError = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerEmailError = 'Please enter a valid email address.';
    }

    /* Password */

    if ($password === '') {
        $registerPasswordError = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $registerPasswordError = 'Password must be at least 8 characters long.';
    }

    /* Confirm Password */

    if ($confirmPassword === '') {
        $registerConfirmPasswordError = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $registerConfirmPasswordError = 'Passwords do not match.';
    }

    /* Privacy Policy */

    if (!$policy) {
        $registerPolicyError = 'You must agree to the privacy policy.';
    }

    /* =========================
       CHECK ALL VALIDATIONS
    ========================= */

    if (
        $registerNameError === '' &&
        $registerEmailError === '' &&
        $registerPasswordError === '' &&
        $registerConfirmPasswordError === '' &&
        $registerPolicyError === ''
    ) {

        /* Check if email already exists */

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $registerEmailError = 'An account with this email already exists.';

        } else {

            /* Hash password */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            /* Create customer account */

            $stmt = $pdo->prepare("
                INSERT INTO users
                (name, email, password, role, is_active)
                VALUES (?, ?, ?, 'customer', 1)
            ");

            $stmt->execute([
                $name,
                $email,
                $passwordHash
            ]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}

/* =========================
   LOGIN
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    /* Login Email */

    if ($email === '') {
        $loginEmailError = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginEmailError = 'Please enter a valid email address.';
    }

    /* Login Password */

    if ($password === '') {
        $loginPasswordError = 'Password is required.';
    }

    /* Continue only if validation passed */

    if (
        $loginEmailError === '' &&
        $loginPasswordError === ''
    ) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                email,
                password,
                role,
                is_active
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $loginEmailError = 'Invalid email or password.';

        } elseif ((int) $user['is_active'] !== 1) {

            $loginEmailError = 'Your account is currently inactive.';

        } elseif (!password_verify($password, $user['password'])) {

            $loginPasswordError = 'Invalid email or password.';

        } else {

            /* Start session */

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            /* Redirect according to role */

            if ($user['role'] === 'admin') {

                header('Location: ../admin/index.php');
                exit;

            } else {

                header('Location: index.php');
                exit;
            }
        }
    }
}

/* =========================
   ACTIVE TAB
========================= */

$showSignIn = (
    $loginSuccess !== '' ||
    $loginEmailError !== '' ||
    $loginPasswordError !== ''
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    ```
    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Login - Molla</title>

    <meta name="keywords" content="HTML5 Template">

    <meta name="description" content="Molla - Bootstrap eCommerce Template">

    <meta name="author" content="p-themes">

    <!-- Favicon -->

    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/favicon-16x16.png">

    <link rel="manifest" href="assets/images/icons/site.html">

    <meta name="apple-mobile-web-app-title" content="Molla">

    <meta name="application-name" content="Molla">

    <meta name="msapplication-TileColor" content="#cc9966">

    <meta name="theme-color" content="#ffffff">

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    ```

</head>

<body>

    <div class="page-wrapper">

        ```
        <?php include '../includes/header.php'; ?>

        <main class="main">

            <div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">

                <div class="container">

                    <h1 class="page-title">
                        My Account
                        <span>Personal Information</span>
                    </h1>

                </div>

            </div>

            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-3">

                <div class="container">

                    <ol class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php">Home</a>
                        </li>

                        <li class="breadcrumb-item active" aria-current="page">
                            My Account
                        </li>

                    </ol>

                </div>

            </nav>

            <div class="page-content">

                <div class="container">

                    <div class="form-box">

                        <div class="form-tab">

                            <ul class="nav nav-pills nav-fill" role="tablist">

                                <li class="nav-item">

                                    <a class="nav-link <?php echo $showSignIn ? 'active' : ''; ?>" id="signin-tab-2"
                                        data-toggle="tab" href="#signin-2" role="tab" aria-controls="signin-2"
                                        aria-selected="<?php echo $showSignIn ? 'true' : 'false'; ?>">

                                        Sign In

                                    </a>

                                </li>

                                <li class="nav-item">

                                    <a class="nav-link <?php echo !$showSignIn ? 'active' : ''; ?>" id="register-tab-2"
                                        data-toggle="tab" href="#register-2" role="tab" aria-controls="register-2"
                                        aria-selected="<?php echo !$showSignIn ? 'true' : 'false'; ?>">

                                        Register

                                    </a>

                                </li>

                            </ul>

                            <div class="tab-content">

                                <!-- =========================
                             SIGN IN
                        ========================== -->

                                <div class="tab-pane fade <?php echo $showSignIn ? 'show active' : ''; ?>" id="signin-2"
                                    role="tabpanel" aria-labelledby="signin-tab-2">

                                    <form action="" method="POST">

                                        <?php if ($loginSuccess !== ''): ?>

                                            <div class="alert alert-success">
                                                <?php echo htmlspecialchars($loginSuccess); ?>
                                            </div>

                                        <?php endif; ?>

                                        <div class="form-group">

                                            <label for="singin-email-2">
                                                Username or email address *
                                            </label>

                                            <input type="text" class="form-control" id="singin-email-2"
                                                name="login_email"
                                                value="<?php echo htmlspecialchars($_POST['login_email'] ?? ''); ?>">

                                            <?php if ($loginEmailError !== ''): ?>

                                                <small class="text-danger">
                                                    <?php echo htmlspecialchars($loginEmailError); ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <div class="form-group">

                                            <label for="singin-password-2">
                                                Password *
                                            </label>

                                            <input type="password" class="form-control" id="singin-password-2"
                                                name="login_password">

                                            <?php if ($loginPasswordError !== ''): ?>

                                                <small class="text-danger">
                                                    <?php echo htmlspecialchars($loginPasswordError); ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <input type="hidden" name="login" value="1">

                                        <div class="form-footer">

                                            <button type="submit" class="btn btn-outline-primary-2">

                                                <span>LOG IN</span>

                                                <i class="icon-long-arrow-right"></i>

                                            </button>

                                            <a href="#" class="forgot-link">
                                                Forgot Your Password?
                                            </a>

                                        </div>

                                    </form>

                                    <div class="form-choice">

                                        <p class="text-center">
                                            or sign in with
                                        </p>

                                        <div class="row">

                                            <div class="col-sm-6">

                                                <a href="#" class="btn btn-login btn-g">

                                                    <i class="icon-google"></i>
                                                    Login With Google

                                                </a>

                                            </div>

                                            <div class="col-sm-6">

                                                <a href="#" class="btn btn-login btn-f">

                                                    <i class="icon-facebook-f"></i>
                                                    Login With Facebook

                                                </a>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <!-- =========================
                             REGISTER
                        ========================== -->

                                <div class="tab-pane fade <?php echo !$showSignIn ? 'show active' : ''; ?>"
                                    id="register-2" role="tabpanel" aria-labelledby="register-tab-2">

                                    <form action="" method="POST">

                                        <div class="form-group">

                                            <label for="register-name-2">
                                                Your name *
                                            </label>

                                            <input type="text" class="form-control" id="register-name-2" name="name"
                                                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

                                            <?php if ($registerNameError !== ''): ?>

                                                <small class="text-danger">
                                                    <?php echo htmlspecialchars($registerNameError); ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <div class="form-group">

                                            <label for="register-email-2">
                                                Your email address *
                                            </label>

                                            <input type="text" class="form-control" id="register-email-2" name="email"
                                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                                            <?php if ($registerEmailError !== ''): ?>

                                                <small class="text-danger">
                                                    <?php echo htmlspecialchars($registerEmailError); ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <div class="form-group">

                                            <label for="register-password-2">
                                                Password *
                                            </label>

                                            <input type="password" class="form-control" id="register-password-2"
                                                name="password">

                                            <?php if ($registerPasswordError !== ''): ?>

                                                <small class="text-danger">
                                                    <?php echo htmlspecialchars($registerPasswordError); ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <div class="form-group">

                                            <label for="register-confirm-password-2">
                                                Confirm password *
                                            </label>

                                            <input type="password" class="form-control" id="register-confirm-password-2"
                                                name="confirm_password">

                                            <?php if ($registerConfirmPasswordError !== ''): ?>

                                                <small class="text-danger">
                                                    <?php echo htmlspecialchars($registerConfirmPasswordError); ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <input type="hidden" name="register" value="1">

                                        <div class="form-footer">

                                            <button type="submit" class="btn btn-outline-primary-2">

                                                <span>SIGN UP</span>

                                                <i class="icon-long-arrow-right"></i>

                                            </button>

                                            <div class="custom-control custom-checkbox">

                                                <input type="checkbox" class="custom-control-input"
                                                    id="register-policy-2" name="policy">

                                                <label class="custom-control-label" for="register-policy-2">

                                                    I agree to the
                                                    <a href="#">
                                                        privacy policy
                                                    </a>
                                                    *

                                                </label>

                                                <?php if ($registerPolicyError !== ''): ?>

                                                    <small class="text-danger d-block">
                                                        <?php echo htmlspecialchars($registerPolicyError); ?>
                                                    </small>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </form>

                                    <div class="form-choice">

                                        <p class="text-center">
                                            or sign in with
                                        </p>

                                        <div class="row">

                                            <div class="col-sm-6">

                                                <a href="#" class="btn btn-login btn-g">

                                                    <i class="icon-google"></i>
                                                    Login With Google

                                                </a>

                                            </div>

                                            <div class="col-sm-6">

                                                <a href="#" class="btn btn-login btn-f">

                                                    <i class="icon-facebook-f"></i>
                                                    Login With Facebook

                                                </a>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </main>

        <?php include '../includes/footer.php'; ?>
        ```

    </div>

    <button id="scroll-top" title="Back to Top">

        ```
        <i class="icon-arrow-up"></i>
        ```

    </button>

    <div class="mobile-menu-overlay"></div>

    <script src="assets/js/jquery.min.js"></script>

    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/jquery.hoverIntent.min.js"></script>

    <script src="assets/js/jquery.waypoints.min.js"></script>

    <script src="assets/js/superfish.min.js"></script>

    <script src="assets/js/owl.carousel.min.js"></script>

    <script src="assets/js/main.js"></script>

</body>

</html>