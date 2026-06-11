<?php
$pageTitle = 'Product';
require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/includes/delivery.php';     // for getDeliveryOptions()

// Get the product ID from the query string and validate it as an integer
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    redirect('/me2u/pages/listings.php');
}

// Fetch product details along with seller info and category name
$stmt = $conn->prepare(
    "SELECT p.id, p.title, p.description, p.price, p.stock, 
            p.is_perishable, p.created_at,
            c.name AS category,
            u.id   AS seller_id,
            u.name AS seller_name,
            u.is_verified AS seller_verified
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN users u      ON p.seller_id   = u.id
     WHERE p.id = ? AND p.status = 'active'"
);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// If no product found or product is not active, redirect back to listings
if (!$product) {
    redirect('/me2u/pages/listings.php');
}

// Set the page title to the product title for better user experience
$pageTitle = sanitize($product['title']);

// Fetch delivery options for this product
$delivery_options = getDeliveryOptions($conn, $product_id);

// Fetch recent reviews for the seller of this product
$stmt = $conn->prepare(
    "SELECT r.rating, r.comment, r.created_at,
            u.name AS reviewer_name
     FROM reviews r
     JOIN users u ON r.reviewer_id = u.id
     WHERE r.seller_id = ?
     ORDER BY r.created_at DESC
     LIMIT 5"
);
$stmt->bind_param('i', $product['seller_id']);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!-- The HTML below displays the product details, delivery options, and seller reviews. -->
<div class="product-page">

    <div class="product-main">

        <div class="product-info">

            <?php if ($product['is_perishable']): ?>
                <span class="badge-perishable">Same-day delivery only</span>
            <?php endif; ?>

            <?php if ($product['seller_verified']): ?>
                <span class="badge-verified">Verified seller</span>
            <?php endif; ?>

            <h1><?= sanitize($product['title']) ?></h1>

            <p class="product-category"><?= sanitize($product['category']) ?></p>

            <p class="product-price">R <?= number_format($product['price'], 2) ?></p>

            <p class="product-stock">
                <?= $product['stock'] > 0
                    ? $product['stock'] . ' available'
                    : 'Out of stock' ?>
            </p>

            <div class="product-description">
                <h2>About this listing</h2>
                <p><?= sanitize($product['description']) ?></p>
            </div>

            <div class="product-seller">
                <h2>Seller</h2>
                <p><?= sanitize($product['seller_name']) ?></p>
            </div>

            <div class="product-delivery">
                <h2>Delivery options</h2>
                <?php foreach ($delivery_options as $option): ?>
                    <div class="delivery-option-card">
                        <strong><?= $option['label'] ?></strong>
                        <p><?= $option['description'] ?></p>
                        <p><?= $option['estimated_time'] ?></p>
                        <p>+ R <?= number_format($option['price'], 2) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <a href="checkout.php?id=<?= $product['id'] ?>"
                    class="btn-primary">Buy now</a>
            <?php else: ?>
                <button class="btn-primary" disabled>Out of stock</button>
            <?php endif; ?>

        </div>

    </div>

    <div class="product-reviews">
        <h2>Seller reviews</h2>

        <?php if (empty($reviews)): ?>
            <p class="empty-state">No reviews yet for this seller.</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <strong><?= sanitize($review['reviewer_name']) ?></strong>
                        <span class="review-rating">
                            <?= str_repeat('★', $review['rating']) ?>
                            <?= str_repeat('☆', 5 - $review['rating']) ?>
                        </span>
                        <span class="review-date">
                            <?= date('d M Y', strtotime($review['created_at'])) ?>
                        </span>
                    </div>
                    <p><?= sanitize($review['comment']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/me2u/me2u/includes/footer.php'; ?>