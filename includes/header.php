<?php

//activate session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/me2u/includes/functions.php';

$pageTitle = $pageTitle ?? 'Me2U';      //page title will be me2u if one is not assigned to the page


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle) ?></title>
    <link rel="stylesheet" href="/me2u/assets/css/style.css">

</head>



<body>
    <header class="header-content">
        <nav class="nav">
            <a href="/me2u/pages/listings.php" class="nav-brand">Me2U</a>
            <ul class="nav-links">
                <li><a href="/me2u/pages/listings.php">Browse</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="/me2u/pages/orders.php">My Orders</a></li>
                    <?php if (isSeller()): ?>
                        <li><a href="/me2u/pages/add-product.php">Sell</a></li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="/me2u/admin/index.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="/me2u/pages/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/me2u/pages/login.php">Login</a></li>
                    <li><a href="/me2u/pages/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container">
</body>