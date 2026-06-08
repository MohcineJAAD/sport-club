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

if (isset($_GET['season'])) {
    $_SESSION['payment_card_season'] = $_GET['season'];
} elseif (isset($_GET['year'])) {
    $y = (int)$_GET['year'];
    $_SESSION['payment_card_season'] = $y . '-' . ($y + 1);
}
$season = $_SESSION['payment_card_season'] ?? $defaultSeason;
$year   = (int)explode('-', $season)[0];

if (empty($identifier)) {
    header("Location: /sport-club/admin/payments.php");
    exit();
}

$payment    = new Payment($conn);
$adherent   = new Adherent($conn);
$eventModel = new MemberEvent($conn);

$memberRows = $adherent->getById($identifier);
if (!$memberRows) {
    header("Location: /sport-club/admin/payments.php");
    exit();
}
$member = $memberRows[0];

$cardData    = $payment->getCardData($identifier, $year);
$tournaments = $eventModel->getBySeason($identifier, $season);
$years       = range($defaultSportYear, 2020);

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

$monthlyDue       = $cardData['monthlyDue'];
$monthsPaid       = $cardData['monthsPaid'];
$monthsDue        = $cardData['monthsDue'];
$attendanceCounts = $cardData['attendanceCounts'];
$totalSessions    = $cardData['totalSessions'];
$yearSessions     = array_sum($attendanceCounts);
$inTrial          = $totalSessions < 5 && $yearSessions < 5;

// Exam data
$examPrice  = $cardData['examPrice'];
$janAmt     = $cardData['examJanAmount'];
$junAmt     = $cardData['examJunAmount'];
$janDue     = $cardData['examJanDue'] > 0 ? $cardData['examJanDue'] : $examPrice;
$junDue     = $cardData['examJunDue'] > 0 ? $cardData['examJunDue'] : $examPrice;
$janPartial = $janAmt > 0 && $janDue > 0 && $janAmt < $janDue - 0.01;
$junPartial = $junAmt > 0 && $junDue > 0 && $junAmt < $junDue - 0.01;

// Exam summary status — treat sub-exam as OK if due=0 AND amt=0 (not applicable)
$janOk        = ($janAmt >= $janDue - 0.01 && $janAmt > 0) || ($janAmt == 0 && $janDue == 0);
$junOk        = ($junAmt >= $junDue - 0.01 && $junAmt > 0) || ($junAmt == 0 && $junDue == 0);
$examAnyPaid  = $janAmt > 0 || $junAmt > 0;
$examPaidFull = $janOk && $junOk && $examAnyPaid;
$examCls      = 'flex-cell' . ($examPaidFull ? ' paid' : ($examAnyPaid ? ' cell-partial' : ''));

// Tournament summary status
$tourTotal    = count($tournaments);
$tourPaidSum  = array_sum(array_column($tournaments, 'paid_amount'));
$tourDueSum   = array_sum(array_column($tournaments, 'due_amount'));
$tourPaidFull = $tourTotal > 0 && $tourPaidSum >= $tourDueSum - 0.01;
$tourAnyPaid  = $tourPaidSum > 0;
$tourCls      = 'flex-cell' . ($tourTotal === 0 ? '' : ($tourPaidFull ? ' paid' : ($tourAnyPaid ? ' cell-partial' : ' cell-overdue')));
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">بطاقة الدفع</h1>

<div class="absences p-20 bg-fff rad-10 m-20">

    <!-- Header -->
    <div class="pc-header mb-20">
        <div class="pc-photo-col">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="صورة" class="pc-photo">
            <?php if (!$hasImage): ?><span class="doc-warn">⚠ لا صورة</span><?php endif; ?>
            <?php if (!$hasBc): ?><span class="doc-warn">⚠ لا عقد ميلاد</span><?php endif; ?>
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
            <option value="<?= $s ?>" <?= $s === $season ? 'selected' : '' ?>><?= $y ?>–<?= $y + 1 ?></option>
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
                        $cellDue = $monthsDue[$num] ?? $monthlyDue;
                        $overdue = !$paid && $att >= 5;
                        $partial = $paid && $cellDue > 0 && $paidAmt < $cellDue - 0.01;
                        $cls = 'flex-cell';
                        if ($paid && !$partial)  $cls .= ' paid';
                        elseif ($partial)         $cls .= ' cell-partial';
                        elseif ($overdue)         $cls .= ' cell-overdue';
                        if ($isFuture)            $cls .= ' cell-future';
                    ?>
                        <div class="<?= $cls ?>" data-due="<?= $cellDue ?>">
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
                                <?php else: ?>
                                    <span class="amt-gray"><?= number_format($cellDue, 2) ?> DH</span>
                                <?php endif; ?>
                                <?php if ($att > 0): ?>
                                    <small class="att-lbl"><?= $att ?> حضور</small>
                                <?php endif; ?>
                            </div>
                            <div class="ce hidden">
                                <h4 class="mb-4"><?= $name ?></h4>
                                <div class="ce-row">
                                    <label>المستحق</label>
                                    <input type="number" name="due_amounts[<?= $num ?>]" class="inp-price" step="0.01" min="0" value="<?= $cellDue ?>">
                                    <span class="dh">DH</span>
                                </div>
                                <div class="ce-row">
                                    <label>النقد</label>
                                    <input type="number" name="amounts[<?= $num ?>]" class="inp-cash" step="0.01" min="0" value="<?= $paid ? $paidAmt : '' ?>" placeholder="0.00">
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
                        <div class="ce-row"><label>المستحق</label><input type="number" name="assurance_due" class="inp-price" step="0.01" min="0" value="<?= $assCellDue ?>"><span class="dh">DH</span></div>
                        <div class="ce-row"><label>النقد</label><input type="number" name="assurance_amount" class="inp-cash" step="0.01" min="0" value="<?= $assAmt > 0 ? $assAmt : '' ?>" placeholder="0.00"><span class="dh">DH</span></div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
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
                        <div class="ce-row"><label>المستحق</label><input type="number" name="adhesion_due" class="inp-price" step="0.01" min="0" value="<?= $adhCellDue ?>"><span class="dh">DH</span></div>
                        <div class="ce-row"><label>النقد</label><input type="number" name="adhesion_amount" class="inp-cash" step="0.01" min="0" value="<?= $adhAmt > 0 ? $adhAmt : '' ?>" placeholder="0.00"><span class="dh">DH</span></div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
            </div>

            <!-- ─── Exams + Tournaments ─── -->
            <div class="flex-row special-row">

                <!-- Exams summary cell -->
                <div class="<?= $examCls ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 الامتحانات</h4>
                        <button type="button" class="popup-toggle mt-4" data-popup="exam-popup">تفاصيل</button>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">🥋 الامتحانات</h4>
                        <button type="button" class="popup-toggle mt-4" data-popup="exam-popup-edit">تعديل</button>
                    </div>
                </div>

                <!-- Tournaments summary cell -->
                <div class="<?= $tourCls ?>">
                    <div class="cv">
                        <h4 class="mb-4">🏆 البطولات</h4>
                        <button type="button" class="popup-toggle mt-4" data-popup="tour-popup">تفاصيل</button>
                    </div>
                    <div class="ce hidden">
                        <h4 class="mb-4">🏆 البطولات</h4>
                        <button type="button" class="popup-toggle mt-4" data-popup="tour-popup-edit">تعديل</button>
                    </div>
                </div>

            </div>
        </div><!-- /flex-table -->

        <!-- ─── Exam popup ─── -->
        <div class="card-popup hidden" id="exam-popup">
            <div class="popup-box">
            <button type="button" class="popup-close">✕</button>
            <div class="popup-title">🥋 الامتحانات</div>
            <div class="popup-inner flex-row exam-row">
                <?php
                $janCls = 'flex-cell' . ($janAmt >= $janDue - 0.01 && $janAmt > 0 ? ' paid' : ($janPartial ? ' cell-partial' : ''));
                $junCls = 'flex-cell' . ($junAmt >= $junDue - 0.01 && $junAmt > 0 ? ' paid' : ($junPartial ? ' cell-partial' : ''));
                ?>
                <div class="<?= $janCls ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 امتحان يناير</h4>
                        <?php if ($janAmt >= $janDue - 0.01 && $janAmt > 0): ?><span class="amt-green">✓ <?= number_format($janAmt, 2) ?> DH</span>
                        <?php elseif ($janPartial): ?><span class="amt-orange"><?= number_format($janAmt, 2) ?> / <?= number_format($janDue, 2) ?> DH</span>
                        <?php else: ?><span class="amt-gray"><?= number_format($janDue, 2) ?> DH</span><?php endif; ?>
                    </div>
                </div>
                <div class="<?= $junCls ?>">
                    <div class="cv">
                        <h4 class="mb-4">🥋 امتحان يونيو</h4>
                        <?php if ($junAmt >= $junDue - 0.01 && $junAmt > 0): ?><span class="amt-green">✓ <?= number_format($junAmt, 2) ?> DH</span>
                        <?php elseif ($junPartial): ?><span class="amt-orange"><?= number_format($junAmt, 2) ?> / <?= number_format($junDue, 2) ?> DH</span>
                        <?php else: ?><span class="amt-gray"><?= number_format($junDue, 2) ?> DH</span><?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- ─── Exam popup (edit) ─── -->
        <div class="card-popup hidden" id="exam-popup-edit">
            <div class="popup-box">
            <button type="button" class="popup-close">✕</button>
            <div class="popup-title">🥋 الامتحانات — تعديل</div>
            <div class="popup-inner flex-row exam-row">
                <div class="flex-cell" data-due="<?= $janDue ?>">
                    <div class="cv hidden"></div>
                    <div class="ce" style="display:block">
                        <h4 class="mb-4">🥋 امتحان يناير</h4>
                        <div class="ce-row"><label>المستحق</label><input type="number" name="exam_jan_due" class="inp-price" step="0.01" min="0" value="<?= $janDue ?>"><span class="dh">DH</span></div>
                        <div class="ce-row"><label>النقد</label><input type="number" name="exam_jan_amount" class="inp-cash" step="0.01" min="0" value="<?= $janAmt > 0 ? $janAmt : '' ?>" placeholder="0.00"><span class="dh">DH</span></div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
                <div class="flex-cell" data-due="<?= $junDue ?>">
                    <div class="cv hidden"></div>
                    <div class="ce" style="display:block">
                        <h4 class="mb-4">🥋 امتحان يونيو</h4>
                        <div class="ce-row"><label>المستحق</label><input type="number" name="exam_jun_due" class="inp-price" step="0.01" min="0" value="<?= $junDue ?>"><span class="dh">DH</span></div>
                        <div class="ce-row"><label>النقد</label><input type="number" name="exam_jun_amount" class="inp-cash" step="0.01" min="0" value="<?= $junAmt > 0 ? $junAmt : '' ?>" placeholder="0.00"><span class="dh">DH</span></div>
                        <div class="ce-rest"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- ─── Tournaments popup (view) ─── -->
        <div class="card-popup hidden" id="tour-popup">
            <div class="popup-box">
            <button type="button" class="popup-close">✕</button>
            <div class="popup-title">🏆 البطولات</div>
            <div class="popup-inner">
                <?php if (empty($tournaments)): ?>
                    <p class="color-aaa txt-c p-10">لا توجد بطولات مسجلة لهذا الموسم</p>
                <?php else: ?>
                    <div class="flex-row exam-row">
                        <?php foreach ($tournaments as $t):
                            $tP = (float)$t['paid_amount']; $tD = (float)$t['due_amount'];
                            $tCls = 'flex-cell' . ($tP >= $tD - 0.01 && $tP > 0 ? ' paid' : ($tP > 0 ? ' cell-partial' : ($tD > 0 ? ' cell-overdue' : '')));
                        ?>
                            <div class="<?= $tCls ?>">
                                <h4 class="mb-4">🏆 <?= htmlspecialchars($t['label']) ?></h4>
                                <?php if ($tP >= $tD - 0.01 && $tP > 0): ?><span class="amt-green">✓ <?= number_format($tP, 2) ?> DH</span>
                                <?php elseif ($tP > 0): ?><span class="amt-orange"><?= number_format($tP, 2) ?> / <?= number_format($tD, 2) ?> DH</span><small class="rest-lbl">متبقي <?= number_format($tD - $tP, 2) ?> DH</small>
                                <?php else: ?><span class="amt-red">⚠ <?= number_format($tD, 2) ?> DH</span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <!-- ─── Tournaments popup (edit) ─── -->
        <div class="card-popup hidden" id="tour-popup-edit">
            <div class="popup-box">
            <button type="button" class="popup-close">✕</button>
            <div class="popup-title">🏆 البطولات — تعديل</div>
            <div class="popup-inner">
                <div id="tourContainer" class="flex-row exam-row" style="flex-wrap:wrap;gap:8px;">
                    <?php foreach ($tournaments as $t):
                        $tP = (float)$t['paid_amount']; $tD = (float)$t['due_amount'];
                    ?>
                        <div class="flex-cell tour-item" data-due="<?= $tD ?>">
                            <div class="cv hidden"></div>
                            <div class="ce" style="display:block">
                                <input type="hidden" name="tournaments[<?= $t['id'] ?>][id]" value="<?= $t['id'] ?>">
                                <h4 class="mb-4">🏆 <?= htmlspecialchars($t['label']) ?></h4>
                                <div class="ce-row"><label>الاسم</label><input type="text" name="tournaments[<?= $t['id'] ?>][label]" class="inp-label" value="<?= htmlspecialchars($t['label']) ?>"></div>
                                <div class="ce-row"><label>المستحق</label><input type="number" name="tournaments[<?= $t['id'] ?>][due]" class="inp-price" step="0.01" min="0" value="<?= $tD ?>"><span class="dh">DH</span></div>
                                <div class="ce-row"><label>النقد</label><input type="number" name="tournaments[<?= $t['id'] ?>][paid]" class="inp-cash" step="0.01" min="0" value="<?= $tP > 0 ? $tP : '' ?>" placeholder="0.00"><span class="dh">DH</span></div>
                                <div class="ce-rest"></div>
                                <button type="button" class="remove-tour btn-remove">✕ حذف</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="addTourBtn" class="btn-shape mt-10" style="background:#2563eb;color:#fff;font-size:13px;padding:6px 16px;">+ إضافة بطولة</button>
            </div>
            </div>
        </div>

        <div class="action-buttons">
            <button type="button" class="btn-shape modify-btn mb-10"><i class="fas fa-edit"></i> تعديل</button>
            <button type="submit" class="btn-shape save-btn hidden mb-10"><i class="fas fa-save"></i> حفظ</button>
            <button type="button" class="btn-shape cancel-btn hidden mb-10 bg-c-60 color-fff"><i class="fas fa-times"></i> إلغاء</button>
        </div>
    </form>
</div>

<style>
.pc-header { display:flex; align-items:flex-start; gap:18px; flex-wrap:wrap; }
.pc-photo-col { display:flex; flex-direction:column; align-items:center; gap:5px; }
.pc-photo { width:92px; height:92px; border-radius:50%; object-fit:cover; border:2px solid #ccc; }
.doc-warn { background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:4px; padding:3px 8px; font-size:12px; white-space:nowrap; }
.pc-info-col { flex:1; }
.pc-badges { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
.badge-active { background:#dbeafe; color:#1e40af; border-radius:20px; padding:4px 12px; font-size:13px; font-weight:600; }
.badge-plan   { background:#f0fdf4; color:#166534; border-radius:20px; padding:4px 12px; font-size:13px; font-weight:600; }

.cell-overdue { background:#ffe0b2; }
.cell-partial { background:#fff9c4; }
.cell-future  { background:#f5f5f5; border-color:#ddd !important; }
.cell-future h4 { color:#aaa; }
.exam-row .flex-cell,
.special-row .flex-cell { flex: 1 1 50%; }

.amt-green  { color:#1a7a3a; font-weight:700; font-size:14px; }
.amt-orange { color:#b36b00; font-weight:700; font-size:14px; }
.amt-red    { color:#c00; font-weight:700; font-size:14px; }
.amt-gray   { color:#888; font-size:13px; }
.rest-lbl   { display:block; color:#b36b00; font-size:12px; margin-top:2px; }
.att-lbl    { display:block; color:#aaa; font-size:12px; margin-top:3px; }
.mb-4       { margin-bottom:4px; }

.ce-row { display:flex; align-items:center; gap:6px; margin:4px 0; font-size:13px; }
.ce-row label { width:52px; text-align:right; color:#555; font-size:12px; }
.inp-price, .inp-cash { width:72px; padding:5px 6px; border:1px solid #ccc; border-radius:5px; font-size:13px; text-align:center; }
.inp-label { width:130px; padding:5px 6px; border:1px solid #ccc; border-radius:5px; font-size:13px; }
.inp-price { border-color:#203a85; }
.dh { font-size:12px; color:#888; }
.ce-rest { font-size:13px; font-weight:700; min-height:18px; margin-top:3px; }
.rest-pos { color:#1a7a3a; }
.rest-neg { color:#c00; }

/* Modal overlay */
.card-popup {
    display:none;
    position:fixed; inset:0; z-index:1000;
    background:rgba(0,0,0,.45);
    align-items:center; justify-content:center;
}
.card-popup:not(.hidden) { display:flex; }
.popup-box {
    background:#fff; border-radius:10px;
    padding:20px 24px; width:90%; max-width:560px;
    max-height:85vh; overflow-y:auto;
    position:relative; box-shadow:0 8px 32px rgba(0,0,0,.2);
}
.popup-close {
    position:absolute; top:10px; left:14px;
    background:none; border:none; font-size:20px;
    cursor:pointer; color:#888; line-height:1;
}
.popup-close:hover { color:#b91c1c; }
.popup-title { font-size:16px; font-weight:700; margin-bottom:14px; }
.popup-toggle {
    display:inline-flex; align-items:center; gap:4px;
    margin-top:8px;
    background:#2563eb; color:#fff; border:none; border-radius:6px;
    cursor:pointer; padding:5px 13px; font-size:12px; font-weight:600;
    letter-spacing:.2px; transition:background .15s, transform .1s;
    box-shadow:0 1px 4px rgba(37,99,235,.25);
}
.popup-toggle:hover { background:#1d4ed8; transform:translateY(-1px); }
.popup-toggle:active { transform:translateY(0); }
.tour-item { flex: 0 0 calc(50% - 4px); min-width:160px; }
.btn-remove {
    display:inline-flex; align-items:center; gap:4px;
    margin-top:8px; padding:5px 12px;
    background:#fef2f2; color:#b91c1c; border:1px solid #fca5a5;
    border-radius:6px; font-size:12px; font-weight:600;
    cursor:pointer; transition:background .15s, border-color .15s;
}
.btn-remove:hover { background:#fee2e2; border-color:#f87171; }
.mt-4  { margin-top:4px; }
.mt-10 { margin-top:10px; }
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
    // Close any open view popups, open edit equivalents if they were open
    document.querySelectorAll('.card-popup:not(.hidden)').forEach(p => p.classList.add('hidden'));
});

cancelBtn.addEventListener('click', () => window.location.reload());

// Popup toggle buttons
document.addEventListener('click', e => {
    const btn = e.target.closest('.popup-toggle');
    if (!btn) return;
    e.preventDefault();
    const popup = document.getElementById(btn.dataset.popup);
    if (!popup) return;
    popup.classList.toggle('hidden');
});

// Close modal: ✕ button or click overlay background
document.addEventListener('click', e => {
    if (e.target.classList.contains('popup-close')) {
        e.target.closest('.card-popup').classList.add('hidden');
    }
    // Click on overlay (not on popup-box)
    if (e.target.classList.contains('card-popup')) {
        e.target.classList.add('hidden');
    }
});

// Remove tournament
document.getElementById('tourContainer').addEventListener('click', e => {
    const btn = e.target.closest('.remove-tour');
    if (!btn) return;
    const label = btn.closest('.tour-item').querySelector('.inp-label')?.value || 'هذه البطولة';
    if (confirm(`هل أنت متأكد من حذف "${label}"؟`)) {
        btn.closest('.tour-item').remove();
    }
});

// Add tournament
let tourIdx = Date.now();
document.getElementById('addTourBtn').addEventListener('click', () => {
    const idx = 'new_' + (tourIdx++);
    const div = document.createElement('div');
    div.className = 'flex-cell tour-item';
    div.dataset.due = '0';
    div.innerHTML = `
        <div class="cv hidden"></div>
        <div class="ce" style="display:block">
            <div class="ce-row"><label>الاسم</label><input type="text" name="tournaments[${idx}][label]" class="inp-label" placeholder="اسم البطولة"></div>
            <div class="ce-row"><label>المستحق</label><input type="number" name="tournaments[${idx}][due]" class="inp-price" step="0.01" min="0" value="0"><span class="dh">DH</span></div>
            <div class="ce-row"><label>النقد</label><input type="number" name="tournaments[${idx}][paid]" class="inp-cash" step="0.01" min="0" placeholder="0.00"><span class="dh">DH</span></div>
            <div class="ce-rest"></div>
            <button type="button" class="remove-tour btn-remove">✕ حذف</button>
        </div>`;
    document.getElementById('tourContainer').appendChild(div);
    wireCalc(div);
});

// Live calc
document.querySelectorAll('.flex-cell, .tour-item').forEach(cell => wireCalc(cell));

document.getElementById('yearSelect').addEventListener('change', function () {
    window.location.href = `?id=<?= urlencode($identifier) ?>&season=${this.value}`;
});

function wireCalc(cell) {
    const priceIn = cell.querySelector('.inp-price');
    const cashIn  = cell.querySelector('.inp-cash');
    const restDiv = cell.querySelector('.ce-rest');
    if (!priceIn || !cashIn || !restDiv) return;
    function calc() {
        const price = parseFloat(priceIn.value) || 0;
        const cash  = parseFloat(cashIn.value)  || 0;
        if (!cashIn.value) { restDiv.textContent = ''; return; }
        const diff = cash - price;
        restDiv.textContent = diff >= 0 ? 'الباقي: +' + diff.toFixed(2) + ' DH' : 'ناقص: ' + Math.abs(diff).toFixed(2) + ' DH';
        restDiv.className   = 'ce-rest ' + (diff >= 0 ? 'rest-pos' : 'rest-neg');
    }
    priceIn.addEventListener('input', calc);
    cashIn.addEventListener('input', calc);
}
</script>

<?php require 'layout/footer.php'; ?>
