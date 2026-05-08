<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$adherent = new Adherent($conn);
$plan     = new Plan($conn);

$active  = $adherent->getAll('active');
$pending = $adherent->getPending();
$plans   = $plan->getNames();
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">إدارة المشتركين</h1>

<!-- Pending registrations -->
<div class="absences p-20 bg-fff rad-10 m-20 special">
    <h2 class="mt-0 mb-20">التسجيلات الجديدة</h2>
    <div class="responsive-table">
        <?php if (count($pending) > 0): ?>
            <table class="fs-15 w-full">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>تاريخ الازدياد</th>
                        <th>الرياضة</th>
                        <th>تاريخ التسجيل</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                            <td><?= htmlspecialchars($m['identifier']) ?></td>
                            <td><?= htmlspecialchars($m['date_naissance']) ?></td>
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
        <?php else: ?>
            <p style="text-align:center;">لا توجد تسجيلات جديدة</p>
        <?php endif; ?>
    </div>
</div>

<!-- Active members -->
<div class="absences p-20 bg-fff rad-10 m-20 special">
    <h2 class="mt-0 mb-20">المشتركون</h2>

    <div class="options w-full">
        <div class="branch-filter mt-10 mb-10">
            <button class="btn-shape bg-c-60 color-fff active mb-10" data-branch="all">الكل</button>
            <?php foreach ($plans as $p): ?>
                <button class="btn-shape bg-c-60 color-fff mb-10" data-branch="<?= htmlspecialchars($p['name']) ?>">
                    <?= htmlspecialchars($p['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <input type="text" id="search" placeholder="بحث بالاسم أو المعرف..."
               style="padding:8px 14px;border:1px solid #ccc;border-radius:8px;font-size:15px;width:280px;margin-bottom:10px;">
    </div>

    <div class="responsive-table">
        <?php if (count($active) > 0): ?>
            <table class="fs-15 w-full" id="adherent-list">
                <thead>
                    <tr>
                        <th>الاسم الكامل</th>
                        <th>المعرف</th>
                        <th>الرياضة</th>
                        <th>تاريخ الانخراط</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active as $m): ?>
                        <tr data-branch="<?= htmlspecialchars($m['type']) ?>">
                            <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                            <td><?= htmlspecialchars($m['identifier']) ?></td>
                            <td><?= htmlspecialchars($m['type']) ?></td>
                            <td><?= htmlspecialchars($m['date_adhesion']) ?></td>
                            <td>
                                <a href="/sport-club/admin/profile.php?id=<?= $m['identifier'] ?>">
                                    <span class="label btn-shape bg-c-60">الملف الشخصي</span>
                                </a>
                                <a href="#" onclick="confirmDelete('<?= htmlspecialchars($m['identifier']) ?>')">
                                    <span class="label btn-shape bg-f00">حذف</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">لا يوجد مشتركون</p>
        <?php endif; ?>
    </div>
</div>

<!-- Delete modal -->
<div id="deleteModal" class="modal" dir="rtl">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>تأكيد الحذف</h2>
        <p>هل أنت متأكد أنك تريد حذف هذا المشترك؟</p>
        <div class="action-buttons">
            <button id="confirmDeleteBtn" class="btn-shape color-fff bg-f00 p-10">حذف</button>
            <button id="cancelDeleteBtn" class="btn-shape color-fff bg-c-60 p-10">إلغاء</button>
        </div>
    </div>
</div>
<?php require 'layout/footer.php'; ?>
