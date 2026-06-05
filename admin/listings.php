<?php
$pageTitle = 'Manage Listings';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
requireAdmin();

// Fetch all products for admin management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Handle listing actions (remove/restore)
    $product_id = intval($_POST['product_id'] ?? 0);
    $action     = sanitize($_POST['action']   ?? '');

    if ($product_id && $action === 'remove') {  // Soft delete listing by setting status to 'removed'
        $stmt = $conn->prepare(
            "UPDATE products SET status = 'removed' WHERE id = ?"
        );
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
    }

    if ($product_id && $action === 'restore') {  // Restore listing by setting status back to 'active'
        $stmt = $conn->prepare(
            "UPDATE products SET status = 'active' WHERE id = ?"
        );
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
    }

    redirect('/admin/listings.php');
}

// Fetch all listings with seller and category details for display
$listings = $conn->query(
    "SELECT p.id, p.title, p.price, p.stock, 
            p.is_perishable, p.status, p.created_at,
            c.name AS category,
            u.name AS seller_name,
            u.is_verified AS seller_verified
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN users u      ON p.seller_id   = u.id
     ORDER BY p.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

?>

<!-- Admin manage listings page content -->
<div class="admin-page">
    <div class="admin-page-header">
        <h1>Manage Listings</h1>
        <a href="index.php" class="btn-secondary">Back to dashboard</a>
    </div>

    <?php if (empty($listings)): ?> <!-- Show empty state if no listings found -->
        <p class="empty-state">No listings found.</p>
    <?php else: ?> <!-- Display listings in a table format -->
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Seller</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Perishable</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $listing): ?> <!-- Loop through listings and display their details with action buttons -->
                    <tr class="<?= $listing['status'] === 'removed' ? 'row-removed' : '' ?>">
                        <td>#<?= $listing['id'] ?></td>
                        <td><?= sanitize($listing['title']) ?></td>
                        <td>
                            <?= sanitize($listing['seller_name']) ?>
                            <?php if (!$listing['seller_verified']): ?>
                                <span class="badge-warning">Unverified</span>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($listing['category']) ?></td>
                        <td>R <?= number_format($listing['price'], 2) ?></td>
                        <td><?= $listing['stock'] ?></td>
                        <td><?= $listing['is_perishable'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <span class="status-badge status-<?= $listing['status'] ?>">
                                <?= ucfirst($listing['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="listings.php">
                                <input type="hidden"
                                    name="product_id"
                                    value="<?= $listing['id'] ?>">

                                <?php if ($listing['status'] === 'active'): ?> <!-- Show 'Remove' button if listing is active, otherwise show 'Restore' button -->
                                    <button type="submit"
                                        name="action"
                                        value="remove"
                                        class="btn-danger"
                                        onclick="return confirm('Remove this listing?')">
                                        Remove
                                    </button>
                                <?php else: ?> <!-- Listing is removed, show 'Restore' button -->
                                    <button type="submit"
                                        name="action"
                                        value="restore"
                                        class="btn-success">
                                        Restore
                                    </button>
                                <?php endif; ?> <!-- End of action buttons based on listing status -->
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?> <!-- End of listings loop -->
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>