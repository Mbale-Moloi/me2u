<?php
$pageTitle = 'Checkout';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/delivery.php';
requireLogin();

// Get product ID from URL and validate it
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    redirect('/pages/listings.php');
}

// Fetch product details from the database
$stmt = $conn->prepare(
    "SELECT p.id, p.title, p.price, p.stock, p.is_perishable,
            u.name AS seller_name
     FROM products p
     JOIN users u ON p.seller_id = u.id
     WHERE p.id = ? AND p.status = 'active'"
);
$stmt->bind_param('i', $product_id);        // Bind the product ID parameter to the prepared statement
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();      // Fetch the product details as an associative array

if (!$product) {        // If the product doesn't exist or isn't active, redirect back to listings page
    redirect('/pages/listings.php');
}

$delivery_options = getDeliveryOptions($conn, $product_id);     // Fetch delivery options for the product

$error = '';

// Handle form submission when the user clicks the "Place Order" button
if ($_SERVER['REQUEST_METHOD'] === 'POST') {        // Get and validate form inputs
    $quantity      = intval($_POST['quantity']      ?? 1);
    $delivery_type = sanitize($_POST['delivery_type'] ?? '');
    $payment       = sanitize($_POST['payment']       ?? '');
    $allowed_types = array_column($delivery_options, 'type');

    if (!in_array($delivery_type, $allowed_types)) {        // Validate that the selected delivery type is one of the allowed options for this product
        $error = 'Invalid delivery option selected.';
    }
    if (empty($delivery_type)) {        // Ensure that a delivery option is selected
        $error = 'Please select a delivery option.';
    }

    if (empty($payment)) {      // Ensure that a payment method is selected
        $error = 'Please select a payment method.';
    }

    if ($quantity < 1 || $quantity > $product['stock']) {       // Validate that the quantity is at least 1 and does not exceed the available stock
        $error = 'Invalid quantity selected.';
    }
    if (empty($error)) {        // If there are no validation errors, proceed to create the order and transaction records in the database

        $total = $product['price'] * $quantity;     // Calculate the total price based on the product price and quantity

        $stmt = $conn->prepare(     // Insert a new order into the orders table with the buyer ID, product ID, quantity, total price, and status set to 'pending'
            "INSERT INTO orders 
                (buyer_id, product_id, quantity, total_price, status)
             VALUES (?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param(      // Bind the parameters for the order: buyer ID from the session, product ID from the URL, quantity from the form, and total price calculated above
            'iiid',
            $_SESSION['user_id'],
            $product_id,
            $quantity,
            $total
        );
        $stmt->execute();       // Execute the statement to insert the order into the database
        $order_id = $conn->insert_id;
        $courier = ($delivery_type === 'national_courier')
            ? 'The Courier Guy'
            : null;

        createDelivery($conn, $order_id, $delivery_type, $courier);     // Create a delivery record for the order using the createDelivery function, passing the order ID, delivery type, and courier (if applicable)

        $stmt = $conn->prepare(     // Insert a new transaction into the transactions table with the order ID, payment method, payment status set to 'pending', and the total amount
            "INSERT INTO transactions 
                (order_id, payment_method, payment_status, amount)
             VALUES (?, ?, 'pending', ?)"
        );
        $stmt->bind_param(      // Bind the parameters for the transaction: order ID from the previous insert, payment method from the form, and total amount calculated above
            'isd',
            $order_id,
            $payment,
            $total
        );
        $stmt->execute();

        redirect('/pages/orders.php?success=1');       // After successfully creating the order and transaction, redirect the user to the orders page with a success message
    }
}
?>

<!-- The HTML below displays the checkout page with the order summary and the form for selecting quantity, delivery option, and payment method. It also shows any validation errors if they occur. -->
<div class="checkout-page">
    <h1>Checkout</h1>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= $error ?></p> <!-- If there is an error message, display it in an alert box -->
    <?php endif; ?>

    <div class="checkout-grid"> <!-- A grid layout for the checkout page, with the order summary on the left and the checkout form on the right -->

        <div class="checkout-summary"> <!-- The order summary section displays the product title, seller name, price, stock availability, and a badge if the product is perishable -->
            <h2>Order summary</h2>
            <p class="checkout-title"><?= sanitize($product['title']) ?></p>
            <p class="checkout-seller">Sold by <?= sanitize($product['seller_name']) ?></p>
            <p class="checkout-price">R <?= number_format($product['price'], 2) ?> each</p>
            <p class="checkout-stock"><?= $product['stock'] ?> available</p>

            <?php if ($product['is_perishable']): ?>
                <span class="badge-perishable">Perishable — same-day delivery required</span>
            <?php endif; ?>
        </div>

        <form class="checkout-form" action="checkout.php?id=<?= $product_id ?>" method="POST"> <!-- The checkout form allows the user to select the quantity, delivery option, and payment method. It submits a POST request to the same page with the product ID in the URL. -->

            <div class="form-group"> <!-- The quantity input allows the user to select how many units of the product they want to purchase, with a minimum of 1 and a maximum equal to the available stock. -->
                <label for="quantity">Quantity</label>
                <input type="number"
                    id="quantity"
                    name="quantity"
                    min="1"
                    max="<?= $product['stock'] ?>"
                    value="1"
                    required>
            </div>

            <!-- The delivery options are displayed as radio buttons, allowing the user to select one of the available delivery methods for this product. Each option shows the label, description, estimated time, and additional cost. -->
            <div class="form-group">
                <label>Delivery option</label>
                <?php foreach ($delivery_options as $option): ?>
                    <label class="delivery-option-card">
                        <input type="radio"
                            name="delivery_type"
                            value="<?= $option['type'] ?>"
                            required>
                        <div class="delivery-option-info">
                            <strong><?= $option['label'] ?></strong>
                            <span><?= $option['description'] ?></span>
                            <span><?= $option['estimated_time'] ?></span>
                            <span>+ R <?= number_format($option['price'], 2) ?></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- The payment method select dropdown allows the user to choose how they want to pay for their order, with options for EFT/Bank transfer, card payment, and cash on delivery. -->
            <div class="form-group">
                <label for="payment">Payment method</label>
                <select id="payment" name="payment" required>
                    <option value="">Select payment method</option>
                    <option value="eft">EFT / Bank transfer</option>
                    <option value="card">Card payment</option>
                    <option value="cash_on_delivery">Cash on delivery</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">Place order</button>

        </form>

    </div>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>