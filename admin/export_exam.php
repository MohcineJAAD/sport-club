<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['adherent'])) {
    header("Location: /sport-club/admin/exam.php");
    exit();
}

$identifiers = $_POST['adherent'];
$session     = trim($_POST['session'] ?? '');
$year        = date('Y');

$adherentObj = new Adherent($conn);
$members = [];
foreach ($identifiers as $id) {
    $rows = $adherentObj->getById(trim($id));
    if (!empty($rows)) $members[] = $rows[0];
}

$admin    = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();
$clubName = $admin['club_name'] ?? 'النادي الرياضي';
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>امتحان التايكواندو - <?= htmlspecialchars($session) ?> <?= $year ?></title>
<style>
@page { margin: 5mm; size: A4 portrait; }
@media print {
    .no-print { display: none !important; }
    body { background: #fff; padding: 0; }
    .exam-card { page-break-after: always; box-shadow: none; margin: 0; }
    .exam-card:last-child { page-break-after: avoid; }
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #ccc; padding: 10px; direction: rtl; }

.no-print { text-align: center; margin-bottom: 12px; }
.no-print button { padding: 8px 20px; background: #1f3864; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
.no-print a { color: #1f3864; font-size: 14px; margin-right: 12px; }

.exam-card { background: #fff; width: 200mm; margin: 0 auto 20px; padding: 2mm; }

/* 8-column table, direction LTR so A=left, H=right */
.t { width: 100%; border-collapse: collapse; direction: ltr; font-size: 9pt; table-layout: fixed; }
.t td { border: 1px solid #000; vertical-align: middle; text-align: center; padding: 2px 3px; overflow: hidden; }
.nb { border: none !important; }

/* Widths matching Excel columns A-H */
.t col:nth-child(1) { width: 13.7%; } /* A: score left */
.t col:nth-child(2) { width:  7.4%; } /* B: pts left */
.t col:nth-child(3) { width: 22.4%; } /* C: desc left */
.t col:nth-child(4) { width:  5.1%; } /* D: desc left ext */
.t col:nth-child(5) { width:  6.4%; } /* E: desc left ext */
.t col:nth-child(6) { width: 14.2%; } /* F: score right */
.t col:nth-child(7) { width:  9.3%; } /* G: pts right */
.t col:nth-child(8) { width: 21.6%; } /* H: desc right */

/* Header */
.fed   { font-size: 13pt; font-weight: bold; direction: rtl; }
.leag  { font-size: 11pt; font-weight: bold; direction: rtl; }
.sess  { font-size: 9pt;  direction: rtl; }
.photo-cell { background: #f9f9f9; padding: 3px; }
.logo-cell  { padding: 3px; }
.photo-img  { max-height: 100px; max-width: 100%; object-fit: cover; display: block; margin: auto; }
.logo-img   { max-height: 100px; max-width: 100%; display: block; margin: auto; }

/* Info rows */
.info-lbl { direction: rtl; text-align: right; font-size: 9pt; font-weight: bold;
            border-top: none !important; border-left: none !important; border-right: none !important;
            border-bottom: 1px solid #555 !important; padding: 3px 5px; }
.info-val { direction: rtl; text-align: right; font-size: 9pt;
            border-top: none !important; border-left: none !important; border-right: none !important;
            border-bottom: 1px solid #555 !important; padding: 3px 5px; }
.yellow   { background: #ffff00 !important; }

/* Section headers */
.shdr { background: #1f3864; color: #fff; font-weight: bold; direction: rtl;
        font-size: 9pt; padding: 3px 5px; }

/* Scoring cells */
.slbl { font-weight: bold; font-size: 8pt; background: #f0f0f0; }
.pts  { font-size: 8pt; font-weight: bold; color: #1f3864; }
.iname { direction: rtl; text-align: right; font-size: 8.5pt; padding: 1px 5px; }
.empty-line td { height: 5px; border-left: none !important; border-right: none !important; background: #fff; }

/* Footer */
.avg  { direction: rtl; font-weight: bold; font-size: 12pt; text-align: center;
        border: 2px solid #c00 !important; color: #c00; }
.sig  { direction: rtl; font-weight: bold; font-size: 13pt; text-align: center; padding: 20px 5px; }
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">&#128438; طبع / حفظ PDF</button>
    <a href="/sport-club/admin/exam.php">← رجوع</a>
</div>

<?php foreach ($members as $m):
    $imgPath  = !empty($m['image_path']) ? '../uploads/' . $m['image_path'] : null;
    $hasPhoto = $imgPath && file_exists($imgPath);
?>
<div class="exam-card">
<table class="t">
<colgroup>
    <col><col><col><col><col><col><col><col>
</colgroup>
<tbody>

<!-- ROW 1: A1,B1 empty | C1:G2 = federation name (rs=2) | H1 empty -->
<tr style="height:18pt">
    <td class="nb"></td>
    <td class="nb"></td>
    <td colspan="5" rowspan="2" class="fed" style="text-align:center;">الجامعة الملكية المغربية للتايكواندو</td>
    <td class="nb"></td>
</tr>

<!-- ROW 2: A2:B7 = photo (rs=6) | C-G covered | H2:H7 = logo (rs=6) -->
<tr style="height:16pt">
    <td colspan="2" rowspan="6" class="photo-cell">
        <?php if ($hasPhoto): ?>
            <img src="/sport-club/uploads/<?= htmlspecialchars($m['image_path']) ?>" class="photo-img" alt="">
        <?php else: ?>
            <div style="height:95px;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:8pt;direction:rtl;">صورة المرشح</div>
        <?php endif; ?>
    </td>
    <td rowspan="6" class="logo-cell">
        <img src="/sport-club/assets/images/lrd_logo.png" class="logo-img" alt=""
             onerror="this.style.display='none'">
    </td>
</tr>

<!-- ROW 3: C3:G4 = league name (rs=2) -->
<tr style="height:14pt">
    <td colspan="5" rowspan="2" class="leag" style="text-align:center;">عصبة جهة الداخلة وادي الذهب للتايكواندو</td>
</tr>

<!-- ROW 4: all covered -->
<tr style="height:13pt"></tr>

<!-- ROW 5: empty -->
<tr style="height:11pt">
    <td colspan="5" class="nb"></td>
</tr>

<!-- ROW 6: C6:G6 = session title -->
<tr style="height:13pt">
    <td colspan="5" class="sess" style="text-align:center;">
        امتحان مختلف الأحزمة لدورة <?= htmlspecialchars($session) ?> <?= $year ?>
    </td>
</tr>

<!-- ROW 7: empty -->
<tr style="height:11pt">
    <td colspan="5" class="nb"></td>
</tr>

<!-- ROW 8: Name -->
<tr style="height:21pt">
    <td colspan="5" class="info-val"><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
    <td colspan="3" class="info-lbl">: اسم ونسب المرشح(ة)</td>
</tr>

<!-- ROW 9: Current belt -->
<tr style="height:19pt">
    <td colspan="5" class="info-val"><?= htmlspecialchars($m['current_belt'] ?? '') ?></td>
    <td colspan="3" class="info-lbl">: الحزام الحالي</td>
</tr>

<!-- ROW 10: Exam belt — YELLOW -->
<tr style="height:18pt">
    <td colspan="5" class="info-val yellow"><?= htmlspecialchars($m['next_belt'] ?? '') ?></td>
    <td colspan="3" class="info-lbl yellow">: الحزام موضوع الامتحان</td>
</tr>

<!-- ROW 11: Club -->
<tr style="height:24pt">
    <td colspan="5" class="info-val"><?= htmlspecialchars($clubName) ?></td>
    <td colspan="3" class="info-lbl">: اسم النادي</td>
</tr>

<!-- ROW 12: Separator -->
<tr><td colspan="8" style="height:6pt;border:none;border-bottom:2px solid #1f3864;"></td></tr>

<!-- ROW 13: Section headers -->
<tr>
    <td colspan="5" class="shdr">الحركات الأساسية لليدين</td>
    <td colspan="3" class="shdr">الحركات الأساسية للأرجل</td>
</tr>

<!-- ROW 14: First items (outside merged score area) -->
<tr style="height:17pt">
    <td></td>
    <td class="pts">1</td>
    <td colspan="3" class="iname">الوضعيات</td>
    <td></td>
    <td class="pts">1</td>
    <td class="iname">الحركات الأمامية</td>
</tr>

<!-- ROW 15–18: A15:A18=/06  F15:F18=/05 -->
<tr style="height:17pt">
    <td rowspan="4" class="slbl">/06</td>
    <td class="pts">1</td>
    <td colspan="3" class="iname">الهجوم (تشيليكي)</td>
    <td rowspan="4" class="slbl">/05</td>
    <td class="pts">1</td>
    <td class="iname">الحركات الجانبية</td>
</tr>
<tr style="height:17pt">
    <td class="pts">1</td>
    <td colspan="3" class="iname">الدفاع (ماكي)</td>
    <td class="pts">1</td>
    <td class="iname">الحركات الخلفية</td>
</tr>
<tr style="height:17pt">
    <td class="pts">2</td>
    <td colspan="3" class="iname">القوة</td>
    <td class="pts">1</td>
    <td class="iname">فوة الحركات</td>
</tr>
<tr style="height:17pt">
    <td class="pts">1</td>
    <td colspan="3" class="iname">الصيحة (كيهاب)</td>
    <td class="pts">1</td>
    <td class="iname">التركيز</td>
</tr>

<!-- ROW 19: Separator -->
<tr class="empty-line"><td colspan="8"></td></tr>

<!-- ROW 20: Section headers -->
<tr>
    <td colspan="5" class="shdr">الأسئلة الشفوية</td>
    <td colspan="3" class="shdr">تقنيات المباراة (الكيوروكي)</td>
</tr>

<!-- ROW 21: First items -->
<tr style="height:17pt">
    <td></td>
    <td class="pts">1</td>
    <td colspan="3" class="iname">سؤال 1</td>
    <td></td>
    <td class="pts">1</td>
    <td class="iname">الرجل اليمنى (أورون)</td>
</tr>

<!-- ROW 22–24: A22:A24=/04  F22:F24=/04 -->
<tr style="height:17pt">
    <td rowspan="3" class="slbl">/04</td>
    <td class="pts">1</td>
    <td colspan="3" class="iname">سؤال 2</td>
    <td rowspan="3" class="slbl">/04</td>
    <td class="pts">1</td>
    <td class="iname">الرجل اليسرى (ون)</td>
</tr>
<tr style="height:17pt">
    <td class="pts">1</td>
    <td colspan="3" class="iname">سؤال 3</td>
    <td class="pts">1</td>
    <td class="iname">الخطوات (ستيب)</td>
</tr>
<tr style="height:17pt">
    <td class="pts">1</td>
    <td colspan="3" class="iname">سؤال 4</td>
    <td class="pts">1</td>
    <td class="iname">المضرب (راكيط)</td>
</tr>

<!-- ROW 25: Separator -->
<tr class="empty-line"><td colspan="8"></td></tr>

<!-- ROW 26: Section headers -->
<tr>
    <td colspan="5" class="shdr">الدفاع عن النفس (الهوشينسول)</td>
    <td colspan="3" class="shdr">البومسي</td>
</tr>

<!-- ROW 27: First items -->
<tr style="height:17pt">
    <td></td>
    <td class="pts">3</td>
    <td colspan="3" class="iname">الوضعية</td>
    <td></td>
    <td class="pts">5</td>
    <td class="iname">الوضعيات</td>
</tr>

<!-- ROW 28–29: A28:A29=/09   ROW 28–34: F28:F34=/40 -->
<tr style="height:17pt">
    <td rowspan="2" class="slbl">/09</td>
    <td class="pts">3</td>
    <td colspan="3" class="iname">الدفاع والهجوم</td>
    <td rowspan="7" class="slbl">/40</td>
    <td class="pts">5</td>
    <td class="iname">حركات اليدين</td>
</tr>
<tr style="height:17pt">
    <td class="pts">3</td>
    <td colspan="3" class="iname">السقوط والصيحة</td>
    <td class="pts">5</td>
    <td class="iname">حركات الأرجل</td>
</tr>

<!-- ROW 30: left empty, right continues البومسي -->
<tr style="height:17pt">
    <td colspan="5"></td>
    <td class="pts">5</td>
    <td class="iname">النظرة</td>
</tr>

<!-- ROW 31: left = السلوك header, right continues -->
<tr>
    <td colspan="5" class="shdr">السلوك والمواضبة</td>
    <td class="pts">5</td>
    <td class="iname">السرعة</td>
</tr>

<!-- ROW 32: first السلوك item -->
<tr style="height:17pt">
    <td></td>
    <td class="pts">4</td>
    <td colspan="3" class="iname">مع الأستاذ</td>
    <td class="pts">5</td>
    <td class="iname">القوة</td>
</tr>

<!-- ROW 33–34: A33:A34=/12  right continues -->
<tr style="height:17pt">
    <td rowspan="2" class="slbl">/12</td>
    <td class="pts">4</td>
    <td colspan="3" class="iname">مع التلاميذ</td>
    <td class="pts">5</td>
    <td class="iname">نقطة الرجوع</td>
</tr>
<tr style="height:17pt">
    <td class="pts">4</td>
    <td colspan="3" class="iname">الحضور</td>
    <td class="pts">5</td>
    <td class="iname">الصيحة (كيهاب)</td>
</tr>

<!-- ROW 35: Separator -->
<tr class="empty-line"><td colspan="8"></td></tr>

<!-- ROW 36: الليونة + اللياقة البدنية -->
<tr style="height:19pt">
    <td colspan="2" class="slbl">/10</td>
    <td colspan="3" style="direction:rtl;font-weight:bold;font-size:9pt;">الليونة</td>
    <td colspan="2" class="slbl">/10</td>
    <td style="direction:rtl;font-weight:bold;font-size:9pt;text-align:right;padding-right:5px;">اللياقة البدنية</td>
</tr>

<!-- ROW 37: empty input row -->
<tr style="height:17pt"><td colspan="8"></td></tr>

<!-- ROW 38: المعدل العام -->
<tr style="height:19pt">
    <td colspan="2" style="border:none"></td>
    <td colspan="3" class="avg">المعدل العام /100</td>
    <td colspan="3" style="border:none"></td>
</tr>

<!-- ROW 39: spacer -->
<tr style="height:8pt"><td colspan="8" style="border:none"></td></tr>

<!-- ROWS 40–46: Signature -->
<tr style="height:65pt">
    <td colspan="8" class="sig">إمضاء لجنة الامتحانات</td>
</tr>

</tbody>
</table>
</div>
<?php endforeach; ?>
</body>
</html>