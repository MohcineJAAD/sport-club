<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = $_GET['id'] ?? '';
if ($id) {
    $adherent = new Adherent($conn);
    $adherent->delete($id);
    $_SESSION['message'] = "تم حذف المشترك بنجاح";
    $_SESSION['status']  = "success";
}

header("Location: /sport-club/admin/adherents.php");
exit();
