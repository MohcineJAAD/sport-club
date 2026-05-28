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

    <div class="section-header">
        <h2>سجل المدفوعات</h2>
        <div class="export-btns">
            <a href="/sport-club/admin/export_month.php?month=<?= urlencode($filterMonth) ?>" class="btn-shape bg-c-60 color-fff">
                <i class="fas fa-file-pdf"></i> المتأخرون عن الواجب الشهري
            </a>
            <a href="/sport-club/admin/export_adhesion.php?month=<?= urlencode($filterMonth) ?>" class="btn-shape bg-c-60 color-fff">
                <i class="fas fa-file-pdf"></i> المتأخرون عن الانخراط السنوي
            </a>
            <a href="/sport-club/admin/export_assurance.php?month=<?= urlencode($filterMonth) ?>" class="btn-shape bg-c-60 color-fff">
                <i class="fas fa-file-pdf"></i> المتأخرون عن التأمين
            </a>
        </div>
    </div>

    <form method="POST" class="mb-20 filter-form">
        <label for="filter_month">الشهر:</label>
        <input type="month" id="filter_month" name="filter_month"
               class="filter-input"
               value="<?= htmlspecialchars($filterMonth) ?>">
        <button type="submit" class="btn">بحث</button>
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
        <p class="txt-c color-aaa">لا توجد مدفوعات في هذا الشهر</p>
    <?php endif; ?>

</div>

<!-- Payment cards by sport -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">بطاقات الدفع</h2>
    <div class="accordion-container">
        <?php foreach ($plans as $index => $p):
            $type    = $p['name'];
            $grouped = $membersByType[$type] ?? [];
            $tableId = 'sport-table-' . $index;
        ?>
            <div class="accordion-item">
                <div class="accordion-header">
                    <span>
                        <?= htmlspecialchars($type) ?>
                        <span class="sport-badge"><?= count($grouped) ?></span>
                    </span>
                    <span class="toggle-icon">›</span>
                </div>
                <div class="accordion-content">
                    <?php if (!empty($grouped)): ?>
                        <input type="text"
                               class="accordion-search"
                               placeholder="ابحث عن مشترك بالاسم أو المعرف..."
                               data-table="<?= $tableId ?>">
                        <div class="responsive-table">
                            <table class="fs-15 w-full" id="<?= $tableId ?>">
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
                    <?php else: ?>
                        <p class="txt-c color-aaa p-10">لا يوجد مشتركون نشطون في هذه الرياضة</p>
                    <?php endif; ?>
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

document.querySelectorAll('.accordion-search').forEach(input => {
    input.addEventListener('input', function () {
        const query   = this.value.trim().toLowerCase();
        const tableId = this.dataset.table;
        const tbody   = document.getElementById(tableId).querySelector('tbody');
        tbody.querySelectorAll('tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });
});
</script>

<?php require 'layout/footer.php'; ?>