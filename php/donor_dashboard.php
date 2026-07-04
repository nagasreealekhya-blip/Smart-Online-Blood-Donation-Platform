<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../db_connect.php';

$userId = (int)$_SESSION['user_id'];

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    $stmt = $pdo->prepare("SELECT availability_status FROM donor_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $current = $stmt->fetchColumn();
    $next = $current === 'available' ? 'unavailable' : 'available';
    $pdo->prepare("UPDATE donor_profiles SET availability_status = ? WHERE user_id = ?")
        ->execute([$next, $userId]);
    header('Location: donor_dashboard.php?msg=availability');
    exit;
}

// Fetch donor profile
$donor = $pdo->prepare("
    SELECT u.*, dp.blood_group, dp.availability_status, dp.total_donations, dp.reward_points, dp.last_donation_date
    FROM users u LEFT JOIN donor_profiles dp ON dp.user_id = u.id
    WHERE u.id = ?
");
$donor->execute([$userId]);
$donor = $donor->fetch();

// Fetch appointments
$appointments = $pdo->prepare("
    SELECT a.*, hp.hospital_name
    FROM appointments a
    LEFT JOIN hospital_profiles hp ON hp.user_id = a.hospital_user_id
    WHERE a.donor_user_id = ?
    ORDER BY a.appointment_datetime DESC LIMIT 5
");
$appointments->execute([$userId]);
$appointments = $appointments->fetchAll();

// Fetch notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$notifStmt->execute([$userId]);
$notifications = $notifStmt->fetchAll();
$unread = array_filter($notifications, fn($n) => !$n['is_read']);

// Mark notification read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $nid = (int)$_POST['mark_read'];
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$nid, $userId]);
    header('Location: donor_dashboard.php'); exit;
}

// Nearby requests matching blood group
$matchingRequests = [];
if (!empty($donor['blood_group'])) {
    $stmt = $pdo->prepare("SELECT * FROM blood_requests WHERE blood_group_needed = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$donor['blood_group']]);
    $matchingRequests = $stmt->fetchAll();
}

// Calculate next eligible date (56 days after last donation)
$nextEligible = null;
if (!empty($donor['last_donation_date'])) {
    $d = new DateTime($donor['last_donation_date']);
    $d->modify('+56 days');
    $nextEligible = $d->format('d M Y');
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:998" class=""></div>
<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-droplet"></i> LifeFlow</div>
        <ul class="sidebar-nav">
            <li><a href="donor_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="donor_dashboard.php#appointments"><i class="fa-solid fa-calendar-days"></i> Appointments</a></li>
            <li><a href="view_requests.php"><i class="fa-solid fa-droplet"></i> Blood Requests</a></li>
            <li><a href="donor_dashboard.php#notifications"><i class="fa-solid fa-bell"></i> Notifications <?php if (count($unread) > 0): ?><span style="background:#ffd966;color:#8b1a2a;border-radius:999px;padding:1px 7px;font-size:.78rem;margin-left:.25rem"><?= count($unread) ?></span><?php endif; ?></a></li>
            <li><a href="edit_profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= h($donor['full_name'] ?? '') ?></div>
            <div class="sidebar-user-role">Donor · <?= h($donor['blood_group'] ?? 'N/A') ?></div>
            <a href="logout.php" style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.75);font-size:.85rem;margin-top:.5rem">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="dashboard-main">
        <button id="sidebarToggle" style="display:none;margin-bottom:1rem;background:none;border:none;font-size:1.5rem;color:#b22234;cursor:pointer"><i class="fa-solid fa-bars"></i></button>

        <div class="dashboard-header">
            <div>
                <h1>🩸 Donor Dashboard</h1>
                <p>Welcome back, <?= h(explode(' ', $donor['full_name'] ?? 'Donor')[0]) ?>! Every drop you give is a life you save.</p>
            </div>
            <form method="POST">
                <button type="submit" name="toggle_availability" class="btn <?= $donor['availability_status'] === 'available' ? 'btn-danger' : 'btn-primary' ?>">
                    <i class="fa-solid fa-<?= $donor['availability_status'] === 'available' ? 'pause' : 'play' ?>"></i>
                    <?= $donor['availability_status'] === 'available' ? 'Mark Unavailable' : 'Mark Available' ?>
                </button>
            </form>
        </div>

        <?php if ($msg === 'availability'): ?>
        <div class="alert alert-success" data-dismiss="1"><i class="fa-solid fa-check-circle"></i> Availability status updated!</div>
        <?php endif; ?>
        <?php if (isset($_GET['booked'])): ?>
        <div class="alert alert-success" data-dismiss="1">
            <i class="fa-solid fa-calendar-check"></i>
            <strong>Appointment booked!</strong> Your donation appointment has been confirmed. The patient has been notified. Check below for details.
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                <div class="stat-card-value"><?= h($donor['total_donations'] ?? 0) ?></div>
                <div class="stat-card-label">Total Donations</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-star"></i></div>
                <div class="stat-card-value"><?= h($donor['reward_points'] ?? 0) ?></div>
                <div class="stat-card-label">Reward Points</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="stat-card-value" style="font-size:1.1rem"><?= $nextEligible ?? 'Now!' ?></div>
                <div class="stat-card-label">Next Eligible Date</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-card-value" style="font-size:1.2rem;color:<?= $donor['availability_status'] === 'available' ? '#15803d' : '#b22234' ?>">
                    <?= ucfirst(str_replace('_', ' ', $donor['availability_status'] ?? 'N/A')) ?>
                </div>
                <div class="stat-card-label">Availability Status</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:1.5rem">

        <!-- Appointments -->
        <div class="table-wrap" id="appointments">
            <div class="table-head">
                <h3><i class="fa-solid fa-calendar-days" style="color:#b22234;margin-right:.4rem"></i> My Appointments</h3>
                <a href="view_requests.php" class="btn btn-primary btn-sm">Book New</a>
            </div>
            <?php if (empty($appointments)): ?>
            <div style="padding:2rem;text-align:center;color:#64748b">
                <i class="fa-solid fa-calendar-xmark" style="font-size:2rem;color:#fcd5d5;margin-bottom:.5rem;display:block"></i>
                No appointments yet. <a href="view_requests.php" style="color:#b22234;font-weight:600">Respond to a request</a> to book one.
            </div>
            <?php else: ?>
            <table>
                <thead><tr><th>Hospital</th><th>Date & Time</th><th>Type</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?= h($a['hospital_name'] ?? 'N/A') ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($a['appointment_datetime'])) ?></td>
                    <td><?= h($a['appointment_type']) ?></td>
                    <td><span class="badge badge-<?= $a['status'] === 'scheduled' ? 'accepted' : ($a['status'] === 'completed' ? 'fulfilled' : 'cancelled') ?>"><?= ucfirst(h($a['status'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Notifications -->
        <div class="table-wrap" id="notifications">
            <div class="table-head">
                <h3><i class="fa-solid fa-bell" style="color:#b22234;margin-right:.4rem"></i> Notifications</h3>
            </div>
            <?php if (empty($notifications)): ?>
            <div style="padding:2rem;text-align:center;color:#64748b">
                <i class="fa-solid fa-bell-slash" style="font-size:2rem;color:#fcd5d5;display:block;margin-bottom:.5rem"></i>
                No notifications yet.
            </div>
            <?php else: ?>
            <div style="max-height:320px;overflow-y:auto">
            <?php foreach ($notifications as $n): ?>
            <div style="padding:.85rem 1.2rem;border-bottom:1px solid #fde8e8;display:flex;gap:.75rem;align-items:flex-start;background:<?= !$n['is_read'] ? '#fff9f9' : '#fff' ?>">
                <div style="width:32px;height:32px;border-radius:50%;background:<?= $n['type']==='success'?'#dcfce7':($n['type']==='error'?'#fee2e2':'#dbeafe') ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;color:<?= $n['type']==='success'?'#15803d':($n['type']==='error'?'#b22234':'#1d4ed8') ?>">
                    <i class="fa-solid fa-<?= $n['type']==='success'?'check':($n['type']==='error'?'xmark':'info') ?>"></i>
                </div>
                <div style="flex:1">
                    <div style="font-weight:700;font-size:.88rem;color:#1e293b"><?= h($n['title']) ?></div>
                    <div style="font-size:.82rem;color:#64748b"><?= h($n['message']) ?></div>
                    <div style="font-size:.75rem;color:#94a3b8;margin-top:.25rem"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
                </div>
                <?php if (!$n['is_read']): ?>
                <form method="POST"><button type="submit" name="mark_read" value="<?= $n['id'] ?>" style="background:none;border:none;cursor:pointer;color:#b22234;font-size:.75rem;font-weight:600">Mark read</button></form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        </div>

        <!-- Matching Blood Requests -->
        <?php if (!empty($matchingRequests)): ?>
        <div style="margin-top:1.5rem">
            <h2 style="font-size:1.2rem;font-weight:800;color:#8b1a2a;margin-bottom:1rem">
                <i class="fa-solid fa-droplet"></i> Requests Matching Your Blood Group (<?= h($donor['blood_group'] ?? '') ?>)
            </h2>
            <div class="grid-auto">
                <?php foreach ($matchingRequests as $req): ?>
                <div class="request-card <?= $req['urgency_level'] === 'critical' ? 'urgent' : '' ?>">
                    <div class="request-header">
                        <div>
                            <strong><?= h($req['patient_name']) ?></strong><br>
                            <span class="badge badge-<?= h($req['urgency_level']) ?>"><?= strtoupper(h($req['urgency_level'])) ?></span>
                        </div>
                        <div class="blood-group-badge"><?= h($req['blood_group_needed']) ?></div>
                    </div>
                    <div class="request-detail"><i class="fa-solid fa-hospital"></i><?= h($req['hospital_name']) ?></div>
                    <div class="request-detail"><i class="fa-solid fa-droplet"></i><?= h($req['units_required']) ?> unit(s)</div>
                    <?php if ($req['contact_number']): ?>
                    <div class="request-detail"><i class="fa-solid fa-phone"></i><a href="tel:<?= h($req['contact_number']) ?>" style="color:#b22234"><?= h($req['contact_number']) ?></a></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="../js/app.js"></script>
</body>
</html>
