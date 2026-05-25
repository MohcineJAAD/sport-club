<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
Auth::check();

$id = trim($_GET['id'] ?? '');
if (!$id) {
    header("Location: /sport-club/admin/adherents.php");
    exit();
}

$adherent = new Adherent($conn);
$member   = $adherent->getById($id);
if (!$member) {
    header("Location: /sport-club/admin/adherents.php");
    exit();
}
$member = $member[0];
$status = $_GET['status'] ?? '';

$stmt = $conn->prepare("SELECT * FROM trophies WHERE adherent_id = ?");
$stmt->bind_param("s", $member['identifier']);
$stmt->execute();
$trophies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$type       = $member['type'] ?? '';
$belts_tae  = ["أبيض","أصفر بخط أبيض","أصفر","برتقالي","أخضر","أزرق","أزرق بخط أحمر","أحمر","أحمر بخط أسود","أحمر بخطين أسودين"];
$belts_full = ["أبيض","أصفر","برتقالي","أخضر","أزرق","بني","أسود"];
$belts      = $type === 'تايكواندو' ? $belts_tae : $belts_full;

$imgSrc = !empty($member['image_path'])
    ? '/sport-club/assets/uploads/' . $member['image_path']
    : '/sport-club/assets/images/defult_image.png';
$bcPath = !empty($member['BC_path'])
    ? '/sport-club/assets/uploads/' . $member['BC_path']
    : '';
?>
<?php require 'layout/header.php'; ?>

<div class="profile-container m-20 bg-fff rad-10">
    <div class="p-20">
        <form action="/sport-club/actions/adherent_update.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="identifier" value="<?= htmlspecialchars($id) ?>">

            <div class="section">

                <!-- Profile photo -->
                <div class="image-preview">
                    <img id="imagePreview" src="<?= htmlspecialchars($imgSrc) ?>" alt="صورة المشترك">
                    <label for="imageUpload" class="custom-file-upload">اختر صورة</label>
                    <input type="file" id="imageUpload" class="file-input" accept="image/*" name="imageUpload" disabled>
                    <a href="<?= htmlspecialchars($imgSrc) ?>" download class="custom-file-upload">تحميل</a>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>الاسم الأول</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($member['prenom'] ?? '') ?>" disabled>
                    </div>
                    <div class="input-field">
                        <label>اسم العائلة</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($member['nom'] ?? '') ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>تاريخ الازدياد</label>
                        <input type="date" name="date_naissance" value="<?= htmlspecialchars($member['date_naissance'] ?? '') ?>" disabled dir="ltr">
                    </div>
                    <div class="input-field">
                        <label>تاريخ الانخراط</label>
                        <input type="date" name="date_adhesion" value="<?= htmlspecialchars($member['date_adhesion'] ?? '') ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>اسم الوصي</label>
                        <input type="text" name="guardian_name" value="<?= htmlspecialchars($member['guardian_name'] ?? '') ?>" disabled>
                    </div>
                    <div class="input-field">
                        <label>هاتف الوصي</label>
                        <input type="tel" name="guardian_phone" value="<?= htmlspecialchars($member['guardian_phone'] ?? '') ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>الهاتف الثاني للوصي</label>
                        <input type="tel" name="second_guardian_phone" value="<?= htmlspecialchars($member['second_guardian_phone'] ?? '') ?>" disabled>
                    </div>
                    <div class="input-field">
                        <label>العنوان</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($member['address'] ?? '') ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>الحزام الحالي</label>
                        <select name="current_belt" disabled>
                            <option value="">اختر</option>
                            <?php foreach ($belts as $b): ?>
                                <option value="<?= $b ?>" <?= ($member['current_belt'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-field">
                        <label>الحزام التالي</label>
                        <select name="next_belt" disabled>
                            <option value="">اختر</option>
                            <?php foreach ($belts as $b): ?>
                                <option value="<?= $b ?>" <?= ($member['next_belt'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>الوزن (كغ)</label>
                        <input type="number" name="poids" step="0.1" value="<?= htmlspecialchars($member['poids'] ?? '') ?>" disabled>
                    </div>
                    <div class="input-field">
                        <label>فصيلة الدم</label>
                        <select name="blood_type" disabled>
                            <option value="">اختر</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                                <option value="<?= $bt ?>" <?= ($member['blood_type'] ?? '') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>الحالة الصحية</label>
                        <select name="health_status" disabled>
                            <option value="">اختر</option>
                            <?php foreach (['سليم','فرط في الحركة','يتناول دواء','ضيق التنفس','السكري','التوثر','الاعصاب','التوحد','إعاقة حركية'] as $h): ?>
                                <option value="<?= $h ?>" <?= ($member['health_status'] ?? '') === $h ? 'selected' : '' ?>><?= $h ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-field">
                        <label>المعرف</label>
                        <input type="text" value="<?= htmlspecialchars($member['identifier']) ?>" disabled data-locked>
                    </div>
                </div>

                <div class="row">
                    <div class="input-field">
                        <label>رقم الرخصة الجامعية</label>
                        <input type="text" name="licence" value="<?= htmlspecialchars($member['licence'] ?? '') ?>" disabled>
                    </div>
                    <div class="input-field">
                        <label>الرياضة</label>
                        <input type="text" value="<?= htmlspecialchars($type) ?>" disabled data-locked>
                    </div>
                </div>

                <!-- Note — full width -->
                <div class="row">
                    <div class="input-field w-full">
                        <label>ملحوظات</label>
                        <textarea name="note" rows="4" class="w-full" disabled><?= htmlspecialchars($member['note'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Birth contract — full width -->
                <div class="row">
                    <div class="input-field w-full">
                        <label>عقد الميلاد</label>
                        <div class="file-upload">
                            <span id="bc-filename">
                                <?= $bcPath ? htmlspecialchars(basename($member['BC_path'])) : 'لم يتم اختيار ملف' ?>
                            </span>
                            <div class="file-upload-actions">
                                <label for="fileUpload" class="file-upload-label">اختر ملف</label>
                                <input type="file" id="fileUpload" class="file-upload-input" accept="image/*,application/pdf" name="BCUpload" disabled>
                                <?php if ($bcPath): ?>
                                    <a href="<?= htmlspecialchars($bcPath) ?>" download class="custom-file-upload">تحميل</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="action-buttons mt-20">
                <button type="button" class="btn-shape modify-btn mb-10"><i class="fas fa-edit"></i> تعديل</button>
                <button type="submit" class="btn-shape save-btn hidden mb-10"><i class="fas fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

<!-- Trophies -->
<div class="profile-container m-20 bg-fff rad-10">
    <div class="p-20">
        <h2>الالقاب</h2>
        <div class="wrapper d-grid gap-20">
            <div class="add-card cards rad-6 p-20 txt-c" id="add-trophy-btn">
                <div class="add-content">
                    <div class="circle-dashed"><i class="fa-solid fa-plus"></i></div>
                    <p class="mt-10 color-333">اضف</p>
                </div>
            </div>
            <?php foreach ($trophies as $trophy): ?>
                <div class="cards rad-10 txt-c">
                    <div class="card-content p-20">
                        <h3><?= htmlspecialchars($trophy['description']) ?></h3>
                        <p><?= htmlspecialchars($trophy['created_at']) ?></p>
                        <i class="fa-solid fa-trophy fa-fw fs-30 mb-10" style="color:#203a85;"></i>
                        <a href="/sport-club/actions/trophy_delete.php?id=<?= $trophy['id'] ?>&member=<?= urlencode($id) ?>"
                           class="btn-shape bg-f00 color-fff d-block mt-10">حذف</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Trophy modal -->
<div id="trophyModal" class="modal" dir="rtl">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>اضف لقب</h2>
        <form action="/sport-club/actions/trophy_add.php" method="POST">
            <input type="hidden" name="identifier" value="<?= htmlspecialchars($id) ?>">
            <div class="input-field">
                <label>الوصف</label>
                <textarea name="description" rows="3" class="w-full" required></textarea>
            </div>
            <button type="submit" class="btn-shape save-btn mt-10">اضف</button>
        </form>
    </div>
</div>

<script>
document.getElementById('imageUpload').addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('imagePreview').src = e.target.result;
        reader.readAsDataURL(file);
    }
});

document.getElementById('fileUpload').addEventListener('change', function () {
    document.getElementById('bc-filename').textContent =
        this.files.length ? this.files[0].name : 'لم يتم اختيار ملف';
});

document.querySelector('.modify-btn').addEventListener('click', function () {
    document.querySelectorAll(
        'input:not([type="hidden"]):not([data-locked]), select:not([data-locked]), textarea:not([data-locked])'
    ).forEach(el => el.disabled = false);
    this.classList.add('hidden');
    document.querySelector('.save-btn').classList.remove('hidden');
});

// Re-enable all before submit so disabled fields are included
document.querySelector('form').addEventListener('submit', function () {
    document.querySelectorAll(
        'input:not([data-locked]), select:not([data-locked]), textarea:not([data-locked])'
    ).forEach(el => el.disabled = false);
});


const modal = document.getElementById('trophyModal');
document.getElementById('add-trophy-btn').addEventListener('click', () => modal.style.display = 'flex');
modal.querySelector('.close').addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

<?php if ($status === 'success'): ?>
Toastify({ text: "تم حفظ البيانات بنجاح", duration: 3000, gravity: "top", position: "center", className: "toast-success" }).showToast();
<?php elseif ($status === 'error'): ?>
Toastify({ text: "حدث خطأ أثناء الحفظ", duration: 4000, gravity: "top", position: "center", className: "toast-error" }).showToast();
<?php endif; ?>
</script>

<?php require 'layout/footer.php'; ?>