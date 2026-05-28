<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$evaluation  = new Evaluation($conn);
$adherent    = new Adherent($conn);
$plan        = new Plan($conn);

$members      = $adherent->getAll('active');
$plans        = $plan->getNames();
$latestEvals  = $evaluation->getLatestAll();

$currentMonth = (int) date('n');
$currentYear  = (int) date('Y');
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">التقييمات</h1>

<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">المشتركين</h2>
    <div class="responsive-table special">
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
        <table class="fs-15 w-full" id="eval-list">
            <thead>
                <tr>
                    <th>الاسم الكامل</th>
                    <th>الرياضة</th>
                    <th>الانضباط</th>
                    <th>الأداء الرياضي</th>
                    <th>السلوك</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                    <?php
                    $eval        = $latestEvals[$m['identifier']] ?? null;
                    $discipline  = min((int) ($eval['discipline']  ?? 0), 5);
                    $performance = min((int) ($eval['performance'] ?? 0), 5);
                    $behavior    = min((int) ($eval['behavior']    ?? 0), 5);
                    ?>
                    <form action="/sport-club/actions/evaluation_save.php" method="post">
                    <tr data-branch="<?= htmlspecialchars($m['type']) ?>">
                        <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                        <td><?= htmlspecialchars($m['type']) ?></td>
                        <td>
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="<?= $i < $discipline ? 'fa-solid' : 'fa-regular' ?> fa-star" data-index="<?= $i ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="discipline" value="<?= $discipline ?>">
                        </td>
                        <td>
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="<?= $i < $performance ? 'fa-solid' : 'fa-regular' ?> fa-star" data-index="<?= $i ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="performance" value="<?= $performance ?>">
                        </td>
                        <td>
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="<?= $i < $behavior ? 'fa-solid' : 'fa-regular' ?> fa-star" data-index="<?= $i ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="behavior" value="<?= $behavior ?>">
                        </td>
                        <td>
                            <input type="hidden" name="identifier" value="<?= htmlspecialchars($m['identifier']) ?>">
                            <input type="hidden" name="month" value="<?= $currentMonth ?>">
                            <input type="hidden" name="year" value="<?= $currentYear ?>">
                            <button type="submit" class="btn-shape bg-c-60 color-fff">حفظ</button>
                        </td>
                    </tr>
                    </form>
                <?php endforeach; ?>
                <tr class="no-results" style="display:none;">
                    <td colspan="6">لا توجد نتائج</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require 'layout/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const branchButtons = document.querySelectorAll(".branch-filter button");
    const rows          = document.querySelectorAll("#eval-list tbody tr[data-branch]");
    const noResultsRow  = document.querySelector("#eval-list tbody .no-results");

    branchButtons.forEach(button => {
        button.addEventListener("click", function () {
            branchButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");

            const branch = button.getAttribute("data-branch");
            let hasVisible = false;

            rows.forEach(row => {
                const match = branch === "all" || row.getAttribute("data-branch") === branch;
                row.style.display = match ? "" : "none";
                if (match) hasVisible = true;
            });

            noResultsRow.style.display = hasVisible ? "none" : "";
        });
    });

    document.querySelectorAll("#eval-list tbody td").forEach(td => {
        const stars  = td.querySelectorAll(".fa-star");
        const hidden = td.querySelector("input[type='hidden']");
        if (!stars.length || !hidden) return;

        stars.forEach((star, index) => {
            star.addEventListener("click", function () {
                stars.forEach((s, i) => {
                    s.classList.toggle("fa-solid",  i <= index);
                    s.classList.toggle("fa-regular", i > index);
                });
                hidden.value = index + 1;
            });
        });
    });
});
</script>