<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$attendance = new Attendance($conn);
$plan       = new Plan($conn);

$plans       = $plan->getNames();
$summary     = $attendance->getMonthlySummary();
$currentDay  = (int) date('j');
$members     = (new Adherent($conn))->getAll('active');
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الحضور</h1>

<!-- Monthly Summary -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">ملخص الشهر الحالي</h2>

    <?php if ($currentDay < 10): ?>
        <p style="text-align:center;color:#888;">
            سيظهر الملخص بعد اليوم العاشر من الشهر (اليوم الحالي: <?= $currentDay ?>)
        </p>
    <?php else: ?>
        <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
            <span style="background:#ffecec;color:#c00;padding:6px 14px;border-radius:20px;font-size:13px;">&#9899; لم يدفع</span>
            <span style="background:#fff7e6;color:#b36b00;padding:6px 14px;border-radius:20px;font-size:13px;">&#9899; دفع لكن أقل من 5 حصص</span>
            <span style="background:#eafaf1;color:#1a7a3a;padding:6px 14px;border-radius:20px;font-size:13px;">&#9899; دفع وحضر بانتظام</span>
        </div>
        <div class="responsive-table">
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>الرياضة</th>
                        <th>الدفع</th>
                        <th>عدد الحصص</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row):
                        $status = $attendance->getStatus((int)$row['paid'], (int)$row['sessions']);
                    ?>
                        <tr style="background:<?= $status['bg'] ?>">
                            <td><?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></td>
                            <td><?= htmlspecialchars($row['identifier']) ?></td>
                            <td><?= htmlspecialchars($row['type']) ?></td>
                            <td><?= (int)$row['paid'] > 0 ? '✅ دفع' : '❌ لم يدفع' ?></td>
                            <td><?= (int)$row['sessions'] ?></td>
                            <td style="color:<?= $status['color'] ?>;font-weight:600;"><?= $status['label'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Attendance form -->
<div class="absences p-20 bg-fff rad-10 m-20 special">
    <h2 class="mt-0 mb-20">تسجيل الحضور</h2>

    <div class="options w-full">
        <div class="branch-filter mt-10 mb-10">
            <button class="btn-shape bg-c-60 color-fff active mb-10" data-branch="all">الكل</button>
            <?php foreach ($plans as $p): ?>
                <button class="btn-shape bg-c-60 color-fff mb-10" data-branch="<?= htmlspecialchars($p['name']) ?>">
                    <?= htmlspecialchars($p['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="POST" action="/sport-club/actions/attendance_save.php">
        <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
        <div class="responsive-table">
            <table class="fs-15 w-full" id="adherent-list">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>الرياضة</th>
                        <th>حضر</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr data-branch="<?= htmlspecialchars($m['type']) ?>">
                            <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                            <td><?= htmlspecialchars($m['identifier']) ?></td>
                            <td><?= htmlspecialchars($m['type']) ?></td>
                            <td>
                                <input type="checkbox" name="present[]" value="<?= htmlspecialchars($m['identifier']) ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn mt-20">حفظ الحضور</button>
    </form>
</div>

<script>
document.querySelectorAll('.branch-filter button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.branch-filter button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const branch = this.dataset.branch;
        document.querySelectorAll('#adherent-list tbody tr').forEach(row => {
            row.style.display = (branch === 'all' || row.dataset.branch === branch) ? '' : 'none';
        });
    });
});
</script>

<?php require 'layout/footer.php'; ?>
