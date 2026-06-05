<?php

//activate session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$pageTitle = $pageTitle ?? 'Me2U';      //page title will be me2u if one is not assigned to the page


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">

</head>



<body>
    <header class="header-content">
        <nav class="nav">
            <a href="/pages/listings.php" class="nav-brand">Me2U</a>
            <ul class="nav-links">
                <li><a href="/pages/listings.php">Browse</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="/pages/orders.php">My Orders</a></li>
                    <?php if (isSeller()): ?>
                        <li><a href="/pages/add-product.php">Sell</a></li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="/admin/index.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="/pages/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/pages/login.php">Login</a></li>
                    <li><a href="/pages/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container">
</body>