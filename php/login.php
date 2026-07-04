<?php
declare(strict_types=1);
session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
require_once __DIR__ . '/../db_connect.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true); // Security fix!
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email']= $user['email'];
                $next = $_GET['next'] ?? 'dashboard.php';
                redirect($next);
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="fa-solid fa-droplet"></i></div>
        <h1>LifeFlow</h1>
        <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" data-dismiss="1"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success" data-dismiss="1"><i class="fa-solid fa-check-circle"></i> Registration successful! Please log in.</div>
    <?php endif; ?>

    <form method="POST" action="login.php<?= isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '' ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Email Address <span>*</span></label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Password <span>*</span></label>
            <div style="position:relative">
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required style="padding-right:3rem">
                <button type="button" class="password-toggle" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem">
            <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>
    </form>

    <div style="text-align:center;margin-top:1.5rem">
        <p style="color:#64748b;font-size:.93rem">
            Don't have an account? <a href="register.php" style="color:#b22234;font-weight:700">Register here</a>
        </p>
        <p style="margin-top:.5rem"><a href="index.php" style="color:#64748b;font-size:.88rem"><i class="fa-solid fa-arrow-left"></i> Back to Home</a></p>
    </div>

    <div style="margin-top:1.75rem;padding:1rem;background:#fff5f5;border-radius:16px;border:1px solid #fde8e8">
        <p style="font-size:.82rem;color:#64748b;font-weight:700;margin-bottom:.5rem;text-align:center">Demo Accounts</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;font-size:.8rem;color:#64748b">
            <div><strong style="color:#b22234">Admin:</strong><br>admin@lifeflow.com</div>
            <div><strong style="color:#b22234">Donor:</strong><br>donor@lifeflow.com</div>
            <div><strong style="color:#b22234">Patient:</strong><br>patient@lifeflow.com</div>
            <div><strong style="color:#b22234">Hospital:</strong><br>hospital@lifeflow.com</div>
        </div>
        <p style="text-align:center;font-size:.8rem;color:#94a3b8;margin-top:.5rem">Password for all: <strong>password</strong></p>
    </div>
</div>
<script src="../js/app.js"></script>
</body>
</html>
