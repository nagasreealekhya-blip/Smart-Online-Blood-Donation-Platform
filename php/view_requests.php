<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db_connect.php';

$loggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? '';

// Filters
$filterBg      = $_GET['blood_group'] ?? '';
$filterUrgency = $_GET['urgency'] ?? '';
$filterStatus  = $_GET['status'] ?? '';

$bgs       = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
$urgencies = ['low','medium','high','critical'];
$statuses  = ['pending','accepted','approved','fulfilled','cancelled'];

$sql    = "SELECT br.*, u.full_name AS requester_name FROM blood_requests br JOIN users u ON u.id = br.requested_by_user_id WHERE 1=1";
$params = [];

if ($filterBg)      { $sql .= " AND br.blood_group_needed = ?"; $params[] = $filterBg; }
if ($filterUrgency) { $sql .= " AND br.urgency_level = ?"; $params[] = $filterUrgency; }
if ($filterStatus)  { $sql .= " AND br.status = ?"; $params[] = $filterStatus; }

$sql .= " ORDER BY FIELD(br.urgency_level,'critical','high','medium','low'), br.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$filtered = (bool)($filterBg || $filterUrgency || $filterStatus);

// HTML Block to render the grid (used by both full page load and AJAX calls)
function renderResultsContainer($requests, $loggedIn, $userRole, $filtered) {
    $count = count($requests);
    ?>
    <div style="margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:center">
        <p style="color:#64748b;font-weight:600">
            <?= $count ?> request<?= $count !== 1 ? 's' : '' ?> found
            <?= $filtered ? ' (filtered)' : '' ?>
        </p>
        <?php if ($loggedIn): ?>
        <a href="blood_request.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Post New Request</a>
        <?php endif; ?>
    </div>

    <?php if (empty($requests)): ?>
    <div style="text-align:center;padding:4rem 2rem;background:#fff;border-radius:28px;border:1px solid #fde8e8">
        <i class="fa-solid fa-droplet-slash" style="font-size:4rem;color:#fcd5d5;display:block;margin-bottom:1rem"></i>
        <h3 style="color:#8b1a2a;font-size:1.4rem;margin-bottom:.5rem">No Requests Found</h3>
        <p style="color:#64748b">No blood requests match your filters. Try adjusting them or clear all filters.</p>
        <?php if ($loggedIn): ?>
        <a href="blood_request.php" class="btn btn-primary" style="margin-top:1.5rem"><i class="fa-solid fa-plus"></i> Post a New Request</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="grid-auto">
        <?php foreach($requests as $req): ?>
        <div class="request-card <?= $req['urgency_level'] === 'critical' ? 'urgent' : '' ?> fade-in">
            <div class="request-header">
                <div>
                    <h3 style="font-weight:800;color:#1e293b;margin-bottom:.4rem"><?= h($req['patient_name']) ?></h3>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                        <span class="badge badge-<?= h($req['urgency_level']) ?>"><?= strtoupper(h($req['urgency_level'])) ?></span>
                        <span class="badge badge-<?= h($req['status']) ?>"><?= ucfirst(h($req['status'])) ?></span>
                    </div>
                </div>
                <div class="blood-group-badge"><?= h($req['blood_group_needed']) ?></div>
            </div>
            <div class="request-detail"><i class="fa-solid fa-hospital"></i><?= h($req['hospital_name']) ?></div>
            <?php if ($req['location']): ?><div class="request-detail"><i class="fa-solid fa-location-dot"></i><?= h($req['location']) ?></div><?php endif; ?>
            <div class="request-detail"><i class="fa-solid fa-droplet"></i><?= h($req['units_required']) ?> unit(s) needed</div>
            <?php if ($req['needed_by']): ?><div class="request-detail"><i class="fa-solid fa-calendar-day"></i>Needed by <?= date('d M Y', strtotime($req['needed_by'])) ?></div><?php endif; ?>
            <?php if ($req['contact_number']): ?><div class="request-detail"><i class="fa-solid fa-phone"></i><a href="tel:<?= h($req['contact_number']) ?>" style="color:#b22234;font-weight:600"><?= h($req['contact_number']) ?></a></div><?php endif; ?>
            <div class="request-detail" style="font-size:.8rem"><i class="fa-solid fa-clock"></i><?= date('d M Y', strtotime($req['created_at'])) ?></div>

            <?php if ($req['medical_notes']): ?>
            <div style="background:#fff5f5;border-radius:12px;padding:.7rem 1rem;margin-top:.75rem;font-size:.85rem;color:#64748b;border:1px solid #fde8e8">
                <strong style="color:#8b1a2a"><i class="fa-solid fa-notes-medical"></i> Notes:</strong> <?= h(substr($req['medical_notes'], 0, 100)) ?><?= strlen($req['medical_notes']) > 100 ? '...' : '' ?>
            </div>
            <?php endif; ?>

            <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.5rem">
                <?php if ($loggedIn && $userRole === 'donor' && $req['status'] === 'pending'): ?>
                <a href="book_appointment.php?request_id=<?= $req['id'] ?>" class="btn btn-primary" style="width:100%">
                    <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                </a>
                <?php if ($req['contact_number']): ?>
                <a href="tel:<?= h($req['contact_number']) ?>" class="btn btn-outline" style="width:100%;font-size:.88rem">
                    <i class="fa-solid fa-phone"></i> Call <?= h($req['contact_number']) ?>
                </a>
                <?php endif; ?>
                <?php elseif ($loggedIn && $userRole !== 'donor'): ?>
                <div style="text-align:center;font-size:.83rem;color:#94a3b8;padding:.5rem">Only donors can book appointments</div>
                <?php else: ?>
                <a href="login.php?next=<?= urlencode('view_requests.php') ?>" class="btn btn-outline" style="width:100%">
                    <i class="fa-solid fa-right-to-bracket"></i> Login to Respond
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif;
}

// Intercept AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    renderResultsContainer($requests, $loggedIn, $userRole, $filtered);
    exit;
}

// Full page load
$pageTitle = 'Blood Requests - LifeFlow';
require_once 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-droplet"></i> Blood Requests</h1>
    <p>Browse all active blood donation requests. Every response can save a life.</p>
</div>

<div class="section">
<div class="container">

    <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success" data-dismiss="1"><i class="fa-solid fa-check-circle"></i> Your blood request has been posted successfully! We'll notify matching donors.</div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" action="view_requests.php" class="search-bar" id="filterForm">
        <div class="search-form">
            <div class="search-field">
                <label>Blood Group</label>
                <select name="blood_group" class="form-control form-select" style="border-radius:14px">
                    <option value="">All groups</option>
                    <?php foreach($bgs as $bg): ?>
                    <option value="<?= $bg ?>" <?= $filterBg === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label>Urgency</label>
                <select name="urgency" class="form-control form-select" style="border-radius:14px">
                    <option value="">All urgencies</option>
                    <?php foreach($urgencies as $u): ?>
                    <option value="<?= $u ?>" <?= $filterUrgency === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label>Status</label>
                <select name="status" class="form-control form-select" style="border-radius:14px">
                    <option value="">All statuses</option>
                    <?php foreach($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <noscript><button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Filter</button></noscript>
            <a href="view_requests.php" class="btn btn-outline" id="resetBtn">Reset</a>
        </div>
    </form>

    <!-- Request Cards Container -->
    <div id="ajaxResultsContainer">
        <?php renderResultsContainer($requests, $loggedIn, $userRole, $filtered); ?>
    </div>

</div>
</div>

<?php 
$extraScripts = "
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('filterForm');
    const container = document.getElementById('ajaxResultsContainer');
    
    filterForm.addEventListener('change', function(e) {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');
        
        container.style.opacity = '0.5';
        
        fetch('view_requests.php?' + params.toString())
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
                const newUrl = window.location.pathname + '?' + new URLSearchParams(formData).toString();
                window.history.pushState({}, '', newUrl);
            })
            .catch(err => console.error('Fetch error:', err));
    });
});
</script>
";

$footerStyle = 'margin-top:3rem';
$showFullFooter = false;
require_once 'includes/footer.php'; 
?>



