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
$assurance  = isset($_POST['assurance']);
$adhesion   = isset($_POST['adhesion']);

if (empty($identifier)) {
    $_SESSION['message'] = 'معرف المشترك غير صالح';
    $_SESSION['status']  = 'error';
    header("Location: /sport-club/admin/payments.php");
    exit();
}

// Build monthAmounts: only include months with a positive cash amount
$rawAmounts   = $_POST['amounts'] ?? [];
$monthAmounts = [];
foreach ($rawAmounts as $m => $amt) {
    $amt = (float)$amt;
    if ($amt > 0) {
        $monthAmounts[(int)$m] = $amt;
    }
}

$payment = new Payment($conn);
$payment->saveCard($identifier, $year, $monthAmounts, $assurance, $adhesion);

$_SESSION['message'] = 'تم حفظ المدفوعات بنجاح';
$_SESSION['status']  = 'success';
header("Location: /sport-club/admin/payment_card.php?id=" . urlencode($identifier) . "&year={$year}");
exit();
