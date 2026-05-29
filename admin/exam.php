<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$plan = new Plan($conn);

$stmt = $conn->prepare("SELECT * FROM adherents WHERE status = 'active' AND type LIKE '%تايك%' ORDER BY nom, prenom");
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة الامتحان</h1>

<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20 mt-20">المنخرطين</h2>
    <form class="responsive-table" method="post" action="/sport-club/admin/export_exam.php">
        <div class="row mb-10">
            <div class="branch-filter">
                <div class="mb-10">
                    <label for="session">الدورة</label>
                    <select name="session" id="session">
                        <option value="" selected disabled>اختر الدورة</option>
                        <option value="يناير">يناير</option>
                        <option value="يونيو">يونيو</option>
                    </select>
                </div>
            </div>
        </div>

        <input type="text" id="exam-search" placeholder="بحث بالاسم أو المعرف..."
               style="padding:8px 14px;border:1px solid #ccc;border-radius:8px;font-size:15px;width:280px;margin-bottom:10px;">

        <table class="fs-15 w-full" id="adherent-list">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>الاسم الكامل</th>
                    <th>المعرف</th>
                    <th>تاريخ الازدياد</th>
                    <th>الرياضة</th>
                    <th>تاريخ الانخراط</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#888;padding:20px">لا يوجد منخرطون في التايكواندو</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <tr data-branch="<?= htmlspecialchars($m['type']) ?>">
                        <td>
                            <input type="checkbox" name="adherent[]" value="<?= htmlspecialchars($m['identifier']) ?>">
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

        <button type="button" onclick="submitExam()" class="save-btn btn-shape mt-10">
            <i class="fa-solid fa-print"></i> طبع
        </button>
    </form>
</div>

<script>
document.getElementById('select-all').onclick = function () {
    document.getElementsByName('adherent[]')
            .forEach(cb => cb.checked = this.checked);
};
document.getElementById('exam-search').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#adherent-list tbody tr').forEach(row => {
        const name = row.cells[1]?.textContent.toLowerCase() ?? '';
        const id   = row.cells[2]?.textContent.toLowerCase() ?? '';
        row.style.display = (name.includes(q) || id.includes(q)) ? '' : 'none';
    });
});

function submitExam() {
    var checked = document.querySelectorAll('input[name="adherent[]"]:checked');
    if (checked.length === 0) {
        showToast('يرجى اختيار مرشح واحد على الأقل', 'error');
        return;
    }
    if (!document.getElementById('session').value) {
        showToast('يرجى اختيار الدورة', 'error');
        return;
    }
    document.querySelector('form').submit();
}
</script>

<?php require 'layout/footer.php'; ?>