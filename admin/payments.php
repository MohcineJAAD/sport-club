<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$payment  = new Payment($conn);
$adherent = new Adherent($conn);
$plan     = new Plan($conn);

$plans   = $plan->getNames();
$members = $adherent->getAll('active');

$membersByType = [];
foreach ($members as $m) {
    $membersByType[$m['type']][] = $m;
}

$filterMonth = $_POST['filter_month'] ?? date('Y-m');
$payments    = $payment->getByMonth($filterMonth);

$arabicMonths = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
function formatPaymentDate($date, $months) {
    [$y, $m] = explode('-', substr($date, 0, 7));
    return $months[(int)$m] . ' ' . $y;
}
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الدفع</h1>

<!-- Payment records -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <div class="between-flex mb-20" style="flex-wrap: wrap; gap: 10px;">
        <h2 class="mt-0 mb-0">سجل المدفوعات</h2>
        <div class="d-flex" style="gap: 10px; flex-wrap: wrap;">
            <a href="/sport-club/admin/export_month.php?month=<?= urlencode($filterMonth) ?>" class="btn-shape bg-c-60 color-fff">
                <i class="fas fa-file-pdf"></i> لائحة الشهر
            </a>
            <a href="/sport-club/admin/export_adhesion.php?month=<?= urlencode($filterMonth) ?>" class="btn-shape bg-c-60 color-fff">
                <i class="fas fa-file-pdf"></i> لائحة الاشتراك
            </a>
            <a href="/sport-club/admin/export_assurance.php?month=<?= urlencode($filterMonth) ?>" class="btn-shape bg-c-60 color-fff">
                <i class="fas fa-file-pdf"></i> لائحة التأمين
            </a>
        </div>
    </div>

    <form method="POST" class="mb-20">
        <div class="d-flex align-c gap-10">
            <label>الشهر:</label>
            <input type="month" name="filter_month" value="<?= htmlspecialchars($filterMonth) ?>">
            <button type="submit" class="btn-shape bg-c-60 color-fff">بحث</button>
        </div>
    </form>

    <?php if (!empty($payments)):
        $total = array_sum(array_column($payments, 'amount'));
    ?>
        <div class="responsive-table">
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>نوع الدفع</th>
                        <th>المبلغ</th>
                        <th>الشهر</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
                            <td><?= htmlspecialchars($p['identifier']) ?></td>
                            <td><?= htmlspecialchars($p['type']) ?></td>
                            <td><?= number_format($p['amount'], 2) ?> DH</td>
                            <td><?= formatPaymentDate($p['payment_date'], $arabicMonths) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-total">
                        <td colspan="3">المجموع</td>
                        <td colspan="2"><?= number_format($total, 2) ?> DH</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <p class="txt-c color-999">لا توجد مدفوعات في هذا الشهر</p>
    <?php endif; ?>
</div>

<!-- Payment cards by sport type -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">بطاقات الدفع</h2>
    <div class="accordion-container">
        <?php foreach ($plans as $p):
            $type    = $p['name'];
            $grouped = $membersByType[$type] ?? [];
            if (empty($grouped)) continue;
        ?>
            <div class="accordion-item">
                <div class="accordion-header">
                    <span><?= htmlspecialchars($type) ?></span>
                    <span class="toggle-icon">›</span>
                </div>
                <div class="accordion-content">
                    <div class="responsive-table">
                        <table class="fs-15 w-full">
                            <thead>
                                <tr>
                                    <th>الاسم الكامل</th>
                                    <th>المعرف</th>
                                    <th>تاريخ الانخراط</th>
                                    <th>بطاقة الدفع</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                                        <td><?= htmlspecialchars($m['identifier']) ?></td>
                                        <td><?= htmlspecialchars($m['date_adhesion'] ?? '') ?></td>
                                        <td>
                                            <a href="/sport-club/admin/payment_card.php?id=<?= urlencode($m['identifier']) ?>&year=<?= date('Y') ?>"
                                               class="btn-shape bg-c-60 color-fff">دفع</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', function () {
        const content = this.nextElementSibling;
        const icon    = this.querySelector('.toggle-icon');
        const isOpen  = content.classList.contains('open');

        document.querySelectorAll('.accordion-content').forEach(c => c.classList.remove('open'));
        document.querySelectorAll('.toggle-icon').forEach(i => i.classList.remove('rotate'));

        if (!isOpen) {
            content.classList.add('open');
            icon.classList.add('rotate');
        }
    });
});
</script>

<?php require 'layout/footer.php'; ?>