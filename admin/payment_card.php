<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$identifier = trim($_GET['id'] ?? '');

// Default sport year: September marks the start
$nowYear  = (int)date('Y');
$nowMonth = (int)date('n');
$defaultSportYear = $nowMonth >= 9 ? $nowYear : $nowYear - 1;

$year = (int)($_GET['year'] ?? $defaultSportYear);

if (empty($identifier)) {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$payment  = new Payment($conn);
$adherent = new Adherent($conn);

$memberRows = $adherent->getById($identifier);
if (!$memberRows) {
    header("Location: /sport-club/admin/payments.php");
    exit();
}
$member = $memberRows[0];

$cardData = $payment->getCardData($identifier, $year);
$years    = range($defaultSportYear, 2020);

// Months in sport-year order: Sep→Aug
$months = [
    9  => 'شتنبر',  10 => 'أكتوبر', 11 => 'نونبر',  12 => 'دجنبر',
    1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',   4  => 'أبريل',
    5  => 'مايو',   6  => 'يونيو',  7  => 'يوليو',  8  => 'غشت',
];

$imgSrc = !empty($member['image_path'])
    ? '/sport-club/assets/uploads/' . $member['image_path']
    : '/sport-club/assets/images/defult_image.png';

$hasImage = !empty($member['image_path']);
$hasBc    = !empty($member['BC_path']);

$monthlyDue      = $cardData['monthlyDue'];
$attendanceCounts= $cardData['attendanceCounts'];
$monthsPaid      = $cardData['monthsPaid'];   // [month => amount]
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">بطاقة الدفع</h1>

<div class="absences p-20 bg-fff rad-10 m-20">

    <!-- Header: member info + image + warnings -->
    <div class="payment-card-header mb-20">
        <div class="member-photo-wrap">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="صورة" class="member-photo">
            <?php if (!$hasImage): ?>
                <span class="doc-warning" title="لا توجد صورة">⚠ لا صورة</span>
            <?php endif; ?>
            <?php if (!$hasBc): ?>
                <span class="doc-warning" title="لا يوجد عقد ميلاد">⚠ لا عقد ميلاد</span>
            <?php endif; ?>
        </div>
        <div class="member-info-wrap">
            <h2 class="mt-0 mb-5">
                <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?>
                <small class="fs-14 color-c-60"><?= htmlspecialchars($identifier) ?></small>
            </h2>
            <p class="mb-5 color-c-60"><?= htmlspecialchars($member['type'] ?? '') ?></p>
            <p class="mb-0">
                الواجب الشهري:
                <strong><?= number_format($monthlyDue, 2) ?> DH</strong>
                <?php if ($member['monthly_price'] !== null && $member['monthly_price'] !== ''): ?>
                    <span class="badge-special">سعر خاص</span>
                <?php endif; ?>
            </p>
        </div>
        <a href="/sport-club/admin/payments.php" class="btn-shape bg-c-60 color-fff">← رجوع</a>
    </div>

    <!-- Year selector -->
    <select id="yearSelect" class="mb-20 p-10">
        <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>
                <?= $y ?>–<?= $y + 1 ?>
            </option>
        <?php endforeach; ?>
    </select>

    <form method="POST" action="/sport-club/actions/payment_card_save.php" id="cardForm">
        <input type="hidden" name="identifier" value="<?= htmlspecialchars($identifier) ?>">
        <input type="hidden" name="year" id="yearInput" value="<?= $year ?>">

        <!-- Monthly cells -->
        <div class="flex-table" id="monthGrid">
            <?php foreach (array_chunk(array_keys($months), 3) as $chunk): ?>
                <div class="flex-row">
                    <?php foreach ($chunk as $num):
                        $name    = $months[$num];
                        $paid    = isset($monthsPaid[$num]);
                        $paidAmt = $paid ? $monthsPaid[$num] : 0;
                        $att     = $attendanceCounts[$num] ?? 0;
                        $overdue = !$paid && $att >= 5;
                        $partial = $paid && $paidAmt < $monthlyDue - 0.01;

                        // Actual calendar year for this month
                        $actualYear = $num >= 9 ? $year : $year + 1;
                        // Is this month in the future?
                        $isFuture = ($actualYear > $nowYear) || ($actualYear === $nowYear && $num > $nowMonth);

                        $cellClass = 'flex-cell';
                        if ($paid && !$partial)  $cellClass .= ' paid';
                        elseif ($partial)         $cellClass .= ' partial';
                        elseif ($overdue)         $cellClass .= ' overdue';
                        if ($isFuture)            $cellClass .= ' future-month';
                    ?>
                        <div class="<?= $cellClass ?>" data-month="<?= $num ?>" data-due="<?= $monthlyDue ?>">

                            <!-- View mode -->
                            <div class="cell-view">
                                <h4 class="mb-5"><?= $name ?></h4>
                                <?php if ($paid): ?>
                                    <span class="cell-paid-amt"><?= number_format($paidAmt, 2) ?> DH</span>
                                    <?php if ($partial): ?>
                                        <span class="cell-rest-badge">متبقي <?= number_format($monthlyDue - $paidAmt, 2) ?> DH</span>
                                    <?php endif; ?>
                                <?php elseif ($overdue): ?>
                                    <span class="cell-overdue-badge">⚠ <?= $att ?> حصص</span>
                                <?php elseif (!$isFuture): ?>
                                    <span class="cell-unpaid-amt"><?= number_format($monthlyDue, 2) ?> DH</span>
                                <?php endif; ?>
                                <?php if ($att > 0 && !$isFuture): ?>
                                    <small class="cell-att"><?= $att ?> حضور</small>
                                <?php endif; ?>
                            </div>

                            <!-- Edit mode (hidden by default) -->
                            <div class="cell-edit hidden">
                                <h4 class="mb-5"><?= $name ?></h4>
                                <small class="color-aaa">المستحق: <?= number_format($monthlyDue, 2) ?> DH</small>
                                <input type="number"
                                       name="amounts[<?= $num ?>]"
                                       class="cash-input"
                                       placeholder="النقد"
                                       step="0.01" min="0"
                                       value="<?= $paidAmt > 0 ? $paidAmt : '' ?>">
                                <div class="rest-display"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- Assurance + Adhesion row -->
            <div class="flex-row">
                <div class="flex-cell <?= $cardData['assurancePaid'] ? 'paid' : '' ?>">
                    <div class="cell-view">
                        <h4 class="mb-5">التأمين</h4>
                        <span class="cell-unpaid-amt"><?= number_format($cardData['assurancePrice'], 2) ?> DH</span>
                    </div>
                    <div class="cell-edit hidden">
                        <h4 class="mb-5">التأمين</h4>
                        <input type="checkbox" name="assurance" value="1" <?= $cardData['assurancePaid'] ? 'checked' : '' ?>>
                        <small><?= number_format($cardData['assurancePrice'], 2) ?> DH</small>
                    </div>
                </div>
                <div class="flex-cell <?= $cardData['adhesionPaid'] ? 'paid' : '' ?>">
                    <div class="cell-view">
                        <h4 class="mb-5">الانخراط السنوي</h4>
                        <span class="cell-unpaid-amt"><?= number_format($cardData['adhesionPrice'], 2) ?> DH</span>
                    </div>
                    <div class="cell-edit hidden">
                        <h4 class="mb-5">الانخراط السنوي</h4>
                        <input type="checkbox" name="adhesion" value="1" <?= $cardData['adhesionPaid'] ? 'checked' : '' ?>>
                        <small><?= number_format($cardData['adhesionPrice'], 2) ?> DH</small>
                    </div>
                </div>
                <div class="flex-cell"></div>
            </div>
        </div>

        <div class="action-buttons">
            <button type="button" class="btn-shape modify-btn mb-10">
                <i class="fas fa-edit"></i> تعديل
            </button>
            <button type="submit" class="btn-shape save-btn hidden mb-10">
                <i class="fas fa-save"></i> حفظ
            </button>
            <button type="button" class="btn-shape cancel-btn hidden mb-10 bg-c-60 color-fff">
                <i class="fas fa-times"></i> إلغاء
            </button>
        </div>
    </form>
</div>

<style>
.payment-card-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
}
.member-photo-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.member-photo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ccc;
}
.doc-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 2px 8px;
    font-size: 12px;
    white-space: nowrap;
}
.member-info-wrap {
    flex: 1;
}
.badge-special {
    background: #d1ecf1;
    color: #0c5460;
    border-radius: 4px;
    padding: 2px 8px;
    font-size: 12px;
    margin-right: 6px;
}
/* Cell status colors */
.overdue  { background-color: #ffe0b2; }
.partial  { background-color: #fff9c4; }
.future-month { opacity: 0.4; }
/* Cell content */
.cell-paid-amt    { color: #1a7a3a; font-weight: bold; font-size: 14px; }
.cell-unpaid-amt  { color: #666; font-size: 13px; }
.cell-overdue-badge { color: #b36b00; font-size: 13px; font-weight: bold; }
.cell-rest-badge  { background: #fffbe6; color: #b36b00; border-radius: 4px;
                    padding: 1px 6px; font-size: 12px; }
.cell-att         { color: #999; display: block; margin-top: 4px; }
/* Edit mode */
.cash-input {
    width: 90%;
    padding: 6px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: 6px;
    font-size: 14px;
}
.rest-display {
    margin-top: 4px;
    font-size: 13px;
    font-weight: bold;
    min-height: 18px;
}
.rest-positive { color: #1a7a3a; }
.rest-negative { color: #c00; }
</style>

<script>
const modifyBtn = document.querySelector('.modify-btn');
const saveBtn   = document.querySelector('.save-btn');
const cancelBtn = document.querySelector('.cancel-btn');

modifyBtn.addEventListener('click', function () {
    document.querySelectorAll('.cell-view').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.cell-edit').forEach(el => el.classList.remove('hidden'));
    modifyBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
    cancelBtn.classList.remove('hidden');
});

cancelBtn.addEventListener('click', function () {
    window.location.reload();
});

// Calculate rest dynamically
document.querySelectorAll('.cash-input').forEach(input => {
    const cell    = input.closest('.flex-cell');
    const due     = parseFloat(cell.dataset.due) || 0;
    const display = cell.querySelector('.rest-display');

    function update() {
        const cash = parseFloat(input.value) || 0;
        if (input.value === '') {
            display.textContent = '';
            return;
        }
        const rest = cash - due;
        if (rest >= 0) {
            display.textContent = 'الباقي: ' + rest.toFixed(2) + ' DH';
            display.className   = 'rest-display rest-positive';
        } else {
            display.textContent = 'ناقص: ' + Math.abs(rest).toFixed(2) + ' DH';
            display.className   = 'rest-display rest-negative';
        }
    }
    input.addEventListener('input', update);
    // Run on page load to restore values
    if (input.value) update();
});

document.getElementById('yearSelect').addEventListener('change', function () {
    window.location.href = `?id=<?= urlencode($identifier) ?>&year=${this.value}`;
});
</script>

<?php require 'layout/footer.php'; ?>
