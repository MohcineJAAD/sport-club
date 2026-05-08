<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$evaluation = new Evaluation($conn);
$adherent   = new Adherent($conn);
$plan       = new Plan($conn);

$members     = $adherent->getAll('active');
$plans       = $plan->getNames();
$filterMonth = (int) ($_POST['month'] ?? date('n'));
$filterYear  = (int) ($_POST['year']  ?? date('Y'));
$results     = $evaluation->getByMonth($filterMonth, $filterYear);
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">التقييمات</h1>

<!-- Save evaluation -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">إضافة تقييم</h2>
    <form method="POST" action="/sport-club/actions/evaluation_save.php">
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
                    <label>الشهر</label>
                    <select name="month" required>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="input-field">
                    <label>السنة</label>
                    <input type="number" name="year" value="<?= date('Y') ?>" required>
                </div>
            </div>
            <div class="row mt-20">
                <div class="input-field">
                    <label>الانضباط (0-10)</label>
                    <input type="number" name="discipline" min="0" max="10" required>
                </div>
                <div class="input-field">
                    <label>الأداء (0-10)</label>
                    <input type="number" name="performance" min="0" max="10" required>
                </div>
                <div class="input-field">
                    <label>السلوك (0-10)</label>
                    <input type="number" name="behavior" min="0" max="10" required>
                </div>
            </div>
        </div>
        <button type="submit" class="btn mt-10">حفظ التقييم</button>
    </form>
</div>

<!-- Filter evaluations -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">سجل التقييمات</h2>
    <form method="POST">
        <div class="form-group d-flex align-c gap-10 mb-20">
            <label>الشهر:</label>
            <select name="month">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $filterMonth ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
            <label>السنة:</label>
            <input type="number" name="year" value="<?= $filterYear ?>" style="width:90px;">
            <button type="submit" class="btn">بحث</button>
        </div>
    </form>

    <div class="responsive-table">
        <?php if (count($results) > 0): ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>الانضباط</th>
                        <th>الأداء</th>
                        <th>السلوك</th>
                        <th>المعدل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <?php $avg = round(($r['discipline'] + $r['performance'] + $r['behavior']) / 3, 1); ?>
                        <tr>
                            <td><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></td>
                            <td><?= $r['discipline'] ?>/10</td>
                            <td><?= $r['performance'] ?>/10</td>
                            <td><?= $r['behavior'] ?>/10</td>
                            <td><strong><?= $avg ?>/10</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">لا توجد تقييمات لهذا الشهر</p>
        <?php endif; ?>
    </div>
</div>

<?php require 'layout/footer.php'; ?>
