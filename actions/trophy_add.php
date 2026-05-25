<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/adherents.php");
    exit();
}

$identifier  = trim($_POST['identifier']  ?? '');
$description = trim($_POST['description'] ?? '');

if ($identifier && $description) {
    $stmt = $conn->prepare("INSERT INTO trophies (adherent_id, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $identifier, $description);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = 'تمت إضافة اللقب بنجاح';
    $_SESSION['status']  = 'success';
}

header("Location: /sport-club/admin/profile.php?id=" . urlencode($identifier));
exit();
