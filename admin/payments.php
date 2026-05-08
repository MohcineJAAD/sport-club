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

$filterDate = $_POST['filter_date'] ?? date('Y-m-d');
$payments   = $payment->getByDate($filterDate);
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الدفع</h1>

<!-- Date filter -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">سجل المدفوعات</h2>
    <form method="POST">
        <div class="d-flex align-c gap-10 mb-20">
            <label>تصفية حسب التاريخ:</label>
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
            <button type="submit" class="btn">بحث</button>
        </div>
    </form>

    <div class="responsive-table">
        <?php if (count($payments) > 0):
            $total = array_sum(array_column($payments, 'amount'));
        ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>نوع الدفع</th>
                        <th>المبلغ</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
                            <td><?= htmlspecialchars($p['identifier']) ?></td>
                            <td><?= htmlspecialchars($p['type']) ?></td>
                            <td><?= number_format($p['amount'], 2) ?> DH</td>
                            <td><?= htmlspecialchars($p['Date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold;background:#f0f0f0;">
                        <td colspan="3">المجموع</td>
                        <td colspan="2"><?= number_format($total, 2) ?> DH</td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">لا توجد مدفوعات في هذا التاريخ</p>
        <?php endif; ?>
    </div>
</div>

<!-- Member accordion by sport type -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">بطاقات الدفع</h2>
    <div class="accordion-container">
        <?php foreach ($plans as $p):
            $type    = $p['name'];
            $grouped = $membersByType[$type] ?? [];
            if (empty($grouped)) continue;
        ?>
            <div class="accordion-item m-20">
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
                                               class="btn-shape bg-c-60 color-fff p-5 rad-6 fs-12">دفع</a>
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
