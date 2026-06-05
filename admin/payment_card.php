<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$identifier = trim($_GET['id'] ?? '');

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
$monthsPaid      = $cardData['monthsPaid'];        // [month => amount]
$attendanceCounts= $cardData['attendanceCounts'];  // [month => count]
$totalSessions   = $cardData['totalSessions'];
$inTrial         = $totalSessions < 5;
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
                <?php if ($inTrial): ?>
                    <span class="badge-trial">⏳ فترة تجريبية (<?= $totalSessions ?>/5 حصص)</span>
                <?php else: ?>
                    <span class="badge-active">✓ منخرط (<?= $totalSessions ?> حصة)</span>
                <?php endif; ?>
                <?php if ($member['monthly_price'] !== null && $member['monthly_price'] !== ''): ?>
                    <span class="badge-special">سعر خاص: <?= number_format((float)$member['monthly_price'], 2) ?> DH</span>
                <?php else: ?>
                    <span class="badge-plan">الواجب: <?= number_format($monthlyDue, 2) ?> DH</span>
                <?php endif; ?>
            </div>
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
        <input type="hidden" name="year" value="<?= $year ?>">

        <!-- ─── Monthly cells ─── -->
        <div class="flex-table">
            <?php foreach (array_chunk(array_keys($months), 3) as $chunk): ?>
                <div class="flex-row">
                    <?php foreach ($chunk as $num):
                        $name    = $months[$num];
                        $paidAmt = $monthsPaid[$num] ?? 0;
                        $paid    = $paidAmt > 0;
                        $att     = $attendanceCounts[$num] ?? 0;
                        $overdue = !$paid && !$inTrial && $att >= 5;
                        $partial = $paid && $monthlyDue > 0 && $paidAmt < $monthlyDue - 0.01;

                        $actualYear = $num >= 9 ? $year : $year + 1;
                        $isFuture   = ($actualYear > $nowYear) || ($actualYear === $nowYear && $num > $nowMonth);

                        $cls = 'flex-cell';
                        if ($paid && !$partial)  $cls .= ' paid';
                        elseif ($partial)         $cls .= ' cell-partial';
                        elseif ($overdue)         $cls .= ' cell-overdue';
                        if ($isFuture)            $cls .= ' cell-future';
                    ?>
                        <div class="<?= $cls ?>" data-due="<?= $monthlyDue ?>">

                            <!-- VIEW -->
                            <div class="cv">
                                <h4 class="mb-4"><?= $name ?></h4>
                                <?php if ($paid && !$partial): ?>
                                    <span class="amt-green">✓ <?= number_format($paidAmt, 2) ?> DH</span>
                                <?php elseif ($partial): ?>
                                    <span class="amt-orange"><?= number_format($paidAmt, 2) ?> / <?= number_format($monthlyDue, 2) ?> DH</span>
                                    <small class="rest-lbl">متبقي <?= number_format($monthlyDue - $paidAmt, 2) ?> DH</small>
                                <?php elseif ($overdue): ?>
                                    <span class="amt-red">⚠ <?= $att ?> حصص</span>
                                    <small class="color-aaa"><?= number_format($monthlyDue, 2) ?> DH</small>
                                <?php elseif (!$isFuture): ?>
                                    <span class="amt-gray"><?= number_format($monthlyDue, 2) ?> DH</span>
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
                                    <input type="number" name="amounts[<?= $num ?>]"
                                           class="inp-price" step="0.01" min="0"
                                           value="<?= $paid ? $paidAmt : $monthlyDue ?>"
                                           <?= !$paid ? 'data-unpaid="'.$monthlyDue.'"' : '' ?>>
                                    <span class="dh">DH</span>
                                </div>
                                <div class="ce-row">
                                    <label>النقد</label>
                                    <input type="number" class="inp-cash" step="0.01" min="0" placeholder="0.00">
                                    <span class="dh">DH</span>
                                </div>
                                <div class="ce-rest"></div>
                                <?php if (!$paid): ?>
                                    <small class="color-aaa">غيّر المبلغ لتسجيله • اتركه كما هو = غير مدفوع</small>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- ─── Assurance + Adhesion ─── -->
            <div class="flex-row special-row">
                <?php
                $assAmt  = $cardData['assuranceAmount'];
                $assPrice= $cardData['assurancePrice'];
                $assPartial = $assAmt > 0 && $assAmt < $assPrice - 0.01;
                $assCls  = 'flex-cell' . ($assAmt > 0 ? ($assPartial ? ' cell-partial' : ' paid') : '');

                $adhAmt  = $cardData['adhesionAmount'];
                $adhPrice= $cardData['adhesionPrice'];
                $adhPartial = $adhAmt > 0 && $adhAmt < $adhPrice - 0.01;
                $adhCls  = 'flex-cell' . ($adhAmt > 0 ? ($adhPartial ? ' cell-partial' : ' paid') : '');
                ?>

                <!-- Assurance -->
                <div class="<?= $assCls ?>" data-due="<?= $assPrice ?>">
                    <div class="cv">
                        <h4 class="mb-4">التأمين</h4>
                        <?php if ($assAmt > 0 && !$assPartial): ?>
                            <span class="amt-green">✓ <?= number_format($assAmt, 2) ?> DH</span>
                        <?php elseif ($assPartial): ?>
                            <span class="amt-orange"><?= number_format($assAmt, 2) ?> / <?= number_format($assPrice, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-gray"><?= number_format($assPrice, 2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">التأمين</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="assurance_amount"
                                   class="inp-price"
                                   step="0.01" min="0" value="<?= $assAmt > 0 ? $assAmt : $assPrice ?>"
                                   <?= $assAmt <= 0 ? 'data-unpaid="'.$assPrice.'"' : '' ?>>
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" class="inp-cash" step="0.01" min="0" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                    </div>
                </div>

                <!-- Adhesion -->
                <div class="<?= $adhCls ?>" data-due="<?= $adhPrice ?>">
                    <div class="cv">
                        <h4 class="mb-4">الانخراط السنوي</h4>
                        <?php if ($adhAmt > 0 && !$adhPartial): ?>
                            <span class="amt-green">✓ <?= number_format($adhAmt, 2) ?> DH</span>
                        <?php elseif ($adhPartial): ?>
                            <span class="amt-orange"><?= number_format($adhAmt, 2) ?> / <?= number_format($adhPrice, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-gray"><?= number_format($adhPrice, 2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">الانخراط السنوي</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="adhesion_amount"
                                   class="inp-price"
                                   step="0.01" min="0" value="<?= $adhAmt > 0 ? $adhAmt : $adhPrice ?>"
                                   <?= $adhAmt <= 0 ? 'data-unpaid="'.$adhPrice.'"' : '' ?>>
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" class="inp-cash" step="0.01" min="0" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
            </div>

            <!-- ─── Belt Exams ─── -->
            <?php
            $examPrice   = $cardData['examPrice'];
            $janAmt      = $cardData['examJanAmount'];
            $junAmt      = $cardData['examJunAmount'];
            $janPartial  = $janAmt > 0 && $examPrice > 0 && $janAmt < $examPrice - 0.01;
            $junPartial  = $junAmt > 0 && $examPrice > 0 && $junAmt < $examPrice - 0.01;
            $janCls      = 'flex-cell' . ($janAmt > 0 ? ($janPartial ? ' cell-partial' : ' paid') : '');
            $junCls      = 'flex-cell' . ($junAmt > 0 ? ($junPartial ? ' cell-partial' : ' paid') : '');
            ?>
            <div class="flex-row exam-row">
                <!-- Jan exam -->
                <div class="<?= $janCls ?>" data-due="<?= $examPrice ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 فحص يناير</h4>
                        <?php if ($janAmt > 0 && !$janPartial): ?>
                            <span class="amt-green">✓ <?= number_format($janAmt, 2) ?> DH</span>
                        <?php elseif ($janPartial): ?>
                            <span class="amt-orange"><?= number_format($janAmt, 2) ?> / <?= number_format($examPrice, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-gray"><?= number_format($examPrice, 2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">🥋 فحص يناير</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="exam_jan_amount"
                                   class="inp-price"
                                   step="0.01" min="0" value="<?= $janAmt > 0 ? $janAmt : $examPrice ?>"
                                   <?= $janAmt <= 0 ? 'data-unpaid="'.$examPrice.'"' : '' ?>>
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" class="inp-cash" step="0.01" min="0" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                        <small class="color-aaa">اتركه 0 إن لم يشارك</small>
                    </div>
                </div>

                <!-- Jun exam -->
                <div class="<?= $junCls ?>" data-due="<?= $examPrice ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 فحص يونيو</h4>
                        <?php if ($junAmt > 0 && !$junPartial): ?>
                            <span class="amt-green">✓ <?= number_format($junAmt, 2) ?> DH</span>
                        <?php elseif ($junPartial): ?>
                            <span class="amt-orange"><?= number_format($junAmt, 2) ?> / <?= number_format($examPrice, 2) ?> DH</span>
                        <?php else: ?>
                            <span class="amt-gray"><?= number_format($examPrice, 2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">🥋 فحص يونيو</h4>
                        <div class="ce-row">
                            <label>المستحق</label>
                            <input type="number" name="exam_jun_amount"
                                   class="inp-price"
                                   step="0.01" min="0" value="<?= $junAmt > 0 ? $junAmt : $examPrice ?>"
                                   <?= $junAmt <= 0 ? 'data-unpaid="'.$examPrice.'"' : '' ?>>
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-row">
                            <label>النقد</label>
                            <input type="number" class="inp-cash" step="0.01" min="0" placeholder="0.00">
                            <span class="dh">DH</span>
                        </div>
                        <div class="ce-rest"></div>
                        <small class="color-aaa">اتركه 0 إن لم يشارك</small>
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
.badge-trial  { background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-active { background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-special{ background:#d1ecf1; color:#0c5460; border:1px solid #bee5eb; border-radius:4px; padding:2px 8px; font-size:12px; }
.badge-plan   { background:#f8f9fa; color:#495057; border:1px solid #dee2e6; border-radius:4px; padding:2px 8px; font-size:12px; }

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

// Before submitting: zero out any unpaid cell the manager didn't change
document.getElementById('cardForm').addEventListener('submit', () => {
    document.querySelectorAll('.inp-price[data-unpaid]').forEach(inp => {
        if (parseFloat(inp.value) === parseFloat(inp.dataset.unpaid)) {
            inp.value = 0;
        }
    });
});

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
    window.location.href = `?id=<?= urlencode($identifier) ?>&year=${this.value}`;
});
</script>

<?php require 'layout/footer.php'; ?>