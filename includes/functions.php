<?php

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));     // Remove whitespace, backslashes, and html tags from the input data
}

function redirect($url)
{
    header("Location: $url");      //redirects to target URL
    exit();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);     // Check if the user is logged in by checking ID's session
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';     // Check if the user is an admin
}

function isSeller()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'seller' || $_SESSION['role'] === 'admin';     // Check if the user is a seller (admins can also access seller functionalities)
}

//Get logged in user data
function getCurrentUser($conn)
{
    if (!isLoggedIn())
        return null;        // Return null if the user is not logged in
    $id = $_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT id, name, email, role, is_verified FROM users WHERE id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Ensure the user is logged in before accessing certain pages
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('/etrade_sa/me2u/me2u/me2u/pages/login.php');
    }
}

//Ensure user has admin prvileges before accessing certain pages
function requireAdmin()
{
    if (!isAdmin()) {
        redirect('/etrade_sa/me2u/me2u/me2u/pages/login.php');
    }
}
