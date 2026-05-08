<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/absence.php");
    exit();
}

$present = $_POST['present'] ?? [];
$date    = $_POST['date'] ?? date('Y-m-d');

if (!empty($present)) {
    $attendance = new Attendance($conn);
    $attendance->save($present, $date);
    $_SESSION['message'] = "تم حفظ الحضور بنجاح";
    $_SESSION['status']  = "success";
} else {
    $_SESSION['message'] = "لم يتم تحديد أي مشترك";
    $_SESSION['status']  = "error";
}

header("Location: /sport-club/admin/absence.php");
exit();
