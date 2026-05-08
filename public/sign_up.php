<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

$formData = $_SESSION['formData'] ?? [];
unset($_SESSION['formData']);

require_once __DIR__ . '/../config/database.php';

$plan  = new Plan($conn);
$plans = $plan->getNames();
?>
<?php require 'layout/header.php'; ?>

<div class="landing sign_up">
    <div class="container">
        <div class="form-container">
            <h2 class="title">تسجيل</h2>
            <form action="/sport-club/actions/signup_save.php" method="POST" enctype="multipart/form-data" dir="rtl">

                <div class="section">
                    <h3>المعلومات الشخصية</h3>

                    <div class="image-preview">
                        <img id="imagePreview" src="/sport-club/assets/images/defult_image.png" alt="صورة">
                        <label for="imageUpload" class="custom-file-upload">اختر الصورة</label>
                        <input type="file" id="imageUpload" class="file-input" accept="image/*" name="imageUpload">
                    </div>

                    <div class="row">
                        <div class="input-field">
                            <label>الاسم الأول <span style="color:#f00;">*</span></label>
                            <input type="text" name="prenom" value="<?= htmlspecialchars($formData['prenom'] ?? '') ?>" placeholder="أدخل اسمك الأول" required>
                        </div>
                        <div class="input-field">
                            <label>اسم العائلة <span style="color:#f00;">*</span></label>
                            <input type="text" name="nom" value="<?= htmlspecialchars($formData['nom'] ?? '') ?>" placeholder="أدخل اسمك الأخير" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field">
                            <label>تاريخ الميلاد <span style="color:#f00;">*</span></label>
                            <input type="date" name="date_naissance" value="<?= htmlspecialchars($formData['date_naissance'] ?? '') ?>" required>
                        </div>
                        <div class="input-field">
                            <label>العنوان <span style="color:#f00;">*</span></label>
                            <input type="text" name="address" value="<?= htmlspecialchars($formData['address'] ?? '') ?>" placeholder="أدخل العنوان" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field">
                            <label>الحالة الصحية <span style="color:#f00;">*</span></label>
                            <select name="health_status" required>
                                <option value="">اختر الحالة الصحية</option>
                                <?php foreach (['سليم', 'فرط في الحركة', 'يتناول دواء', 'ضيق التنفس', 'السكري', 'التوثر', 'الاعصاب', 'التوحد', 'إعاقة حركية'] as $h): ?>
                                    <option value="<?= $h ?>" <?= ($formData['health_status'] ?? '') === $h ? 'selected' : '' ?>><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-field">
                            <label>فصيلة الدم</label>
                            <select name="blood_type">
                                <option value="">اختر فصيلة الدم</option>
                                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bt): ?>
                                    <option value="<?= $bt ?>" <?= ($formData['blood_type'] ?? '') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field">
                            <label>الوزن (كغ) <span style="color:#f00;">*</span></label>
                            <input type="number" name="poids" value="<?= htmlspecialchars($formData['poids'] ?? '') ?>" placeholder="أدخل الوزن" step="0.1" min="0" required>
                        </div>
                        <div class="input-field">
                            <label>الرياضة <span style="color:#f00;">*</span></label>
                            <select name="type" id="sport" required>
                                <option value="">اختر الرياضة</option>
                                <?php foreach ($plans as $p): ?>
                                    <option value="<?= htmlspecialchars($p['name']) ?>" <?= ($formData['type'] ?? '') === $p['name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field">
                            <label>اسم الوصي</label>
                            <input type="text" name="guardian_name" value="<?= htmlspecialchars($formData['guardian_name'] ?? '') ?>" placeholder="اسم الوصي">
                        </div>
                        <div class="input-field">
                            <label>هاتف الوصي</label>
                            <input type="tel" name="guardian_phone" value="<?= htmlspecialchars($formData['guardian_phone'] ?? '') ?>" placeholder="هاتف الوصي">
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field">
                            <label>الهاتف الثاني للوصي</label>
                            <input type="tel" name="second_guardian_phone" value="<?= htmlspecialchars($formData['second_guardian_phone'] ?? '') ?>" placeholder="الهاتف الثاني">
                        </div>
                        <div class="input-field">
                            <label>مستوى الحزام</label>
                            <select name="current_belt" id="beltLevel">
                                <option value="">اختر مستوى الحزام</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn">تسجيل</button>
            </form>
        </div>
    </div>
</div>

<script>
const beltsTae  = ["أبيض","أصفر بخط أبيض","أصفر","برتقالي","أخضر","أزرق","أزرق بخط أحمر","أحمر","أحمر بخط أسود","أحمر بخطين أسودين"];
const beltsFull = ["أبيض","أصفر","برتقالي","أخضر","أزرق","بني","أسود"];

document.getElementById('sport').addEventListener('change', function () {
    const beltSelect = document.getElementById('beltLevel');
    beltSelect.innerHTML = '<option value="">اختر مستوى الحزام</option>';
    const belts = this.value === 'تايكواندو' ? beltsTae : this.value === 'فول كونتاكت' ? beltsFull : [];
    belts.forEach(b => {
        const opt = document.createElement('option');
        opt.value = opt.textContent = b;
        beltSelect.appendChild(opt);
    });
});

document.getElementById('imageUpload').addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('imagePreview').src = e.target.result;
        reader.readAsDataURL(file);
    }
});

document.querySelector('.custom-file-upload').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('imageUpload').click();
});

<?php if (isset($_SESSION['message'])): ?>
showToast("<?= addslashes($_SESSION['message']) ?>", "<?= $_SESSION['status'] ?? 'success' ?>");
<?php unset($_SESSION['message'], $_SESSION['status']); ?>
<?php endif; ?>

function showToast(message, type) {
    Toastify({ text: message, duration: 3000, backgroundColor: type === 'error' ? '#FF3030' : '#2F8C37', gravity: 'top', position: 'center' }).showToast();
}
</script>

<?php require 'layout/footer.php'; ?>
