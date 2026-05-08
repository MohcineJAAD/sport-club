<?php

use PhpOffice\PhpSpreadsheet\Helper\Sample;

session_start();

$id = '';

if (isset($_SESSION['message'])) {
    $id = $_SESSION['message'];
    unset($_SESSION['message']);
}

require 'assets/php/db_connection.php';

$sql = "SELECT * FROM plans";
$result = $conn->query($sql);
$plans = [];
if ($result->num_rows > 0) {
    $plans = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch schedule
$sql = "SELECT day, timeslot, sport_type FROM schedule ORDER BY FIELD(day, 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'), timeslot";
$result = $conn->query($sql);

$schedule = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedule[$row['day']][$row['timeslot']] = $row['sport_type'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/master1.css" />
    <link rel="stylesheet" href="assets/css/normalize.css" />
    <link rel="stylesheet" href="assets/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Work+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet" />
    <title>Document</title>
</head>

<body>
    <?php require "header.php"; ?>
    <section id="hero">
        <div class="hero-slider">
            <div class="slide active" style="background-image: url('assets/images/FUllcontct.jpg');background-position: center;background-size: cover;"></div>
            <div class="slide" style="background-image: url('assets/images/aerobic.jpg');background-position: center;background-size: cover;"></div>
            <div class="slide" style="background-image: url('assets/images/taekwondo.jpg');background-position: center;background-size: cover;"></div>
        </div>
        <div class="overlay"></div>
        <div class="hero-content">
            <h1>اكتشف قدراتك</h1>
            <p>انضم إلى مجتمعنا وتقدم بخطى تناسبك</p>
            <div class="button-container">
                <a href="sign_up.php" class="button">ابدأ الآن</a>
            </div>
        </div>
    </section>
    <section id="about" dir="rtl">
        <div class="container">
            <h2>حول صالتنا الرياضية</h2>
            <p>في النادي <?php echo $row['club_name']?>، نحن مقتنعون بأهمية تقديم برامج تدريبية عالية الجودة لمساعدة أعضائنا على تحقيق أهدافهم في اللياقة البدنية. بفضل مرافق حديثة وفريق من المدربين ذوي الخبرة، نقدم مجموعة واسعة من الدروس، بما في ذلك التايكواندو والفول كونتاكت والأيروبيك.</p>
        </div>
    </section>
    <section id="plans">
        <h2 class="speacial-heading">خططنا</h2>
        <div class="container" dir="rtl">
            <?php foreach ($plans as $plan) : ?>
                <div class="plan">
                    <h3><?= htmlspecialchars($plan['name']); ?></h3>
                    <p><?= htmlspecialchars($plan['description']); ?></p>
                    <p>السعر: <?= htmlspecialchars($plan['price']); ?> درهم / الشهر</p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
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
                    <?php
                    $days = ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'];
                    $timeslots = [
                        "16:30:00-17:30:00",
                        "17:30:00-18:30:00",
                        "18:30:00-19:30:00",
                        "19:30:00-20:30:00",
                        "20:30:00-21:30:00",
                        "21:30:00-22:30:00",
                        "22:30:00-23:30:00"
                    ];
                    foreach ($days as $day) {
                        echo "<tr>";
                        echo "<th>$day</th>";
                        foreach ($timeslots as $timeslot) {
                            if (isset($schedule[$day][$timeslot]))
                                echo "<td>{$schedule[$day][$timeslot]}</td>";
                            else
                                echo "<td>--</td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>
    <footer id="footer" dir="rtl">
        <div class="container">
            <div class="footer-sections">
                <div class="footer-section">
                    <h3>حولنا</h3>
                    <p>في نادي <?php echo $row['club_name']?> نحن ملتزمون بتقديم برامج تدريب عالية الجودة لمساعدة أعضائنا على تحقيق أهدافهم في اللياقة البدنية.</p>
                </div>
                <div class="footer-section">
                    <h3>روابط سريعة</h3>
                    <ul>
                        <li><a href="#hero">الصفحة الرئيسية</a></li>
                        <li><a href="#about">معلومات عنا</a></li>
                        <li><a href="#plans">الخطط</a></li>
                        <li><a href="#Horaire">المواعيد</a></li>
                        <li><a href="login.php">تسجيل الدخول</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>اتصل بنا</h3>
                    <ul>
                        <li>البريد الإلكتروني: <?php echo $row['email']?></li>
                        <li>الهاتف: <?php echo $row['phone']?></li>
                        <li>العنوان: <?php echo $row['address']?></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>تابعنا</h3>
                    <div class="social-links">
                        <a href="<?php echo $row['facebook']?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?php echo $row['twitter']?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="<?php echo $row['instagram']?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
        <p>&copy; 2024  جميع الحقوق محفوظة النادي <?php echo $row['club_name']?> .</p>
        </div>
    </footer>

    <div class="modal" id="modal"></div>
    <div class="popup" id="popup">
        <span class="x-close" onclick="closePopup()">&times;</span>
        <div class="popup-content">
            <h2>تم تسجيل الاشتراك بنجاح</h2>
            <p>تم تسجيل اشتراكك بنجاح، واسم المستخدم الخاص بك هو: <strong id="user-identifier"></strong></p>
            <p>لتفعيل اشتراكك، يرجى التوجه شخصياً إلى ناديكم على العنوان التالي:</p>
            <p><strong>العنوان:</strong> <span id="club-address"></span></p>
            <p>للمزيد من المعلومات، اتصل بالمدير على: <strong id="admin-phone"></strong></p>
        </div>
        <button class="close-btn" onclick="closePopup()">إغلاق</button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            let currentSlide = 0;
            const slideInterval = setInterval(nextSlide, 3000);

            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }
        });

        const userData = {
            identifier: "<?php echo $id; ?>",
            clubAddress: "<?php echo $row['address']; ?>",
            adminPhone: "<?php echo $row['phone']; ?>"
        };
        window.onload = function() {
            if (userData.identifier) {
                document.getElementById('modal').style.display = 'block';
                document.getElementById('popup').style.display = 'block';
                document.getElementById('user-identifier').textContent = userData.identifier;
                document.getElementById('club-address').textContent = userData.clubAddress;
                document.getElementById('admin-phone').textContent = userData.adminPhone;
            }
        }

        function closePopup() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('popup').style.display = 'none';
        }
        document.getElementById('modal').addEventListener('click', closePopup);
        document.getElementById('popup').addEventListener('click', function(event) {
            event.stopPropagation();
        });
    </script>
</body>

</html>