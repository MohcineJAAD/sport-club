<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: /sport-club/admin/adherents.php");
    exit();
}

$adherent   = new Adherent($conn);
$payment    = new Payment($conn);
$evaluation = new Evaluation($conn);
$attendance = new Attendance($conn);

$member     = $adherent->getById($id);
if (!$member) {
    header("Location: /sport-club/admin/adherents.php");
    exit();
}

$payments    = $payment->getByAdherent($id);
$evaluations = $evaluation->getByAdherent($id);
$averages    = $evaluation->getAverage($id);
$sessions    = $attendance->countThisMonth($id);

$belts_tae  = ["أبيض", "أصفر بخط أبيض", "أصفر", "برتقالي", "أخضر", "أزرق", "أزرق بخط أحمر", "أحمر", "أحمر بخط أسود", "أحمر بخطين أسودين"];
$belts_full = ["أبيض", "أصفر", "برتقالي", "أخضر", "أزرق", "بني", "أسود"];
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">الملف الشخصي</h1>

<div class="profile-container m-20 bg-fff rad-10 p-20">

    <!-- Member info -->
    <div class="d-flex gap-20 mb-20">
        <img src="/sport-club/assets/uploads/<?= htmlspecialchars($member['image_path'] ?? 'default.png') ?>"
             alt="صورة المشترك" style="width:120px;height:120px;object-fit:cover;border-radius:50%;">
        <div>
            <h2><?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?></h2>
            <p>المعرف: <?= htmlspecialchars($member['identifier']) ?></p>
            <p>الرياضة: <?= htmlspecialchars($member['type']) ?></p>
            <p>تاريخ الازدياد: <?= htmlspecialchars($member['date_naissance']) ?></p>
            <p>تاريخ الانخراط: <?= htmlspecialchars($member['date_adhesion']) ?></p>
            <p>الحزام الحالي: <?= htmlspecialchars($member['current_belt'] ?? 'غير محدد') ?></p>
            <p>حصص هذا الشهر: <strong><?= $sessions ?></strong></p>
        </div>
    </div>

    <!-- Update belt -->
    <div class="section mb-20">
        <h3>تحديث الحزام</h3>
        <form method="POST" action="/sport-club/actions/adherent_update.php">
            <input type="hidden" name="identifier" value="<?= htmlspecialchars($id) ?>">
            <div class="row">
                <div class="input-field">
                    <label>الحزام</label>
                    <select name="current_belt">
                        <optgroup label="تايكواندو">
                            <?php foreach ($belts_tae as $belt): ?>
                                <option value="<?= $belt ?>" <?= $member['current_belt'] === $belt ? 'selected' : '' ?>>
                                    <?= $belt ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="كونج فو / فول كونطاكت">
                            <?php foreach ($belts_full as $belt): ?>
                                <option value="<?= $belt ?>" <?= $member['current_belt'] === $belt ? 'selected' : '' ?>>
                                    <?= $belt ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn mt-10">حفظ</button>
        </form>
    </div>

    <!-- Evaluations average -->
    <?php if ($averages['avg_discipline']): ?>
    <div class="section mb-20">
        <h3>متوسط التقييمات</h3>
        <div class="d-flex gap-20">
            <span>الانضباط: <strong><?= $averages['avg_discipline'] ?>/10</strong></span>
            <span>الأداء: <strong><?= $averages['avg_performance'] ?>/10</strong></span>
            <span>السلوك: <strong><?= $averages['avg_behavior'] ?>/10</strong></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payment history -->
    <div class="section mb-20">
        <h3>سجل المدفوعات</h3>
        <?php if (count($payments) > 0): ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr><th>المبلغ</th><th>النوع</th><th>التاريخ</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= number_format($p['amount'], 2) ?> DH</td>
                            <td><?= htmlspecialchars($p['type']) ?></td>
                            <td><?= htmlspecialchars($p['Date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>لا توجد مدفوعات</p>
        <?php endif; ?>
    </div>

</div>

<?php require 'layout/footer.php'; ?>
