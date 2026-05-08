<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$admin = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();
?>
<?php require 'layout/header.php'; ?>

<h1 class="p-relative">الإعدادات</h1>

<div class="profile-container m-20 bg-fff rad-10 p-20">
    <form method="POST" action="/sport-club/actions/settings_save.php" enctype="multipart/form-data">

        <div class="section mb-20">
            <h3>معلومات النادي</h3>
            <div class="row">
                <div class="input-field">
                    <label>اسم النادي</label>
                    <input type="text" name="club_name" value="<?= htmlspecialchars($admin['club_name']) ?>" required>
                </div>
                <div class="input-field">
                    <label>شعار النادي</label>
                    <input type="file" name="logo" accept="image/*">
                    <small>اتركه فارغاً للإبقاء على الشعار الحالي</small>
                </div>
            </div>
            <div class="row mt-20">
                <div class="input-field">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>">
                </div>
                <div class="input-field">
                    <label>الهاتف</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>">
                </div>
                <div class="input-field">
                    <label>العنوان</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($admin['address']) ?>">
                </div>
            </div>
        </div>

        <div class="section mb-20">
            <h3>وسائل التواصل الاجتماعي</h3>
            <div class="row">
                <div class="input-field">
                    <label>فيسبوك</label>
                    <input type="text" name="facebook" value="<?= htmlspecialchars($admin['facebook'] ?? '') ?>">
                </div>
                <div class="input-field">
                    <label>إنستغرام</label>
                    <input type="text" name="instagram" value="<?= htmlspecialchars($admin['instagram'] ?? '') ?>">
                </div>
                <div class="input-field">
                    <label>تويتر</label>
                    <input type="text" name="twitter" value="<?= htmlspecialchars($admin['twitter'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="section mb-20">
            <h3>تغيير كلمة المرور</h3>
            <div class="row">
                <div class="input-field">
                    <label>كلمة المرور الجديدة</label>
                    <input type="password" name="new_password" placeholder="اتركه فارغاً إذا لم تريد التغيير">
                </div>
                <div class="input-field">
                    <label>تأكيد كلمة المرور</label>
                    <input type="password" name="confirm_password">
                </div>
            </div>
        </div>

        <button type="submit" class="btn mt-10">حفظ التغييرات</button>
    </form>
</div>

<?php require 'layout/footer.php'; ?>
