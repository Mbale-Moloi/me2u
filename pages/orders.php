<?php
$pageTitle = 'My Orders';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';       //users must be logged in to view their orders
requireLogin();

// Fetch orders with product, delivery, and transaction details
$stmt = $conn->prepare(
    "SELECT o.id, o.quantity, o.total_price, o.status, o.created_at,
            p.title, p.is_perishable,
            d.delivery_type, d.status AS delivery_status, d.estimated_time,
            t.payment_method, t.payment_status
     FROM orders o
     JOIN products p       ON o.product_id  = p.id
     LEFT JOIN deliveries d ON d.order_id   = o.id
     LEFT JOIN transactions t ON t.order_id = o.id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Status labels for display
$status_labels = [
    'pending'    => 'Pending',
    'confirmed'  => 'Confirmed',
    'dispatched' => 'Dispatched',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled'
];
?>

<!-- Orders page content -->
<section class="orders-page">
    <h1>My Orders</h1>

    <?php if (isset($_GET['success'])): ?> <!-- Show success message if order was just placed -->
        <p class="alert alert-success">
            Your order was placed successfully!
        </p>
    <?php endif; ?>

    <?php if (empty($orders)): ?> <!-- Show empty state if no orders found -->
        <p class="empty-state">
            You have not placed any orders yet.
            <a href="listings.php">Start browsing</a>
        </p>

    <?php else: ?>

        <?php foreach ($orders as $order): ?> <!-- Display each order in a card format -->
            <article class="order-card">

                <div class="order-header">
                    <span class="order-id">Order #<?= $order['id'] ?></span>
                    <span class="order-date">
                        <?= date('d M Y', strtotime($order['created_at'])) ?>
                    </span>
                    <span class="status-badge status-<?= $order['status'] ?>">
                        <?= $status_labels[$order['status']] ?? ucfirst($order['status']) ?>
                    </span>
                </div>

                <p class="order-product">
                    <?= sanitize($order['title']) ?>
                </p>

                <p class="order-meta">
                    Quantity: <?= $order['quantity'] ?>
                    &middot;
                    Total: R <?= number_format($order['total_price'], 2) ?>
                </p>

                <div class="order-delivery">
                    <p>
                        Delivery type:
                        <strong>
                            <?= $order['delivery_type'] === 'same_day'
                                ? 'Same-day delivery'
                                : 'National courier' ?>
                        </strong>
                    </p>
                    <p>
                        Estimated time:
                        <strong>
                            <?= sanitize($order['estimated_time'] ?? 'To be confirmed') ?>
                        </strong>
                    </p>
                    <p>
                        Delivery status:
                        <strong>
                            <?= ucfirst($order['delivery_status'] ?? 'pending') ?>
                        </strong>
                    </p>
                </div>

                <div class="order-payment">
                    <p>
                        Payment method:
                        <strong>
                            <?= strtoupper(str_replace('_', ' ', $order['payment_method'] ?? '')) ?>
                        </strong>
                    </p>
                    <p>
                        Payment status:
                        <strong>
                            <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                        </strong>
                    </p>
                </div>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>