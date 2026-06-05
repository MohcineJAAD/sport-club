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

// Filter by sport type
$filterType = trim($_GET['type'] ?? '');
if ($filterType !== '') {
    $blackList = array_filter($blackList, fn($a) => $a['sport_type'] === $filterType);
}

// Collect all distinct sport types for the filter
$allTypes = [];
foreach ($blackList as $a) {
    $allTypes[$a['sport_type']] = true;
}
// Re-fetch for filter dropdown (before type filter)
$allBlackList = $payment->getBlackListData();
$allTypesForFilter = [];
foreach ($allBlackList as $a) {
    $allTypesForFilter[$a['sport_type']] = true;
}
ksort($allTypesForFilter);

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
            <form method="GET" class="d-flex gap-10 align-center">
                <select name="type" class="p-8 rad-6">
                    <option value="">كل الرياضات</option>
                    <?php foreach ($allTypesForFilter as $t => $_): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-shape">تصفية</button>
            </form>
            <a href="/sport-club/admin/payments.php" class="btn-shape bg-c-60 color-fff">← رجوع</a>
        </div>
    </div>

    <?php if (empty($blackList)): ?>
        <p class="txt-c color-aaa p-20">لا يوجد متأخرون 🎉</p>
    <?php else: ?>

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
                                            <span class="tag tag-red"><?= htmlspecialchars($i['label']) ?></span>
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
                                        <span class="tag tag-blue"><?= htmlspecialchars($i['label']) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Adhesion -->
                            <td>
                                <?php if (!empty($adhIssues)): ?>
                                    <?php foreach ($adhIssues as $i): ?>
                                        <span class="tag tag-purple"><?= htmlspecialchars($i['label']) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Total -->
                            <td style="color:#c0392b;font-weight:bold;">
                                <?= number_format($adh['total_rest'], 2) ?> DH
                            </td>

                            <td>
                                <a href="/sport-club/admin/payment_card.php?id=<?= urlencode($adh['identifier']) ?>&year=<?= $defaultSportYear ?>"
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
.issue-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
}
.tag-red    { background:#ffecec; color:#c00; border:1px solid #f5c6cb; }
.tag-orange { background:#fff3e0; color:#b36b00; border:1px solid #ffc107; }
.tag-blue   { background:#e3f2fd; color:#0d47a1; border:1px solid #90caf9; }
.tag-purple { background:#f3e5f5; color:#6a1b9a; border:1px solid #ce93d8; }
.flex-wrap  { flex-wrap: wrap; }
.gap-10     { gap: 10px; }
.align-center { align-items: center; }
.d-flex     { display: flex; }
.p-8        { padding: 8px; }
.rad-6      { border-radius: 6px; }
.mb-15      { margin-bottom: 15px; }
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