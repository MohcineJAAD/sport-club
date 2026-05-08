<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/evaluations.php");
    exit();
}

$identifier  = trim($_POST['identifier']  ?? '');
$month       = (int) ($_POST['month']       ?? 0);
$year        = (int) ($_POST['year']        ?? 0);
$discipline  = (int) ($_POST['discipline']  ?? 0);
$performance = (int) ($_POST['performance'] ?? 0);
$behavior    = (int) ($_POST['behavior']    ?? 0);

if (empty($identifier) || !$month || !$year) {
    $_SESSION['message'] = "يرجى ملء جميع الحقول";
    $_SESSION['status']  = "error";
    header("Location: /sport-club/admin/evaluations.php");
    exit();
}

$evaluation = new Evaluation($conn);
$evaluation->save($identifier, $month, $year, $discipline, $performance, $behavior);

$_SESSION['message'] = "تم حفظ التقييم بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/evaluations.php");
exit();
