<?php
// php/includes/header.php
$loggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

// Determine current page for active nav link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'LifeFlow - Smart Blood Donation Platform' ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">

<?php if (!isset($hideNav) || !$hideNav): ?>
<nav class="navbar">
    <div class="nav-inner">
        <a class="logo" href="index.php"><i class="fa-solid fa-droplet"></i> LifeFlow</a>
        <ul class="nav-links" id="navLinks">
            <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a></li>
            <li><a href="about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">About</a></li>
            <li><a href="view_requests.php" class="<?= $current_page === 'view_requests.php' ? 'active' : '' ?>">Requests</a></li>
            <li><a href="contact.php" class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <?php if ($loggedIn): ?>
                <?php if (isset($showNavUser) && $showNavUser): ?>
                    <span class="nav-user">👋 <?= h($userName) ?></span>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-white btn-sm">Dashboard</a>
                <a href="logout.php" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,.6)">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-white btn-sm">Login</a>
                <a href="register.php" class="btn btn-accent btn-sm">Register</a>
            <?php endif; ?>
            <button class="mobile-btn" id="mobileMenuBtn" aria-label="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
    <div id="mobileMenu" style="display:none;padding:1rem 1.5rem;background:rgba(0,0,0,.1)">
        <ul style="list-style:none;display:flex;flex-direction:column;gap:.5rem">
            <li><a href="index.php" style="color:#fff;font-weight:600">Home</a></li>
            <li><a href="about.php" style="color:#fff;font-weight:600">About</a></li>
            <li><a href="view_requests.php" style="color:#fff;font-weight:600">Requests</a></li>
            <li><a href="contact.php" style="color:#fff;font-weight:600">Contact</a></li>
            <?php if (!$loggedIn): ?>
            <li><a href="login.php" style="color:#ffd966;font-weight:700">Login</a></li>
            <li><a href="register.php" style="color:#ffd966;font-weight:700">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<?php endif; ?>

