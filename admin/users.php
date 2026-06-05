<?php
$pageTitle = 'Manage Users';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
requireAdmin();


//admin actions for verifying/unverifying sellers and deleting users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Handle user actions (verify/unverify/delete)
    $user_id = intval($_POST['user_id'] ?? 0);
    $action  = sanitize($_POST['action'] ?? '');

    if ($user_id && $action === 'verify') {  // Verify seller
        $stmt = $conn->prepare(
            "UPDATE users SET is_verified = 1 WHERE id = ?"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }

    if ($user_id && $action === 'unverify') {  // Unverify seller
        $stmt = $conn->prepare(
            "UPDATE users SET is_verified = 0 WHERE id = ?"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }

    if ($user_id && $action === 'delete') {  // Delete user (only if not admin)
        $stmt = $conn->prepare(
            "DELETE FROM users WHERE id = ? AND role != 'admin'"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }

    redirect('/admin/users.php');
}

// Fetch all non-admin users for display
$users = $conn->query(
    "SELECT id, name, email, role, is_verified, created_at
     FROM users
     WHERE role != 'admin'
     ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);
?>

<!-- Admin manage users page content -->
<div class="admin-page">
    <div class="admin-page-header">
        <h1>Manage Users</h1>
        <a href="index.php" class="btn-secondary">Back to dashboard</a>
    </div>

    <?php if (empty($users)): ?> <!-- Show empty state if no users found -->
        <p class="empty-state">No users registered yet.</p>
    <?php else: ?>
        <table class="admin-table"> <!-- Display users in a table format -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?> <!-- Loop through users and display their details with action buttons -->
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td><?= sanitize($user['name']) ?></td>
                        <td><?= sanitize($user['email']) ?></td>
                        <td><?= ucfirst($user['role']) ?></td>
                        <td><?= $user['is_verified'] ? 'Yes' : 'No' ?></td>
                        <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <form method="POST" action="users.php">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                                <?php if ($user['is_verified']): ?>
                                    <button type="submit" name="action" value="unverify"
                                        class="btn-warning">
                                        Unverify
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="verify"
                                        class="btn-success">
                                        Verify
                                    </button>
                                <?php endif; ?>

                                <button type="submit" name="action" value="delete"
                                    class="btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>