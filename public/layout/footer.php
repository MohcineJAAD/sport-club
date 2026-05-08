<footer id="footer" dir="rtl">
    <div class="container">
        <div class="footer-sections">
            <div class="footer-section">
                <h3>حولنا</h3>
                <p>في نادي <?= htmlspecialchars($club['club_name']) ?> نحن ملتزمون بتقديم برامج تدريب عالية الجودة.</p>
            </div>
            <div class="footer-section">
                <h3>روابط سريعة</h3>
                <ul>
                    <li><a href="#hero">الصفحة الرئيسية</a></li>
                    <li><a href="#about">معلومات عنا</a></li>
                    <li><a href="#plans">الخطط</a></li>
                    <li><a href="#Horaire">المواعيد</a></li>
                    <li><a href="/sport-club/login.php">تسجيل الدخول</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>اتصل بنا</h3>
                <ul>
                    <li>البريد: <?= htmlspecialchars($club['email'] ?? '') ?></li>
                    <li>الهاتف: <?= htmlspecialchars($club['phone'] ?? '') ?></li>
                    <li>العنوان: <?= htmlspecialchars($club['address'] ?? '') ?></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>تابعنا</h3>
                <div class="social-links">
                    <?php if (!empty($club['facebook'])): ?>
                        <a href="<?= htmlspecialchars($club['facebook']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($club['twitter'])): ?>
                        <a href="<?= htmlspecialchars($club['twitter']) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($club['instagram'])): ?>
                        <a href="<?= htmlspecialchars($club['instagram']) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> جميع الحقوق محفوظة - <?= htmlspecialchars($club['club_name']) ?></p>
    </div>
</footer>
<script src="/sport-club/assets/js/public.js"></script>
</body>
</html>
