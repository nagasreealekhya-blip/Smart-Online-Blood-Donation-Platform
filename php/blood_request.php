<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php?next=blood_request.php'); exit; }
require_once __DIR__ . '/../db_connect.php';

$userId = (int)$_SESSION['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientName  = trim($_POST['patient_name'] ?? '');
    $bloodGroup   = $_POST['blood_group_needed'] ?? '';
    $units        = max(1, (int)($_POST['units_required'] ?? 1));
    $hospitalName = trim($_POST['hospital_name'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $contact      = trim($_POST['contact_number'] ?? '');
    $urgency      = $_POST['urgency_level'] ?? 'medium';
    $neededBy     = $_POST['needed_by'] ?? '';
    $notes        = trim($_POST['medical_notes'] ?? '');

    $validBGs  = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
    $validUrg  = ['low','medium','high','critical'];

    if (!$patientName || !$bloodGroup || !$hospitalName) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($bloodGroup, $validBGs, true)) {
        $error = 'Invalid blood group selected.';
    } elseif (!in_array($urgency, $validUrg, true)) {
        $error = 'Invalid urgency level.';
    } else {
        $code = 'REQ-' . time() . '-' . rand(100,999);
        $pdo->prepare("INSERT INTO blood_requests
            (request_code, requested_by_user_id, patient_name, blood_group_needed, units_required,
             hospital_name, location, contact_number, urgency_level, needed_by, medical_notes, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending')")
            ->execute([$code, $userId, $patientName, $bloodGroup, $units,
                $hospitalName, $location ?: null, $contact ?: null, $urgency,
                $neededBy ?: null, $notes ?: null]);
        header('Location: view_requests.php?created=1');
        exit;
    }
}

$bgs = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a class="logo" href="index.php"><i class="fa-solid fa-droplet"></i> LifeFlow</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="view_requests.php">Requests</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
        </ul>
        <div class="nav-actions">
            <a href="dashboard.php" class="btn btn-white btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,.6)">Logout</a>
        </div>
    </div>
</nav>

<div class="page-header">
    <h1><i class="fa-solid fa-syringe"></i> Post a Blood Request</h1>
    <p>Fill in the details below and we'll match you with available donors immediately.</p>
</div>

<div class="section">
<div class="container" style="max-width:780px">

    <?php if ($error): ?>
    <div class="alert alert-error" data-dismiss="1"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
    <form method="POST" action="blood_request.php">

        <!-- Urgency Level (prominent) -->
        <div style="margin-bottom:1.5rem">
            <label style="display:block;font-weight:700;color:#8b1a2a;margin-bottom:.75rem">Urgency Level <span style="color:#dc2626">*</span></label>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem">
                <?php foreach(['low'=>['Low','fa-circle-check','#15803d','#dcfce7'],'medium'=>['Medium','fa-clock','#854d0e','#fef9c3'],'high'=>['High','fa-exclamation-circle','#b45309','#fef3c7'],'critical'=>['Critical','fa-triangle-exclamation','#b22234','#fee2e2']] as $v=>[$lbl,$icon,$color,$bg]): ?>
                <label style="border:2px solid <?= ($_POST['urgency_level'] ?? 'medium') === $v ? $color : '#fde8e8' ?>;border-radius:16px;padding:1rem;text-align:center;cursor:pointer;background:<?= ($_POST['urgency_level'] ?? 'medium') === $v ? $bg : '#fff' ?>;transition:all .2s">
                    <input type="radio" name="urgency_level" value="<?= $v ?>" <?= ($_POST['urgency_level'] ?? 'medium') === $v ? 'checked' : '' ?> style="position:absolute;opacity:0">
                    <i class="fa-solid <?= $icon ?>" style="font-size:1.4rem;color:<?= $color ?>;display:block;margin-bottom:.4rem"></i>
                    <span style="font-weight:800;font-size:.92rem;color:<?= $color ?>"><?= $lbl ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Patient Name <span>*</span></label>
                <input type="text" name="patient_name" class="form-control" placeholder="Full name of the patient" value="<?= h($_POST['patient_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Blood Group Needed <span>*</span></label>
                <select name="blood_group_needed" class="form-control form-select" required>
                    <option value="">-- Select blood group --</option>
                    <?php foreach($bgs as $bg): ?>
                    <option value="<?= $bg ?>" <?= ($_POST['blood_group_needed'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Units Required <span>*</span></label>
                <input type="number" name="units_required" class="form-control" placeholder="e.g. 2" min="1" max="20" value="<?= h($_POST['units_required'] ?? '1') ?>" required>
            </div>
            <div class="form-group">
                <label>Needed By Date</label>
                <input type="date" name="needed_by" class="form-control" value="<?= h($_POST['needed_by'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Hospital Name <span>*</span></label>
            <input type="text" name="hospital_name" class="form-control" placeholder="e.g. AIIMS Delhi, Apollo Hospital" value="<?= h($_POST['hospital_name'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Location / City</label>
                <input type="text" name="location" class="form-control" placeholder="e.g. New Delhi, Delhi" value="<?= h($_POST['location'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="tel" name="contact_number" class="form-control" placeholder="+91 98765 43210" value="<?= h($_POST['contact_number'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Medical Notes <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
            <textarea name="medical_notes" class="form-control textarea" placeholder="Any additional information about the patient's condition, diagnosis, etc."><?= h($_POST['medical_notes'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:1rem;justify-content:flex-end">
            <a href="view_requests.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-paper-plane"></i> Post Blood Request
            </button>
        </div>
    </form>
    </div></div>
</div>
</div>
<script src="../js/app.js"></script>
<script>
    // Urgency radio card visual update
    document.querySelectorAll('input[name="urgency_level"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('input[name="urgency_level"]').forEach(r => {
                const label = r.closest('label');
                label.style.borderColor = '#fde8e8';
                label.style.background = '#fff';
            });
            const label = radio.closest('label');
            const color = { low:'#15803d', medium:'#854d0e', high:'#b45309', critical:'#b22234' }[radio.value];
            const bg    = { low:'#dcfce7', medium:'#fef9c3', high:'#fef3c7', critical:'#fee2e2' }[radio.value];
            label.style.borderColor = color;
            label.style.background  = bg;
        });
    });
</script>
</body>
</html>

