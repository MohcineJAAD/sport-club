<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/schedule.php");
    exit();
}

$grid     = $_POST['sport'] ?? [];
$schedule = new Schedule($conn);
$schedule->replaceAll($grid);

$_SESSION['message'] = "تم حفظ الجدول بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/schedule.php");
exit();