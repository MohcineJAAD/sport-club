<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$identifier = trim($_GET['id']   ?? '');
$year       = (int)($_GET['year'] ?? date('Y'));

if (empty($identifier)) {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$payment  = new Payment($conn);
$adherent = new Adherent($conn);

$member = $adherent->getById($identifier);
if (!$member) {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$cardData = $payment->getCardData($identifier, $year);
$years    = range(date('Y'), 2020);

$months = [
    1 => 'يناير',  2 => 'فبراير', 3 => 'مارس',
    4 => 'أبريل',  5 => 'مايو',   6 => 'يونيو',
    7 => 'يوليو',  8 => 'غشت',    9 => 'شتنبر',
    10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر',
];
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">بطاقة الدفع</h1>

<div class="absences p-20 bg-fff rad-10 m-20">
    <div class="between-flex mb-20">
        <h2 class="mt-0 mb-0">
            <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?>
            <small class="fs-14" style="color:#999;"><?= htmlspecialchars($identifier) ?></small>
        </h2>
        <a href="/sport-club/admin/payments.php" class="btn">← رجوع</a>
    </div>

    <select id="yearSelect" class="mb-20 p-10" style="width:200px;border:1px solid #333;border-radius:6px;">
        <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
    </select>

    <form method="POST" action="/sport-club/actions/payment_card_save.php">
        <input type="hidden" name="identifier" value="<?= htmlspecialchars($identifier) ?>">
        <input type="hidden" name="year" id="yearInput" value="<?= $year ?>">

        <div class="flex-table">
            <?php foreach (array_chunk($months, 3, true) as $chunk): ?>
                <div class="flex-row">
                    <?php foreach ($chunk as $num => $name):
                        $paid = in_array($num, $cardData['monthsPaid']);
                    ?>
                        <div class="flex-cell <?= $paid ? 'paid' : '' ?>">
                            <input type="checkbox" name="months[]" value="<?= $num ?>" <?= $paid ? 'checked' : '' ?> disabled>
                            <h4><?= $name ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="flex-row">
                <div class="flex-cell <?= $cardData['assurancePaid'] ? 'paid' : '' ?>" style="flex:1;">
                    <input type="checkbox" name="assurance" value="1" <?= $cardData['assurancePaid'] ? 'checked' : '' ?> disabled>
                    <h4>التأمين</h4>
                </div>
                <div class="flex-cell <?= $cardData['adhesionPaid'] ? 'paid' : '' ?>" style="flex:1;">
                    <input type="checkbox" name="adhesion" value="1" <?= $cardData['adhesionPaid'] ? 'checked' : '' ?> disabled>
                    <h4>الانخراط السنوي</h4>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button type="button" class="btn modify-btn"><i class="fas fa-edit"></i> تعديل</button>
            <button type="submit" class="btn save-btn" style="display:none;background:#2F8C37;"><i class="fas fa-save"></i> حفظ</button>
        </div>
    </form>
</div>

<script>
document.querySelector('.modify-btn').addEventListener('click', function () {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false);
    this.style.display = 'none';
    document.querySelector('.save-btn').style.display = '';
});

document.querySelector('form').addEventListener('submit', function () {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false);
});

document.getElementById('yearSelect').addEventListener('change', function () {
    window.location.href = `?id=<?= urlencode($identifier) ?>&year=${this.value}`;
});
</script>

<?php require 'layout/footer.php'; ?>
