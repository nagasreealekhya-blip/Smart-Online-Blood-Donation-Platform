<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../db_connect.php';

$userId = (int)$_SESSION['user_id'];

// Fetch patient profile
$stmt = $pdo->prepare("SELECT u.*, pp.blood_group_needed, pp.hospital_name AS my_hospital FROM users u LEFT JOIN patient_profiles pp ON pp.user_id = u.id WHERE u.id = ?");
$stmt->execute([$userId]);
$patient = $stmt->fetch();

// My blood requests
$myRequests = $pdo->prepare("SELECT * FROM blood_requests WHERE requested_by_user_id = ? ORDER BY created_at DESC");
$myRequests->execute([$userId]);
$myRequests = $myRequests->fetchAll();

// Stats
$active    = count(array_filter($myRequests, fn($r) => in_array($r['status'], ['pending','accepted','approved'])));
$fulfilled = count(array_filter($myRequests, fn($r) => $r['status'] === 'fulfilled'));
$critical  = count(array_filter($myRequests, fn($r) => $r['urgency_level'] === 'critical'));

// Donor search
$donors = [];
$searchBg   = $_GET['blood_group'] ?? '';
$searchCity = $_GET['city'] ?? '';
if ($searchBg || $searchCity) {
    $sql = "SELECT u.full_name, u.city, u.state, dp.blood_group, dp.availability_status, dp.total_donations
            FROM users u INNER JOIN donor_profiles dp ON dp.user_id = u.id
            WHERE u.role = 'donor' AND u.status = 'active' AND dp.availability_status = 'available'";
    $params = [];
    if ($searchBg)   { $sql .= " AND dp.blood_group = ?"; $params[] = $searchBg; }
    if ($searchCity) { $sql .= " AND u.city LIKE ?"; $params[] = "%{$searchCity}%"; }
    $sql .= " LIMIT 20";
    $s = $pdo->prepare($sql);
    $s->execute($params);
    $donors = $s->fetchAll();
}

$bgs = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:998"></div>
<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-droplet"></i> LifeFlow</div>
        <ul class="sidebar-nav">
            <li><a href="patient_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="blood_request.php"><i class="fa-solid fa-plus-circle"></i> New Request</a></li>
            <li><a href="patient_dashboard.php#find-donors"><i class="fa-solid fa-magnifying-glass"></i> Find Donors</a></li>
            <li><a href="view_requests.php"><i class="fa-solid fa-list"></i> All Requests</a></li>
            <li><a href="edit_profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= h($patient['full_name'] ?? '') ?></div>
            <div class="sidebar-user-role">Patient · Needs <?= h($patient['blood_group_needed'] ?? 'N/A') ?></div>
            <a href="logout.php" style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.75);font-size:.85rem;margin-top:.5rem"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="dashboard-main">
        <button id="sidebarToggle" style="display:none;margin-bottom:1rem;background:none;border:none;font-size:1.5rem;color:#b22234;cursor:pointer"><i class="fa-solid fa-bars"></i></button>

        <div class="dashboard-header">
            <div>
                <h1>🏥 Patient Dashboard</h1>
                <p>Welcome, <?= h(explode(' ', $patient['full_name'] ?? 'Patient')[0]) ?>. Manage your blood requests here.</p>
            </div>
            <a href="blood_request.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Request</a>
        </div>

        <!-- Stats -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-card-value"><?= $active ?></div>
                <div class="stat-card-label">Active Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-card-value"><?= $fulfilled ?></div>
                <div class="stat-card-label">Fulfilled Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-card-value" style="color:<?= $critical > 0 ? '#dc2626' : 'inherit' ?>"><?= $critical ?></div>
                <div class="stat-card-label">Critical Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="fa-solid fa-droplet"></i></div>
                <div class="stat-card-value"><?= h($patient['blood_group_needed'] ?? 'N/A') ?></div>
                <div class="stat-card-label">Blood Group Needed</div>
            </div>
        </div>

        <!-- My Requests Table -->
        <div class="table-wrap" style="margin-bottom:1.5rem">
            <div class="table-head">
                <h3><i class="fa-solid fa-list" style="color:#b22234;margin-right:.4rem"></i> My Blood Requests</h3>
                <a href="blood_request.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New</a>
            </div>
            <?php if (empty($myRequests)): ?>
            <div style="padding:3rem;text-align:center;color:#64748b">
                <i class="fa-solid fa-droplet-slash" style="font-size:3rem;color:#fcd5d5;display:block;margin-bottom:1rem"></i>
                You have no blood requests yet. <a href="blood_request.php" style="color:#b22234;font-weight:600">Post your first request</a>.
            </div>
            <?php else: ?>
            <table>
                <thead><tr><th>Code</th><th>Patient</th><th>Blood Group</th><th>Hospital</th><th>Units</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($myRequests as $r): ?>
                <tr>
                    <td style="font-family:monospace;font-size:.82rem;color:#64748b"><?= h($r['request_code']) ?></td>
                    <td style="font-weight:600"><?= h($r['patient_name']) ?></td>
                    <td><span class="badge badge-red"><?= h($r['blood_group_needed']) ?></span></td>
                    <td><?= h($r['hospital_name']) ?></td>
                    <td><?= h($r['units_required']) ?></td>
                    <td><span class="badge badge-<?= h($r['urgency_level']) ?>"><?= ucfirst(h($r['urgency_level'])) ?></span></td>
                    <td><span class="badge badge-<?= h($r['status']) ?>"><?= ucfirst(h($r['status'])) ?></span></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Find Donors -->
        <div id="find-donors">
            <h2 style="font-size:1.2rem;font-weight:800;color:#8b1a2a;margin-bottom:1rem"><i class="fa-solid fa-magnifying-glass"></i> Find Available Donors</h2>
            <form method="GET" action="patient_dashboard.php#find-donors" class="search-bar">
                <div class="search-form">
                    <div class="search-field">
                        <label>Blood Group</label>
                        <select name="blood_group" class="form-control form-select" style="border-radius:14px">
                            <option value="">All groups</option>
                            <?php foreach($bgs as $bg): ?>
                            <option value="<?= $bg ?>" <?= $searchBg === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-field">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" placeholder="e.g. Mumbai" value="<?= h($searchCity) ?>" style="border-radius:14px">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Search</button>
                    <?php if ($searchBg || $searchCity): ?>
                    <a href="patient_dashboard.php#find-donors" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($searchBg || $searchCity): ?>
            <?php if (empty($donors)): ?>
            <div style="padding:2rem;text-align:center;color:#64748b;background:#fff;border-radius:20px;border:1px solid #fde8e8">
                <i class="fa-solid fa-user-slash" style="font-size:2rem;color:#fcd5d5;display:block;margin-bottom:.5rem"></i>
                No available donors found for your search criteria.
            </div>
            <?php else: ?>
            <div class="grid-auto">
                <?php foreach($donors as $d): ?>
                <div class="card"><div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
                        <div style="font-weight:800;font-size:1.05rem"><?= h($d['full_name']) ?></div>
                        <div class="blood-group-badge" style="width:42px;height:42px;font-size:.9rem"><?= h($d['blood_group']) ?></div>
                    </div>
                    <div class="request-detail"><i class="fa-solid fa-location-dot"></i><?= h($d['city'] ?? 'N/A') ?>, <?= h($d['state'] ?? '') ?></div>
                    <div class="request-detail"><i class="fa-solid fa-heart-pulse"></i><?= h($d['total_donations']) ?> donations</div>
                    <div style="margin-top:.75rem"><span class="badge badge-available">Available</span></div>
                </div></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../js/app.js"></script>
</body>
</html>

