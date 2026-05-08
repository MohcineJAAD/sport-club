<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$payment  = new Payment($conn);
$adherent = new Adherent($conn);
$plan     = new Plan($conn);

$plans   = $plan->getNames();
$members = $adherent->getAll('active');

$filterDate = $_POST['filter_date'] ?? date('Y-m-d');
$payments   = $payment->getByDate($filterDate);
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الدفع</h1>

<!-- Save payment form -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">تسجيل دفعة جديدة</h2>
    <form method="POST" action="/sport-club/actions/payment_save.php">
        <div class="section mb-20">
            <div class="row">
                <div class="input-field">
                    <label>المشترك</label>
                    <select name="identifier" required>
                        <option value="">-- اختر المشترك --</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= htmlspecialchars($m['identifier']) ?>">
                                <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-field">
                    <label>المبلغ</label>
                    <input type="number" name="amount" step="0.01" required>
                </div>
                <div class="input-field">
                    <label>نوع الدفع</label>
                    <select name="type" required>
                        <option value="mois">شهري</option>
                        <option value="assurance">تأمين</option>
                        <option value="adhesion">اشتراك</option>
                    </select>
                </div>
            </div>
        </div>
        <button type="submit" class="btn mt-10">حفظ</button>
    </form>
</div>

<!-- Filter payments by date -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">سجل المدفوعات</h2>
    <form method="POST">
        <div class="form-group d-flex align-c gap-10 mb-20">
            <label>تصفية حسب التاريخ:</label>
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
            <button type="submit" class="btn">بحث</button>
        </div>
    </form>

    <div class="responsive-table">
        <?php if (count($payments) > 0): ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>المبلغ</th>
                        <th>نوع الدفع</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
                            <td><?= htmlspecialchars($p['identifier']) ?></td>
                            <td><?= number_format($p['amount'], 2) ?> DH</td>
                            <td><?= htmlspecialchars($p['type']) ?></td>
                            <td><?= htmlspecialchars($p['Date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">لا توجد مدفوعات في هذا التاريخ</p>
        <?php endif; ?>
    </div>
</div>

<?php require 'layout/footer.php'; ?>