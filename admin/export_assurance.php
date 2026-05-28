<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
$year = (int)explode('-', $month)[0];

$payment = new Payment($conn);
$members = $payment->getUnpaidByYearAndType($year, 'assurance');
$club    = $conn->query("SELECT club_name FROM admin LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>غير المؤمَّنين - <?= $year ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; direction: rtl; padding: 30px; color: #333; font-size: 14px; line-height: 1.6; }
        .no-print { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
        .btn { padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #203a85; color: white; }
        .btn-secondary { background: #666; color: white; }
        .report-header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #203a85; padding-bottom: 15px; }
        .report-header h2 { color: #203a85; font-size: 22px; margin-bottom: 6px; }
        .report-header h3 { color: #555; font-size: 16px; font-weight: normal; }
        .report-meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 14px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #203a85; color: white; font-weight: bold; }
        tbody tr:nth-child(even) { background-color: #f8f8f8; }
        tbody tr:hover { background-color: #eef2ff; }
        tfoot td { background-color: #203a85; color: white; font-weight: bold; }
        .empty { text-align: center; padding: 30px; color: #27ae60; font-size: 16px; border: 2px solid #27ae60; border-radius: 5px; margin-top: 10px; }

        @page {
            margin: 0;
            size: A4;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()">🖨 طباعة / تنزيل PDF</button>
        <a href="/sport-club/admin/payments.php" class="btn btn-secondary">→ رجوع</a>
    </div>

    <div class="report-header">
        <h2><?= htmlspecialchars($club['club_name'] ?? '') ?></h2>
        <h3>قائمة غير المؤمَّنين - <?= $year ?></h3>
    </div>

    <div class="report-meta">
        <span>عدد غير المؤمَّنين: <strong><?= count($members) ?></strong></span>
        <span>تاريخ الطباعة: <strong><?= date('d/m/Y') ?></strong></span>
    </div>

    <?php if (!empty($members)): ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم الكامل</th>
                <th>المعرف</th>
                <th>الرياضة</th>
                <th>ولي الأمر</th>
                <th>الهاتف</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $i => $m): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                <td><?= htmlspecialchars($m['identifier']) ?></td>
                <td><?= htmlspecialchars($m['sport_type']) ?></td>
                <td><?= htmlspecialchars($m['guardian_name']) ?></td>
                <td><?= htmlspecialchars($m['guardian_phone']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="6">المجموع: <?= count($members) ?> مشترك لم يؤدِ التأمين</td></tr>
        </tfoot>
    </table>
    <?php else: ?>
    <p class="empty">✓ جميع المشتركين أدوا التأمين السنوي</p>
    <?php endif; ?>
</body>
</html>