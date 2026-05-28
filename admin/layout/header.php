<?php
$current_page = basename($_SERVER['PHP_SELF']);
$plan = new Plan($conn);
$club = $conn->query("SELECT logo, club_name FROM admin LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="/sport-club/assets/css/framework.css">
    <link rel="stylesheet" href="/sport-club/assets/css/dashbord.css">
    <link rel="stylesheet" href="/sport-club/assets/css/master1.css">
    <link rel="stylesheet" href="/sport-club/assets/css/normalize.css">
    <link rel="stylesheet" href="/sport-club/assets/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body dir="rtl">
<div class="page d-flex">

    <!-- Sidebar -->
    <div class="sidebar bg-fff p-20 p-relative">
        <div class="profile-header">
            <img src="/sport-club/assets/images/<?= htmlspecialchars($club['logo']) ?>" alt="logo" class="profile-image m-0 mr-10">
            <div class="profile-info">
                <h3 class="p-relative txt-c mt-0"><?= htmlspecialchars($club['club_name']) ?></h3>
            </div>
        </div>
        <ul>
            <li>
                <a href="/sport-club/admin/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-chart-simple fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">الرئيسية</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/adherents.php" class="<?= $current_page == 'adherents.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-user fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">المشتركين</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/payments.php" class="<?= $current_page == 'payments.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-money-bill ml-5"></i>
                    <span class="fs-14 ml-10">الدفع</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/evaluations.php" class="<?= $current_page == 'evaluations.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-star fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">التقييم</span>
                </a>
            </li>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/exam.php" class="<?= $current_page == 'exam.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-graduation-cap fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">الامتحان</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/absence.php" class="<?= $current_page == 'absence.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-calendar-xmark fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">الغياب</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/schedule.php" class="<?= $current_page == 'schedule.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-calendar-days ml-5"></i>
                    <span class="fs-14 ml-10">الجدول الزمني</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/plans.php" class="<?= $current_page == 'plans.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-credit-card fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">الخطط</span>
                </a>
            </li>
            <li>
                <a href="/sport-club/admin/settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?> d-flex align-c fs-14 color-000 rad-6 p-10">
                    <i class="fa-solid fa-gear fa-fw ml-5"></i>
                    <span class="fs-14 ml-10">الإعدادات</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main content -->
    <div class="content w-full">
        <div class="head p-15 between-flex">
            <h2 class="welcomUser">مرحبا, <?= htmlspecialchars(Auth::user()) ?></h2>
            <a href="/sport-club/actions/logout.php" class="d-flex align-c fs-14 color-000 rad-6 p-10">
                <i class="fa-solid fa-right-from-bracket fa-fw"></i>
                <span class="fs-14 ml-10">خروج</span>
            </a>
        </div>
