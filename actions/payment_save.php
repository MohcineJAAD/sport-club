<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$amount     = (float) ($_POST['amount'] ?? 0);
$type       = trim($_POST['type'] ?? '');

if (empty($identifier) || $amount <= 0 || empty($type)) {
    $_SESSION['message'] = "يرجى ملء جميع الحقول بشكل صحيح";
    $_SESSION['status']  = "error";
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$payment = new Payment($conn);
$payment->save($identifier, $amount, $type);

$_SESSION['message'] = "تم حفظ الدفعة بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/payments.php");
exit();