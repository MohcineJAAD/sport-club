<?php
require_once __DIR__ . '/../../config/database.php';
$club = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club['club_name'] ?? 'النادي الرياضي') ?></title>
    <link rel="stylesheet" href="/sport-club/assets/css/master1.css">
    <link rel="stylesheet" href="/sport-club/assets/css/normalize.css">
    <link rel="stylesheet" href="/sport-club/assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Work+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
<header class="header" id="header">
    <div class="container">
        <a href="/sport-club/public/index.php" class="logo">
            <img src="/sport-club/assets/images/<?= htmlspecialchars($club['logo'] ?? '') ?>" alt="logo">
        </a>
        <ul class="main-nav">
            <li><a href="/sport-club/public/index.php#hero">الرئيسية</a></li>
            <li><a href="/sport-club/public/index.php#about">حولنا</a></li>
            <li><a href="/sport-club/public/index.php#plans">الخطط</a></li>
            <li><a href="/sport-club/public/index.php#Horaire">المواعيد</a></li>
            <li><a href="/sport-club/login.php">تسجيل الدخول</a></li>
        </ul>
    </div>
</header>
