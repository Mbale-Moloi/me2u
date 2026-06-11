<?php
$pageTitle = 'Add Listing';
require_once $_SERVER['DOCUMENT_ROOT'] . 'me2u/includes/header.php';
requireLogin();

if (!isSeller()) {
    redirect('/me2u/me2u/me2u/pages/listings.php');
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title']       ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price       = floatval($_POST['price']       ?? 0);
    $stock       = intval($_POST['stock']         ?? 1);
    $category_id = intval($_POST['category_id']   ?? 0);
    $is_perishable = intval($_POST['is_perishable'] ?? 0);

    if (empty($title))        $errors[] = 'Title is required.';
    if ($price <= 0)          $errors[] = 'Price must be greater than zero.';
    if ($stock < 1)           $errors[] = 'Stock must be at least 1.';
    if (!$category_id)        $errors[] = 'Please select a category.';

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO products 
                (seller_id, category_id, title, description, price, stock, is_perishable)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'iissdii',
            $_SESSION['user_id'],
            $category_id,
            $title,
            $description,
            $price,
            $stock,
            $is_perishable
        );

        if ($stmt->execute()) {
            $success = 'Listing created successfully.';
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$categories = $conn->query(
    "SELECT id, name, is_perishable FROM categories ORDER BY name"
)->fetch_all(MYSQLI_ASSOC);
?>

<section class="form-section">
    <h1>Add a listing</h1>

    <?php if ($success): ?>
        <p class="alert alert-success"><?= $success ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="add-product.php" method="POST">

        <div class="form-group">
            <label for="title">Listing title</label>
            <input type="text"
                id="title"
                name="title"
                required
                value="<?= isset($_POST['title']) ? sanitize($_POST['title']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">
                <?= isset($_POST['description']) ? sanitize($_POST['description']) : '' ?>
            </textarea>
        </div>

        <div class="form-group">
            <label for="price">Price (R)</label>
            <input type="number"
                id="price"
                name="price"
                min="0.01"
                step="0.01"
                required
                value="<?= isset($_POST['price']) ? $_POST['price'] : '' ?>">
        </div>

        <div class="form-group">
            <label for="stock">Stock available</label>
            <input type="number"
                id="stock"
                name="stock"
                min="1"
                required
                value="<?= isset($_POST['stock']) ? $_POST['stock'] : '1' ?>">
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select a category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= (isset($_POST['category_id']) &&
                            $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= sanitize($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Is this product perishable?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio"
                        name="is_perishable"
                        value="1"
                        <?= (isset($_POST['is_perishable']) &&
                            $_POST['is_perishable'] == '1') ? 'checked' : '' ?>>
                    Yes — same-day delivery only
                </label>
                <label class="radio-label">
                    <input type="radio"
                        name="is_perishable"
                        value="0"
                        <?= (!isset($_POST['is_perishable']) ||
                            $_POST['is_perishable'] == '0') ? 'checked' : '' ?>>
                    No — all delivery options available
                </label>
            </div>
        </div>

        <button type="submit" class="btn-primary">Post listing</button>

    </form>
</section>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/me2u/me2u/includes/footer.php'; ?>