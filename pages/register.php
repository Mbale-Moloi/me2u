<?php
$pageTitle = 'Register';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$errors  = [];      //displays error messages to the user
$success = '';      //success message to show after successful registration


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //registration form feilds
    $name      = sanitize($_POST['name']      ?? '');
    $email     = sanitize($_POST['email']     ?? '');
    $password  = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';
    $phone     = sanitize($_POST['phone']     ?? '');
    $province  = intval($_POST['province']  ?? 0);
    $role      = sanitize($_POST['role']      ?? 'buyer');      //default to buyer if not set

    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['buyer', 'seller'])) {
        $errors[] = 'Please select a valid role.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = 'That email address is already registered.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO users 
                (name, email, password, phone, province_id, role)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssls',
            $name,
            $email,
            $hashed,
            $phone,
            $province,
            $role
        );

        if ($stmt->execute()) {
            $success = 'Account created successfully. You can now log in.';
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$provinces = $conn->query(
    "SELECT id, name FROM provinces ORDER BY name"
)->fetch_all(MYSQLI_ASSOC);
?>

<section class="form-section">
    <h1>Create an account</h1>

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

    <form action="register.php" method="POST" novalidate>

        <div class="form-group">
            <label for="name">Full name</label>
            <input type="text"
                id="name"
                name="name"
                required
                value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email"
                id="email"
                name="email"
                required
                value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password"
                id="password"
                name="password"
                required
                minlength="8">
        </div>

        <div class="form-group">
            <label for="confirm">Confirm password</label>
            <input type="password"
                id="confirm"
                name="confirm"
                required>
        </div>

        <div class="form-group">
            <label for="phone">Phone number</label>
            <input type="tel"
                id="phone"
                name="phone"
                pattern="[0-9]{10}"
                placeholder="0821234567"
                value="<?= isset($_POST['phone']) ? sanitize($_POST['phone']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="province">Province</label>
            <select id="province" name="province">
                <option value="">Select your province</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= (isset($_POST['province']) &&
                            $_POST['province'] == $p['id']) ? 'selected' : '' ?>>
                        <?= sanitize($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>I want to</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio"
                        name="role"
                        value="buyer"
                        <?= (!isset($_POST['role']) ||
                            $_POST['role'] === 'buyer') ? 'checked' : '' ?>>
                    Buy items
                </label>
                <label class="radio-label">
                    <input type="radio"
                        name="role"
                        value="seller"
                        <?= (isset($_POST['role']) &&
                            $_POST['role'] === 'seller') ? 'checked' : '' ?>>
                    Buy and sell items
                </label>
            </div>
        </div>

        <button type="submit" class="btn-primary">Create account</button>

        <p class="form-footer">
            Already have an account? <a href="login.php">Log in here</a>
        </p>

    </form>
</section>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>