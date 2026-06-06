<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');

// Accept season=YYYY-YYYY (new) or year=YYYY (legacy)
$season = trim($_POST['season'] ?? '');
if (!$season && !empty($_POST['year'])) {
    $y      = (int)$_POST['year'];
    $season = $y . '-' . ($y + 1);
}
$year = $season ? (int)explode('-', $season)[0] : (int)date('Y');
if (!$season) $season = $year . '-' . ($year + 1);

if (empty($identifier)) {
    $_SESSION['message'] = 'معرف المشترك غير صالح';
    $_SESSION['status']  = 'error';
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$monthAmounts = [];
foreach ($_POST['amounts'] ?? [] as $m => $amt) {
    $monthAmounts[(int)$m] = (float)$amt;
}

$monthDueAmounts = [];
foreach ($_POST['due_amounts'] ?? [] as $m => $amt) {
    $monthDueAmounts[(int)$m] = (float)$amt;
}

$assuranceAmount = (float)($_POST['assurance_amount'] ?? 0);
$assuranceDue    = (float)($_POST['assurance_due']    ?? 0);
$adhesionAmount  = (float)($_POST['adhesion_amount']  ?? 0);
$adhesionDue     = (float)($_POST['adhesion_due']     ?? 0);
$examJanAmount   = (float)($_POST['exam_jan_amount']  ?? 0);
$examJanDue      = (float)($_POST['exam_jan_due']     ?? 0);
$examJunAmount   = (float)($_POST['exam_jun_amount']  ?? 0);
$examJunDue      = (float)($_POST['exam_jun_due']     ?? 0);

$payment = new Payment($conn);
$payment->saveCard(
    $identifier,
    $year,
    $monthAmounts,
    $monthDueAmounts,
    $assuranceAmount,
    $assuranceDue,
    $adhesionAmount,
    $adhesionDue,
    $examJanAmount,
    $examJanDue,
    $examJunAmount,
    $examJunDue
);

$_SESSION['message'] = 'تم حفظ المدفوعات بنجاح';
$_SESSION['status']  = 'success';
header("Location: /sport-club/admin/payment_card.php?id=" . urlencode($identifier) . "&season={$season}");
exit();