<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

//logged in= dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    //Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($password !== $confirm) $errors[] = 'Passwords doesnt match';

    //Check email not used 
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email exist, please use another';
        }
    }

    //Insert user in db
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([$name, $email, $hash]);

        $newId = $pdo->lastInsertId();

        // Auto-login 
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $newId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'student';

        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - YIC Lost &amp; Found</title>
    <link rel="stylesheet" href="../Style/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-card">
        <div class="site-title">✨ YIC Lost &amp; Found</div>
        <h2>Create Account</h2>
        <p class="login-subtitle">Register with your YIC college email</p>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $err): ?>
                    <p>⚠️ <?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="input-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name"
                       value="<?php echo e($_POST['name'] ?? ''); ?>"
                       placeholder="Norah AlHosain" required>
            </div>
            <div class="input-group">
                <label for="email">College Email *</label>
                <input type="email" id="email" name="email"
                       value="<?php echo e($_POST['email'] ?? ''); ?>"
                       placeholder="id@rcjy.edu.sa" required>
            </div>
            <div class="input-group">
                <label for="password">Password * (min. 8 characters)</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter password" required minlength="8">
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn btn-blue" style="width:100%">Create Account →</button>
        </form>

        <div class="switch-page">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
        <div class="switch-page">
            <a href="index.php">← Back to Browse</a>
        </div>
    </div>
</div>

</body>
</html>
