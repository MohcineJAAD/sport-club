<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$adherent = new Adherent($conn);
$allMembers = $adherent->getAll('active');
$members = array_filter($allMembers, fn($m) => mb_strpos($m['type'], 'تايك') !== false);
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الامتحان</h1>

<div class="absences p-20 bg-fff rad-10 m-20">
    <form method="POST" action="/sport-club/admin/export_exam.php">
        <div class="between-flex mb-20" style="flex-wrap:wrap;gap:10px;">
            <h2 class="mt-0 mb-0">مشتركو التايكواندو</h2>
            <div class="d-flex align-c gap-10" style="flex-wrap:wrap;">
                <label>الدورة:</label>
                <select name="session" required>
                    <option value="" disabled selected>اختر الدورة</option>
                    <option value="يناير">يناير</option>
                    <option value="يونيو">يونيو</option>
                </select>
                <button type="submit" class="btn-shape save-btn">
                    <i class="fas fa-print"></i> طبع
                </button>
            </div>
        </div>

        <div class="responsive-table">
            <table class="fs-15 w-full" id="exam-list">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>تاريخ الازدياد</th>
                        <th>الحزام الحالي</th>
                        <th>الحزام المرتقب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="6" class="txt-c color-888">لا يوجد مشتركون في التايكواندو</td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><input type="checkbox" name="adherent[]" value="<?= htmlspecialchars($m['identifier']) ?>"></td>
                                <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                                <td><?= htmlspecialchars($m['identifier']) ?></td>
                                <td><?= htmlspecialchars($m['date_naissance'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['current_belt'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['next_belt'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
document.getElementById('select-all').addEventListener('change', function () {
    document.querySelectorAll('input[name="adherent[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require 'layout/footer.php'; ?>