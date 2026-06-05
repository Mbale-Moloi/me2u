<?php
$pageTitle = 'Browse listings';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

//URL GET filters
$category_filter   = intval($_GET['category']   ?? 0);
$perishable_filter = $_GET['perishable'] ?? '';
$search            = sanitize($_GET['search']   ?? '');     //read catgory values from URL

// WHERE conditions and parameters for prepared statement
$where  = ["p.status = 'active'"];
$params = [];
$types  = '';

if ($category_filter) {     // 0 means no filter, so only add condition if it's a positive integer
    $where[]  = 'p.category_id = ?';
    $params[] = $category_filter;
    $types   .= 'i';
}

if ($perishable_filter === '1') {       // '1' means filter for perishable items
    $where[] = 'p.is_perishable = 1';
}

if ($perishable_filter === '0') {       // '0' means filter for non-perishable items
    $where[] = 'p.is_perishable = 0';
}

if ($search) {      // search term provided, filter by title or description
    $where[]  = '(p.title LIKE ? OR p.description LIKE ?)';
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$where_sql = implode(' AND ', $where);      // Combine all conditions into a single string for the SQL query

// Main query to fetch products with optional filters
$stmt = $conn->prepare(
    "SELECT p.id, p.title, p.price, p.is_perishable, p.stock,
            c.name AS category,
            u.name AS seller_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN users u      ON p.seller_id   = u.id
     WHERE $where_sql
     ORDER BY p.created_at DESC"
);

if ($params) {      // Bind parameters if there are any filters applied
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();       // Execute the query
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all categories for the category filter dropdown
$categories = $conn->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetch_all(MYSQLI_ASSOC);

?>


<div class="listings-page">

    <aside class="filters">
        <h2>Filter</h2>
        <form method="GET" action="listings.php">

            <div class="form-group"> <!-- Search input for filtering by title or description -->
                <label for="search">Search</label>
                <input type="search"
                    id="search"
                    name="search"
                    value="<?= $search ?>">
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category"> <!-- Dropdown for category filter, populated from the database -->
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Delivery type</label> <!-- Radio buttons for perishable filter (same-day delivery vs non-perishable) -->
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio"
                            name="perishable"
                            value=""
                            <?= $perishable_filter === '' ? 'checked' : '' ?>>
                        All
                    </label>
                    <label class="radio-label">
                        <input type="radio"
                            name="perishable"
                            value="1"
                            <?= $perishable_filter === '1' ? 'checked' : '' ?>>
                        Same-day only
                    </label>
                    <label class="radio-label">
                        <input type="radio"
                            name="perishable"
                            value="0"
                            <?= $perishable_filter === '0' ? 'checked' : '' ?>>
                        Non-perishable
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-primary">Apply filters</button> <!-- Submit button to apply the selected filters -->
        </form>
    </aside>

    <div class="listings-grid">
        <?php if (empty($products)): ?>
            <p class="empty-state">No products found. Try adjusting your filters.</p> <!-- Show a message if no products match the filters -->
        <?php else: ?>
            <?php foreach ($products as $p): ?> <!-- Loop through the products and display each one in a card format -->
                <article class="product-card"
                    data-perishable="<?= $p['is_perishable'] ?>">

                    <?php if ($p['is_perishable']): ?> <!-- Show a badge for perishable items (same-day delivery) -->
                        <span class="badge-perishable">Same-day delivery only</span>
                    <?php endif; ?>

                    <h3 class="card-title"><?= sanitize($p['title']) ?></h3>

                    <p class="card-meta">
                        by <?= sanitize($p['seller_name']) ?>
                        &middot;
                        <?= sanitize($p['category']) ?>
                    </p>

                    <p class="card-price">R <?= number_format($p['price'], 2) ?></p>

                    <a href="product.php?id=<?= $p['id'] ?>"
                        class="btn-secondary">View listing</a>

                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>