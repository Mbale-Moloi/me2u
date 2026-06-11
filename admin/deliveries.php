<?php
$pageTitle = 'Manage Deliveries';
require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/includes/header.php';
requireAdmin();

// Fetch all deliveries with related order, product, and driver details for admin management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Handle delivery actions (assign driver/update status)
    $delivery_id = intval($_POST['delivery_id'] ?? 0);
    $action      = sanitize($_POST['action']    ?? '');

    if ($delivery_id && $action === 'assign_driver') {  // Assign driver to delivery and update status to 'assigned'
        $driver_id = intval($_POST['driver_id'] ?? 0);

        if ($driver_id) {  // Ensure driver exists and is verified before assignment
            $stmt = $conn->prepare(
                "UPDATE deliveries SET driver_id = ?, status = 'assigned' 
                 WHERE id = ?"
            );
            $stmt->bind_param('ii', $driver_id, $delivery_id);
            $stmt->execute();
        }
    }

    if ($delivery_id && $action === 'update_status') {  // Update delivery status and cascade to order if delivered
        $new_status = sanitize($_POST['new_status'] ?? '');
        $allowed    = ['pending', 'assigned', 'in_transit', 'delivered', 'failed'];

        if (in_array($new_status, $allowed)) {  // Update delivery status and if delivered, also update order status to 'delivered'
            $stmt = $conn->prepare(
                "UPDATE deliveries SET status = ? WHERE id = ?"
            );
            $stmt->bind_param('si', $new_status, $delivery_id);
            $stmt->execute();

            if ($new_status === 'delivered') {  // If delivery is marked as delivered, also update the related order status to 'delivered'
                $stmt = $conn->prepare(
                    "UPDATE orders o
                     JOIN deliveries d ON d.order_id = o.id
                     SET o.status = 'delivered'
                     WHERE d.id = ?"
                );
                $stmt->bind_param('i', $delivery_id);
                $stmt->execute();
            }
        }
    }

    redirect('/me2u/me2u/admin/deliveries.php');
}

// Fetch all deliveries with related order, product, buyer, and driver details for display in admin panel
$deliveries = $conn->query(
    "SELECT d.id, d.delivery_type, d.courier_name, 
            d.status, d.estimated_time, d.created_at,
            o.id AS order_id, o.total_price,
            p.title AS product_title, p.is_perishable,
            b.name AS buyer_name,
            dr.id   AS driver_id,
            dr.name AS driver_name
     FROM deliveries d
     JOIN orders o       ON d.order_id  = o.id
     JOIN products p     ON o.product_id = p.id
     JOIN users b        ON o.buyer_id  = b.id
     LEFT JOIN users dr  ON d.driver_id = dr.id
     ORDER BY d.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);


// Fetch all verified drivers for assignment dropdown in admin delivery management
$drivers = $conn->query(
    "SELECT id, name FROM users 
     WHERE is_verified = 1 AND role != 'admin'
     ORDER BY name"
)->fetch_all(MYSQLI_ASSOC);
?>

<!-- Admin manage deliveries page content -->
<div class="admin-page">
    <div class="admin-page-header">
        <h1>Manage Deliveries</h1>
        <a href="index.php" class="btn-secondary">Back to dashboard</a>
    </div>

    <?php if (empty($deliveries)): ?> <!-- Show empty state if no deliveries found -->
        <p class="empty-state">No deliveries yet.</p>
    <?php else: ?> <!-- Display deliveries in a card format with details and action forms -->
        <?php foreach ($deliveries as $delivery): ?>
            <div class="delivery-card">

                <div class="delivery-header">
                    <span class="delivery-id">Delivery #<?= $delivery['id'] ?></span>
                    <span class="status-badge status-<?= $delivery['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $delivery['status'])) ?>
                    </span>
                    <?php if ($delivery['is_perishable']): ?> <!-- Show perishable badge if product is perishable -->
                        <span class="badge-perishable">Perishable</span>
                    <?php endif; ?> <!-- Show perishable badge if product is perishable -->
                </div>

                <div class="delivery-info">
                    <p>
                        Order #<?= $delivery['order_id'] ?>
                        &middot;
                        <?= sanitize($delivery['product_title']) ?>
                    </p>
                    <p>
                        Buyer: <?= sanitize($delivery['buyer_name']) ?>
                    </p>
                    <p>
                        Type:
                        <strong>
                            <?= $delivery['delivery_type'] === 'same_day'
                                ? 'Same-day delivery'
                                : 'National courier' ?>
                        </strong>
                    </p>
                    <p>
                        Estimated: <?= sanitize($delivery['estimated_time']) ?>
                    </p>
                    <?php if ($delivery['courier_name']): ?> <!-- Show courier name if delivery type is national courier and courier is assigned -->
                        <p>Courier: <?= sanitize($delivery['courier_name']) ?></p>
                    <?php endif; ?>
                    <p>
                        Driver: <!-- Show assigned driver name or indicate if not yet assigned -->
                        <strong>
                            <?= $delivery['driver_name']
                                ? sanitize($delivery['driver_name'])
                                : 'Not yet assigned' ?>
                        </strong>
                    </p>
                </div>

                <div class="delivery-actions"> <!-- Forms for assigning driver and updating delivery status with dropdowns and submit buttons -->

                    <form method="POST" action="deliveries.php">
                        <input type="hidden"
                            name="delivery_id"
                            value="<?= $delivery['id'] ?>">
                        <input type="hidden"
                            name="action"
                            value="assign_driver">

                        <label for="driver_<?= $delivery['id'] ?>">Assign driver</label>
                        <select id="driver_<?= $delivery['id'] ?>" name="driver_id">
                            <option value="">Select driver</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= $driver['id'] ?>"
                                    <?= $delivery['driver_id'] == $driver['id']
                                        ? 'selected' : '' ?>>
                                    <?= sanitize($driver['name']) ?>
                                </option>
                            <?php endforeach; ?> <!-- Loop through verified drivers and create options for assignment dropdown, pre-selecting current driver if assigned -->
                        </select>
                        <button type="submit" class="btn-primary">Assign</button>
                    </form>

                    <form method="POST" action="deliveries.php">
                        <input type="hidden"
                            name="delivery_id"
                            value="<?= $delivery['id'] ?>">
                        <input type="hidden"
                            name="action"
                            value="update_status">

                        <label for="status_<?= $delivery['id'] ?>">Update status</label>
                        <select id="status_<?= $delivery['id'] ?>" name="new_status">
                            <option value="pending"
                                <?= $delivery['status'] === 'pending'    ? 'selected' : '' ?>>
                                Pending
                            </option>
                            <option value="assigned"
                                <?= $delivery['status'] === 'assigned'   ? 'selected' : '' ?>>
                                Assigned
                            </option>
                            <option value="in_transit"
                                <?= $delivery['status'] === 'in_transit' ? 'selected' : '' ?>>
                                In transit
                            </option>
                            <option value="delivered"
                                <?= $delivery['status'] === 'delivered'  ? 'selected' : '' ?>>
                                Delivered
                            </option>
                            <option value="failed"
                                <?= $delivery['status'] === 'failed'     ? 'selected' : '' ?>>
                                Failed
                            </option>
                        </select>
                        <button type="submit" class="btn-primary">Update</button>
                    </form>

                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/me2u/me2u/includes/footer.php'; ?>