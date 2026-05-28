<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$adherent   = new Adherent($conn);
$allMembers = $adherent->getAll('active');
$members    = array_values(array_filter($allMembers, fn($m) => mb_strpos($m['type'], 'تايك') !== false));

$sessions       = ['يناير', 'يونيو'];
$currentSession = $_GET['session'] ?? $sessions[0];
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">امتحانات التايكواندو</h1>

<div class="absences p-20 bg-fff rad-10 m-20">
    <div class="between-flex mb-20" style="flex-wrap:wrap;gap:10px;">
        <h2 class="mt-0 mb-0">اختيار المرشحين للامتحان</h2>
        <div class="d-flex align-c gap-10">
            <label>الدورة:</label>
            <select id="sessionPicker" class="filter-input">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"
                            <?= $s === $currentSession ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <form method="POST" action="/sport-club/admin/export_exam.php" id="exam-form">
        <input type="hidden" name="session" id="sessionInput" value="<?= htmlspecialchars($currentSession) ?>">

        <div class="options w-full mb-10">
            <input type="text" id="search" placeholder="بحث بالاسم أو المعرف..."
                   style="padding:8px 14px;border:1px solid #ccc;border-radius:8px;font-size:15px;width:280px;margin-bottom:10px;">
        </div>

        <div class="responsive-table">
            <table class="fs-15 w-full" id="adherent-list">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" style="width:20px;height:20px;cursor:pointer;"></th>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>تاريخ الازدياد</th>
                        <th>الرياضة</th>
                        <th>تاريخ الانخراط</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#888;">لا يوجد مشتركون في التايكواندو</td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="candidates[]"
                                       value="<?= htmlspecialchars($m['identifier']) ?>"
                                       style="width:20px;height:20px;cursor:pointer;">
                            </td>
                            <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                            <td><?= htmlspecialchars($m['identifier']) ?></td>
                            <td><?= htmlspecialchars($m['date_naissance'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['date_adhesion'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <button type="button" onclick="submitExam()" class="btn mt-20">
            <i class="fa-solid fa-print"></i> طباعة أوراق الامتحان
        </button>
    </form>
</div>

<script>
document.getElementById('select-all').addEventListener('change', function () {
    document.querySelectorAll('#adherent-list tbody input[type="checkbox"]')
            .forEach(cb => cb.checked = this.checked);
});

document.getElementById('sessionPicker').addEventListener('change', function () {
    document.getElementById('sessionInput').value = this.value;
});

function submitExam() {
    const checked = document.querySelectorAll('#adherent-list tbody input[type="checkbox"]:checked');
    if (checked.length === 0) {
        alert('يرجى اختيار مرشح واحد على الأقل');
        return;
    }
    document.getElementById('exam-form').submit();
}
</script>

<?php require 'layout/footer.php'; ?>
