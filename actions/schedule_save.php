<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/schedule.php");
    exit();
}

$day        = trim($_POST['day']        ?? '');
$timeslot   = trim($_POST['timeslot']   ?? '');
$sport_type = trim($_POST['sport_type'] ?? '');

if (empty($day) || empty($timeslot) || empty($sport_type)) {
    $_SESSION['message'] = "يرجى ملء جميع الحقول";
    $_SESSION['status']  = "error";
    header("Location: /sport-club/admin/schedule.php");
    exit();
}

$schedule = new Schedule($conn);
$schedule->add($day, $timeslot, $sport_type);

$_SESSION['message'] = "تمت إضافة الحصة بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/schedule.php");
exit();
