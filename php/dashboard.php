<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$role = $_SESSION['user_role'] ?? '';
switch ($role) {
    case 'donor':    header('Location: donor_dashboard.php'); break;
    case 'patient':  header('Location: patient_dashboard.php'); break;
    case 'hospital': header('Location: hospital_dashboard.php'); break;
    case 'admin':    header('Location: admin_dashboard.php'); break;
    default:         header('Location: index.php');
}
exit;