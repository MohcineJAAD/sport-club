<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$payment  = new Payment($conn);
$list     = $payment->getUnpaidMonthData();

$nowYear  = (int)date('Y');
$nowMonth = (int)date('n');
$arabicMonths = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','غشت','شتنبر','أكتوبر','نونبر','دجنبر'];
$monthLabel   = $arabicMonths[$nowMonth] . ' ' . $nowYear;
$grandTotal   = array_sum(array_column($list, 'total_rest'));

function tagList(array $issues, array $types, bool $useRest = false): string {
    $out = [];
    foreach ($issues as $i) {
        if (!in_array($i['type'], $types)) continue;
        $amount = $useRest
            ? ($i['paid'] > 0 ? $i['rest'] : $i['due'])
            : ($i['rest'] ?? $i['due'] ?? 0);
        $out[] = htmlspecialchars($i['label']) . ' <span class="amt">(' . number_format((float)$amount, 2) . ')</span>';
    }
    if (empty($out)) return '<span class="none">—</span>';
    $html = '<ul>';
    foreach ($out as $item) $html .= '<li>' . $item . '</li>';
    return $html . '</ul>';
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<title></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

@page {
    size: A4 landscape;
    margin: 10mm;
}

.toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
    justify-content: flex-start;
}
.toolbar button, .toolbar a {
    padding: 7px 18px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-print { background: #1e3a8a; color: #fff; }
.btn-back  { background: #e5e7eb; color: #111; }

@media print {
    .toolbar { display: none; }
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 10.5px;
    direction: rtl;
    color: #111;
    background: #fff;
    padding: 8mm;
}

.header {
    text-align: center;
    margin-bottom: 10px;
}
.header h2 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 3px;
}
.header .meta {
    font-size: 11px;
    color: #555;
}
.header .meta strong { color: #111; }

table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

th {
    background: #1e3a8a;
    color: #fff;
    padding: 6px 4px;
    text-align: center;
    font-size: 10px;
    border: 1px solid #1e3a8a;
}

td {
    border: 1px solid #d1d5db;
    padding: 5px 4px;
    vertical-align: top;
    font-size: 10px;
}

tr:nth-child(even) td { background: #f5f7ff; }

.col-name    { width: 13%; }
.col-id      { width: 8%; text-align: center; }
.col-phone   { width: 9%; text-align: center; }
.col-unpaid  { width: 13%; }
.col-partial { width: 13%; }
.col-ass     { width: 10%; }
.col-adh     { width: 10%; }
.col-events  { width: 13%; }
.col-total   { width: 8%; text-align: center; font-weight: 700; }

ul {
    list-style: disc;
    padding-right: 14px;
    margin: 0;
}
ul li { margin-bottom: 2px; line-height: 1.4; }
.amt  { color: #b45309; font-size: 9.5px; }
.none { color: #aaa; }

.footer-row td {
    background: #f3f4f6;
    font-weight: 700;
    border-top: 2px solid #111;
    text-align: center;
    color: #111;
}

@media print {
    @page { margin: 0; }

}
</style>
</head>
<body>

<div class="toolbar">
    <button class="btn-print" onclick="window.print()">&#128438; طبع</button>
    <a class="btn-back" href="/sport-club/admin/unpaid_month.php">&#8594; رجوع</a>
</div>

<div class="header">
    <h2>غير المدفوعين — <?= htmlspecialchars($monthLabel) ?></h2>
    <div class="meta">
        إجمالي غير المدفوعين: <strong style="color:#1e3a8a"><?= count($list) ?></strong>
        &nbsp;|&nbsp;
        إجمالي المبلغ المستحق: <strong><?= number_format($grandTotal, 2) ?> DH</strong>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="col-name">الاسم الكامل</th>
            <th class="col-id">المعرف</th>
            <th class="col-phone">الهاتف</th>
            <th class="col-unpaid">الأشهر غير المدفوعة</th>
            <th class="col-partial">الأشهر الناقصة</th>
            <th class="col-ass">التأمين</th>
            <th class="col-adh">الانخراط</th>
            <th class="col-events">الامتحانات / البطولات</th>
            <th class="col-total">المجموع المستحق</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($list as $adh): ?>
        <tr>
            <td class="col-name"><?= htmlspecialchars($adh['prenom'] . ' ' . $adh['nom']) ?></td>
            <td class="col-id"><?= htmlspecialchars($adh['identifier']) ?></td>
            <td class="col-phone">
                <?php if (!empty($adh['guardian_phone'])): ?>
                    <?= htmlspecialchars($adh['guardian_phone']) ?>
                <?php endif; ?>
                <?php if (!empty($adh['second_guardian_phone'])): ?>
                    <br><?= htmlspecialchars($adh['second_guardian_phone']) ?>
                <?php endif; ?>
                <?php if (empty($adh['guardian_phone']) && empty($adh['second_guardian_phone'])): ?>
                    <span class="none">—</span>
                <?php endif; ?>
            </td>
            <td class="col-unpaid"><?= tagList($adh['issues'], ['month_unpaid']) ?></td>
            <td class="col-partial"><?= tagList($adh['issues'], ['month_partial']) ?></td>
            <td class="col-ass"><?= tagList($adh['issues'], ['assurance'], true) ?></td>
            <td class="col-adh"><?= tagList($adh['issues'], ['adhesion'], true) ?></td>
            <td class="col-events"><?= tagList($adh['issues'], ['exam', 'tournament'], true) ?></td>
            <td class="col-total"><?= number_format($adh['total_rest'], 2) ?> DH</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="footer-row">
            <td colspan="8">الإجمالي</td>
            <td><?= number_format($grandTotal, 2) ?> DH</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
