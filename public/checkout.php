<?php

require_once '../config/database.php';
require_once '../core/Mailer.php';
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['user_id'])) {
	header('Location: /public/login.php');
	exit;
}

$userId = (int) $_SESSION['user_id'];


/* =========================
   GET ACTIVE ORDER
   This is only set when
   user clicks Continue Shopping
========================= */

$activeOrderId = (int) ($_SESSION['active_order_id'] ?? 0);


/* =========================
   FETCH SAVED CUSTOMER DETAILS
========================= */

$customerDetails = [];

$customerStmt = $pdo->prepare("
    SELECT
        first_name,
        last_name,
        company_name,
        country,
        address,
        address_2,
        city,
        state,
        postcode,
        phone,
        email
    FROM customer_details
    WHERE user_id = ?
    LIMIT 1
");

$customerStmt->execute([$userId]);

$customerDetails = $customerStmt->fetch(PDO::FETCH_ASSOC) ?: [];


/* =========================
   GET CART
   Cart contains ONLY the
   new products being added
========================= */

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
	header('Location: /public/cart.php');
	exit;
}


/* =========================
   FETCH CART PRODUCTS
========================= */

$cartIds = array_keys($cart);

$placeholders = implode(
	',',
	array_fill(0, count($cartIds), '?')
);

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        price,
        stock,
        image
    FROM products
    WHERE id IN ($placeholders)
      AND status = 1
");

$stmt->execute($cartIds);

$cartProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   CALCULATE TOTALS
========================= */

$subtotal = 0;

foreach ($cartProducts as &$cartProduct) {

	$productId = (int) $cartProduct['id'];

	$quantity = (int) ($cart[$productId] ?? 0);

	if ($quantity <= 0) {
		continue;
	}

	/* Make sure quantity does not exceed stock */
	$quantity = min(
		$quantity,
		(int) $cartProduct['stock']
	);

	$cartProduct['quantity'] = $quantity;

	$cartProduct['item_total'] =
		(float) $cartProduct['price'] * $quantity;

	$subtotal += $cartProduct['item_total'];
}

unset($cartProduct);


/* =========================
   SHIPPING
========================= */

$shippingOptions = [
	'free' => 0,
	'standard' => 10,
	'express' => 20
];

$selectedShipping = $_SESSION['shipping_method'] ?? 'free';

if (!array_key_exists($selectedShipping, $shippingOptions)) {
	$selectedShipping = 'free';
}

$shipping = $shippingOptions[$selectedShipping];


/* =========================
   FINAL TOTAL
========================= */

$total = $subtotal + $shipping;


/* =========================
   PLACE / UPDATE ORDER
========================= */

$orderError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$paymentMethod = $_POST['payment_method'] ?? '';


	/* =========================
	   PAYMENT METHOD CHECK
	========================= */

	if ($paymentMethod === '') {

		$orderError = 'Please select a payment method.';

	} else {


		/* =========================
		   GET BILLING DETAILS
		========================= */

		$firstName = trim($_POST['first_name'] ?? '');
		$lastName = trim($_POST['last_name'] ?? '');
		$companyName = trim($_POST['company_name'] ?? '');
		$country = trim($_POST['country'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$address2 = trim($_POST['address_2'] ?? '');
		$city = trim($_POST['city'] ?? '');
		$state = trim($_POST['state'] ?? '');
		$postcode = trim($_POST['postcode'] ?? '');
		$phone = trim($_POST['phone'] ?? '');
		$email = trim($_POST['email'] ?? '');

		$orderNotes = trim($_POST['order_notes'] ?? '');


		/* =========================
		   REQUIRED FIELD CHECK
		========================= */

		if (
			$firstName === '' ||
			$lastName === '' ||
			$country === '' ||
			$address === '' ||
			$city === '' ||
			$state === '' ||
			$postcode === '' ||
			$phone === '' ||
			$email === ''
		) {

			$orderError =
				'Please fill in all required billing details.';

		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

			$orderError =
				'Please enter a valid email address.';

		} else {

			/* =================================================
			   STRIPE PAYMENT
			================================================= */

			if ($paymentMethod === 'stripe') {

				/*
				 * Save billing details temporarily.
				 * They will be used after successful
				 * Stripe payment.
				 */

				$_SESSION['stripe_billing_details'] = [
					'first_name' => $firstName,
					'last_name' => $lastName,
					'company_name' => $companyName,
					'country' => $country,
					'address' => $address,
					'address_2' => $address2,
					'city' => $city,
					'state' => $state,
					'postcode' => $postcode,
					'phone' => $phone,
					'email' => $email,
					'order_notes' => $orderNotes
				];

				$_SESSION['customer_email'] = $email;

				header(
					'Location: /public/stripe-checkout.php'
				);

				exit;
			}


			/* =================================================
			   CASH ON DELIVERY
			================================================= */

			if ($paymentMethod !== 'cod') {

				$orderError =
					'Invalid payment method selected.';

			} else {

				try {

					$pdo->beginTransaction();


					/* =========================
					   RECHECK STOCK
					========================= */

					$orderItems = [];
					$calculatedTotal = 0;

					foreach ($cartProducts as $cartProduct) {

						$productId =
							(int) $cartProduct['id'];

						$quantity =
							(int) ($cart[$productId] ?? 0);

						if ($quantity <= 0) {
							continue;
						}


						$stockStmt = $pdo->prepare("
                            SELECT
                                id,
                                name,
                                price,
                                stock
                            FROM products
                            WHERE id = ?
                              AND status = 1
                            FOR UPDATE
                        ");

						$stockStmt->execute([
							$productId
						]);

						$currentProduct =
							$stockStmt->fetch(PDO::FETCH_ASSOC);


						if (!$currentProduct) {

							throw new Exception(
								'One of the products in your cart is no longer available.'
							);
						}


						if (
							(int) $currentProduct['stock']
							< $quantity
						) {

							throw new Exception(
								'Not enough stock available for ' .
								$currentProduct['name'] .
								'.'
							);
						}


						$unitPrice =
							(float) $currentProduct['price'];

						$itemTotal =
							$unitPrice * $quantity;

						$calculatedTotal +=
							$itemTotal;


						$orderItems[] = [
							'product_id' =>
								$productId,

							'quantity' =>
								$quantity,

							'unit_price' =>
								$unitPrice,

							'subtotal' =>
								$itemTotal
						];
					}


					if (empty($orderItems)) {

						throw new Exception(
							'Your cart is empty.'
						);
					}


					/* =========================
					   ADD SHIPPING TO TOTAL
					========================= */

					$calculatedTotal += $shipping;


					/* =========================
					   SAVE / UPDATE CUSTOMER
					   DETAILS
					========================= */

					$customerStmt = $pdo->prepare("
                        INSERT INTO customer_details (
                            user_id,
                            first_name,
                            last_name,
                            company_name,
                            country,
                            address,
                            address_2,
                            city,
                            state,
                            postcode,
                            phone,
                            email
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            first_name = VALUES(first_name),
                            last_name = VALUES(last_name),
                            company_name = VALUES(company_name),
                            country = VALUES(country),
                            address = VALUES(address),
                            address_2 = VALUES(address_2),
                            city = VALUES(city),
                            state = VALUES(state),
                            postcode = VALUES(postcode),
                            phone = VALUES(phone),
                            email = VALUES(email)
                    ");

					$customerStmt->execute([
						$userId,
						$firstName,
						$lastName,
						$companyName,
						$country,
						$address,
						$address2,
						$city,
						$state,
						$postcode,
						$phone,
						$email
					]);


					/* =========================
					   CHECK EXISTING ORDER
					========================= */

					$existingOrder = null;

					if ($activeOrderId > 0) {

						$existingOrderStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount
    FROM orders
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
    FOR UPDATE
");

						$existingOrderStmt->execute([
							$activeOrderId,
							$userId
						]);

						$existingOrder =
							$existingOrderStmt->fetch(
								PDO::FETCH_ASSOC
							);
					}


					/* =================================================
					   EXISTING ORDER
					   Add new products to SAME ORDER
					================================================= */

					if ($existingOrder) {

						$orderId =
							(int) $existingOrder['id'];

						$orderNumber =
							$existingOrder['order_number'];

						$oldOrderTotal =
							(float) $existingOrder['total_amount'];

						/* =========================
						   UPDATE ORDER
						========================= */

						$newOrderTotal =
							$oldOrderTotal +
							$calculatedTotal;


						$updateOrderStmt = $pdo->prepare("
                            UPDATE orders
                            SET
                                first_name = ?,
                                last_name = ?,
                                company_name = ?,
                                country = ?,
                                address = ?,
                                address_2 = ?,
                                city = ?,
                                state = ?,
                                postcode = ?,
                                phone = ?,
                                email = ?,
                                order_notes = ?,
                                total_amount = ?,
                                payment_method = 'cod'
                            WHERE id = ?
                              AND user_id = ?
                        ");

						$updateOrderStmt->execute([
							$firstName,
							$lastName,
							$companyName,
							$country,
							$address,
							$address2,
							$city,
							$state,
							$postcode,
							$phone,
							$email,
							$orderNotes,
							$newOrderTotal,
							$orderId,
							$userId
						]);


						/* =========================
						   INSERT ORDER ITEMS
						========================= */

						$itemStmt = $pdo->prepare("
                            INSERT INTO order_items (
                                order_id,
                                product_id,
                                quantity,
                                unit_price,
                                subtotal
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ");


						/* =========================
						   REDUCE STOCK
						========================= */

						$stockUpdateStmt = $pdo->prepare("
                            UPDATE products
                            SET stock = stock - ?
                            WHERE id = ?
                              AND stock >= ?
                        ");


						foreach ($orderItems as $item) {

							$itemStmt->execute([
								$orderId,
								$item['product_id'],
								$item['quantity'],
								$item['unit_price'],
								$item['subtotal']
							]);


							$stockUpdateStmt->execute([
								$item['quantity'],
								$item['product_id'],
								$item['quantity']
							]);


							if (
								$stockUpdateStmt->rowCount()
								!== 1
							) {

								throw new Exception(
									'Unable to update product stock.'
								);
							}
						}


					} else {


						/* =================================================
						   NEW ORDER
						================================================= */


						/* =========================
						   GENERATE ORDER NUMBER
						========================= */

						$orderNumber =
							'ORD-' .
							date('YmdHis') .
							'-' .
							rand(1000, 9999);


						/* =========================
						   INSERT NEW ORDER
						========================= */

						$orderStmt = $pdo->prepare("
                            INSERT INTO orders (
                                user_id,
                                first_name,
                                last_name,
                                company_name,
                                country,
                                address,
                                address_2,
                                city,
                                state,
                                postcode,
                                phone,
                                email,
                                order_notes,
                                order_number,
                                total_amount,
                                payment_method,
                                payment_status,
                                order_status,
                                transaction_id
                            )
                            VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, 'cod', 'pending', 'processing', NULL
                            )
                        ");


						$orderStmt->execute([
							$userId,
							$firstName,
							$lastName,
							$companyName,
							$country,
							$address,
							$address2,
							$city,
							$state,
							$postcode,
							$phone,
							$email,
							$orderNotes,
							$orderNumber,
							$calculatedTotal
						]);


						$orderId =
							(int) $pdo->lastInsertId();


						/* =========================
						   INSERT ORDER ITEMS
						========================= */

						$itemStmt = $pdo->prepare("
                            INSERT INTO order_items (
                                order_id,
                                product_id,
                                quantity,
                                unit_price,
                                subtotal
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ");


						/* =========================
						   REDUCE PRODUCT STOCK
						========================= */

						$stockUpdateStmt = $pdo->prepare("
                            UPDATE products
                            SET stock = stock - ?
                            WHERE id = ?
                              AND stock >= ?
                        ");


						foreach ($orderItems as $item) {

							$itemStmt->execute([
								$orderId,
								$item['product_id'],
								$item['quantity'],
								$item['unit_price'],
								$item['subtotal']
							]);


							$stockUpdateStmt->execute([
								$item['quantity'],
								$item['product_id'],
								$item['quantity']
							]);


							if (
								$stockUpdateStmt->rowCount()
								!== 1
							) {

								throw new Exception(
									'Unable to update product stock.'
								);
							}
						}
					}


					/* =========================
					   COMPLETE TRANSACTION
					========================= */

					$pdo->commit();
					/* =========================
					   SEND COD ORDER EMAIL
					========================= */

					$mailer = new Mailer();

					$emailSubject = 'Order Update - ' . $orderNumber;

					$emailBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Update</title>
</head>

<body>

    <h2>Thank you for shopping with us!</h2>

    <p>Hello ' . htmlspecialchars($firstName) . ',</p>

    <p>
        Your order has been successfully placed/updated.
    </p>

    <p>
        <strong>Order Number:</strong>
        ' . htmlspecialchars($orderNumber) . '
    </p>

    <p>
       <strong>Total Amount:</strong>
Rs ' . number_format(
						$existingOrder ? $newOrderTotal : $calculatedTotal,
						2
					) . '
    </p>

    <p>
        <strong>Payment Method:</strong>
        Cash on Delivery
    </p>

    <p>
        <strong>Order Status:</strong>
        Processing
    </p>

    <p>
        We will process your order shortly.
    </p>

    <p>
        Thank you for shopping with us!
    </p>

</body>
</html>
';

					$mailer->send(
						$email,
						$emailSubject,
						$emailBody
					);

					/* =========================
					   CLEAR CART
					========================= */

					unset($_SESSION['cart']);

					unset($_SESSION['active_order_id']);


					/* =========================
					   GO TO CONFIRMATION
					========================= */

					header(
						'Location: /public/order-confirmation.php?order_id=' .
						(int) $orderId
					);

					exit;


				} catch (Exception $e) {

					if ($pdo->inTransaction()) {
						$pdo->rollBack();
					}

					$orderError =
						$e->getMessage();
				}
			}
		}
	}
}

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


	<link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">

	<link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/favicon-32x32.png">

	<link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/favicon-16x16.png">

	<link rel="manifest" href="assets/images/icons/site.html">

	<link rel="mask-icon" href="assets/images/icons/safari-pinned-tab.svg" color="#666666">

	<link rel="shortcut icon" href="assets/images/icons/favicon.ico">

	<meta name="apple-mobile-web-app-title" content="Molla">

	<meta name="application-name" content="Molla">

	<meta name="msapplication-TileColor" content="#cc9966">

	<meta name="theme-color" content="#ffffff">


	<link rel="stylesheet" href="assets/css/bootstrap.min.css">

	<link rel="stylesheet" href="assets/css/style.css">
	<style>
		.order-loading {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
		}

		.loading-spinner {
			width: 22px;
			height: 22px;
			border: 3px solid rgba(0, 0, 0, 0.2);
			border-top-color: #333;
			border-radius: 50%;
			animation: order-spin 0.7s linear infinite;
		}

		@keyframes order-spin {
			to {
				transform: rotate(360deg);
			}
		}
	</style>
</head>


<body>

	<div class="page-wrapper">

		<?php include '../includes/header.php'; ?>


		<main class="main">


			<div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">

				<div class="container">

					<h1 class="page-title">

						Checkout
						<span>Shop</span>

					</h1>

				</div>

			</div>


			<nav aria-label="breadcrumb" class="breadcrumb-nav">

				<div class="container">

					<ol class="breadcrumb">

						<li class="breadcrumb-item">
							<a href="index.php">
								Home
							</a>
						</li>

						<li class="breadcrumb-item">
							<a href="#">
								Shop
							</a>
						</li>

						<li class="breadcrumb-item active" aria-current="page">

							Checkout

						</li>

					</ol>

				</div>

			</nav>


			<div class="page-content">

				<div class="checkout">

					<div class="container">


						<div class="checkout-discount">

							<form action="#">

								<input type="text" class="form-control" required id="checkout-discount-input">

								<label for="checkout-discount-input" class="text-truncate">

									Have a coupon?

									<span>
										Click here to enter your code
									</span>

								</label>

							</form>

						</div>


						<form action="checkout.php" method="POST">


							<?php if (!empty($orderError)): ?>

								<div class="alert alert-danger">

									<?php
									echo htmlspecialchars(
										$orderError
									);
									?>

								</div>

							<?php endif; ?>


							<div class="row">


								<div class="col-lg-9">

									<h2 class="checkout-title">
										Billing Details
									</h2>


									<div class="row">

										<div class="col-sm-6">

											<label>
												First Name *
											</label>

											<input type="text" name="first_name" class="form-control"
												value="<?php echo htmlspecialchars($customerDetails['first_name'] ?? ''); ?>"
												required>

										</div>


										<div class="col-sm-6">

											<label>
												Last Name *
											</label>

											<input type="text" name="last_name" class="form-control"
												value="<?php echo htmlspecialchars($customerDetails['last_name'] ?? ''); ?>"
												required>

										</div>

									</div>


									<label>
										Company Name (Optional)
									</label>

									<input type="text" name="company_name" class="form-control"
										value="<?php echo htmlspecialchars($customerDetails['company_name'] ?? ''); ?>">


									<label>
										Country *
									</label>

									<input type="text" name="country" class="form-control"
										value="<?php echo htmlspecialchars($customerDetails['country'] ?? ''); ?>"
										required>


									<label>
										Street address *
									</label>

									<input type="text" name="address" class="form-control"
										placeholder="House number and Street name"
										value="<?php echo htmlspecialchars($customerDetails['address'] ?? ''); ?>"
										required>


									<input type="text" name="address_2" class="form-control"
										placeholder="Appartments, suite, unit etc ..."
										value="<?php echo htmlspecialchars($customerDetails['address_2'] ?? ''); ?>">


									<div class="row">

										<div class="col-sm-6">

											<label>
												Town / City *
											</label>

											<input type="text" name="city" class="form-control"
												value="<?php echo htmlspecialchars($customerDetails['city'] ?? ''); ?>"
												required>

										</div>


										<div class="col-sm-6">

											<label>
												State / County *
											</label>

											<input type="text" name="state" class="form-control"
												value="<?php echo htmlspecialchars($customerDetails['state'] ?? ''); ?>"
												required>

										</div>

									</div>


									<div class="row">

										<div class="col-sm-6">

											<label>
												Postcode / ZIP *
											</label>

											<input type="text" name="postcode" class="form-control"
												value="<?php echo htmlspecialchars($customerDetails['postcode'] ?? ''); ?>"
												required>

										</div>


										<div class="col-sm-6">

											<label>
												Phone *
											</label>

											<input type="tel" name="phone" class="form-control"
												value="<?php echo htmlspecialchars($customerDetails['phone'] ?? ''); ?>"
												required>

										</div>

									</div>


									<label>
										Email address *
									</label>

									<input type="email" name="email" class="form-control"
										value="<?php echo htmlspecialchars($customerDetails['email'] ?? ''); ?>"
										required>


									<div class="custom-control custom-checkbox">

										<input type="checkbox" class="custom-control-input" id="checkout-create-acc">

										<label class="custom-control-label" for="checkout-create-acc">

											Create an account?

										</label>

									</div>


									<div class="custom-control custom-checkbox">

										<input type="checkbox" class="custom-control-input" id="checkout-diff-address">

										<label class="custom-control-label" for="checkout-diff-address">

											Ship to a different address?

										</label>

									</div>


									<label>
										Order notes (optional)
									</label>

									<textarea name="order_notes" class="form-control" cols="30" rows="4"
										placeholder="Notes about your order, e.g. special notes for delivery"></textarea>

								</div>


								<aside class="col-lg-3">

									<div class="summary">

										<h3 class="summary-title">
											Your Order
										</h3>


										<table class="table table-summary">

											<thead>

												<tr>

													<th>
														Product
													</th>

													<th>
														Total
													</th>

												</tr>

											</thead>


											<tbody>

												<?php foreach ($cartProducts as $cartProduct): ?>

													<?php if (($cartProduct['quantity'] ?? 0) > 0): ?>

														<tr>

															<td>

																<a
																	href="product-detail.php?id=<?php echo (int) $cartProduct['id']; ?>">

																	<?php
																	echo htmlspecialchars(
																		$cartProduct['name']
																	);
																	?>

																	×

																	<?php
																	echo (int) $cartProduct['quantity'];
																	?>

																</a>

															</td>


															<td>

																$

																<?php
																echo number_format(
																	$cartProduct['item_total'],
																	2
																);
																?>

															</td>

														</tr>

													<?php endif; ?>

												<?php endforeach; ?>


												<tr class="summary-subtotal">

													<td>
														Subtotal:
													</td>

													<td>

														Rs

														<?php
														echo number_format(
															$subtotal,
															2
														);
														?>

													</td>

												</tr>


												<tr>

													<td>
														Shipping:
													</td>

													<td>

														<?php if ($shipping == 0): ?>

															Free Shipping

														<?php else: ?>

															Rs

															<?php
															echo number_format(
																$shipping,
																2
															);
															?>

														<?php endif; ?>

													</td>

												</tr>


												<tr class="summary-total">

													<td>
														Total:
													</td>

													<td>

														Rs

														<?php
														echo number_format(
															$total,
															2
														);
														?>

													</td>

												</tr>

											</tbody>

										</table>


										<div class="payment-methods mb-3">

											<h4 class="mb-3">
												Payment Method
											</h4>


											<label style="display: block; cursor: pointer; margin-bottom: 12px;">

												<input type="radio" name="payment_method" value="cod">

												<span style="margin-left: 6px;">
													Cash on Delivery
												</span>

											</label>


											<label style="display: block; cursor: pointer;">

												<input type="radio" name="payment_method" value="stripe">

												<span style="margin-left: 6px;">
													Stripe
												</span>

											</label>

										</div>

										<button type="submit" class="btn btn-outline-primary-2 btn-order btn-block"
											id="place-order-btn">

											<span id="place-order-content">
												Place Order
											</span>

										</button>

									</div>

								</aside>

							</div>

						</form>

					</div>

				</div>

			</div>

		</main>


		<?php include '../includes/footer.php'; ?>

	</div>


	<button id="scroll-top" title="Back to Top">

		<i class="icon-arrow-up"></i>

	</button>


	<script src="assets/js/jquery.min.js"></script>

	<script src="assets/js/bootstrap.bundle.min.js"></script>

	<script src="assets/js/jquery.hoverIntent.min.js"></script>

	<script src="assets/js/jquery.waypoints.min.js"></script>

	<script src="assets/js/owl.carousel.min.js"></script>

	<script src="assets/js/main.js"></script>
	<script>
		document.querySelector('form[action="checkout.php"]').addEventListener('submit', function (e) {

			const button = document.getElementById('place-order-btn');

			button.disabled = true;

			button.innerHTML = `
		<span class="order-loading">
			<span class="loading-spinner"></span>
		</span>
	`;
		});
	</script>
</body>

</html>