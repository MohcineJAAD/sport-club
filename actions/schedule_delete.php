<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $schedule = new Schedule($conn);
    $schedule->delete($id);
    $_SESSION['message'] = "تم حذف الحصة بنجاح";
    $_SESSION['status']  = "success";
}

header("Location: /sport-club/admin/schedule.php");
exit();
