<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id     = (int)($_GET['id']     ?? 0);
$member = trim($_GET['member']  ?? '');

if ($id && $member) {
    $stmt = $conn->prepare("DELETE FROM trophies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = 'تم حذف اللقب';
    $_SESSION['status']  = 'success';
}

header("Location: /sport-club/admin/profile.php?id=" . urlencode($member));
exit();
