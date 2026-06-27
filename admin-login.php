<?php
session_start();
require_once __DIR__ . '/app/helpers.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    $validPassword = false;
    if ($admin) {
        $storedHash = (string)$admin['password_hash'];
        $validPassword = password_verify($password, $storedHash) || hash_equals($storedHash, hash('sha256', $password));
    }
    if ($admin && $validPassword) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: admin.php');
        exit;
    }
    $error = 'نام کاربری یا رمز عبور درست نیست.';
}
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>ورود ادمین | دوپینگ شیمی</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/backend.css"></head><body>
<section class="auth-section auth-clean"><div class="container auth-container auth-centered"><div class="auth-card auth-card-clean login-card"><div class="auth-card-header auth-card-header-clean"><h1>ورود ادمین</h1><p>برای مدیریت دوره‌ها، مدرسین، ویدیوها و پیام‌های مشاور وارد شوید.</p></div><?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?><form method="post" class="auth-form"><label><span>نام کاربری</span><input name="username" required value="<?= e($_POST['username'] ?? 'admin') ?>"></label><label class="password-field"><span>رمز عبور</span><span class="password-input-wrap"><input type="password" name="password" required><button type="button" class="password-toggle" data-password-toggle aria-label="نمایش رمز عبور"><svg viewBox="0 0 24 24" class="icon password-eye-icon" aria-hidden="true"><use href="images/icons.svg#eye"></use></svg></button></span></label><button class="btn btn-primary btn-lg auth-submit">ورود به پنل</button></form><p class="auth-powered">اطلاعات پیش‌فرض: admin / admin123</p></div></div></section><script src="js/main.js"></script></body></html>
