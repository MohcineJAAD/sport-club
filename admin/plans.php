<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$plan  = new Plan($conn);
$plans = $plan->getAll();
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">الخطط</h1>

<!-- Add plan -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">إضافة خطة جديدة</h2>
    <form method="POST" action="/sport-club/actions/plan_save.php">
        <input type="hidden" name="action" value="add">
        <div class="section mb-20">
            <div class="row">
                <div class="input-field">
                    <label>الاسم</label>
                    <input type="text" name="name" required>
                </div>
                <div class="input-field">
                    <label>السعر (DH)</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="input-field">
                    <label>التأمين (DH)</label>
                    <input type="number" name="assurance" step="0.01" required>
                </div>
                <div class="input-field">
                    <label>الاشتراك (DH)</label>
                    <input type="number" name="adherence" step="0.01" required>
                </div>
            </div>
            <div class="row mt-20">
                <div class="input-field">
                    <label>الوصف</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn mt-10">إضافة</button>
    </form>
</div>

<!-- Plans list -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">الخطط الحالية</h2>
    <div class="responsive-table">
        <?php if (count($plans) > 0): ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>السعر</th>
                        <th>التأمين</th>
                        <th>الاشتراك</th>
                        <th>عدد المشتركين</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= number_format($p['price'], 2) ?> DH</td>
                            <td><?= number_format($p['assurance'], 2) ?> DH</td>
                            <td><?= number_format($p['adherence'], 2) ?> DH</td>
                            <td><?= $p['adherents_count'] ?></td>
                            <td>
                                <a href="/sport-club/actions/plan_delete.php?id=<?= $p['id'] ?>">
                                    <span class="label btn-shape bg-f00">حذف</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">لا توجد خطط</p>
        <?php endif; ?>
    </div>
</div>

<?php require 'layout/footer.php'; ?>
