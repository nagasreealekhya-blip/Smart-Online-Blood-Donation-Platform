<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db_connect.php';

$loggedIn = isset($_SESSION['user_id']);
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$subject || !$message) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)")
            ->execute([$name, $email, $subject, $message]);
        $success = "Thank you {$name}! We've received your message and will get back to you within 24 hours.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a class="logo" href="index.php"><i class="fa-solid fa-droplet"></i> LifeFlow</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="view_requests.php">Requests</a></li>
            <li><a href="contact.php" class="active">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <?php if ($loggedIn): ?>
            <a href="dashboard.php" class="btn btn-white btn-sm">Dashboard</a>
            <?php else: ?>
            <a href="login.php" class="btn btn-white btn-sm">Login</a>
            <a href="register.php" class="btn btn-accent btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="page-header">
    <h1><i class="fa-solid fa-envelope"></i> Contact Us</h1>
    <p>We're here to help. Reach out for emergencies, partnerships, or general inquiries.</p>
</div>

<div class="section">
<div class="container">

    <!-- Info Cards -->
    <div class="info-grid">
        <div class="info-card">
            <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
            <h3>Emergency Hotline</h3>
            <p>📞 1800-BLOOD (25663)<br>Available 24/7 for urgent needs</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
            <h3>Email Us</h3>
            <p>support@lifeflow.in<br>Respond within 24 hours</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
            <h3>Headquarters</h3>
            <p>LifeFlow India Pvt. Ltd.<br>Bandra West, Mumbai 400050</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fa-solid fa-clock"></i></div>
            <h3>Support Hours</h3>
            <p>Monday – Saturday<br>9:00 AM – 9:00 PM IST</p>
        </div>
    </div>

    <!-- Contact Form -->
    <div class="contact-grid">
        <div class="contact-form-card">
            <h2><i class="fa-solid fa-paper-plane" style="color:#b22234;margin-right:.5rem"></i> Send Us a Message</h2>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= h($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error" data-dismiss="1"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="contact.php">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name <span>*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Full name" value="<?= h($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span>*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject <span>*</span></label>
                    <select name="subject" class="form-control form-select" required>
                        <option value="">Select a subject</option>
                        <option <?= ($_POST['subject'] ?? '') === 'Emergency Blood Requirement' ? 'selected' : '' ?>>Emergency Blood Requirement</option>
                        <option <?= ($_POST['subject'] ?? '') === 'Hospital Partnership' ? 'selected' : '' ?>>Hospital Partnership</option>
                        <option <?= ($_POST['subject'] ?? '') === 'Technical Support' ? 'selected' : '' ?>>Technical Support</option>
                        <option <?= ($_POST['subject'] ?? '') === 'Donation Query' ? 'selected' : '' ?>>Donation Query</option>
                        <option <?= ($_POST['subject'] ?? '') === 'Feedback' ? 'selected' : '' ?>>Feedback</option>
                        <option <?= ($_POST['subject'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Message <span>*</span></label>
                    <textarea name="message" class="form-control textarea" rows="6" placeholder="Describe your query in detail..." required><?= h($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Emergency Panel -->
        <div>
            <div style="background:linear-gradient(135deg,#b22234,#8b1a2a);border-radius:28px;padding:2rem;color:#fff;margin-bottom:1.5rem">
                <h3 style="font-size:1.4rem;font-weight:900;margin-bottom:.75rem"><i class="fa-solid fa-triangle-exclamation" style="color:#ffd966"></i> Medical Emergency?</h3>
                <p style="opacity:.9;line-height:1.7;margin-bottom:1.25rem">If you need blood urgently, don't wait! Post a request right now and get matched with available donors in minutes.</p>
                <a href="<?= $loggedIn ? 'blood_request.php' : 'register.php' ?>" class="btn btn-accent"><i class="fa-solid fa-syringe"></i> Post Emergency Request</a>
                <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.2)">
                    <div style="font-size:.9rem;opacity:.85;margin-bottom:.5rem"><i class="fa-solid fa-phone" style="color:#ffd966"></i> Call Emergency: <strong>1800-BLOOD</strong></div>
                    <div style="font-size:.9rem;opacity:.85"><i class="fa-solid fa-ambulance" style="color:#ffd966"></i> National Emergency: <strong>Dial 112</strong></div>
                </div>
            </div>

            <div style="background:#fff;border:1px solid #fde8e8;border-radius:28px;padding:2rem;box-shadow:0 10px 25px -5px rgba(0,0,0,.1)">
                <h3 style="font-weight:800;color:#8b1a2a;margin-bottom:1rem">Follow Us</h3>
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    <?php foreach([['fa-twitter','Twitter','@LifeFlowIndia'],['fa-instagram','Instagram','@lifeflow.india'],['fa-facebook','Facebook','LifeFlow India'],['fa-linkedin','LinkedIn','LifeFlow India Pvt. Ltd.']] as [$icon,$name,$handle]): ?>
                    <div style="display:flex;align-items:center;gap:.75rem">
                        <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;color:#b22234">
                            <i class="fa-brands <?= $icon ?>"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:.9rem"><?= $name ?></div>
                            <div style="font-size:.82rem;color:#64748b"><?= $handle ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<footer class="footer" style="margin-top:3rem">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> LifeFlow. All rights reserved.</p>
        </div>
    </div>
</footer>
<script src="../js/app.js"></script>
</body>
</html>
