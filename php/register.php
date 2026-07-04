<?php
declare(strict_types=1);
session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
require_once __DIR__ . '/../db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName       = trim($_POST['full_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirmPass    = $_POST['confirm_password'] ?? '';
    $role           = $_POST['role'] ?? '';
    $gender         = $_POST['gender'] ?? 'other';
    $age            = (int)($_POST['age'] ?? 0) ?: null;
    $city           = trim($_POST['city'] ?? '');
    $state          = trim($_POST['state'] ?? '');

    $bloodGroup     = $_POST['blood_group'] ?? '';
    $bloodGroupNeeded = $_POST['blood_group_needed'] ?? '';
    $hospitalName   = trim($_POST['hospital_name'] ?? '');
    $licenseNumber  = trim($_POST['license_number'] ?? '');

    $validRoles = ['donor', 'patient', 'hospital', 'admin'];
    $validBGs   = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];

    if (!$fullName || !$email || !$password || !$role) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPass) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $validRoles, true)) {
        $error = 'Please select a valid role.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email address is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, gender, age, city, state, status)
                VALUES (?,?,?,?,?,?,?,?,?,'active')")
                ->execute([$fullName, $email, $phone ?: null, $hash, $role, $gender, $age, $city ?: null, $state ?: null]);
            $userId = (int)$pdo->lastInsertId();

            if ($role === 'donor' && in_array($bloodGroup, $validBGs, true)) {
                $pdo->prepare("INSERT INTO donor_profiles (user_id, blood_group, availability_status) VALUES (?,?,'available')")
                    ->execute([$userId, $bloodGroup]);
            } elseif ($role === 'patient' && in_array($bloodGroupNeeded, $validBGs, true)) {
                $pdo->prepare("INSERT INTO patient_profiles (user_id, blood_group_needed, hospital_name) VALUES (?,?,?)")
                    ->execute([$userId, $bloodGroupNeeded, $hospitalName ?: null]);
            } elseif ($role === 'hospital' && $hospitalName && $licenseNumber) {
                $pdo->prepare("INSERT INTO hospital_profiles (user_id, hospital_name, license_number, city, state) VALUES (?,?,?,?,?)")
                    ->execute([$userId, $hospitalName, $licenseNumber, $city ?: null, $state ?: null]);
            } elseif ($role === 'admin') {
                $adminId = 'ADM-' . time();
                $pdo->prepare("INSERT INTO admin_profiles (user_id, admin_id, access_level) VALUES (?,?,'operations')")
                    ->execute([$userId, $adminId]);
            }

            redirect('login.php?registered=1');
        }
    }
}

$preRole = $_GET['role'] ?? ($_POST['role'] ?? '');
$bgs = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LifeFlow</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-page" style="padding:2rem 1rem">
<div class="auth-card wide" style="max-width:860px">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="fa-solid fa-user-plus"></i></div>
        <h1>Join LifeFlow</h1>
        <p>Create your account and start saving lives</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" data-dismiss="1"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registerForm">
        <!-- Role Selector -->
        <div style="margin-bottom:1.5rem">
            <label style="display:block;font-weight:700;color:#8b1a2a;margin-bottom:.75rem;font-size:.95rem">I am a... <span style="color:#dc2626">*</span></label>
            <div class="role-grid">
                <?php
                $roles = [
                    ['donor',    'fa-heart-pulse',  'Donor',    'I want to donate blood'],
                    ['patient',  'fa-bed',           'Patient',  'I need blood'],
                    ['hospital', 'fa-hospital',      'Hospital', 'We manage donations'],
                    ['admin',    'fa-gear',          'Admin',    'Platform administrator'],
                ];
                foreach ($roles as [$r, $icon, $label, $desc]): ?>
                <div class="role-option <?= $preRole === $r ? 'selected' : '' ?>" data-role="<?= $r ?>">
                    <i class="fa-solid <?= $icon ?>"></i>
                    <span><?= $label ?></span>
                    <small><?= $desc ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="role" id="roleInput" value="<?= h($preRole) ?>">
        </div>

        <!-- Basic Info -->
        <div class="form-row">
            <div class="form-group">
                <label>Full Name <span>*</span></label>
                <input type="text" name="full_name" class="form-control" placeholder="Your full name" value="<?= h($_POST['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address <span>*</span></label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= h($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control form-select">
                    <?php foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($_POST['gender'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Age</label>
                <input type="number" name="age" class="form-control" placeholder="e.g. 25" min="16" max="65" value="<?= h($_POST['age'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="form-control" placeholder="Your city" value="<?= h($_POST['city'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>State</label>
            <input type="text" name="state" class="form-control" placeholder="Your state" value="<?= h($_POST['state'] ?? '') ?>">
        </div>

        <!-- Donor-specific -->
        <div class="role-fields" data-role="donor" style="display:<?= ($preRole === 'donor' || !$preRole) ? 'block' : 'none' ?>">
            <hr style="border:1px solid #fde8e8;margin:1.25rem 0">
            <div class="form-group">
                <label>Your Blood Group <span>*</span></label>
                <select name="blood_group" class="form-control form-select">
                    <option value="">Select blood group</option>
                    <?php foreach($bgs as $bg): ?>
                    <option value="<?= $bg ?>" <?= ($_POST['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Patient-specific -->
        <div class="role-fields" data-role="patient" style="display:<?= $preRole === 'patient' ? 'block' : 'none' ?>">
            <hr style="border:1px solid #fde8e8;margin:1.25rem 0">
            <div class="form-row">
                <div class="form-group">
                    <label>Blood Group Needed <span>*</span></label>
                    <select name="blood_group_needed" class="form-control form-select">
                        <option value="">Select blood group</option>
                        <?php foreach($bgs as $bg): ?>
                        <option value="<?= $bg ?>" <?= ($_POST['blood_group_needed'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hospital Name</label>
                    <input type="text" name="hospital_name" class="form-control" placeholder="Hospital where admitted" value="<?= h($_POST['hospital_name'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Hospital-specific -->
        <div class="role-fields" data-role="hospital" style="display:<?= $preRole === 'hospital' ? 'block' : 'none' ?>">
            <hr style="border:1px solid #fde8e8;margin:1.25rem 0">
            <div class="form-row">
                <div class="form-group">
                    <label>Hospital Name <span>*</span></label>
                    <input type="text" name="hospital_name" class="form-control" placeholder="Official hospital name" value="<?= h($_POST['hospital_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>License Number <span>*</span></label>
                    <input type="text" name="license_number" class="form-control" placeholder="MH-HOSP-2024-XXX" value="<?= h($_POST['license_number'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Password -->
        <hr style="border:1px solid #fde8e8;margin:1.25rem 0">
        <div class="form-row">
            <div class="form-group">
                <label>Password <span>*</span></label>
                <div style="position:relative">
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required style="padding-right:3rem">
                    <button type="button" class="password-toggle" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password <span>*</span></label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:.5rem">
            <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
    </form>

    <div style="text-align:center;margin-top:1.25rem">
        <p style="color:#64748b;font-size:.93rem">Already have an account? <a href="login.php" style="color:#b22234;font-weight:700">Sign In</a></p>
        <p style="margin-top:.5rem"><a href="index.php" style="color:#64748b;font-size:.88rem"><i class="fa-solid fa-arrow-left"></i> Back to Home</a></p>
    </div>
</div>
<script src="../js/app.js"></script>
</body>
</html>
