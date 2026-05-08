<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = $_GET['id'] ?? '';
if ($id) {
    $adherent = new Adherent($conn);
    $adherent->reject($id);
    $_SESSION['message'] = "تم رفض المشترك";
    $_SESSION['status']  = "error";
}

header("Location: /sport-club/admin/adherents.php");
exit();