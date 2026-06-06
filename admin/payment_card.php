<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$identifier = trim($_GET['id'] ?? '');

$nowYear  = (int)date('Y');
$nowMonth = (int)date('n');
$defaultSportYear = $nowMonth >= 9 ? $nowYear : $nowYear - 1;
$defaultSeason    = $defaultSportYear . '-' . ($defaultSportYear + 1);

// Accept season=YYYY-YYYY (preferred) or legacy year=YYYY
if (isset($_GET['season'])) {
    $_SESSION['payment_card_season'] = $_GET['season'];
} elseif (isset($_GET['year'])) {
    $y = (int)$_GET['year'];
    $_SESSION['payment_card_season'] = $y . '-' . ($y + 1);
}
$season = $_SESSION['payment_card_season'] ?? $defaultSeason;
$year   = (int)explode('-', $season)[0]; // sport year start (e.g. 2025 from "2025-2026")

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

// Sport-year month order: Sep → Aug
$months = [
    9=>'شتنبر', 10=>'أكتوبر', 11=>'نونبر', 12=>'دجنبر',
    1=>'يناير',  2=>'فبراير',  3=>'مارس',   4=>'أبريل',
    5=>'مايو',   6=>'يونيو',   7=>'يوليو',  8=>'غشت',
];

$imgSrc   = !empty($member['image_path'])
    ? '/sport-club/assets/uploads/' . $member['image_path']
    : '/sport-club/assets/images/defult_image.png';
$hasImage = !empty($member['image_path']);
$hasBc    = !empty($member['BC_path']);

$monthlyDue      = $cardData['monthlyDue'];
$monthsPaid      = $cardData['monthsPaid'];        // [month => cash paid]
$monthsDue       = $cardData['monthsDue'];         // [month => saved due_amount]
$attendanceCounts= $cardData['attendanceCounts'];  // [month => count]
$totalSessions   = $cardData['totalSessions'];
// Use all-time count but also count sessions in this sport year for trial check
$yearSessions    = array_sum($attendanceCounts);
$inTrial         = $totalSessions < 5 && $yearSessions < 5;
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">بطاقة الدفع</h1>

<div class="absences p-20 bg-fff rad-10 m-20">

    <!-- Header -->
    <div class="pc-header mb-20">
        <div class="pc-photo-col">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="صورة" class="pc-photo">
            <?php if (!$hasImage): ?>
                <span class="doc-warn">⚠ لا صورة</span>
            <?php endif; ?>
            <?php if (!$hasBc): ?>
                <span class="doc-warn">⚠ لا عقد ميلاد</span>
            <?php endif; ?>
        </div>
        <div class="pc-info-col">
            <h2 class="mt-0 mb-5">
                <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?>
                <small class="fs-14 color-c-60"><?= htmlspecialchars($identifier) ?></small>
            </h2>
            <p class="mb-5 color-c-60"><?= htmlspecialchars($member['type'] ?? '') ?></p>
            <div class="pc-badges">
                <span class="badge-active">حضور هذا الشهر: <?= (int)($attendanceCounts[(int)date('n')] ?? 0) ?> حصص</span>
                <span class="badge-plan">الواجب: <?= number_format($monthlyDue, 2) ?> DH</span>
            </div>
        </div>
        <a href="/sport-club/admin/payments.php" class="btn-shape bg-c-60 color-fff">← رجوع</a>
    </div>

    <!-- Season selector -->
    <select id="yearSelect" class="mb-20 p-10">
        <?php foreach ($years as $y):
            $s = $y . '-' . ($y + 1); ?>
            <option value="<?= $s ?>" <?= $s === $season ? 'selected' : '' ?>>
                <?= $y ?>–<?= $y + 1 ?>
            </option>
        <?php endforeach; ?>
    </select>


    <form method="POST" action="/sport-club/actions/payment_card_save.php" id="cardForm">
        <input type="hidden" name="identifier" value="<?= htmlspecialchars($identifier) ?>">
        <input type="hidden" name="season" value="<?= htmlspecialchars($season) ?>">

        <!-- ─── Monthly cells ─── -->
        <div class="flex-table">
            <?php foreach (array_chunk(array_keys($months), 3) as $chunk): ?>
                <div class="flex-row">
                    <?php foreach ($chunk as $num):
                        $name    = $months[$num];
                        $paidAmt = $monthsPaid[$num] ?? 0;
                        $paid    = $paidAmt > 0;
                        $att     = $attendanceCounts[$num] ?? 0;
                        $cellDue = $monthsDue[$num] ?? $monthlyDue; // saved due or plan price
                        $overdue = !$paid && $att >= 5;
                        $partial = $paid && $cellDue > 0 && $paidAmt < $cellDue - 0.01;

                        $actualYear = $num >= 9 ? $year : $year + 1;
                        $isFuture   = ($actualYear > $nowYear) || ($actualYear === $nowYear && $num > $nowMonth);

                        $cls = 'flex-cell';
                        if ($paid && !$partial)  $cls .= ' paid';
                        elseif ($partial)         $cls .= ' cell-partial';
                        elseif ($overdue)         $cls .= ' cell-overdue';
                        if ($isFuture)            $cls .= ' cell-future';
                    ?>
                        <div class="<?= $cls ?>" data-due="<?= $cellDue ?>">

                            <!-- VIEW -->
                            <div class="cv">
                                <h4 class="mb-4"><?= $name ?></h4>
                                <?php if ($paid && !$partial): ?>
                                    <span class="amt-green">✓ <?= number_format($paidAmt, 2) ?> DH</span>
                                <?php elseif ($partial): ?>
                                    <span class="amt-orange"><?= number_format($paidAmt, 2) ?> / <?= number_format($cellDue, 2) ?> DH</span>
                                    <small class="rest-lbl">متبقي <?= number_format($cellDue - $paidAmt, 2) ?> DH</small>
                                <?php elseif ($overdue): ?>
                                    <span class="amt-red">⚠ <?= $att ?> حصص</span>
                                    <small class="color-aaa"><?= number_format($cellDue, 2) ?> DH</small>
                                <?php elseif (!$isFuture): ?>
                                    <span class="amt-gray"><?= number_format($cellDue, 2) ?> DH</span>
                                <?php endif; ?>
                                <?php if ($att > 0 && !$isFuture): ?>
                                    <small class="att-lbl"><?= $att ?> حضور</small>
                                <?php endif; ?>
                            </div>

                            <!-- EDIT -->
                            <div class="ce hidden">
                                <h4 class="mb-4"><?= $name ?></h4>
                                <div class="ce-row">
                                    <label>المستحق</label>
                                    <input type="number" name="due_amounts[<?= $num ?>]"
                                           class="inp-price" step="0.01" min="0"
                                           value="<?= $cellDue ?>">
                                    <span class="dh">DH</span>
                                </div>
                                <div class="ce-row">
                                    <label>النقد</label>
                                    <input type="number" name="amounts[<?= $num ?>]"
                                           class="inp-cash" step="0.01" min="0"
                                           value="<?= $paid ? $paidAmt : '' ?>" placeholder="0.00">
                                    <span class="dh">DH</span>
                                </div>
                                <div class="ce-rest"></div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- ─── Assurance + Adhesion ─── -->
            <div class="flex-row special-row">
                <?php
                $assAmt     = $cardData['assuranceAmount'];
                $assPrice   = $cardData['assurancePrice'];
                $assCellDue = $cardData['assuranceDue'] > 0 ? $cardData['assuranceDue'] : $assPrice;
                $assPartial = $assAmt > 0 && $assCellDue > 0 && $assAmt < $assCellDue - 0.01;
                $assCls     = 'flex-cell' . ($assAmt > 0 ? ($assPartial ? ' cell-partial' : ' paid') : ' cell-overdue');

                $adhAmt     = $cardData['adhesionAmount'];
                $adhPrice   = $cardData['adhesionPrice'];
                $adhCellDue = $cardData['adhesionDue'] > 0 ? $cardData['adhesionDue'] : $adhPrice;
                $adhPartial = $adhAmt > 0 && $adhCellDue > 0 && $adhAmt < $adhCellDue - 0.01;
                $adhCls     = 'flex-cell' . ($adhAmt > 0 ? ($adhPartial ? ' cell-partial' : ' paid') : ' cell-overdue');
                ?>

                <!-- Assurance -->
                <div class="<?= $assCls ?>" data-due="<?= $assCellDue ?>">
                    <div class="cv">
                        <h4 class="mb-4">التأمين</h4>
                        <?php if ($assAmt > 0 && !$assPartial): ?>
                            <span class="amt-green">✓ <?= number_format($assAmt, 2) ?> DH</span>
                        <?php elseif ($assPartial): ?>
                            <span class="amt-orange"><?= number_format($assAmt, 2) ?> / <?= number_format($assCellDue, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-red">⚠ غير مدفوع</span>
                            <small class="color-aaa"><?= number_format($assCellDue, 2) ?> DH</small>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">التأمين</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="assurance_due" class="inp-price"
                                   step="0.01" min="0" value="<?= $assCellDue ?>">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" name="assurance_amount" class="inp-cash"
                                   step="0.01" min="0" value="<?= $assAmt > 0 ? $assAmt : '' ?>" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                    </div>
                </div>

                <!-- Adhesion -->
                <div class="<?= $adhCls ?>" data-due="<?= $adhCellDue ?>">
                    <div class="cv">
                        <h4 class="mb-4">الانخراط السنوي</h4>
                        <?php if ($adhAmt > 0 && !$adhPartial): ?>
                            <span class="amt-green">✓ <?= number_format($adhAmt, 2) ?> DH</span>
                        <?php elseif ($adhPartial): ?>
                            <span class="amt-orange"><?= number_format($adhAmt, 2) ?> / <?= number_format($adhCellDue, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-red">⚠ غير مدفوع</span>
                            <small class="color-aaa"><?= number_format($adhCellDue, 2) ?> DH</small>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">الانخراط السنوي</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="adhesion_due" class="inp-price"
                                   step="0.01" min="0" value="<?= $adhCellDue ?>">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" name="adhesion_amount" class="inp-cash"
                                   step="0.01" min="0"
                                   value="<?= $adhAmt > 0 ? $adhAmt : '' ?>" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
            </div>

            <!-- ─── Belt Exams ─── -->
            <?php
            $examPrice    = $cardData['examPrice'];
            $janAmt       = $cardData['examJanAmount'];
            $junAmt       = $cardData['examJunAmount'];
            $janCellDue   = $cardData['examJanDue'] > 0 ? $cardData['examJanDue'] : $examPrice;
            $junCellDue   = $cardData['examJunDue'] > 0 ? $cardData['examJunDue'] : $examPrice;
            $janPartial   = $janAmt > 0 && $janCellDue > 0 && $janAmt < $janCellDue - 0.01;
            $junPartial   = $junAmt > 0 && $junCellDue > 0 && $junAmt < $junCellDue - 0.01;
            $janCls       = 'flex-cell' . ($janAmt > 0 ? ($janPartial ? ' cell-partial' : ' paid') : '');
            $junCls       = 'flex-cell' . ($junAmt > 0 ? ($junPartial ? ' cell-partial' : ' paid') : '');
            ?>
            <div class="flex-row exam-row">
                <!-- Jan exam -->
                <div class="<?= $janCls ?>" data-due="<?= $janCellDue ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 امتحان يناير</h4>
                        <?php if ($janAmt > 0 && !$janPartial): ?>
                            <span class="amt-green">✓ <?= number_format($janAmt, 2) ?> DH</span>
                        <?php elseif ($janPartial): ?>
                            <span class="amt-orange"><?= number_format($janAmt, 2) ?> / <?= number_format($janCellDue, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-gray"><?= number_format($janCellDue, 2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">🥋 امتحان يناير</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="exam_jan_due" class="inp-price"
                                   step="0.01" min="0" value="<?= $janCellDue ?>">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" name="exam_jan_amount" class="inp-cash"
                                   step="0.01" min="0"
                                   value="<?= $janAmt > 0 ? $janAmt : '' ?>" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                    </div>
                </div>

                <!-- Jun exam -->
                <div class="<?= $junCls ?>" data-due="<?= $junCellDue ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 امتحان يونيو</h4>
                        <?php if ($junAmt > 0 && !$junPartial): ?>
                            <span class="amt-green">✓ <?= number_format($junAmt, 2) ?> DH</span>
                        <?php elseif ($junPartial): ?>
                            <span class="amt-orange"><?= number_format($junAmt, 2) ?> / <?= number_format($junCellDue, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-gray"><?= number_format($junCellDue, 2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">🥋 امتحان يونيو</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="exam_jun_due" class="inp-price"
                                   step="0.01" min="0" value="<?= $junCellDue ?>">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" name="exam_jun_amount" class="inp-cash"
                                   step="0.01" min="0"
                                   value="<?= $junAmt > 0 ? $junAmt : '' ?>" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
            </div>

        </div><!-- /flex-table -->

        <div class="action-buttons">
            <button type="button" class="btn-shape modify-btn mb-10"><i class="fas fa-edit"></i> تعديل</button>
            <button type="submit" class="btn-shape save-btn hidden mb-10"><i class="fas fa-save"></i> حفظ</button>
            <button type="button" class="btn-shape cancel-btn hidden mb-10 bg-c-60 color-fff"><i class="fas fa-times"></i> إلغاء</button>
        </div>
    </form>
</div>

<style>
/* Header */
.pc-header { display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap; }
.pc-photo-col { display:flex; flex-direction:column; align-items:center; gap:5px; }
.pc-photo { width:88px; height:88px; border-radius:50%; object-fit:cover; border:2px solid #ccc; }
.doc-warn { background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:4px;
            padding:2px 7px; font-size:12px; white-space:nowrap; }
.pc-info-col { flex:1; }
.pc-badges { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
.badge-active { background:#dbeafe; color:#1e40af; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }
.badge-plan   { background:#f0fdf4; color:#166534; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }

/* Cell status */
.cell-overdue { background:#ffe0b2; }
.cell-partial { background:#fff9c4; }
.cell-future  { opacity:.6; }
/* Exam and special rows: 2 cells each taking 50% */
.exam-row .flex-cell,
.special-row .flex-cell { flex: 1 1 50%; }

/* Cell text */
.amt-green  { color:#1a7a3a; font-weight:bold; font-size:13px; }
.amt-orange { color:#b36b00; font-weight:bold; font-size:13px; }
.amt-red    { color:#c00; font-weight:bold; font-size:13px; }
.amt-gray   { color:#888; font-size:13px; }
.rest-lbl   { display:block; color:#b36b00; font-size:11px; }
.att-lbl    { display:block; color:#aaa; font-size:11px; margin-top:3px; }
.mb-4       { margin-bottom:4px; }

/* Edit mode cell */
.ce-row { display:flex; align-items:center; gap:4px; margin:3px 0; font-size:12px; }
.ce-row label { width:48px; text-align:right; color:#666; }
.inp-price, .inp-cash {
    width:70px; padding:4px 5px; border:1px solid #ccc;
    border-radius:4px; font-size:13px; text-align:center;
}
.inp-price { border-color:#203a85; }
.dh { font-size:11px; color:#888; }
.ce-rest { font-size:12px; font-weight:bold; min-height:16px; margin-top:2px; }
.rest-pos { color:#1a7a3a; }
.rest-neg { color:#c00; }
</style>

<script>
const modBtn    = document.querySelector('.modify-btn');
const saveBtn   = document.querySelector('.save-btn');
const cancelBtn = document.querySelector('.cancel-btn');

modBtn.addEventListener('click', () => {
    document.querySelectorAll('.cv').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.ce').forEach(el => el.classList.remove('hidden'));
    modBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
    cancelBtn.classList.remove('hidden');
});

cancelBtn.addEventListener('click', () => window.location.reload());

// Live change/rest calculation
document.querySelectorAll('.flex-cell').forEach(cell => {
    const priceIn = cell.querySelector('.inp-price');
    const cashIn  = cell.querySelector('.inp-cash');
    const restDiv = cell.querySelector('.ce-rest');
    if (!priceIn || !cashIn || !restDiv) return;

    function calc() {
        const price = parseFloat(priceIn.value) || 0;
        const cash  = parseFloat(cashIn.value)  || 0;
        if (!cashIn.value) { restDiv.textContent = ''; return; }
        const diff = cash - price;
        if (diff >= 0) {
            restDiv.textContent = 'الباقي: +' + diff.toFixed(2) + ' DH';
            restDiv.className   = 'ce-rest rest-pos';
        } else {
            restDiv.textContent = 'ناقص: ' + Math.abs(diff).toFixed(2) + ' DH';
            restDiv.className   = 'ce-rest rest-neg';
        }
    }
    priceIn.addEventListener('input', calc);
    cashIn.addEventListener('input', calc);
});

document.getElementById('yearSelect').addEventListener('change', function () {
    window.location.href = `?id=<?= urlencode($identifier) ?>&season=${this.value}`;
});
</script>

<?php require 'layout/footer.php'; ?>