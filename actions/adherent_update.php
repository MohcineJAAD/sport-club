<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/adherents.php");
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$data = [
    'nom'                   => trim($_POST['nom']                   ?? ''),
    'prenom'                => trim($_POST['prenom']                ?? ''),
    'date_naissance'        => trim($_POST['date_naissance']        ?? ''),
    'poids'                 => (float)($_POST['poids']              ?? 0),
    'type'                  => trim($_POST['type']                  ?? ''),
    'guardian_name'         => trim($_POST['guardian_name']         ?? ''),
    'guardian_phone'        => trim($_POST['guardian_phone']        ?? ''),
    'address'               => trim($_POST['address']               ?? ''),
    'health_status'         => trim($_POST['health_status']         ?? ''),
    'blood_type'            => trim($_POST['blood_type']            ?? ''),
    'current_belt'          => trim($_POST['current_belt']          ?? ''),
];

$adherent = new Adherent($conn);
$adherent->update($identifier, $data);

$_SESSION['message'] = "تم تحديث الملف الشخصي بنجاح";
$_SESSION['status']  = "success";
header("Location: /sport-club/admin/profile.php?id=" . urlencode($identifier));
exit();
