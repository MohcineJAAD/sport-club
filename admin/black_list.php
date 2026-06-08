<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$payment = new Payment($conn);
$blackList = $payment->getBlackListData();

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
                إجمالي المبلغ المستحق: <strong style="color:#b91c1c"><?= number_format($grandTotal, 2) ?> DH</strong>
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

        <input type="text" id="blSearch" class="mb-15 p-10 w-full rad-6"
               style="border:1px solid #ddd;box-sizing:border-box"
               placeholder="ابحث بالاسم أو المعرف...">

        <div class="responsive-table">
            <table class="fs-15 w-full" id="blTable">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>الأشهر غير المدفوعة</th>
                        <th>الأشهر الناقصة</th>
                        <th>التأمين</th>
                        <th>الانخراط</th>
                        <th>الامتحانات / البطولات</th>
                        <th>المجموع المستحق</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blackList as $idx => $adh):
                        $unpaidMonths  = array_filter($adh['issues'], fn($i) => $i['type'] === 'month_unpaid');
                        $partialMonths = array_filter($adh['issues'], fn($i) => $i['type'] === 'month_partial');
                        $assIssues     = array_filter($adh['issues'], fn($i) => $i['type'] === 'assurance');
                        $adhIssues     = array_filter($adh['issues'], fn($i) => $i['type'] === 'adhesion');
                        $eventIssues   = array_filter($adh['issues'], fn($i) => in_array($i['type'], ['exam','tournament']));
                        $detailId      = 'detail-' . $idx;
                    ?>
                        <tr class="main-row" data-detail="<?= $detailId ?>">
                            <td>
                                <button class="detail-toggle" data-target="<?= $detailId ?>" title="عرض التفاصيل">
                                    &#9664;
                                </button>
                                <?= htmlspecialchars($adh['prenom'] . ' ' . $adh['nom']) ?>
                            </td>
                            <td><?= htmlspecialchars($adh['identifier']) ?></td>

                            <td>
                                <?php if (!empty($unpaidMonths)): ?>
                                    <div class="issue-tags">
                                        <?php foreach ($unpaidMonths as $i): ?>
                                            <span class="tag tag-red">
                                                <?= htmlspecialchars($i['label']) ?>
                                                <small>(<?= number_format($i['due'], 2) ?>)</small>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="color-aaa">—</span>
                                <?php endif; ?>
                            </td>

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

                            <td>
                                <?php if (!empty($assIssues)): ?>
                                    <div class="issue-tags">
                                    <?php foreach ($assIssues as $i): ?>
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

                            <td>
                                <?php if (!empty($adhIssues)): ?>
                                    <div class="issue-tags">
                                    <?php foreach ($adhIssues as $i): ?>
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

                            <td style="color:#b91c1c;font-weight:bold;">
                                <?= number_format($adh['total_rest'], 2) ?> DH
                            </td>

                            <td>
                                <a href="/sport-club/admin/payment_card.php?id=<?= urlencode($adh['identifier']) ?>&season=<?= $currentSeason ?>"
                                   class="btn-shape bg-c-60 color-fff">دفع</a>
                            </td>
                        </tr>

                        <!-- Expandable detail row -->
                        <tr class="detail-row" id="<?= $detailId ?>">
                            <td colspan="9">
                                <div class="detail-panel">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <span class="detail-label">الرياضة</span>
                                            <span class="detail-value"><?= htmlspecialchars($adh['sport_type']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">الواجب الشهري</span>
                                            <span class="detail-value"><?= number_format($adh['monthly_due'], 2) ?> DH</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">تاريخ الانخراط</span>
                                            <span class="detail-value"><?= htmlspecialchars($adh['date_adhesion'] ?? '—') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">ولي الأمر</span>
                                            <span class="detail-value"><?= htmlspecialchars($adh['guardian_name'] ?? '—') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">الهاتف</span>
                                            <span class="detail-value">
                                                <?php if (!empty($adh['guardian_phone'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($adh['guardian_phone']) ?>" class="phone-link">
                                                        <?= htmlspecialchars($adh['guardian_phone']) ?>
                                                    </a>
                                                <?php else: ?>—<?php endif; ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($adh['second_guardian_phone'])): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">هاتف ثانٍ</span>
                                            <span class="detail-value">
                                                <a href="tel:<?= htmlspecialchars($adh['second_guardian_phone']) ?>" class="phone-link">
                                                    <?= htmlspecialchars($adh['second_guardian_phone']) ?>
                                                </a>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="detail-actions">
                                        <a href="/sport-club/admin/profile.php?id=<?= urlencode($adh['identifier']) ?>"
                                           class="btn-shape bg-c-60 color-fff" target="_blank">الملف الشخصي</a>
                                        <a href="/sport-club/admin/payment_card.php?id=<?= urlencode($adh['identifier']) ?>&season=<?= $currentSeason ?>"
                                           class="btn-shape color-fff" style="background:#16a34a;" target="_blank">بطاقة الدفع</a>
                                    </div>
                                </div>
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
.tag small { font-weight: 400; opacity: .85; }
.tag-red    { background:#fde8e8; color:#b91c1c; }
.tag-orange { background:#fef3c7; color:#b45309; }
.tag-legend { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
.legend-item { display:flex; align-items:center; gap:4px; font-size:13px; color:#555; }
.flex-wrap  { flex-wrap: wrap; }
.gap-10     { gap: 10px; }
.align-center { align-items: center; }
.d-flex     { display: flex; }
.rad-6      { border-radius: 6px; }
.mb-15      { margin-bottom: 15px; }

.detail-toggle {
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    font-size: 11px;
    padding: 2px 5px;
    border-radius: 4px;
    transition: transform .2s, color .2s;
    vertical-align: middle;
}
.detail-toggle.open { transform: rotate(90deg); color: #2563eb; }

.detail-row { display: none; background: #f8fafc; }
.detail-row.open { display: table-row; }

.detail-panel {
    padding: 14px 20px;
    border-top: 1px solid #e5e7eb;
    border-bottom: 2px solid #2563eb22;
}
.detail-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 18px 32px;
    margin-bottom: 12px;
}
.detail-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 140px;
}
.detail-label {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.detail-value {
    font-size: 14px;
    font-weight: 500;
    color: #111827;
}
.phone-link {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}
.phone-link:hover { text-decoration: underline; }
.detail-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>

<script>
document.getElementById('blSearch').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#blTable tbody tr.main-row').forEach(row => {
        const visible = row.textContent.toLowerCase().includes(q);
        row.style.display = visible ? '' : 'none';
        const detailId = row.dataset.detail;
        const detailRow = document.getElementById(detailId);
        if (detailRow && !visible) {
            detailRow.classList.remove('open');
            row.querySelector('.detail-toggle').classList.remove('open');
        }
        if (detailRow) detailRow.style.display = visible && detailRow.classList.contains('open') ? '' : (visible ? '' : 'none');
    });
});

document.querySelectorAll('.detail-toggle').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const target = document.getElementById(this.dataset.target);
        const isOpen = target.classList.contains('open');
        target.classList.toggle('open', !isOpen);
        this.classList.toggle('open', !isOpen);
    });
});
</script>

<?php require 'layout/footer.php'; ?>
