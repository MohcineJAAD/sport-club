<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$payment = new Payment($conn);

$nowMonth = (int)date('n');
$nowYear  = (int)date('Y');
$currentSeason = ($nowMonth >= 9 ? $nowYear : $nowYear - 1) . '-' . ($nowMonth >= 9 ? $nowYear + 1 : $nowYear);
$filterMonth   = date('Y-m');

$unpaid = $payment->getUnpaidWithAttendance($filterMonth);

$byType = [];
foreach ($unpaid as $m) {
    $byType[$m['sport_type']][] = $m;
}
ksort($byType);

$arabicMonths = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','غشت','شتنبر','أكتوبر','نونبر','دجنبر'];
[$fYear, $fMon] = explode('-', $filterMonth);
$monthLabel = ($arabicMonths[(int)$fMon] ?? $fMon) . ' ' . $fYear;

$adminRow = $conn->query("SELECT club_name, logo FROM admin LIMIT 1")->fetch_assoc();
$logoSrc  = !empty($adminRow['logo'])
    ? '/sport-club/assets/images/' . htmlspecialchars($adminRow['logo'])
    : '/sport-club/assets/images/logo officiel ASS CLUB SPORTIF-1.png';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>قائمة غير المدفوعين — <?= htmlspecialchars($monthLabel) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, 'Traditional Arabic', sans-serif; background: #eee; direction: rtl; }

.no-print {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: #333;
}
.btn {
    padding: 10px 28px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
    display: inline-block;
    font-family: inherit;
    color: #fff;
}
.btn-primary   { background: #203a85; }
.btn-secondary { background: #666; }
.no-print span { color: #ddd; font-size: 14px; }

.doc-sheet {
    width: 210mm;
    min-height: 297mm;
    margin: 10px auto;
    padding: 14mm 16mm 12mm;
    background: #fff;
    page-break-after: always;
    page-break-inside: avoid;
    font-size: 12pt;
}
.doc-sheet:last-child { page-break-after: avoid; }

.doc-header {
    text-align: center;
    border-bottom: 3px double #000;
    padding-bottom: 8px;
    margin-bottom: 12px;
}
.doc-logo {
    height: 130px;
    width: auto;
    object-fit: contain;
}

.doc-notice {
    border: 2px solid #000;
    padding: 10px 16px;
    font-size: 13pt;
    font-weight: bold;
    text-align: center;
    margin-bottom: 12px;
    background: #f5f5f5;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.doc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11pt;
}
.doc-table th, .doc-table td {
    border: 1px solid #000;
    padding: 6px 10px;
    text-align: center;
}
.doc-table thead tr {
    background: #d0d0d0;
    font-weight: bold;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.doc-table tbody tr:nth-child(even) {
    background: #f5f5f5;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.col-name { width: 70%; text-align: right !important; }
.col-id   { width: 30%; }

.empty-state {
    width: 210mm;
    margin: 30px auto;
    background: #fff;
    padding: 40px;
    text-align: center;
    font-size: 16px;
    color: #666;
    border-radius: 8px;
}

@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .doc-sheet { margin: 0; }
    @page { size: A4 portrait; margin: 0; }
}
</style>
</head>
<body>

<div class="no-print">
    <?php if (!empty($unpaid)): ?>
    <button class="btn btn-primary" onclick="window.print()">&#128438; طباعة / تنزيل PDF</button>
    <?php endif; ?>
    <a href="/sport-club/admin/payments.php" class="btn btn-secondary">&#8594; رجوع</a>
    <span>غير المدفوعين — <?= htmlspecialchars($monthLabel) ?> &nbsp;|&nbsp; العدد: <?= count($unpaid) ?></span>
</div>

<?php if (empty($unpaid)): ?>
    <div class="empty-state">لا يوجد مشتركون غير مدفوعين بعد 5 جلسات هذا الشهر 🎉</div>
<?php else: ?>
    <?php foreach ($byType as $sport => $members): ?>
    <div class="doc-sheet">

        <div class="doc-header">
            <img src="<?= $logoSrc ?>" alt="شعار" class="doc-logo">
        </div>

        <div class="doc-notice">
            تذكير: المرجو من المنخرطين المذكورة أسماؤهم في اللائحة أداء واجبهم الشهري
        </div>

        <table class="doc-table">
            <thead>
                <tr>
                    <th class="col-id">المعرف</th>
                    <th class="col-name">الاسم الكامل</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                <tr>
                    <td class="col-id"><?= htmlspecialchars($m['identifier']) ?></td>
                    <td class="col-name"><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
