<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$identifier      = trim($_POST['identifier']      ?? '');
$year            = (int)($_POST['year']            ?? date('Y'));
$assuranceAmount = (float)($_POST['assurance_amount'] ?? 0);
$adhesionAmount  = (float)($_POST['adhesion_amount']  ?? 0);
$examJanAmount   = (float)($_POST['exam_jan_amount']  ?? 0);
$examJunAmount   = (float)($_POST['exam_jun_amount']  ?? 0);

if (empty($identifier)) {
    $_SESSION['message'] = 'معرف المشترك غير صالح';
    $_SESSION['status']  = 'error';
    header("Location: /sport-club/admin/payments.php");
    exit();
}

// Build month amounts: only include months where the recorded price > 0
$monthAmounts = [];
foreach ($_POST['amounts'] ?? [] as $m => $amt) {
    $amt = (float)$amt;
    if ($amt > 0) {
        $monthAmounts[(int)$m] = $amt;
    }
}

$payment = new Payment($conn);
$payment->saveCard(
    $identifier,
    $year,
    $monthAmounts,
    $assuranceAmount,
    $adhesionAmount,
    $examJanAmount,
    $examJunAmount
);

$_SESSION['message'] = 'تم حفظ المدفوعات بنجاح';
$_SESSION['status']  = 'success';
header("Location: /sport-club/admin/payment_card.php?id=" . urlencode($identifier) . "&year={$year}");
exit();
