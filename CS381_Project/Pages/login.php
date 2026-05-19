<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// if logged in go to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();//verify token

    $email = trim($_POST['email']); //trim and clean inputs
    $password = $_POST['password'];

    // Find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($password, $user['password'])) {

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] =$user['id'];
        $_SESSION['user_name']= $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];

        //Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();

    } else {
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - YIC Lost &amp; Found</title>
    <link rel="stylesheet" href="../Style/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-card">
        <div class="site-title">🔒 YIC Lost &amp; Found</div>
        <h2>Welcome back</h2>
        <p class="login-subtitle">Sign in to your YIC account</p>

        <div class="demo-accounts"> <!-- more later -->
            📌 Student: <strong>4311085@rcjy.edu.sa</strong> / <strong>password</strong><br>
            🔑 Admin: <strong>admin@rcjy.edu.sa</strong> / <strong>password</strong>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <p>⚠️ <?php echo e($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php csrfField(); ?> 
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo e($_POST['email'] ?? ''); ?>"
                       placeholder="id@rcjy.edu.sa" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-blue" style="width:100%">Sign In →</button>
        </form>

        <div class="switch-page">
            No account? <a href="register.php">Register here</a>
        </div>
        <div class="switch-page">
            <a href="index.php">← Back to Browse</a>
        </div>
    </div>
</div>

</body>
</html>