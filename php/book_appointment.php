<?php
declare(strict_types=1);
session_start();

// Must be logged in as a donor
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
if ($_SESSION['user_role'] !== 'donor') {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../db_connect.php';

$userId    = (int)$_SESSION['user_id'];
$requestId = (int)($_GET['request_id'] ?? 0);

// Load the blood request
$reqStmt = $pdo->prepare("
    SELECT br.*, u.full_name AS requester_name, u.phone AS requester_phone
    FROM blood_requests br
    JOIN users u ON u.id = br.requested_by_user_id
    WHERE br.id = ? AND br.status = 'pending'
");
$reqStmt->execute([$requestId]);
$request = $reqStmt->fetch();

if (!$request) {
    header('Location: view_requests.php?error=not_found');
    exit;
}

// Check donor hasn't already booked for this request
$dupCheck = $pdo->prepare("
    SELECT id FROM appointments
    WHERE donor_user_id = ? AND blood_request_id = ? AND status != 'cancelled'
");
$dupCheck->execute([$userId, $requestId]);
$alreadyBooked = $dupCheck->fetch();

// Load all active hospitals
$hospitals = $pdo->query("
    SELECT u.id, hp.hospital_name, u.city, u.state
    FROM users u
    JOIN hospital_profiles hp ON hp.user_id = u.id
    WHERE u.role = 'hospital' AND u.status = 'active'
    ORDER BY hp.hospital_name
")->fetchAll();

// Load donor's blood group for compatibility check
$donorProfile = $pdo->prepare("SELECT blood_group FROM donor_profiles WHERE user_id = ?");
$donorProfile->execute([$userId]);
$donorBG = $donorProfile->fetchColumn();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyBooked) {
    $hospitalId       = (int)($_POST['hospital_id'] ?? 0);
    $appointmentDate  = trim($_POST['appointment_date'] ?? '');
    $appointmentTime  = trim($_POST['appointment_time'] ?? '');
    $appointmentType  = trim($_POST['appointment_type'] ?? 'Donation');
    $notes            = trim($_POST['notes'] ?? '');

    // Validate
    if (!$hospitalId || !$appointmentDate || !$appointmentTime) {
        $error = 'Please select a hospital and pick a date and time.';
    } elseif (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
        $error = 'Appointment date cannot be in the past.';
    } else {
        // Verify hospital exists
        $hospCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'hospital' AND status = 'active'");
        $hospCheck->execute([$hospitalId]);
        if (!$hospCheck->fetch()) {
            $error = 'Selected hospital is not valid.';
        } else {
            $datetimeStr = $appointmentDate . ' ' . $appointmentTime . ':00';

            $pdo->prepare("
                INSERT INTO appointments
                    (donor_user_id, hospital_user_id, blood_request_id, appointment_datetime, appointment_type, notes, status)
                VALUES (?,?,?,?,?,?,'scheduled')
            ")->execute([$userId, $hospitalId, $requestId, $datetimeStr, $appointmentType, $notes ?: null]);

            $appointmentId = (int)$pdo->lastInsertId();

            // Update blood request status to 'accepted'
            $pdo->prepare("UPDATE blood_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?")
                ->execute([$requestId]);

            // Create notification for the requester
            $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, 'Donor Matched!', ?, 'success')
            ")->execute([
                $request['requested_by_user_id'],
                "A donor has accepted your blood request for {$request['patient_name']} and booked an appointment."
            ]);

            // Create confirmation notification for the donor
            $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, 'Appointment Confirmed', ?, 'success')
            ")->execute([
                $userId,
                "Your donation appointment has been scheduled for " . date('d M Y', strtotime($appointmentDate)) . " at " . date('h:i A', strtotime($appointmentTime)) . "."
            ]);

            header('Location: donor_dashboard.php?booked=1');
            exit;
        }
    }
}

// Min date = today, max = 30 days from now
$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+30 days'));

// Time slots (8 AM to 6 PM, every 30 minutes)
$timeSlots = [];
$start = strtotime('08:00');
$end   = strtotime('18:00');
while ($start <= $end) {
    $timeSlots[] = date('H:i', $start);
    $start += 1800;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .hospital-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; margin-top: .75rem; }
        .hospital-card {
            border: 2px solid #fde8e8; border-radius: 18px; padding: 1.1rem 1.25rem;
            cursor: pointer; transition: all .2s; background: #fff5f5;
            display: flex; align-items: flex-start; gap: .85rem;
        }
        .hospital-card:hover { border-color: var(--primary); background: #fff; transform: translateY(-2px); box-shadow: var(--shadow); }
        .hospital-card.selected { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(178,34,52,.15); }
        .hospital-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .hospital-icon { width: 44px; height: 44px; border-radius: 12px; background: #fee2e2; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.3rem; flex-shrink: 0; }
        .hospital-card.selected .hospital-icon { background: var(--primary); color: #fff; }

        .time-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: .5rem; margin-top: .75rem; }
        .time-slot {
            border: 2px solid #fde8e8; border-radius: 12px; padding: .55rem .5rem;
            text-align: center; cursor: pointer; transition: all .2s; background: #fff5f5;
            font-weight: 700; font-size: .88rem; color: #64748b;
        }
        .time-slot:hover { border-color: var(--primary); color: var(--primary); background: #fff; }
        .time-slot.selected { background: var(--primary); color: #fff; border-color: var(--primary); }
        .time-slot input { position: absolute; opacity: 0; pointer-events: none; }

        .step-indicator { display: flex; gap: 0; margin-bottom: 2rem; }
        .step-dot { flex: 1; text-align: center; position: relative; }
        .step-dot::before { content: ''; position: absolute; top: 16px; left: 50%; width: 100%; height: 2px; background: #fde8e8; z-index: 0; }
        .step-dot:last-child::before { display: none; }
        .step-circle { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: .9rem; position: relative; z-index: 1; margin: 0 auto .4rem; border: 2px solid #fde8e8; background: #fff; color: #94a3b8; }
        .step-dot.done .step-circle { background: var(--primary); border-color: var(--primary); color: #fff; }
        .step-dot.active .step-circle { background: #fff; border-color: var(--primary); color: var(--primary); box-shadow: 0 0 0 3px rgba(178,34,52,.15); }
        .step-label { font-size: .78rem; font-weight: 700; color: #94a3b8; }
        .step-dot.active .step-label, .step-dot.done .step-label { color: var(--primary-dark); }

        .compatibility-badge { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem 1rem; border-radius: 999px; font-weight: 700; font-size: .88rem; }
        .compatible { background: #dcfce7; color: #15803d; }
        .check-compatibility { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a class="logo" href="index.php"><i class="fa-solid fa-droplet"></i> LifeFlow</a>
        <ul class="nav-links">
            <li><a href="view_requests.php">Blood Requests</a></li>
            <li><a href="donor_dashboard.php">My Dashboard</a></li>
        </ul>
        <div class="nav-actions">
            <a href="donor_dashboard.php" class="btn btn-white btn-sm">← Dashboard</a>
            <a href="logout.php" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,.6)">Logout</a>
        </div>
    </div>
</nav>

<div class="page-header">
    <h1><i class="fa-solid fa-calendar-plus"></i> Book Donation Appointment</h1>
    <p>Choose a hospital, pick a time, and confirm your life-saving appointment.</p>
</div>

<div class="section">
<div class="container" style="max-width:900px">

    <?php if ($alreadyBooked): ?>
    <!-- Already booked -->
    <div style="text-align:center;padding:4rem 2rem;background:#fff;border-radius:28px;border:1px solid #fde8e8;box-shadow:var(--shadow)">
        <i class="fa-solid fa-calendar-check" style="font-size:4rem;color:#15803d;display:block;margin-bottom:1rem"></i>
        <h2 style="color:#15803d;font-weight:900;margin-bottom:.75rem">You Already Have an Appointment for This Request</h2>
        <p style="color:#64748b;max-width:500px;margin:0 auto 1.5rem">You've already booked a donation appointment for this blood request. Check your dashboard to view the details.</p>
        <div style="display:flex;gap:1rem;justify-content:center">
            <a href="donor_dashboard.php" class="btn btn-primary">View My Appointments</a>
            <a href="view_requests.php" class="btn btn-outline">Browse Other Requests</a>
        </div>
    </div>

    <?php else: ?>

    <!-- Step indicator -->
    <div class="step-indicator">
        <div class="step-dot done"><div class="step-circle"><i class="fa-solid fa-check" style="font-size:.75rem"></i></div><div class="step-label">Request</div></div>
        <div class="step-dot active"><div class="step-circle">2</div><div class="step-label">Hospital</div></div>
        <div class="step-dot"><div class="step-circle">3</div><div class="step-label">Date &amp; Time</div></div>
        <div class="step-dot"><div class="step-circle">4</div><div class="step-label">Confirm</div></div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" data-dismiss="1"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:flex-start">

    <div>
    <form method="POST" action="book_appointment.php?request_id=<?= $requestId ?>" id="bookingForm">

        <!-- Step 1: Hospital Selection -->
        <div class="card" style="margin-bottom:1.25rem" id="stepHospital">
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900">2</div>
                    <div>
                        <h3 style="color:#8b1a2a;font-weight:900;margin-bottom:.15rem">Select a Hospital</h3>
                        <p style="color:#64748b;font-size:.88rem">Choose where you'd like to donate blood.</p>
                    </div>
                </div>

                <?php if (empty($hospitals)): ?>
                <div class="alert alert-info">No registered hospitals found yet. Please contact the patient directly using the phone number on the request.</div>
                <?php else: ?>
                <div class="hospital-cards" id="hospitalCards">
                    <?php foreach ($hospitals as $h): ?>
                    <label class="hospital-card <?= ($_POST['hospital_id'] ?? '') == $h['id'] ? 'selected' : '' ?>">
                        <input type="radio" name="hospital_id" value="<?= $h['id'] ?>" <?= ($_POST['hospital_id'] ?? '') == $h['id'] ? 'checked' : '' ?> required>
                        <div class="hospital-icon"><i class="fa-solid fa-hospital"></i></div>
                        <div>
                            <div style="font-weight:800;color:#1e293b;margin-bottom:.2rem"><?= h($h['hospital_name']) ?></div>
                            <div style="font-size:.82rem;color:#64748b"><i class="fa-solid fa-location-dot" style="color:#b22234"></i> <?= h($h['city'] ?? 'N/A') ?>, <?= h($h['state'] ?? '') ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Date Selection -->
        <div class="card" style="margin-bottom:1.25rem">
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900">3</div>
                    <div>
                        <h3 style="color:#8b1a2a;font-weight:900;margin-bottom:.15rem">Pick Date &amp; Time</h3>
                        <p style="color:#64748b;font-size:.88rem">Available slots within the next 30 days, 8 AM – 6 PM.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Appointment Date <span>*</span></label>
                    <input type="date" name="appointment_date" id="dateInput" class="form-control"
                        min="<?= $minDate ?>" max="<?= $maxDate ?>"
                        value="<?= h($_POST['appointment_date'] ?? '') ?>" required
                        style="max-width:280px">
                </div>

                <div class="form-group">
                    <label>Appointment Time <span>*</span></label>
                    <div class="time-grid" id="timeGrid">
                        <?php foreach ($timeSlots as $slot): ?>
                        <label class="time-slot <?= ($_POST['appointment_time'] ?? '') === $slot ? 'selected' : '' ?>">
                            <input type="radio" name="appointment_time" value="<?= $slot ?>" <?= ($_POST['appointment_time'] ?? '') === $slot ? 'checked' : '' ?> required>
                            <?= date('h:i A', strtotime($slot)) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group" style="margin-top:1rem">
                    <label>Appointment Type</label>
                    <select name="appointment_type" class="form-control form-select" style="max-width:280px">
                        <?php foreach(['Whole Blood Donation','Platelet Donation','Plasma Donation','Red Cell Donation'] as $type): ?>
                        <option <?= ($_POST['appointment_type'] ?? 'Whole Blood Donation') === $type ? 'selected' : '' ?>><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 3: Notes & Confirm -->
        <div class="card" style="margin-bottom:1.5rem">
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900">4</div>
                    <div>
                        <h3 style="color:#8b1a2a;font-weight:900;margin-bottom:.15rem">Confirm &amp; Notes</h3>
                        <p style="color:#64748b;font-size:.88rem">Add any notes and confirm your appointment.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Additional Notes <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
                    <textarea name="notes" class="form-control textarea" rows="3" placeholder="Any special requirements, health information, or questions..."><?= h($_POST['notes'] ?? '') ?></textarea>
                </div>

                <!-- Health checklist -->
                <div style="background:#fff5f5;border-radius:16px;padding:1.25rem;border:1px solid #fde8e8;margin-bottom:1.25rem">
                    <h4 style="color:#8b1a2a;font-weight:800;margin-bottom:.85rem"><i class="fa-solid fa-circle-check"></i> Pre-Donation Checklist</h4>
                    <?php foreach([
                        'I am aged 18–65 and weigh at least 45 kg',
                        'I have not donated whole blood in the last 56 days',
                        'I do not have any active infections or fever',
                        'I am not on blood-thinning medications',
                        'I have had adequate sleep and a light meal today',
                    ] as $check): ?>
                    <label style="display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.6rem;cursor:pointer;font-size:.9rem;color:#334155">
                        <input type="checkbox" required style="margin-top:2px;accent-color:#b22234;width:16px;height:16px;flex-shrink:0">
                        <?= $check ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex;gap:1rem;align-items:center">
                    <a href="view_requests.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fa-solid fa-calendar-check"></i> Confirm Appointment
                    </button>
                </div>
            </div>
        </div>

    </form>
    </div>

    <!-- Sidebar: Request Summary -->
    <div>
        <div class="card" style="position:sticky;top:1.5rem">
            <div class="card-body">
                <h3 style="color:#8b1a2a;font-weight:900;margin-bottom:1.25rem"><i class="fa-solid fa-droplet"></i> Blood Request</h3>

                <div style="text-align:center;margin-bottom:1.25rem">
                    <div class="blood-group-badge" style="width:64px;height:64px;font-size:1.3rem;margin:0 auto .75rem"><?= h($request['blood_group_needed']) ?></div>
                    <div style="font-size:1.1rem;font-weight:800;color:#1e293b"><?= h($request['patient_name']) ?></div>
                    <span class="badge badge-<?= h($request['urgency_level']) ?>" style="margin-top:.4rem"><?= strtoupper(h($request['urgency_level'])) ?></span>
                </div>

                <?php
                // Blood compatibility check
                $compatible = ($donorBG === $request['blood_group_needed']);
                $universalDonor = in_array($donorBG, ['O-', 'O+']);
                $isCompatible = $compatible || ($universalDonor && $request['blood_group_needed'] !== 'AB-' && strpos($request['blood_group_needed'], '-') === false && $donorBG === 'O+')
                    || $donorBG === 'O-';
                ?>

                <?php if ($donorBG): ?>
                <div style="background:#f8fafc;border-radius:12px;padding:.85rem;margin-bottom:1rem;border:1px solid #e2e8f0">
                    <div style="font-size:.82rem;font-weight:700;color:#64748b;margin-bottom:.4rem">Blood Compatibility</div>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-weight:700">Your blood: <strong style="color:#b22234"><?= h($donorBG) ?></strong></span>
                        <?php if ($compatible): ?>
                        <span class="compatibility-badge compatible"><i class="fa-solid fa-check"></i> Exact Match</span>
                        <?php elseif ($donorBG === 'O-'): ?>
                        <span class="compatibility-badge compatible"><i class="fa-solid fa-check"></i> Universal Donor</span>
                        <?php else: ?>
                        <span class="compatibility-badge check-compatibility"><i class="fa-solid fa-triangle-exclamation"></i> Verify Match</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:flex;flex-direction:column;gap:.6rem;font-size:.88rem">
                    <div class="request-detail"><i class="fa-solid fa-hospital"></i><?= h($request['hospital_name']) ?></div>
                    <?php if ($request['location']): ?><div class="request-detail"><i class="fa-solid fa-location-dot"></i><?= h($request['location']) ?></div><?php endif; ?>
                    <div class="request-detail"><i class="fa-solid fa-droplet"></i><?= h($request['units_required']) ?> unit(s) needed</div>
                    <?php if ($request['contact_number']): ?>
                    <div class="request-detail"><i class="fa-solid fa-phone"></i><a href="tel:<?= h($request['contact_number']) ?>" style="color:#b22234;font-weight:600"><?= h($request['contact_number']) ?></a></div>
                    <?php endif; ?>
                    <?php if ($request['needed_by']): ?>
                    <div class="request-detail"><i class="fa-solid fa-calendar-day"></i>By <?= date('d M Y', strtotime($request['needed_by'])) ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($request['medical_notes']): ?>
                <div style="background:#fff5f5;border-radius:10px;padding:.75rem;margin-top:.85rem;border:1px solid #fde8e8;font-size:.83rem;color:#64748b">
                    <strong style="color:#8b1a2a"><i class="fa-solid fa-notes-medical"></i> Notes:</strong><br>
                    <?= h($request['medical_notes']) ?>
                </div>
                <?php endif; ?>

                <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #fde8e8">
                    <div style="font-size:.8rem;color:#94a3b8;font-weight:600;margin-bottom:.35rem">Request Code</div>
                    <code style="font-size:.8rem;color:#64748b;background:#f8fafc;padding:4px 8px;border-radius:6px"><?= h($request['request_code']) ?></code>
                </div>

                <div style="background:linear-gradient(135deg,#b22234,#8b1a2a);border-radius:14px;padding:1rem;margin-top:1rem;text-align:center;color:#fff">
                    <i class="fa-solid fa-heart" style="font-size:1.4rem;margin-bottom:.35rem;display:block;color:#ffd966"></i>
                    <div style="font-weight:800;font-size:.9rem">Thank you for helping!</div>
                    <div style="font-size:.8rem;opacity:.85;margin-top:.25rem">Your donation can save up to 3 lives</div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php endif; ?>

</div>
</div>

<script src="../js/app.js"></script>
<script>
// Hospital card toggle
document.querySelectorAll('.hospital-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.hospital-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    });
});

// Time slot toggle
document.querySelectorAll('.time-slot').forEach(slot => {
    slot.addEventListener('click', () => {
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        slot.classList.add('selected');
        const radio = slot.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    });
});

// Step indicator — update as user fills in fields
const dateInput = document.getElementById('dateInput');
const stepDots  = document.querySelectorAll('.step-dot');

function updateSteps() {
    const hospitalPicked = !!document.querySelector('.hospital-card.selected');
    const datePicked     = !!dateInput?.value;
    const timePicked     = !!document.querySelector('.time-slot.selected');

    const states = ['done', hospitalPicked ? 'active' : '', datePicked && timePicked ? 'active' : '', ''];
    stepDots.forEach((dot, i) => {
        dot.classList.remove('done', 'active');
        if (i === 0) dot.classList.add('done');
        if (i === 1 && hospitalPicked) dot.classList.add('done');
        else if (i === 1) dot.classList.add('active');
        if (i === 2 && hospitalPicked && datePicked) dot.classList.add('active');
        if (i === 2 && datePicked && timePicked) dot.classList.add('done');
        if (i === 3 && hospitalPicked && datePicked && timePicked) dot.classList.add('active');
    });
}

document.querySelectorAll('.hospital-card').forEach(c => c.addEventListener('click', updateSteps));
document.querySelectorAll('.time-slot').forEach(s => s.addEventListener('click', updateSteps));
if (dateInput) dateInput.addEventListener('change', updateSteps);
updateSteps();

// Prevent double-submit
const form = document.getElementById('bookingForm');
const submitBtn = document.getElementById('submitBtn');
if (form && submitBtn) {
    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking...';
    });
}
</script>
</body>
</html>

