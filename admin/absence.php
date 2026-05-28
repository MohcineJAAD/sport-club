<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$attendance   = new Attendance($conn);
$plan         = new Plan($conn);

$plans        = $plan->getNames();
$summary      = $attendance->getMonthlySummary();
$currentDay   = (int) date('j');
$members      = (new Adherent($conn))->getAll('active');
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$presentIds   = $attendance->getByDate($selectedDate);

$summaryByType = [];
foreach ($summary as $row) {
    $summaryByType[$row['type']][] = $row;
}

$membersByType = [];
foreach ($members as $m) {
    $membersByType[$m['type']][] = $m;
}
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الحضور</h1>

<!-- Monthly Summary -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">ملخص الشهر الحالي</h2>

    <?php if ($currentDay < 10): ?>
        <p class="txt-c color-888">
            سيظهر الملخص بعد اليوم العاشر من الشهر (اليوم الحالي: <?= $currentDay ?>)
        </p>
    <?php else: ?>
        <div class="attendance-legend mb-20">
            <span class="legend-item legend-danger">لم يدفع</span>
            <span class="legend-item legend-warning">دفع لكن حضور ضعيف</span>
            <span class="legend-item legend-success">دفع وحضر بانتظام</span>
        </div>
        <div class="accordion-container">
            <?php foreach ($plans as $p):
                $type = $p['name'];
                $rows = $summaryByType[$type] ?? [];
            ?>
                <div class="accordion-item mb-10">
                    <div class="accordion-header">
                        <span><?= htmlspecialchars($type) ?> <span class="sport-badge"><?= count($rows) ?></span></span>
                        <span class="toggle-icon">›</span>
                    </div>
                    <div class="accordion-content">
                        <div class="responsive-table">
                            <table class="fs-15 w-full">
                                <thead>
                                    <tr>
                                        <th>الاسم الكامل</th>
                                        <th>الدفع</th>
                                        <th>عدد الحصص</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rows)): ?>
                                        <tr><td colspan="4" class="txt-c color-888">لا يوجد أعضاء في هذه الرياضة</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $row):
                                            $status = $attendance->getStatus((int)$row['paid'], (int)$row['sessions']);
                                        ?>
                                            <tr style="background:<?= $status['bg'] ?>">
                                                <td><?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></td>
                                                <td><?= (int)$row['paid'] > 0 ? '✅ دفع' : '❌ لم يدفع' ?></td>
                                                <td><?= (int)$row['sessions'] ?></td>
                                                <td style="color:<?= $status['color'] ?>;font-weight:600;"><?= $status['label'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Attendance form -->
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">تسجيل الحضور</h2>

    <div class="filter-form mb-20">
        <label for="datePicker">التاريخ:</label>
        <input type="date" id="datePicker" class="filter-input" value="<?= htmlspecialchars($selectedDate) ?>">
    </div>

    <form method="POST" action="/sport-club/actions/attendance_save.php">
        <input type="hidden" name="date" id="dateHidden" value="<?= htmlspecialchars($selectedDate) ?>">
        <div class="accordion-container">
            <?php foreach ($plans as $p):
                $type    = $p['name'];
                $grouped = $membersByType[$type] ?? [];
            ?>
                <div class="accordion-item mb-10">
                    <div class="accordion-header">
                        <span><?= htmlspecialchars($type) ?> <span class="sport-badge"><?= count($grouped) ?></span></span>
                        <span class="toggle-icon">›</span>
                    </div>
                    <div class="accordion-content">
                        <input type="text"
                               class="accordion-search"
                               placeholder="بحث بالاسم أو المعرف..."
                               data-table="table-<?= htmlspecialchars($type) ?>">
                        <div class="responsive-table">
                            <table class="fs-15 w-full" id="table-<?= htmlspecialchars($type) ?>">
                                <thead>
                                    <tr>
                                        <th>الاسم الكامل</th>
                                        <th>المعرف</th>
                                        <th>حضر</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($grouped)): ?>
                                        <tr><td colspan="3" class="txt-c color-888">لا يوجد أعضاء في هذه الرياضة</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($grouped as $m):
                                            $isPresent = in_array($m['identifier'], $presentIds);
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                                                <td><?= htmlspecialchars($m['identifier']) ?></td>
                                                <td>
                                                    <input type="checkbox"
                                                           name="present[]"
                                                           value="<?= htmlspecialchars($m['identifier']) ?>"
                                                           <?= $isPresent ? 'checked' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn mt-20">حفظ الحضور</button>
    </form>
</div>

<script>
// Date picker reloads page to fetch existing attendance
document.getElementById('datePicker').addEventListener('change', function () {
    document.getElementById('dateHidden').value = this.value;
    window.location.href = '?date=' + encodeURIComponent(this.value);
});

// Accordion toggle (scoped per container)
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', function () {
        const container = this.closest('.accordion-container');
        const content   = this.nextElementSibling;
        const icon      = this.querySelector('.toggle-icon');
        const isOpen    = content.classList.contains('open');

        container.querySelectorAll('.accordion-content').forEach(c => c.classList.remove('open'));
        container.querySelectorAll('.toggle-icon').forEach(i => i.classList.remove('rotate'));

        if (!isOpen) {
            content.classList.add('open');
            icon.classList.add('rotate');
        }
    });
});

// Per-accordion search
document.querySelectorAll('.accordion-search').forEach(input => {
    input.addEventListener('input', function () {
        const q     = this.value.trim().toLowerCase();
        const table = document.getElementById(this.dataset.table);
        table.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
});
</script>

<?php require 'layout/footer.php'; ?>