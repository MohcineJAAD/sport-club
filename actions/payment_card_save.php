<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$year       = (int)($_POST['year']       ?? date('Y'));
$months     = array_map('intval', $_POST['months'] ?? []);
$assurance  = isset($_POST['assurance']);
$adhesion   = isset($_POST['adhesion']);

if (empty($identifier)) {
    $_SESSION['message'] = 'معرف المشترك غير صالح';
    $_SESSION['status']  = 'error';
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$payment = new Payment($conn);
$payment->saveCard($identifier, $year, $months, $assurance, $adhesion);

$_SESSION['message'] = 'تم حفظ المدفوعات بنجاح';
$_SESSION['status']  = 'success';
header("Location: /sport-club/admin/payment_card.php?id=" . urlencode($identifier) . "&year={$year}");
exit();
