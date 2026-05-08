<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/public/sign_up.php");
    exit();
}

$data = [
    'nom'                   => trim($_POST['nom']                   ?? ''),
    'prenom'                => trim($_POST['prenom']                ?? ''),
    'date_naissance'        => trim($_POST['date_naissance']        ?? ''),
    'poids'                 => (float)($_POST['poids']              ?? 0),
    'type'                  => trim($_POST['type']                  ?? ''),
    'guardian_name'         => trim($_POST['guardian_name']         ?? ''),
    'guardian_phone'        => trim($_POST['guardian_phone']        ?? ''),
    'second_guardian_phone' => trim($_POST['second_guardian_phone'] ?? ''),
    'address'               => trim($_POST['address']               ?? ''),
    'health_status'         => trim($_POST['health_status']         ?? ''),
    'blood_type'            => trim($_POST['blood_type']            ?? ''),
    'current_belt'          => trim($_POST['current_belt']          ?? ''),
];

if (empty($data['nom']) || empty($data['prenom']) || empty($data['date_naissance']) || empty($data['type'])) {
    $_SESSION['message']  = "يرجى ملء جميع الحقول الإلزامية";
    $_SESSION['status']   = "error";
    $_SESSION['formData'] = $data;
    header("Location: /sport-club/public/sign_up.php");
    exit();
}

$imagePath = '';
if (!empty($_FILES['imageUpload']['name'])) {
    $ext       = pathinfo($_FILES['imageUpload']['name'], PATHINFO_EXTENSION);
    $imagePath = uniqid('img_') . '.' . $ext;
    move_uploaded_file($_FILES['imageUpload']['tmp_name'], __DIR__ . '/../assets/uploads/' . $imagePath);
}

$adherent   = new Adherent($conn);
$identifier = $adherent->create($data, $imagePath, '');

$_SESSION['new_member'] = ['identifier' => $identifier];
header("Location: /sport-club/public/index.php");
exit();
