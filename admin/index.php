<?php
$pageTitle = 'Admin Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/includes/header.php';
requireAdmin();

//fetch total users
$total_users = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role != 'admin'"
)->fetch_assoc()['total'];

//count active listings
$total_listings = $conn->query(
    "SELECT COUNT(*) AS total FROM products WHERE status = 'active'"
)->fetch_assoc()['total'];

//count pending orders
$pending_orders = $conn->query(
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'"
)->fetch_assoc()['total'];

//count pending deliveries
$pending_deliveries = $conn->query(
    "SELECT COUNT(*) AS total FROM deliveries WHERE status = 'pending'"
)->fetch_assoc()['total'];

//count unverified sellers
$unverified_sellers = $conn->query(
    "SELECT COUNT(*) AS total FROM users 
     WHERE role = 'seller' AND is_verified = 0"
)->fetch_assoc()['total'];


//fetch recent orders with buyer and product details
$recent_orders = $conn->query(
    "SELECT o.id, o.total_price, o.status, o.created_at,
            p.title,
            u.name AS buyer_name
     FROM orders o
     JOIN products p ON o.product_id = p.id
     JOIN users u    ON o.buyer_id   = u.id
     ORDER BY o.created_at DESC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

?>

<!-- Admin dashboard content -->
<div class="admin-dashboard">
    <h1>Admin Dashboard</h1>

    <div class="stats-grid"> <!-- Display key metrics in cards -->

        <div class="stat-card"> <!-- Total users card -->
            <span class="stat-number"><?= $total_users ?></span>
            <span class="stat-label">Total users</span>
        </div>

        <div class="stat-card"> <!-- Active listings card -->
            <span class="stat-number"><?= $total_listings ?></span>
            <span class="stat-label">Active listings</span>
        </div>

        <div class="stat-card"> <!-- Pending orders card -->
            <span class="stat-number"><?= $pending_orders ?></span>
            <span class="stat-label">Pending orders</span>
        </div>

        <div class="stat-card"> <!-- Pending deliveries card -->
            <span class="stat-number"><?= $pending_deliveries ?></span>
            <span class="stat-label">Pending deliveries</span>
        </div>

        <div class="stat-card <?= $unverified_sellers > 0 ? 'stat-warning' : '' ?>"> <!-- Unverified sellers card with warning highlight if > 0 -->
            <span class="stat-number"><?= $unverified_sellers ?></span>
            <span class="stat-label">Unverified sellers</span>
        </div>

    </div>

    <div class="admin-nav"> <!-- Navigation links for managing different sections -->
        <h2>Manage</h2>
        <div class="admin-nav-grid">
            <a href="users.php" class="admin-nav-card">
                <strong>Users</strong>
                <span>Manage accounts and verify sellers</span>
            </a>
            <a href="listings.php" class="admin-nav-card">
                <strong>Listings</strong>
                <span>Review and moderate product listings</span>
            </a>
            <a href="deliveries.php" class="admin-nav-card">
                <strong>Deliveries</strong>
                <span>Assign drivers and update delivery status</span>
            </a>
        </div>
    </div>

    <div class="recent-orders"> <!-- Section for displaying recent orders -->
        <h2>Recent orders</h2>

        <?php if (empty($recent_orders)): ?>
            <p class="empty-state">No orders yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Product</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?> <!-- Loop through recent orders and display in table rows -->
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= sanitize($order['buyer_name']) ?></td>
                            <td><?= sanitize($order['title']) ?></td>
                            <td>R <?= number_format($order['total_price'], 2) ?></td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/me2u/me2u/includes/footer.php'; ?>