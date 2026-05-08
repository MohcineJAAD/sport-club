<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="/sport-club/assets/css/master1.css">
    <link rel="stylesheet" href="/sport-club/assets/css/normalize.css">
    <link rel="stylesheet" href="/sport-club/assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <section class="landing">
        <div class="container">
            <div class="illustration">
                <img src="/sport-club/assets/images/login.png" alt="Illustration">
            </div>
            <div class="form-container" dir="rtl">
                <form action="/sport-club/actions/login.php" method="POST" class="login">
                    <h2 class="title">تسجيل الدخول</h2>
                    <div class="input-filde">
                        <i class="fa-regular fa-circle-user"></i>
                        <input type="text" name="identifier" placeholder="أدخل المعرف" autocomplete="off">
                    </div>
                    <div class="input-filde">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="أدخل كلمة المرور" autocomplete="off">
                    </div>
                    <input type="submit" value="دخول" class="btn">
                </form>
            </div>
        </div>
    </section>
    <script>
    <?php
    session_start();
    if (isset($_SESSION['error'])) {
        echo "Toastify({ text: '" . addslashes($_SESSION['error']) . "', duration: 3000, backgroundColor: '#FF3030', gravity: 'top', position: 'center' }).showToast();";
        unset($_SESSION['error']);
    }
    ?>
    </script>
</body>
</html>