<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$payment = new Payment($conn);
$blackList = $payment->getBlackListData();

$monthNames = [
    1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',
    5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'غشت',
    9=>'شتنبر',10=>'أكتوبر',11=>'نونبر',12=>'دجنبر',
];

$nowYear  = (int)date('Y');
$nowMonth = (int)date('n');
$defaultSportYear = $nowMonth >= 9 ? $nowYear : $nowYear - 1;
$currentSeason = $defaultSportYear . '-' . ($defaultSportYear + 1);

$grandTotal = array_sum(array_column($blackList, 'total_rest'));
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">القائمة السوداء</h1>

<div class="absences p-20 bg-fff rad-10 m-20">

    <div class="between-flex mb-20 flex-wrap gap-10">
        <div>
            <span class="fs-16">
                إجمالي عدد المتأخرين: <strong><?= count($blackList) ?></strong>
                &nbsp;|&nbsp;
                إجمالي المبلغ المستحق: <strong style="color:#c0392b"><?= number_format($grandTotal, 2) ?> DH</strong>
            </span>
        </div>
        <div class="d-flex gap-10 align-center">
            <a href="/sport-club/admin/payments.php" class="btn-shape bg-c-60 color-fff">← رجوع</a>
        </div>
    </div>

    <?php if (empty($blackList)): ?>
        <p class="txt-c color-aaa p-20">لا يوجد متأخرون 🎉</p>
    <?php else: ?>

        <!-- Legend -->
        <div class="tag-legend mb-15">
            <span class="legend-item"><span class="tag tag-red">غير مدفوع</span></span>
            <span class="legend-item"><span class="tag tag-orange">ناقص (المبلغ المتبقي)</span></span>
        </div>

        <!-- Search -->
        <input type="text" id="blSearch" class="mb-15 p-10 w-full rad-6"
               style="border:1px solid #ddd;box-sizing:border-box"
               placeholder="ابحث بالاسم أو المعرف...">

        <div class="responsive-table">
            <table class="fs-15 w-full" id="blTable">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>الرياضة</th>
                        <th>الواجب الشهري</th>
                        <th>الأشهر غير المدفوعة</th>
                        <th>الأشهر الناقصة</th>
                        <th>التأمين</th>
                        <th>الانخراط</th>
                        <th>الامتحانات / البطولات</th>
                        <th>المجموع المستحق</th>
                        <th>بطاقة الدفع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blackList as $adh):
                        $unpaidMonths  = array_filter($adh['issues'], fn($i) => $i['type'] === 'month_unpaid');
                        $partialMonths = array_filter($adh['issues'], fn($i) => $i['type'] === 'month_partial');
                        $assIssues     = array_filter($adh['issues'], fn($i) => $i['type'] === 'assurance');
                        $adhIssues     = array_filter($adh['issues'], fn($i) => $i['type'] === 'adhesion');
                        $eventIssues   = array_filter($adh['issues'], fn($i) => in_array($i['type'], ['exam','tournament']));
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($adh['prenom'] . ' ' . $adh['nom']) ?></td>
                            <td><?= htmlspecialchars($adh['identifier']) ?></td>
                            <td><?= htmlspecialchars($adh['sport_type']) ?></td>
                            <td><?= number_format($adh['monthly_due'], 2) ?> DH</td>

                            <!-- Unpaid months -->
                            <td>
                                <?php if (!empty($unpaidMonths)): ?>
                                    <div class="issue-tags">
                                        <?php foreach ($unpaidMonths as $i): ?>
                                            <span class="tag tag-red">
                                                <?= htmlspecialchars($i['label']) ?>
                                                <small>(<?= number_format($i['due'],2) ?>)</small>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Partial months -->
                            <td>
                                <?php if (!empty($partialMonths)): ?>
                                    <div class="issue-tags">
                                        <?php foreach ($partialMonths as $i): ?>
                                            <span class="tag tag-orange" title="دفع <?= number_format($i['paid'],2) ?> / <?= number_format($i['due'],2) ?> DH">
                                                <?= htmlspecialchars($i['label']) ?>
                                                <small>(<?= number_format($i['rest'],2) ?>)</small>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Assurance -->
                            <td>
                                <?php if (!empty($assIssues)): ?>
                                    <?php foreach ($assIssues as $i): ?>
                                        <span class="tag <?= $i['paid'] > 0 ? 'tag-orange' : 'tag-red' ?>"
                                              <?= $i['paid'] > 0 ? 'title="دفع ' . number_format($i['paid'],2) . ' / ' . number_format($i['due'],2) . ' DH"' : '' ?>>
                                            <?= htmlspecialchars($i['label']) ?>
                                            <small>(<?= number_format($i['paid'] > 0 ? $i['rest'] : $i['due'], 2) ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Adhesion -->
                            <td>
                                <?php if (!empty($adhIssues)): ?>
                                    <?php foreach ($adhIssues as $i): ?>
                                        <span class="tag <?= $i['paid'] > 0 ? 'tag-orange' : 'tag-red' ?>"
                                              <?= $i['paid'] > 0 ? 'title="دفع ' . number_format($i['paid'],2) . ' / ' . number_format($i['due'],2) . ' DH"' : '' ?>>
                                            <?= htmlspecialchars($i['label']) ?>
                                            <small>(<?= number_format($i['paid'] > 0 ? $i['rest'] : $i['due'], 2) ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Events (exams/tournaments) -->
                            <td>
                                <?php if (!empty($eventIssues)): ?>
                                    <div class="issue-tags">
                                    <?php foreach ($eventIssues as $i): ?>
                                        <span class="tag <?= $i['paid'] > 0 ? 'tag-orange' : 'tag-red' ?>"
                                              <?= $i['paid'] > 0 ? 'title="دفع ' . number_format($i['paid'],2) . ' / ' . number_format($i['due'],2) . ' DH"' : '' ?>>
                                            <?= htmlspecialchars($i['label']) ?>
                                            <small>(<?= number_format($i['paid'] > 0 ? $i['rest'] : $i['due'], 2) ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Total -->
                            <td style="color:#c0392b;font-weight:bold;">
                                <?= number_format($adh['total_rest'], 2) ?> DH
                            </td>

                            <td>
                                <a href="/sport-club/admin/payment_card.php?id=<?= urlencode($adh['identifier']) ?>&season=<?= $currentSeason ?>"
                                   class="btn-shape bg-c-60 color-fff">دفع</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.issue-tags { display: flex; flex-wrap: wrap; gap: 5px; }
.tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    letter-spacing: 0.2px;
}
.tag small   { font-weight: 400; opacity: .85; }
.tag-red     { background:#fde8e8; color:#b91c1c; }
.tag-orange  { background:#fef3c7; color:#b45309; }
.tag-legend  { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
.legend-item { display:flex; align-items:center; gap:4px; font-size:13px; color:#555; }
.flex-wrap   { flex-wrap: wrap; }
.gap-10      { gap: 10px; }
.align-center { align-items: center; }
.d-flex      { display: flex; }
.rad-6       { border-radius: 6px; }
.mb-15       { margin-bottom: 15px; }
</style>

<script>
document.getElementById('blSearch').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#blTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require 'layout/footer.php'; ?>
