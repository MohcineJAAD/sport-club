<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sport-club/admin/exam.php");
    exit();
}

$ids     = $_POST['candidates'] ?? [];
$session = trim($_POST['session'] ?? 'يناير');

$adherentObj = new Adherent($conn);
$members = [];
foreach ($ids as $id) {
    $rows = $adherentObj->getById(trim($id));
    if (!empty($rows)) $members[] = $rows[0];
}
if (empty($members)) { header("Location: /sport-club/admin/exam.php"); exit(); }

$year = date('Y');
$belts = [
    'أبيض','أصفر بخط أبيض','أصفر','برتقالي','أخضر',
    'أزرق','أزرق بخط أحمر','أحمر','أحمر بخط أسود','أحمر بخطين أسودين'
];
$greenIdx = array_search('أخضر', $belts);

function getBeltClass($nxtBelt, $belts, $greenIdx) {
    $nxtIdx = array_search($nxtBelt, $belts);
    if ($nxtIdx === false) return 'belt-yellow';
    return ($nxtIdx >= $greenIdx) ? 'belt-green' : 'belt-yellow';
}

function isGreenOrAbove($nxtBelt, $belts, $greenIdx) {
    $nxtIdx = array_search($nxtBelt, $belts);
    return ($nxtIdx !== false && $nxtIdx >= $greenIdx);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>امتحان التايكواندو</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, 'Traditional Arabic', sans-serif; background: #eee; direction: rtl; }

.no-print {
    display: flex; justify-content: center; gap: 12px;
    padding: 14px; background: #333;
}
.btn {
    padding: 10px 28px; border: none; border-radius: 5px;
    cursor: pointer; font-size: 15px; text-decoration: none;
    display: inline-block; font-family: inherit;
}
.btn-primary   { background: #203a85; color: #fff; }
.btn-secondary { background: #666;    color: #fff; }

/* ── A4 page ── */
.exam-page {
    width: 210mm;
    height: 297mm;
    overflow: hidden;
    margin: 10px auto;
    padding: 8mm 10mm;
    background: #fff;
    page-break-after: always;
    page-break-inside: avoid;
    font-size: 14px;
    line-height: 1.4;
    display: flex;
    flex-direction: column;
}
.exam-page:last-child { page-break-after: avoid; }

/* ── Header ── */
.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2mm;
    direction: rtl;
}
.header-table td { padding: 1px 3px; vertical-align: middle; }
.fed-name  { font-size: 16px; font-weight: bold; text-align: center; }
.lgue-name { font-size: 14px; font-weight: bold; text-align: center; }
.sess-name { font-size: 13px;   font-weight: bold; text-align: center; }
.member-photo { width: 90px; height: 110px; object-fit: cover; display: block; border: 1px solid #ccc; }
.league-logo  { width: 85px; height: 85px; object-fit: contain; display: block; margin: auto; }

/* ── Shared table styles ── */
.t {
    border-collapse: collapse;
    direction: rtl;
    font-size: 10.5px;
    width: 100%;
}
.t td, .t th {
    border: 1px solid #000;
    padding: 3px 5px;
    vertical-align: middle;
    text-align: center;
}
.sec-hdr {
    background: #c0c0c0;
    font-weight: bold;
    font-size: 11px;
    text-align: center;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.lbl { font-weight: bold; }
.tot { font-weight: bold; background: #e8e8e8; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.sc  { font-weight: bold; }
.info-lbl { font-weight: bold; background: #f0f0f0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.info-val { text-align: right; padding: 1px 5px; }

.belt-green {
    background-color: #00b050 !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.belt-yellow {
    background-color: #ffff00 !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Two-column layout ── */
.two-col {
    display: flex;
    gap: 3mm;
    margin-bottom: 1.5mm;
}
.two-col > * { flex: 1; min-width: 0; }

/* ── Candidate info row ── */
.info-table { margin-bottom: 3mm; }
.info-table td { height: 26px; }

/* ── Poomse (full width) ── */
.poomse-wrap { margin-bottom: 1.5mm; }

/* ── Bottom two-col: Hoshinsool + Behavior ── */
.bottom-wrap { margin-bottom: 1.5mm; }

/* ── Single-row sections: fitness + flexibility/written ── */
.singles-wrap {
    display: flex;
    gap: 2mm;
    margin-bottom: 3mm;
}
.singles-wrap > * { flex: 1; }

/* ── Divider ── */
.divider {
    height: 3px;
    background: #555;
    margin: 1mm 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.final-row td {
    border: 2px solid #000 !important;
    font-weight: bold;
    font-size: 12px;
    height: 32px;
}
.sign-row td {
    font-weight: bold;
    font-size: 11px;
    text-align: center;
    height: 45px;
    vertical-align: bottom;
    padding-bottom: 5px;
}

@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .exam-page { margin: 0; padding: 4mm 5mm; }
    @page { size: A4 portrait; margin: 0; }
}
</style>
</head>
<body>

<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">🖨 طباعة / تنزيل PDF</button>
    <a href="/sport-club/admin/exam.php" class="btn btn-secondary">← رجوع</a>
</div>

<?php 
    $adminRow = $conn->query("SELECT club_name FROM admin LIMIT 1")->fetch_assoc();
    $clubName = htmlspecialchars($adminRow['club_name'] ?? '');
    foreach ($members as $m):
        $imgPath = !empty($m['image_path'])
            ? '/sport-club/assets/uploads/' . htmlspecialchars($m['image_path'])
            : '/sport-club/assets/images/defult_image.png';

        $name      = htmlspecialchars($m['prenom'] . ' ' . $m['nom']);
        $nxtBelt   = $m['next_belt'] ?? '';
        $curentBelt = $m['current_belt'] ?? '';
        $id        = htmlspecialchars($m['identifier']);
        $beltClass = getBeltClass($nxtBelt, $belts, $greenIdx);
        $isGreen   = isGreenOrAbove($nxtBelt, $belts, $greenIdx);
?>
<div class="exam-page">

    <!-- ══ HEADER ══ -->
    <table class="header-table">
        <tr>
            <td style="width:75px" rowspan="4">
                <img src="/sport-club/assets/images/lrd_logo.jpg" alt="الشعار" class="league-logo">
            </td>
            <td style="padding-bottom:1px">
                <div class="fed-name">الجامعة الملكية المغربية للتايكواندو</div>
            </td>
            <td style="width:75px" rowspan="4">
                <img src="<?= $imgPath ?>" alt="صورة المرشح" class="member-photo">
            </td>
        </tr>
        <tr><td><div class="lgue-name">عصبة جهة الداخلة وادي الذهب للتايكواندو</div></td></tr>
        <tr><td><div class="sess-name">امتحان مختلف الأحزمة لدورة <?= htmlspecialchars($session) ?> <?= $year ?></div></td></tr>
        <tr><td></td></tr>
    </table>

    <!-- ══ CANDIDATE INFO ══ -->
    <table class="t info-table">
        <tr>
            <td class="info-lbl" style="width:32%">: اسم ونسب المرشح(ة)</td>
            <td class="info-val" colspan="3" style="text-align:center"><?= $name ?></td>
        </tr>
        <tr>
            <td class="info-lbl">: الحزام الحالي</td>
            <td class="info-val" colspan="3"><?= $curentBelt ?></td>
        </tr>
        <tr>
            <td class="info-lbl <?= $beltClass ?>">: الحزام موضوع الامتحان</td>
            <td class="info-val <?= $beltClass ?>" colspan="3"><?= htmlspecialchars($nxtBelt) ?></td>
        </tr>
        <tr>
            <td class="info-lbl">: اسم النادي</td>
            <td class="info-val" colspan="3"><?= htmlspecialchars($clubName) ?></td>
        </tr>
    </table>

    <!-- ══ ROW 1: الأرجل  |  اليدين ══ -->
    <div class="two-col">
        <!-- الحركات الأساسية للأرجل -->
        <table class="t">
            <tr><td class="sec-hdr" colspan="3">الحركات الأساسية للأرجل</td></tr>
            <tr>
                <td class="lbl" style="width:40%">الحركات الأمامية</td>
                <td class="sc" style="width:25%">1</td>
                <td style="width:35%"></td>
            </tr>
            <tr><td class="lbl">الحركات الجانبية</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">الحركات الخلفية</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">قوة الحركات</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">التركيز</td><td class="sc">1</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/05</td></tr>
        </table>

        <!-- الحركات الأساسية لليدين -->
        <table class="t">
            <tr><td class="sec-hdr" colspan="3">الحركات الأساسية لليدين</td></tr>
            <tr>
                <td class="lbl" style="width:40%">الوضعيات</td>
                <td class="sc" style="width:25%">1</td>
                <td style="width:35%"></td>
            </tr>
            <tr><td class="lbl">الهجوم (تشيليكي)</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">الدفاع (ماكي)</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">القوة</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">الصيحة (كيهاب)</td><td class="sc">1</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/05</td></tr>
        </table>
    </div>

    <!-- ══ ROW 2: الأسئلة الشفوية  |  تقنيات المباراة ══ -->
    <div class="two-col">
        <!-- تقنيات المباراة (الكيوروكي) -->
        <table class="t">
            <tr><td class="sec-hdr" colspan="3">تقنيات المباراة (الكيوروكي)</td></tr>
            <tr>
                <td class="lbl" style="width:40%">الرجل اليمنى (اورون)</td>
                <td class="sc" style="width:25%">1</td>
                <td style="width:35%"></td>
            </tr>
            <tr><td class="lbl">الرجل اليسرى (ون)</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">الخطوات (سطيب)</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">المضرب (راكيط)</td><td class="sc">1</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/04</td></tr>
        </table>
        <!-- الأسئلة الشفوية -->
        <table class="t">
            <tr><td class="sec-hdr" colspan="3">الأسئلة الشفوية</td></tr>
            <tr>
                <td class="lbl" style="width:40%">سؤال 1</td>
                <td class="sc" style="width:25%">1</td>
                <td style="width:35%"></td>
            </tr>
            <tr><td class="lbl">سؤال 2</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">سؤال 3</td><td class="sc">1</td><td></td></tr>
            <tr><td class="lbl">سؤال 4</td><td class="sc">1</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/04</td></tr>
        </table>
    </div>
    

    <!-- ══ ROW 3: الدفاع عن النفس  |  البومسي ══ -->
    <div class="two-col bottom-wrap">
        <!-- البومسي -->
        <table class="t">
            <tr><td class="sec-hdr" colspan="3">البومسي</td></tr>
            <tr>
                <td class="lbl" style="width:40%">الوضعيات</td>
                <td class="sc" style="width:25%">5</td>
                <td style="width:35%"></td>
            </tr>
            <tr><td class="lbl">حركات اليدين</td><td class="sc">5</td><td></td></tr>
            <tr><td class="lbl">حركات الأرجل</td><td class="sc">5</td><td></td></tr>
            <tr><td class="lbl">النظرة</td><td class="sc">5</td><td></td></tr>
            <tr><td class="lbl">السرعة</td><td class="sc">5</td><td></td></tr>
            <tr><td class="lbl">القوة</td><td class="sc">5</td><td></td></tr>
            <tr><td class="lbl">نقطة الرجوع</td><td class="sc">5</td><td></td></tr>
            <tr><td class="lbl">الصيحة (كيهاب)</td><td class="sc">5</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/40</td></tr>
        </table>
        <!-- الدفاع عن النفس (الهوشينسول) -->
        <table class="t">
            <tr><td class="sec-hdr" colspan="3">الدفاع عن النفس (الهوشينسول)</td></tr>
            <tr>
                <td class="lbl" style="width:40%">الوضعية</td>
                <td class="sc" style="width:25%">3</td>
                <td style="width:35%"></td>
            </tr>
            <tr><td class="lbl">الدفاع والهجوم</td><td class="sc">3</td><td></td></tr>
            <tr><td class="lbl">السقوط والصحة</td><td class="sc">3</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/09</td></tr>
            <!-- spacer to match height -->
            <tr><td colspan="3" style="border:none; height:4px"></td></tr>
            <!-- ══ السلوك والمواظبة ══ -->
            <tr><td class="sec-hdr" colspan="3">السلوك والمواظبة</td></tr>
            <tr><td class="lbl">مع الأستاذ</td><td class="sc">4</td><td></td></tr>
            <tr><td class="lbl">مع التلاميذ</td><td class="sc">4</td><td></td></tr>
            <tr><td class="lbl">الحضور</td><td class="sc">4</td><td></td></tr>
            <tr class="tot"><td>المجموع</td><td colspan="2">/12</td></tr>
        </table>
    </div>

    <!-- ══ اللياقة البدنية | الليونة/الكتابي ══ -->
    <div class="singles-wrap">
        <table class="t">
            <tr>
                <td class="sec-hdr" style="width:50%">اللياقة البدنية</td>
                <td class="sc" style="width:30%">/10</td>
                <td style="width:20%"></td>
            </tr>
        </table>
        <table class="t">
            <?php if ($isGreen): ?>
            <tr>
                <td class="sec-hdr" style="width:50%">الكتابي</td>
                <td class="sc" style="width:30%">/10</td>
                <td style="width:20%"></td>
            </tr>
            <?php else: ?>
            <tr>
                <td class="sec-hdr" style="width:50%">الليونة</td>
                <td class="sc" style="width:30%">/10</td>
                <td style="width:20%"></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- ══ المعدل العام ══ -->
    <table class="t" style="margin-bottom:1.5mm">
        <tr class="final-row">
            <td class="info-lbl" style="width:30%">المعدل العام 100/</td>
            <td colspan="3"></td>
        </tr>
    </table>

    <!-- ══ الإمضاء ══ -->
    <table class="t">
        <tr class="sign-row">
            <td colspan="4" style="text-decoration:underline; font-weight:bold; font-size:12px">إمضاء لجنة الامتحانات</td>
        </tr>
    </table>

</div>
<?php endforeach; ?>
</body>
</html>