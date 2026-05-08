<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();
$newMember = $_SESSION['new_member'] ?? null;
unset($_SESSION['new_member']);

require_once __DIR__ . '/../config/database.php';

$plan     = new Plan($conn);
$schedule = new Schedule($conn);

$plans     = $plan->getAll();
$schedules = $schedule->getAll();

$scheduleByDay = [];
foreach ($schedules as $s) {
    $scheduleByDay[$s['day']][$s['timeslot']] = $s['sport_type'];
}

$days      = ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'];
$timeslots = ['16:30:00-17:30:00', '17:30:00-18:30:00', '18:30:00-19:30:00', '19:30:00-20:30:00', '20:30:00-21:30:00', '21:30:00-22:30:00', '22:30:00-23:30:00'];
?>
<?php require 'layout/header.php'; ?>

<!-- Hero -->
<section id="hero">
    <div class="hero-slider">
        <div class="slide active" style="background-image:url('/sport-club/assets/images/FUllcontct.jpg');background-size:cover;background-position:center;"></div>
        <div class="slide" style="background-image:url('/sport-club/assets/images/aerobic.jpg');background-size:cover;background-position:center;"></div>
        <div class="slide" style="background-image:url('/sport-club/assets/images/taekwondo.jpg');background-size:cover;background-position:center;"></div>
    </div>
    <div class="overlay"></div>
    <div class="hero-content">
        <h1>اكتشف قدراتك</h1>
        <p>انضم إلى مجتمعنا وتقدم بخطى تناسبك</p>
        <div class="button-container">
            <a href="/sport-club/public/sign_up.php" class="button">ابدأ الآن</a>
        </div>
    </div>
</section>

<!-- About -->
<section id="about" dir="rtl">
    <div class="container">
        <h2>حول صالتنا الرياضية</h2>
        <p>في نادي <?= htmlspecialchars($club['club_name']) ?>، نحن مقتنعون بأهمية تقديم برامج تدريبية عالية الجودة لمساعدة أعضائنا على تحقيق أهدافهم في اللياقة البدنية.</p>
    </div>
</section>

<!-- Plans -->
<section id="plans">
    <h2 class="speacial-heading">خططنا</h2>
    <div class="container" dir="rtl">
        <?php foreach ($plans as $p): ?>
            <div class="plan">
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <p><?= htmlspecialchars($p['description']) ?></p>
                <p>السعر: <?= number_format($p['price'], 2) ?> درهم / الشهر</p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Schedule -->
<section id="Horaire">
    <h2 class="speacial-heading">المواعيد</h2>
    <div class="container" dir="rtl">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>اليوم/الوقت</th>
                    <th>16:30-17:30</th>
                    <th>17:30-18:30</th>
                    <th>18:30-19:30</th>
                    <th>19:30-20:30</th>
                    <th>20:30-21:30</th>
                    <th>21:30-22:30</th>
                    <th>22:30-23:30</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                    <tr>
                        <th><?= $day ?></th>
                        <?php foreach ($timeslots as $slot): ?>
                            <td><?= htmlspecialchars($scheduleByDay[$day][$slot] ?? '--') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require 'layout/footer.php'; ?>

<!-- Success popup after registration -->
<?php if ($newMember): ?>
<div class="modal" id="modal" style="display:block;"></div>
<div class="popup" id="popup" style="display:block;">
    <span class="x-close" onclick="closePopup()">&times;</span>
    <div class="popup-content">
        <h2>تم تسجيل الاشتراك بنجاح</h2>
        <p>معرفك هو: <strong><?= htmlspecialchars($newMember['identifier']) ?></strong></p>
        <p>توجه إلى النادي لتفعيل اشتراكك.</p>
        <p><strong>العنوان:</strong> <?= htmlspecialchars($club['address']) ?></p>
        <p><strong>الهاتف:</strong> <?= htmlspecialchars($club['phone']) ?></p>
    </div>
    <button class="close-btn" onclick="closePopup()">إغلاق</button>
</div>
<?php endif; ?>
