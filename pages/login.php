<?php
// Set the page title for the browser tab
$pageTitle = 'Login';

// Load the header — starts session, connects database, loads functions, prints HTML top
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

// If the user is already logged in redirect them away from the login page
if (isLoggedIn()) {
    redirect('/pages/listings.php');
}

// Empty string to hold any error message
$error = '';

// Only run this if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // email is sanitized, password is not
    $email    = sanitize($_POST['email']    ?? '');
    $password =          $_POST['password'] ?? '';

    // Both fields must have a value
    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {

        // Look up the user by email
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role, is_verified 
             FROM users WHERE email = ?"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Verify the typed password against the stored hash
        if ($user && password_verify($password, $user['password'])) {

            // Password matched — regenerate session ID for security
            session_regenerate_id(true);

            // Store user details in the session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['name']     = $user['name'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['verified'] = $user['is_verified'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('/admin/index.php');
            } else {
                redirect('/pages/listings.php');
            }
        } else {
            // Same message for wrong email or wrong password
            // never tell the user which one was wrong
            $error = 'Incorrect email or password.';
        }
    }
} // end of POST block
?>

<section class="form-section">
    <h1>Log in</h1>

    <?php if ($error): ?>
        <!-- Show the error message if login failed -->
        <p class="alert alert-error"><?= $error ?></p>
    <?php endif; ?>

    <!-- action points back to this same file -->
    <form action="login.php" method="POST">

        <div class="form-group">
            <label for="email">Email address</label>
            <!-- value repopulates the email field so the user 
                 doesn't have to retype it after a failed login -->
            <input type="email"
                id="email"
                name="email"
                required
                value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <!-- passwords never repopulate — always cleared after a failed attempt -->
            <input type="password"
                id="password"
                name="password"
                required>
        </div>

        <button type="submit" class="btn-primary">Log in</button>

        <p class="form-footer">
            No account yet? <a href="register.php">Register here</a>
        </p>

    </form>
</section>

<!-- Load the footer — closes </main>, prints footer, loads JS, closes </body> and </html> -->
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>