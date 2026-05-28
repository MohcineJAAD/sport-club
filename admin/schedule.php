<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$schedule  = new Schedule($conn);
$plan      = new Plan($conn);
$plans     = $plan->getNames();

$days = ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'];
$timeslots = [
    '16:30-17:30',
    '17:30-18:30',
    '18:30-19:30',
    '19:30-20:30',
    '20:30-21:30',
    '21:30-22:30',
    '22:30-23:30',
];

$existing = $schedule->getAll();
$grid = [];
foreach ($existing as $row) {
    $grid[$row['day']][$row['timeslot']] = $row['sport_type'];
}
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">الجدول الزمني</h1>

<div class="absences p-20 bg-fff rad-10 m-20">
    <form class="horaire responsive-table special" method="POST" action="/sport-club/actions/schedule_save.php">
        <table>
            <thead>
                <tr>
                    <th>اليوم / الوقت</th>
                    <?php foreach ($timeslots as $slot): ?>
                        <th><?= $slot ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                    <tr>
                        <th><?= $day ?></th>
                        <?php foreach ($timeslots as $slot): ?>
                            <td>
                                <select name="sport[<?= htmlspecialchars($day) ?>][<?= htmlspecialchars($slot) ?>]" class="sport" disabled>
                                    <option value="">--</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?= htmlspecialchars($p['name']) ?>"
                                            <?= ($grid[$day][$slot] ?? '') === $p['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="action-buttons mt-20">
            <button type="button" class="btn-shape modify-btn mb-10">
                <i class="fas fa-edit"></i> تعديل
            </button>
            <button type="submit" class="btn-shape save-btn hidden mb-10">
                <i class="fas fa-save"></i> حفظ
            </button>
        </div>
    </form>
</div>

<script>
const editBtn = document.querySelector('.modify-btn');
const saveBtn = document.querySelector('.save-btn');
const selects = document.querySelectorAll('.sport');

editBtn.addEventListener('click', function () {
    selects.forEach(s => {
        s.disabled = false;
        s.classList.add('editable');
    });
    editBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
});
</script>

<?php require 'layout/footer.php'; ?>