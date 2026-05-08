<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$schedule  = new Schedule($conn);
$schedules = $schedule->getAll();
$days      = ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'];
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">الجدول الزمني</h1>

<!-- Add schedule -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">إضافة حصة</h2>
    <form method="POST" action="/sport-club/actions/schedule_save.php">
        <div class="section mb-20">
            <div class="row">
                <div class="input-field">
                    <label>اليوم</label>
                    <select name="day" required>
                        <option value="">-- اختر اليوم --</option>
                        <?php foreach ($days as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-field">
                    <label>التوقيت</label>
                    <input type="text" name="timeslot" placeholder="مثال: 10:00 - 11:00" required>
                </div>
                <div class="input-field">
                    <label>نوع الرياضة</label>
                    <input type="text" name="sport_type" placeholder="مثال: تايكواندو" required>
                </div>
            </div>
        </div>
        <button type="submit" class="btn mt-10">إضافة</button>
    </form>
</div>

<!-- Schedule list -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">الجدول الحالي</h2>
    <div class="responsive-table">
        <?php if (count($schedules) > 0): ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>التوقيت</th>
                        <th>الرياضة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['day']) ?></td>
                            <td><?= htmlspecialchars($s['timeslot']) ?></td>
                            <td><?= htmlspecialchars($s['sport_type']) ?></td>
                            <td>
                                <a href="/sport-club/actions/schedule_delete.php?id=<?= $s['id'] ?>">
                                    <span class="label btn-shape bg-f00">حذف</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">لا توجد حصص مضافة</p>
        <?php endif; ?>
    </div>
</div>

<?php require 'layout/footer.php'; ?>
