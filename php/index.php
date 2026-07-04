<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db_connect.php';

// Fetch latest blood requests
$requests = $pdo->query("
    SELECT br.*, u.full_name as requester_name
    FROM blood_requests br
    JOIN users u ON u.id = br.requested_by_user_id
    WHERE br.status = 'pending'
    ORDER BY FIELD(br.urgency_level,'critical','high','medium','low'), br.created_at DESC
    LIMIT 6
")->fetchAll();

// Newsletter subscribe
$nl_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    if (!verify_csrf_token()) {
        $nl_message = 'error';
    } else {
        $email = filter_var(trim($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
        if ($email) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
            $stmt->execute([$email]);
            $nl_message = 'success';
        } else {
            $nl_message = 'error';
        }
    }
}

$pageTitle = 'LifeFlow - Smart Blood Donation Platform';
$showNavUser = true;
require_once 'includes/header.php';
?>

<!-- Hero -->
<section class="hero">
    <div class="hero-content fade-in">
        <div class="hero-badge"><i class="fa-solid fa-droplet"></i> India's Smart Blood Donation Platform</div>
        <h1>Save Lives.<br><span>Donate Blood.</span></h1>
        <p>Connect donors with patients, hospitals, and blood banks in real-time. Your single donation can save up to 3 lives.</p>
        <div class="hero-btns">
            <a href="view_requests.php" class="btn btn-white btn-lg"><i class="fa-solid fa-magnifying-glass"></i> Find Requests</a>
            <a href="<?= isset($_SESSION['user_id']) ? 'blood_request.php' : 'register.php' ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-hand-holding-heart"></i> Request Blood</a>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><span class="stat-counter" data-target="10000" data-suffix="+">0</span></div>
            <div class="stat-label">Registered Donors</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><span class="stat-counter" data-target="500" data-suffix="+">0</span></div>
            <div class="stat-label">Partner Hospitals</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><span class="stat-counter" data-target="50000" data-suffix="+">0</span></div>
            <div class="stat-label">Lives Saved</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Emergency Support</div>
        </div>
    </div>
</div>

<!-- Emergency Requests -->
<?php if (!empty($requests)): ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>🆘 Active Blood Requests</h2>
            <p>These patients urgently need your help. Every minute counts.</p>
        </div>
        <div class="grid-auto">
            <?php foreach ($requests as $req): ?>
            <div class="request-card <?= $req['urgency_level'] === 'critical' ? 'urgent' : '' ?>">
                <div class="request-header">
                    <div>
                        <h3 style="font-weight:800;color:#1e293b;margin-bottom:.25rem"><?= h($req['patient_name']) ?></h3>
                        <span class="badge badge-<?= h($req['urgency_level']) ?>"><?= strtoupper(h($req['urgency_level'])) ?></span>
                    </div>
                    <div class="blood-group-badge"><?= h($req['blood_group_needed']) ?></div>
                </div>
                <div class="request-detail"><i class="fa-solid fa-hospital"></i><?= h($req['hospital_name']) ?></div>
                <div class="request-detail"><i class="fa-solid fa-location-dot"></i><?= h($req['location'] ?? 'N/A') ?></div>
                <div class="request-detail"><i class="fa-solid fa-droplet"></i><?= h($req['units_required']) ?> unit(s) needed</div>
                <?php if ($req['contact_number']): ?>
                <div class="request-detail"><i class="fa-solid fa-phone"></i><?= h($req['contact_number']) ?></div>
                <?php endif; ?>
                <div style="margin-top:1rem">
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'donor'): ?>
                        <a href="view_requests.php?id=<?= $req['id'] ?>" class="btn btn-primary btn-sm" style="width:100%">Respond to Request</a>
                    <?php else: ?>
                        <a href="login.php?next=view_requests.php" class="btn btn-outline btn-sm" style="width:100%">Login to Respond</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="view_requests.php" class="btn btn-primary">View All Requests <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- How It Works -->
<section class="section section-white">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Four simple steps to save a life through LifeFlow.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card"><div class="step-number">1</div><h3>Register</h3><p>Create your free account as a Donor, Patient, or Hospital in under 2 minutes.</p></div>
            <div class="step-card"><div class="step-number">2</div><h3>Find a Match</h3><p>Search donors by blood group and location, or post an urgent request.</p></div>
            <div class="step-card"><div class="step-number">3</div><h3>Schedule & Donate</h3><p>Book an appointment at a nearby partner hospital at your convenience.</p></div>
            <div class="step-card"><div class="step-number">4</div><h3>Save a Life</h3><p>Complete your donation and earn reward points. Track your impact over time.</p></div>
        </div>
    </div>
</section>

<!-- Why Donate -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Why Donate With Us?</h2>
            <p>LifeFlow makes blood donation safe, simple, and rewarding.</p>
        </div>
        <div class="grid-3">
            <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-bolt"></i></div><h3>Quick Process</h3><p>From registration to donation in under 30 minutes. Our streamlined process respects your time.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div><h3>Safe & Secure</h3><p>All donations are medically supervised at certified hospitals with full screening protocols.</p></div>
            <div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div><h3>Track Your Impact</h3><p>See how many lives you've saved, earn reward points, and celebrate your milestones.</p></div>
        </div>
    </div>
</section>

<!-- Emergency CTA -->
<section class="section section-white">
    <div class="container">
        <div class="emergency-cta">
            <h2>🩸 Urgent: Blood Needed Now!</h2>
            <p>Multiple patients are waiting for blood donations right now. Register today and make an immediate difference.</p>
            <div class="hero-btns">
                <a href="register.php" class="btn btn-white btn-lg"><i class="fa-solid fa-user-plus"></i> Become a Donor</a>
                <a href="blood_request.php" class="btn" style="background:rgba(255,255,255,.2);color:#fff;border:2px solid rgba(255,255,255,.7)" class="btn-lg"><i class="fa-solid fa-syringe"></i> Request Blood</a>
            </div>
        </div>
    </div>
</section>

<?php 
$showFullFooter = true;
require_once 'includes/footer.php'; 
?>


