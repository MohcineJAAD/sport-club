<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// require_once '../vendor/autoload.php';
// ... rest of file
require_once '../vendor/autoload.php';
require_once '../config/database.php';

Auth::check();

$identifier = $_POST['identifier'] ?? '';
if (!$identifier) {
    header('Location: ../admin/adherents.php');
    exit;
}

$data = [
    'nom'                    => trim($_POST['nom']                   ?? ''),
    'prenom'                 => trim($_POST['prenom']                ?? ''),
    'date_naissance'         => trim($_POST['date_naissance']        ?? ''),
    'date_adhesion'          => trim($_POST['date_adhesion']         ?? ''),
    'poids'                  => trim($_POST['poids']                 ?? '0'),
    'guardian_name'          => trim($_POST['guardian_name']         ?? ''),
    'guardian_phone'         => trim($_POST['guardian_phone']        ?? ''),
    'second_guardian_phone'  => trim($_POST['second_guardian_phone'] ?? ''),
    'address'                => trim($_POST['address']               ?? ''),
    'health_status'          => trim($_POST['health_status']         ?? ''),
    'blood_type'             => trim($_POST['blood_type']            ?? ''),
    'current_belt'           => trim($_POST['current_belt']          ?? ''),
    'next_belt'              => trim($_POST['next_belt']             ?? ''),
    'licence'                => trim($_POST['licence']               ?? ''),
    'note'                   => trim($_POST['note']                  ?? ''),
    'BC_path'                => '',
];

// Empty date strings → null (avoids NOT NULL constraint violation)
if ($data['date_naissance'] === '') $data['date_naissance'] = null;
if ($data['date_adhesion']  === '') $data['date_adhesion']  = null;

$imagePath = '';

// Profile image upload
if (!empty($_FILES['imageUpload']['name'])) {
    $ext      = pathinfo($_FILES['imageUpload']['name'], PATHINFO_EXTENSION);
    $filename = 'img_' . $identifier . '.' . $ext;
    $dest     = '../assets/uploads/' . $filename;
    if (move_uploaded_file($_FILES['imageUpload']['tmp_name'], $dest)) {
        $imagePath = $filename;
    }
}

// Birth certificate / contract upload
if (!empty($_FILES['BCUpload']['name'])) {
    $ext      = pathinfo($_FILES['BCUpload']['name'], PATHINFO_EXTENSION);
    $filename = 'bc_' . $identifier . '.' . $ext;
    $dest     = '../assets/uploads/' . $filename;
    if (move_uploaded_file($_FILES['BCUpload']['tmp_name'], $dest)) {
        $data['BC_path'] = $filename;
    }
}

$adherent = new Adherent($conn);
try {
    $adherent->update($identifier, $data, $imagePath);
    header('Location: ../admin/profile.php?id=' . urlencode($identifier) . '&status=success');

} catch (\Throwable $th) {
    header('Location: ../admin/profile.php?id=' . urlencode($identifier) . '&status=error');
}
exit;