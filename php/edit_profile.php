<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../db_connect.php';

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$role = $user['role'];

// Fetch role-specific data
$roleData = [];
if ($role === 'donor') {
    $s = $pdo->prepare("SELECT * FROM donor_profiles WHERE user_id = ?");
    $s->execute([$userId]); $roleData = $s->fetch() ?: [];
} elseif ($role === 'patient') {
    $s = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
    $s->execute([$userId]); $roleData = $s->fetch() ?: [];
} elseif ($role === 'hospital') {
    $s = $pdo->prepare("SELECT * FROM hospital_profiles WHERE user_id = ?");
    $s->execute([$userId]); $roleData = $s->fetch() ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $gender   = $_POST['gender'] ?? 'other';
    $age      = (int)($_POST['age'] ?? 0) ?: null;
    $city     = trim($_POST['city'] ?? '');
    $state    = trim($_POST['state'] ?? '');

    if (!$fullName) {
        $error = 'Name is required.';
    } else {
        $pdo->prepare("UPDATE users SET full_name=?, phone=?, gender=?, age=?, city=?, state=? WHERE id=?")
            ->execute([$fullName, $phone ?: null, $gender, $age, $city ?: null, $state ?: null, $userId]);

        // Role-specific update
        if ($role === 'donor') {
            $bg = $_POST['blood_group'] ?? '';
            $avail = $_POST['availability_status'] ?? 'available';
            if ($bg) {
                $pdo->prepare("UPDATE donor_profiles SET blood_group=?, availability_status=? WHERE user_id=?")
                    ->execute([$bg, $avail, $userId]);
            }
        } elseif ($role === 'patient') {
            $bgn = $_POST['blood_group_needed'] ?? '';
            $hn  = trim($_POST['hospital_name'] ?? '');
            if ($bgn) {
                $pdo->prepare("UPDATE patient_profiles SET blood_group_needed=?, hospital_name=? WHERE user_id=?")
                    ->execute([$bgn, $hn ?: null, $userId]);
            }
        } elseif ($role === 'hospital') {
            $hn = trim($_POST['hospital_name'] ?? '');
            if ($hn) {
                $pdo->prepare("UPDATE hospital_profiles SET hospital_name=?, city=?, state=? WHERE user_id=?")
                    ->execute([$hn, $city ?: null, $state ?: null, $userId]);
            }
        }

        // Update session name
        $_SESSION['user_name'] = $fullName;

        // Password change
        $newPass = $_POST['new_password'] ?? '';
        $curPass = $_POST['current_password'] ?? '';
        if ($newPass) {
            if (!$curPass || !password_verify($curPass, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPass) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $userId]);
                $success = 'Profile and password updated successfully!';
            }
        } else {
            $success = 'Profile updated successfully!';
        }

        // Reload user data
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
}

$bgs = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a class="logo" href="index.php"><i class="fa-solid fa-droplet"></i> LifeFlow</a>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
        </ul>
        <div class="nav-actions">
            <a href="dashboard.php" class="btn btn-white btn-sm">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,.6)">Logout</a>
        </div>
    </div>
</nav>

<div class="page-header">
    <h1><i class="fa-solid fa-user-pen"></i> Edit Profile</h1>
    <p>Keep your information up to date for the best matching experience.</p>
</div>

<div class="section">
<div class="container" style="max-width:700px">

    <?php if ($success): ?>
    <div class="alert alert-success" data-dismiss="1"><i class="fa-solid fa-check-circle"></i> <?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error" data-dismiss="1"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
    <?php endif; ?>

    <!-- Profile avatar -->
    <div style="text-align:center;margin-bottom:2rem">
        <div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#b22234,#8b1a2a);display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:2.5rem;color:#fff;font-weight:900">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
        <div style="margin-top:.75rem;font-weight:800;color:#1e293b;font-size:1.2rem"><?= h($user['full_name']) ?></div>
        <div><span class="badge badge-<?= ['donor'=>'normal','patient'=>'medium','hospital'=>'accepted','admin'=>'crit-inv'][$role] ?? 'normal' ?>" style="margin-top:.4rem"><?= ucfirst(h($role)) ?></span></div>
    </div>

    <form method="POST" action="edit_profile.php">
        <div class="card"><div class="card-body">
            <h3 style="color:#8b1a2a;font-weight:800;margin-bottom:1.25rem"><i class="fa-solid fa-user"></i> Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name <span>*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= h($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?= h($user['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control form-select">
                        <?php foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $user['gender']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" class="form-control" value="<?= h($user['age'] ?? '') ?>" min="16" max="65">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= h($user['city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" class="form-control" value="<?= h($user['state'] ?? '') ?>">
                </div>
            </div>
        </div></div>

        <!-- Role-specific -->
        <?php if ($role === 'donor' && $roleData): ?>
        <div class="card" style="margin-top:1.25rem"><div class="card-body">
            <h3 style="color:#8b1a2a;font-weight:800;margin-bottom:1.25rem"><i class="fa-solid fa-heart-pulse"></i> Donor Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group" class="form-control form-select">
                        <?php foreach($bgs as $bg): ?>
                        <option value="<?= $bg ?>" <?= ($roleData['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Availability Status</label>
                    <select name="availability_status" class="form-control form-select">
                        <?php foreach(['available'=>'Available','unavailable'=>'Unavailable','temporarily_unavailable'=>'Temporarily Unavailable'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($roleData['availability_status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div></div>
        <?php elseif ($role === 'patient' && $roleData): ?>
        <div class="card" style="margin-top:1.25rem"><div class="card-body">
            <h3 style="color:#8b1a2a;font-weight:800;margin-bottom:1.25rem"><i class="fa-solid fa-bed"></i> Patient Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Blood Group Needed</label>
                    <select name="blood_group_needed" class="form-control form-select">
                        <?php foreach($bgs as $bg): ?>
                        <option value="<?= $bg ?>" <?= ($roleData['blood_group_needed'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hospital Name</label>
                    <input type="text" name="hospital_name" class="form-control" value="<?= h($roleData['hospital_name'] ?? '') ?>">
                </div>
            </div>
        </div></div>
        <?php elseif ($role === 'hospital' && $roleData): ?>
        <div class="card" style="margin-top:1.25rem"><div class="card-body">
            <h3 style="color:#8b1a2a;font-weight:800;margin-bottom:1.25rem"><i class="fa-solid fa-hospital"></i> Hospital Information</h3>
            <div class="form-group">
                <label>Hospital Name</label>
                <input type="text" name="hospital_name" class="form-control" value="<?= h($roleData['hospital_name'] ?? '') ?>">
            </div>
        </div></div>
        <?php endif; ?>

        <!-- Password Change -->
        <div class="card" style="margin-top:1.25rem"><div class="card-body">
            <h3 style="color:#8b1a2a;font-weight:800;margin-bottom:1.25rem"><i class="fa-solid fa-lock"></i> Change Password <small style="font-weight:400;color:#64748b;font-size:.85rem">(leave blank to keep current)</small></h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Current Password</label>
                    <div style="position:relative">
                        <input type="password" name="current_password" class="form-control" placeholder="Current password" style="padding-right:3rem">
                        <button type="button" class="password-toggle" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters">
                </div>
            </div>
        </div></div>

        <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem">
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>
</div>
<script src="../js/app.js"></script>
</body>
</html>
