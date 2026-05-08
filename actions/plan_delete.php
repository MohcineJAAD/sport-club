<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $plan = new Plan($conn);
    $plan->delete($id);
    $_SESSION['message'] = "تم حذف الخطة بنجاح";
    $_SESSION['status']  = "success";
}

header("Location: /sport-club/admin/plans.php");
exit();
