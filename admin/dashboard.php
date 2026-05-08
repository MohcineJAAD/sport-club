<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$attendance = new Attendance($conn);
$payment    = new Payment($conn);
$adherent   = new Adherent($conn);

// Stats
$unpaid     = array_filter($attendance->getMonthlySummary(), fn($r) => (int)$r['paid'] === 0);
$lowSession = array_filter($attendance->getMonthlySummary(), fn($r) => (int)$r['paid'] > 0 && (int)$r['sessions'] < 5);
$pending    = $adherent->getPending();
$revenue    = $conn->query("SELECT SUM(amount) AS total FROM payments 
              WHERE MONTH(Date)=MONTH(CURDATE()) 
              AND YEAR(Date)=YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">الرئيسية</h1>

<!-- Stats cards -->
<div class="wrapper d-grid gap-20">
    <div class="cards rad-10 txt-c-mobile block-mobile" style="border-right:4px solid #c00;">
        <div class="card-content">
            <h3>لم يدفعوا هذا الشهر</h3>
            <p class="value"><?= count($unpaid) ?></p>
            <i class="fa-solid fa-circle-exclamation" style="color:#c00;"></i>
        </div>
    </div>
    <div class="cards rad-10 txt-c-mobile block-mobile" style="border-right:4px solid #b36b00;">
        <div class="card-content">
            <h3>حضور ضعيف</h3>
            <p class="value"><?= count($lowSession) ?></p>
            <i class="fa-solid fa-triangle-exclamation" style="color:#b36b00;"></i>
        </div>
    </div>
    <div class="cards rad-10 txt-c-mobile block-mobile" style="border-right:4px solid #203a85;">
        <div class="card-content">
            <h3>بانتظار الموافقة</h3>
            <p class="value"><?= count($pending) ?></p>
            <i class="fa-solid fa-user-clock" style="color:#203a85;"></i>
        </div>
    </div>
    <div class="cards rad-10 txt-c-mobile block-mobile" style="border-right:4px solid #1a7a3a;">
        <div class="card-content">
            <h3>إيرادات الشهر</h3>
            <p class="value"><?= number_format($revenue, 2) ?> DH</p>
            <i class="fa-solid fa-money-bills" style="color:#1a7a3a;"></i>
        </div>
    </div>
</div>

<!-- Pending approvals -->
<?php if (count($pending) > 0): ?>
<div class="absences p-20 bg-fff rad-10 m-20">
    <h2 class="mt-0 mb-20">تسجيلات تنتظر الموافقة</h2>
    <div class="responsive-table">
        <table class="fs-15 w-full">
            <thead>
                <tr>
                    <th>الاسم الكامل</th>
                    <th>الرياضة</th>
                    <th>تاريخ التسجيل</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                        <td><?= htmlspecialchars($m['type']) ?></td>
                        <td><?= htmlspecialchars($m['date_adhesion']) ?></td>
                        <td>
                            <a href="/sport-club/actions/adherent_approve.php?id=<?= $m['identifier'] ?>">
                                <span class="label btn-shape bg-green">قبول</span>
                            </a>
                            <a href="/sport-club/actions/adherent_reject.php?id=<?= $m['identifier'] ?>">
                                <span class="label btn-shape bg-f00">رفض</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require 'layout/footer.php'; ?>