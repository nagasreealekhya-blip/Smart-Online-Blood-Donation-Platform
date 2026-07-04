<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../db_connect.php';

$userId = (int)$_SESSION['user_id'];

// Stats
$totalDonors    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn();
$totalPatients  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn();
$totalHospitals = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='hospital'")->fetchColumn();
$totalRequests  = (int)$pdo->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
$pendingReqs    = (int)$pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status='pending'")->fetchColumn();
$criticalReqs   = (int)$pdo->query("SELECT COUNT(*) FROM blood_requests WHERE urgency_level='critical'")->fetchColumn();
$totalDonations = (int)$pdo->query("SELECT COUNT(*) FROM donation_history")->fetchColumn();

// Recent blood requests
$recentReqs = $pdo->query("
    SELECT br.*, u.full_name AS requester_name
    FROM blood_requests br
    JOIN users u ON u.id = br.requested_by_user_id
    ORDER BY br.created_at DESC LIMIT 10
")->fetchAll();

// Recent users
$recentUsers = $pdo->query("
    SELECT id, full_name, email, role, city, status, created_at
    FROM users ORDER BY created_at DESC LIMIT 8
")->fetchAll();

// Admin logs
$logs = $pdo->query("SELECT al.*, u.full_name AS admin_name FROM admin_logs al JOIN users u ON u.id = al.admin_id ORDER BY al.created_at DESC LIMIT 10")->fetchAll();

// Contact messages
$contactMsgs = $pdo->query("SELECT * FROM contact_messages WHERE is_resolved = 0 ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Resolve contact
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_contact'])) {
    if (verify_csrf_token()) {
        $pdo->prepare("UPDATE contact_messages SET is_resolved=1 WHERE id=?")->execute([(int)$_POST['contact_id']]);
        header('Location: admin_dashboard.php'); exit;
    }
}

// Suspend/activate user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user'])) {
    if (verify_csrf_token()) {
        $uid = (int)$_POST['user_id'];
        $cur = $pdo->prepare("SELECT status FROM users WHERE id=?");
        $cur->execute([$uid]);
        $curStatus = $cur->fetchColumn();
        $newStatus = $curStatus === 'active' ? 'suspended' : 'active';
        $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$newStatus, $uid]);
        header('Location: admin_dashboard.php'); exit;
    }
}

$pageTitle = 'Admin Dashboard - LifeFlow';
$hideNav = true;
require_once 'includes/header.php';
?>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:998"></div>
<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-droplet"></i> LifeFlow</div>
        <ul class="sidebar-nav">
            <li><a href="admin_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="#users"><i class="fa-solid fa-users"></i> Users</a></li>
            <li><a href="#requests"><i class="fa-solid fa-droplet"></i> Blood Requests</a></li>
            <li><a href="#contacts"><i class="fa-solid fa-envelope"></i> Messages <?php if(count($contactMsgs)>0): ?><span style="background:#ffd966;color:#8b1a2a;border-radius:999px;padding:1px 7px;font-size:.78rem;margin-left:.25rem"><?= count($contactMsgs) ?></span><?php endif; ?></a></li>
            <li><a href="view_requests.php"><i class="fa-solid fa-globe"></i> Public View</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= h($_SESSION['user_name'] ?? '') ?></div>
            <div class="sidebar-user-role">Administrator</div>
            <a href="logout.php" style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.75);font-size:.85rem;margin-top:.5rem"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="dashboard-main">
        <button id="sidebarToggle" style="display:none;margin-bottom:1rem;background:none;border:none;font-size:1.5rem;color:#b22234;cursor:pointer"><i class="fa-solid fa-bars"></i></button>

        <div class="dashboard-header">
            <div>
                <h1>⚙️ Admin Dashboard</h1>
                <p>Full platform overview and management controls.</p>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;margin-bottom:2rem">
            <?php
            $statCards = [
                ['fa-heart-pulse', $totalDonors,    'Total Donors',    '#fee2e2'],
                ['fa-bed',         $totalPatients,  'Total Patients',  '#fef9c3'],
                ['fa-hospital',    $totalHospitals, 'Hospitals',       '#dbeafe'],
                ['fa-droplet',     $totalRequests,  'Blood Requests',  '#dcfce7'],
                ['fa-clock',       $pendingReqs,    'Pending',         '#fef9c3'],
                ['fa-triangle-exclamation', $criticalReqs, 'Critical', '#fee2e2'],
                ['fa-syringe',     $totalDonations, 'Donations Made',  '#dcfce7'],
                ['fa-envelope',    count($contactMsgs), 'Unread Msgs', '#ede9fe'],
            ];
            foreach($statCards as [$icon, $val, $lbl, $bg]): ?>
            <div class="stat-card">
                <div class="stat-card-icon" style="background:<?= $bg ?>"><i class="fa-solid <?= $icon ?>"></i></div>
                <div class="stat-card-value"><?= $val ?></div>
                <div class="stat-card-label"><?= $lbl ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

        <!-- Recent Blood Requests -->
        <div class="table-wrap" id="requests">
            <div class="table-head">
                <h3><i class="fa-solid fa-droplet" style="color:#b22234;margin-right:.4rem"></i> Recent Blood Requests</h3>
                <a href="view_requests.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <?php if (empty($recentReqs)): ?>
            <div style="padding:2rem;text-align:center;color:#64748b">No requests yet.</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Patient</th><th>Blood</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($recentReqs as $r): ?>
                <tr>
                    <td><strong><?= h($r['patient_name']) ?></strong><br><small style="color:#94a3b8"><?= h($r['requester_name']) ?></small></td>
                    <td><span class="badge badge-red"><?= h($r['blood_group_needed']) ?></span></td>
                    <td><span class="badge badge-<?= h($r['urgency_level']) ?>"><?= ucfirst(h($r['urgency_level'])) ?></span></td>
                    <td><span class="badge badge-<?= h($r['status']) ?>"><?= ucfirst(h($r['status'])) ?></span></td>
                    <td style="font-size:.82rem"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Unread Contact Messages -->
        <div class="table-wrap" id="contacts">
            <div class="table-head">
                <h3><i class="fa-solid fa-envelope" style="color:#b22234;margin-right:.4rem"></i> Contact Messages</h3>
            </div>
            <?php if (empty($contactMsgs)): ?>
            <div style="padding:2rem;text-align:center;color:#64748b">No unread messages.</div>
            <?php else: ?>
            <div>
            <?php foreach($contactMsgs as $msg): ?>
            <div style="padding:1rem 1.25rem;border-bottom:1px solid #fde8e8">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <strong style="color:#1e293b"><?= h($msg['name']) ?></strong>
                        <small style="color:#94a3b8;margin-left:.5rem"><?= h($msg['email']) ?></small><br>
                        <span style="font-weight:700;font-size:.88rem;color:#b22234"><?= h($msg['subject']) ?></span><br>
                        <p style="color:#64748b;font-size:.85rem;margin-top:.25rem"><?= h(substr($msg['message'], 0, 120)) ?>...</p>
                    </div>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="contact_id" value="<?= $msg['id'] ?>">
                        <button type="submit" name="resolve_contact" class="btn btn-sm" style="background:#dcfce7;color:#15803d;border:none;border-radius:10px;white-space:nowrap">Resolve</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        </div>

        <!-- Users Table -->
        <div class="table-wrap" id="users">
            <div class="table-head">
                <h3><i class="fa-solid fa-users" style="color:#b22234;margin-right:.4rem"></i> Recent Users</h3>
            </div>
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>City</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($recentUsers as $u): ?>
                <tr>
                    <td style="color:#94a3b8;font-size:.82rem"><?= $u['id'] ?></td>
                    <td style="font-weight:600"><?= h($u['full_name']) ?></td>
                    <td style="font-size:.85rem;color:#64748b"><?= h($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role']==='donor'?'normal':($u['role']==='patient'?'medium':($u['role']==='hospital'?'accepted':'crit-inv')) ?>"><?= ucfirst(h($u['role'])) ?></span></td>
                    <td><?= h($u['city'] ?? 'N/A') ?></td>
                    <td><span class="badge badge-<?= $u['status']==='active'?'available':'cancelled' ?>"><?= ucfirst(h($u['status'])) ?></span></td>
                    <td style="font-size:.82rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['role'] !== 'admin'): ?>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="toggle_user"
                                class="btn btn-sm"
                                style="background:<?= $u['status']==='active'?'#fee2e2':'#dcfce7' ?>;color:<?= $u['status']==='active'?'#b22234':'#15803d' ?>;border:none;border-radius:10px"
                                data-confirm="<?= $u['status']==='active'?'Suspend this user?':'Reactivate this user?' ?>">
                                <?= $u['status']==='active'?'Suspend':'Activate' ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:.82rem">Admin</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($logs)): ?>
        <!-- Admin Logs -->
        <div class="table-wrap" style="margin-top:1.5rem">
            <div class="table-head"><h3><i class="fa-solid fa-scroll" style="color:#b22234;margin-right:.4rem"></i> Admin Activity Logs</h3></div>
            <table>
                <thead><tr><th>Admin</th><th>Action</th><th>Entity</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td><?= h($log['admin_name'] ?? 'N/A') ?></td>
                    <td><?= h($log['action']) ?></td>
                    <td><?= h($log['entity_type'] ?? '-') ?> <?= $log['entity_id'] ? '#'.$log['entity_id'] : '' ?></td>
                    <td style="font-size:.82rem"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php 
$hideFooter = true;
require_once 'includes/footer.php'; 
?>
