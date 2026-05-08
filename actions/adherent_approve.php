<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = $_GET['id'] ?? '';
if ($id) {
    $adhrent = new Adherent($conn);
    $adhrent->approve($id);
    $_SESSION['success'] = "تم قبول المشترك بنجاح";
    $_SESSION['status']  = "success";
}

header("Location: /sport-club/admin/adherents.php");
exit();