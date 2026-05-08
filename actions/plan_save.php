<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/plans.php");
    exit();
}

$name       = trim($_POST['name']        ?? '');
$price      = (float) ($_POST['price']      ?? 0);
$description= trim($_POST['description'] ?? '');
$assurance  = (float) ($_POST['assurance']  ?? 0);
$adherence  = (float) ($_POST['adherence']  ?? 0);

if (empty($name) || $price <= 0) {
    $_SESSION['message'] = "يرجى ملء جميع الحقول بشكل صحيح";
    $_SESSION['status']  = "error";
    header("Location: /sport-club/admin/plans.php");
    exit();
}

$plan = new Plan($conn);
$plan->add($name, $price, $description, $assurance, $adherence);

$_SESSION['message'] = "تمت إضافة الخطة بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/plans.php");
exit();
