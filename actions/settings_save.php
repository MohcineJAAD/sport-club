<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/settings.php");
    exit();
}

$clubName  = trim($_POST['club_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$address   = trim($_POST['address']    ?? '');
$facebook  = trim($_POST['facebook']   ?? '');
$instagram = trim($_POST['instagram']  ?? '');
$twitter   = trim($_POST['twitter']    ?? '');

$logoName = null;
if (!empty($_FILES['logo']['name'])) {
    $ext      = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $logoName = uniqid('logo_') . '.' . $ext;
    move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../assets/images/' . $logoName);
}

if ($logoName) {
    $stmt = $conn->prepare("UPDATE admin SET club_name=?, email=?, phone=?, address=?, facebook=?, instagram=?, twitter=?, logo=?");
    $stmt->bind_param("ssssssss", $clubName, $email, $phone, $address, $facebook, $instagram, $twitter, $logoName);
} else {
    $stmt = $conn->prepare("UPDATE admin SET club_name=?, email=?, phone=?, address=?, facebook=?, instagram=?, twitter=?");
    $stmt->bind_param("sssssss", $clubName, $email, $phone, $address, $facebook, $instagram, $twitter);
}
$stmt->execute();
$stmt->close();

$newPassword     = $_POST['new_password']     ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!empty($newPassword)) {
    if ($newPassword === $confirmPassword) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE admin SET password=?");
        $stmt->bind_param("s", $hashed);
        $stmt->execute();
        $stmt->close();
    } else {
        $_SESSION['message'] = "كلمتا المرور غير متطابقتين";
        $_SESSION['status']  = "error";
        header("Location: /sport-club/admin/settings.php");
        exit();
    }
}

$_SESSION['message'] = "تم حفظ الإعدادات بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/settings.php");
exit();
