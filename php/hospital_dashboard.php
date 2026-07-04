<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hospital') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../db_connect.php';

$userId = (int)$_SESSION['user_id'];

// Handle Inventory Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    if (!verify_csrf_token()) {
        die('Invalid CSRF token');
    }
    $bg = $_POST['blood_group'] ?? '';
    $units = (int)($_POST['units_available'] ?? 0);
    
    // Determine status level
    $status = 'normal';
    if ($units <= 5) $status = 'critical';
    elseif ($units <= 15) $status = 'low';

    $stmt = $pdo->prepare("
        INSERT INTO blood_inventory (hospital_user_id, blood_group, units_available, status_level) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE units_available = ?, status_level = ?
    ");
    $stmt->execute([$userId, $bg, $units, $status, $units, $status]);
    header('Location: hospital_dashboard.php?updated=1');
    exit;
}

// Fetch Hospital Profile
$hospital = $pdo->prepare("SELECT u.*, hp.hospital_name, hp.license_number FROM users u LEFT JOIN hospital_profiles hp ON hp.user_id = u.id WHERE u.id = ?");
$hospital->execute([$userId]);
$hospital = $hospital->fetch();

// Fetch Inventory
$inventoryStmt = $pdo->prepare("SELECT * FROM blood_inventory WHERE hospital_user_id = ?");
$inventoryStmt->execute([$userId]);
$inventoryRaw = $inventoryStmt->fetchAll();
$inventory = [];
foreach ($inventoryRaw as $inv) {
    $inventory[$inv['blood_group']] = $inv;
}

// Fetch Upcoming Appointments
$appointments = $pdo->prepare("
    SELECT a.*, u.full_name as donor_name, u.phone as donor_phone, dp.blood_group 
    FROM appointments a 
    JOIN users u ON u.id = a.donor_user_id 
    LEFT JOIN donor_profiles dp ON dp.user_id = u.id
    WHERE a.hospital_user_id = ? AND a.status = 'scheduled'
    ORDER BY a.appointment_datetime ASC LIMIT 10
");
$appointments->execute([$userId]);
$appointments = $appointments->fetchAll();

$bgs = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];

$pageTitle = 'Hospital Dashboard - LifeFlow';
$hideNav = true;
require_once 'includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-droplet"></i> LifeFlow</div>
        <ul class="sidebar-nav">
            <li><a href="hospital_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="#inventory"><i class="fa-solid fa-boxes-stacked"></i> Manage Inventory</a></li>
            <li><a href="view_requests.php"><i class="fa-solid fa-list"></i> Global Requests</a></li>
            <li><a href="edit_profile.php"><i class="fa-solid fa-user-pen"></i> Edit Profile</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= h($hospital['hospital_name'] ?? 'Hospital') ?></div>
            <div class="sidebar-user-role">Partner Hospital</div>
            <a href="logout.php" style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.75);font-size:.85rem;margin-top:.5rem"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>🏥 Hospital Dashboard</h1>
                <p>Manage your blood bank inventory and upcoming donor appointments.</p>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" data-dismiss="1"><i class="fa-solid fa-check-circle"></i> Inventory updated successfully!</div>
        <?php endif; ?>

        <!-- Inventory Grid -->
        <h2 id="inventory" style="margin-bottom: 1rem; color: #8b1a2a;"><i class="fa-solid fa-boxes-stacked"></i> Blood Bank Inventory</h2>
        <div class="inventory-grid" style="margin-bottom: 2rem;">
            <?php foreach($bgs as $bg): 
                $inv = $inventory[$bg] ?? ['units_available' => 0, 'status_level' => 'critical'];
            ?>
            <div class="inv-card <?= $inv['status_level'] ?>">
                <div class="inv-blood-type"><?= $bg ?></div>
                
                <div class="inv-units-display">
                    <div class="inv-units"><?= $inv['units_available'] ?> <span style="font-size:0.9rem; color:#64748b;">Units</span></div>
                    <span class="badge badge-<?= $inv['status_level'] === 'critical' ? 'crit-inv' : ($inv['status_level'] === 'low' ? 'low-inv' : 'normal') ?>">
                        <?= ucfirst($inv['status_level']) ?>
                    </span>
                    <br>
                    <button class="btn btn-outline btn-sm inv-edit-btn" style="margin-top: 10px;">Update</button>
                </div>

                <form method="POST" class="inv-edit-form" style="display:none; margin-top: 10px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="blood_group" value="<?= $bg ?>">
                    <input type="number" name="units_available" class="form-control" value="<?= $inv['units_available'] ?>" min="0" required style="text-align:center; margin-bottom:10px;">
                    <button type="submit" name="update_inventory" class="btn btn-primary btn-sm" style="width:100%; margin-bottom:5px;">Save</button>
                    <button type="button" class="btn btn-white btn-sm inv-cancel-btn" style="width:100%;">Cancel</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Appointments Table -->
        <div class="table-wrap">
            <div class="table-head">
                <h3><i class="fa-solid fa-calendar-days" style="color:#b22234; margin-right:.4rem"></i> Upcoming Donor Appointments</h3>
            </div>
            <?php if (empty($appointments)): ?>
            <div style="padding:2rem;text-align:center;color:#64748b;">No upcoming appointments.</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Donor Name</th><th>Blood Group</th><th>Date & Time</th><th>Type</th><th>Contact</th></tr></thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td style="font-weight:600"><?= h($a['donor_name']) ?></td>
                    <td><span class="blood-group-badge" style="width:35px; height:35px; font-size:0.8rem;"><?= h($a['blood_group'] ?? '?') ?></span></td>
                    <td><?= date('d M Y, h:i A', strtotime($a['appointment_datetime'])) ?></td>
                    <td><?= h($a['appointment_type']) ?></td>
                    <td><?= h($a['donor_phone'] ?? 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php 
$hideFooter = true;
require_once 'includes/footer.php'; 
?>


