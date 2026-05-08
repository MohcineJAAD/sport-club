<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/login.php");
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = trim($_POST['password'] ?? '');

if (empty($identifier) || empty($password)) {
    $_SESSION['error'] = "يرجى ملء جميع الحقول";
    header("Location: /sport-club/login.php");
    exit();
}

if (Auth::login($conn, $identifier, $password)) {
    header("Location: /sport-club/admin/dashboard.php");
} else {
    $_SESSION['error'] = "المعرف أو كلمة المرور غير صحيحة";
    header("Location: /sport-club/login.php");
}
exit();
