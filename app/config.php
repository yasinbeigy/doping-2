<?php
// تنظیمات دیتابیس MySQL
// در XAMPP معمولاً نام کاربری root و رمز عبور خالی است.
return [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'doping_shimi',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',

    // در هاست اشتراکی مثل InfinityFree دیتابیس را از پنل بسازید و install.sql را ایمپورت کنید.
    // بنابراین ساخت خودکار دیتابیس خاموش است. برای لوکال اگر خواستید روشنش کنید.
    'auto_create_database' => false,

    // اگر install.sql را ایمپورت نکرده باشید، این دو گزینه جدول‌ها و داده نمونه را می‌سازند.
    'auto_migrate' => true,
    'auto_seed' => true,
];
