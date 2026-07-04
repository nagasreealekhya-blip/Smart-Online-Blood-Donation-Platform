<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db_connect.php';

$loggedIn = isset($_SESSION['user_id']);

// Live stats
$totalDonors = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn();
$totalReqs   = (int)$pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status='fulfilled'")->fetchColumn();
$totalHosp   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='hospital'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About LifeFlow - Blood Donation Platform</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a class="logo" href="index.php"><i class="fa-solid fa-droplet"></i> LifeFlow</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php" class="active">About</a></li>
            <li><a href="view_requests.php">Requests</a></li>
            <li><a href="contact.php">Contact</a></li>
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
    <h1>About <span style="color:#ffd966">LifeFlow</span></h1>
    <p>India's most trusted smart blood donation platform — built on compassion, powered by technology.</p>
</div>

<div class="section">
<div class="container">

    <!-- Mission & Vision -->
    <div class="section-title">
        <h2>Our Mission & Vision</h2>
        <p>Every decision we make is guided by one goal: no one should die waiting for blood.</p>
    </div>
    <div class="mv-grid" style="margin-bottom:4rem">
        <div class="mv-card">
            <div class="mv-icon"><i class="fa-solid fa-bullseye"></i></div>
            <h3>Our Mission</h3>
            <p>To create a seamless, technology-driven bridge between blood donors and patients across India, ensuring that safe and compatible blood is available to everyone who needs it — anytime, anywhere.</p>
        </div>
        <div class="mv-card">
            <div class="mv-icon"><i class="fa-solid fa-eye"></i></div>
            <h3>Our Vision</h3>
            <p>A world where every patient in need of blood receives it within hours of the request. We envision a community where donating blood is as easy and rewarding as helping a neighbor in need.</p>
        </div>
        <div class="mv-card">
            <div class="mv-icon"><i class="fa-solid fa-heart"></i></div>
            <h3>Our Values</h3>
            <p>Compassion, transparency, and innovation drive everything we do. We believe in building trust through consistent action — connecting thousands of generous donors with patients every single day.</p>
        </div>
        <div class="mv-card">
            <div class="mv-icon"><i class="fa-solid fa-handshake"></i></div>
            <h3>Community First</h3>
            <p>LifeFlow is built by the community, for the community. Donors, hospitals, and patients work together on our platform to create a life-saving ecosystem that benefits all of India.</p>
        </div>
    </div>

    <!-- About Intro -->
    <div class="about-intro" style="margin-bottom:4rem">
        <div>
            <h2>The Platform That Connects Hearts</h2>
            <p>LifeFlow was born out of a simple but urgent need — thousands of patients across India struggle to find blood donors in time, while willing donors have no easy way to reach them.</p>
            <p>We built a platform that changes this. Real-time blood request posting, smart donor matching by blood group and location, hospital inventory management, and a reward system that motivates donors to keep giving.</p>
            <p>Today, LifeFlow serves donors, patients, hospitals, and blood banks across India with a seamless, mobile-ready experience.</p>
            <div class="about-stats">
                <div class="about-stat">
                    <div class="about-stat-num"><span class="stat-counter" data-target="<?= max($totalDonors, 10000) ?>" data-suffix="+">0</span></div>
                    <div class="about-stat-lbl">Registered Donors</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-num"><span class="stat-counter" data-target="<?= max($totalHosp, 500) ?>" data-suffix="+">0</span></div>
                    <div class="about-stat-lbl">Partner Hospitals</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-num"><span class="stat-counter" data-target="<?= max($totalReqs, 50000) ?>" data-suffix="+">0</span></div>
                    <div class="about-stat-lbl">Requests Fulfilled</div>
                </div>
            </div>
        </div>
        <div>
            <div style="background:linear-gradient(135deg,#b22234,#8b1a2a);border-radius:28px;padding:2.5rem;color:#fff;text-align:center">
                <i class="fa-solid fa-droplet" style="font-size:5rem;color:#ffd966;display:block;margin-bottom:1.5rem"></i>
                <h3 style="font-size:1.6rem;font-weight:900;margin-bottom:.75rem">1 Donation = 3 Lives</h3>
                <p style="opacity:.9;line-height:1.7">A single whole blood donation can be separated into red cells, platelets, and plasma — each saving a different patient. Your 30 minutes of time can save up to 3 lives.</p>
                <a href="register.php?role=donor" class="btn btn-accent" style="margin-top:1.5rem">Start Donating Today</a>
            </div>
        </div>
    </div>

    <!-- How Donation Works -->
    <div class="section-title">
        <h2>How Blood Donation Works</h2>
        <p>Safe, simple, and completely voluntary — here's what to expect when you donate.</p>
    </div>
    <div class="steps-grid" style="margin-bottom:4rem">
        <div class="step-card"><div class="step-number">1</div><h3>Register</h3><p>Create your free donor account with your blood group and availability status.</p></div>
        <div class="step-card"><div class="step-number">2</div><h3>Get Matched</h3><p>Our system alerts you when a patient near you needs your blood group urgently.</p></div>
        <div class="step-card"><div class="step-number">3</div><h3>Visit Hospital</h3><p>Schedule an appointment at a certified hospital. The whole process takes about 30-45 minutes.</p></div>
        <div class="step-card"><div class="step-number">4</div><h3>Earn Rewards</h3><p>Get reward points for every donation. Track your impact and celebrate your milestones.</p></div>
    </div>

    <!-- FAQ -->
    <div class="section-title">
        <h2>Frequently Asked Questions</h2>
        <p>Everything you need to know about blood donation and the LifeFlow platform.</p>
    </div>
    <div style="max-width:800px;margin:0 auto">
        <?php
        $faqs = [
            ['Who can donate blood?', 'Anyone aged 18–65, weighing at least 45 kg, and in good health can donate. Certain medical conditions and medications may affect eligibility. Our hospital partners conduct a brief health screening before each donation.'],
            ['How often can I donate?', 'You can donate whole blood every 56 days (8 weeks). Platelets can be donated every 7 days, up to 24 times per year. Plasma every 28 days.'],
            ['Is blood donation safe?', 'Absolutely. All equipment is sterile and used only once. You cannot get any infection from donating blood. The entire process is supervised by certified medical professionals.'],
            ['Does it hurt to donate blood?', 'You may feel a brief sting when the needle is inserted, but the actual donation is generally painless. Most donors feel fine immediately afterward.'],
            ['How do I register my hospital on LifeFlow?', 'Select "Hospital" as your role during registration. You\'ll need to provide your hospital name and valid license number. Our team verifies and approves hospital registrations within 24 hours.'],
            ['Can I track my donation history?', 'Yes! Your donor dashboard shows your complete donation history, reward points, next eligible date, and the impact of each donation.'],
        ];
        foreach($faqs as [$q,$a]): ?>
        <div class="faq-item">
            <div class="faq-question">
                <?= h($q) ?>
                <i class="fa-solid fa-chevron-down" style="color:#b22234;flex-shrink:0"></i>
            </div>
            <div class="faq-answer"><?= h($a) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

<!-- CTA Banner -->
<div style="background:linear-gradient(135deg,#b22234,#8b1a2a);padding:4rem 1.5rem;text-align:center;color:#fff">
    <h2 style="font-size:2.2rem;font-weight:900;margin-bottom:.75rem">Ready to Save Lives?</h2>
    <p style="font-size:1.1rem;opacity:.9;max-width:520px;margin:0 auto 2rem">Join thousands of donors across India who are making a difference every day with LifeFlow.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
        <a href="register.php?role=donor" class="btn btn-accent btn-lg"><i class="fa-solid fa-heart-pulse"></i> Become a Donor</a>
        <a href="contact.php" class="btn btn-white btn-lg"><i class="fa-solid fa-envelope"></i> Contact Us</a>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> LifeFlow. All rights reserved.</p>
        </div>
    </div>
</footer>
<script src="../js/app.js"></script>
</body>
</html>
