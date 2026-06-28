<?php

function db_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $file = __DIR__ . '/config.php';
    $config = file_exists($file) ? require $file : [];

    return array_merge([
        'db_host' => '127.0.0.1',
        'db_port' => '3306',
        'db_name' => 'doping_shimi',
        'db_user' => 'root',
        'db_pass' => '',
        'db_charset' => 'utf8mb4',
        'auto_create_database' => false,
        'auto_migrate' => true,
        'auto_seed' => true,
    ], $config);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = db_config();
    if (!empty($config['auto_create_database'])) {
        ensure_mysql_database($config);
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db_host'],
        $config['db_port'],
        $config['db_name'],
        $config['db_charset']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("SET NAMES {$config['db_charset']} COLLATE {$config['db_charset']}_unicode_ci");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    if (!empty($config['auto_migrate'])) {
        migrate($pdo);
    }
    if (!empty($config['auto_seed'])) {
        seed_database($pdo);
    }

    return $pdo;
}

function ensure_mysql_database(array $config): void
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $config['db_host'],
        $config['db_port'],
        $config['db_charset']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $dbName = str_replace('`', '``', $config['db_name']);
    $charset = preg_replace('/[^a-zA-Z0-9_]/', '', $config['db_charset']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
}

function migrate(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS teachers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  role VARCHAR(190) NOT NULL,
  bio MEDIUMTEXT NULL,
  photo VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS videos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  video_url VARCHAR(500) NOT NULL,
  description TEXT NULL,
  duration VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  badge VARCHAR(80) NULL,
  grade VARCHAR(80) NULL,
  level VARCHAR(120) NULL,
  hours INT UNSIGNED NOT NULL DEFAULT 0,
  short_desc TEXT NULL,
  full_desc MEDIUMTEXT NULL,
  price INT UNSIGNED NOT NULL DEFAULT 0,
  sessions_count INT UNSIGNED NOT NULL DEFAULT 0,
  exams_count INT UNSIGNED NOT NULL DEFAULT 0,
  image VARCHAR(255) NULL,
  teacher_id INT UNSIGNED NULL,
  sample_video_id INT UNSIGNED NULL,
  free_video_id INT UNSIGNED NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
  CONSTRAINT fk_courses_sample_video FOREIGN KEY (sample_video_id) REFERENCES videos(id) ON DELETE SET NULL,
  CONSTRAINT fk_courses_free_video FOREIGN KEY (free_video_id) REFERENCES videos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS course_learnings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_learnings_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS course_features (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  text VARCHAR(255) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_features_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS featured_courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_featured_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS enrollments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  student_name VARCHAR(190) NULL,
  student_contact VARCHAR(190) NULL,
  paid_amount INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_enrollments_course_id (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS advisor_threads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_name VARCHAR(190) NOT NULL,
  student_contact VARCHAR(190) NOT NULL,
  grade VARCHAR(80) NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_advisor_contact (student_contact)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS advisor_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id INT UNSIGNED NOT NULL,
  sender ENUM('student','advisor') NOT NULL,
  body MEDIUMTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_advisor_messages_thread FOREIGN KEY (thread_id) REFERENCES advisor_threads(id) ON DELETE CASCADE,
  INDEX idx_advisor_messages_thread_id (thread_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    // جدول دانشجویان
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(190) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(190) NULL,
  grade VARCHAR(80) NULL,
  password_hash VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_students_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    // جدول آزمون‌های دانشجو
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS student_exams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  exam_title VARCHAR(190) NOT NULL,
  score INT UNSIGNED NOT NULL DEFAULT 0,
  total_questions INT UNSIGNED NOT NULL DEFAULT 0,
  correct_count INT UNSIGNED NOT NULL DEFAULT 0,
  wrong_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_exams_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

    // اگر دیتابیس قبلاً با داده نمونه ساخته شده باشد، خریدهای نمونه را پرداخت‌شده حساب می‌کنیم.
    $pdo->exec("UPDATE enrollments SET paid_amount = 1 WHERE paid_amount = 0 AND student_contact LIKE 'sample%@example.com'");
}

function seed_database(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    $teacherCount = (int)$pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
    if ($teacherCount === 0) {
        $teachers = [
            ['استاد محمدی', 'مدرس شیمی و برنامه‌ریز کنکور', 'تمرکز اصلی استاد محمدی روی ساده‌سازی مفاهیم و تبدیل درس شیمی به مسیر قابل تمرین است. در دوره‌ها، توضیح مفهومی با حل مثال و آزمون مرحله‌ای همراه می‌شود.', 'images/teacher-portrait.png'],
            ['استاد رحیمی', 'متخصص محاسبات و شیمی دوازدهم', 'استاد رحیمی برای مباحثی مثل استوکیومتری، تعادل و محاسبات، مسیر حل را مرحله‌به‌مرحله توضیح می‌دهد تا دانش‌آموز بداند از کجا شروع کند.', ''],
            ['استاد کاظمی', 'مدرس مفاهیم پایه شیمی', 'استاد کاظمی روی شروع اصولی شیمی و رفع ابهام‌های پایه تمرکز دارد و مباحث را با مثال‌های ساده و قابل لمس توضیح می‌دهد.', ''],
        ];
        $stmt = $pdo->prepare('INSERT INTO teachers (name, role, bio, photo) VALUES (?, ?, ?, ?)');
        foreach ($teachers as $teacher) {
            $stmt->execute($teacher);
        }
    }

    $videoCount = (int)$pdo->query('SELECT COUNT(*) FROM videos')->fetchColumn();
    if ($videoCount === 0) {
        $videos = [
            ['نمونه درس آرایش الکترونی', 'uploads/videos/sample-electron.mp4', 'نمونه ویدیو برای معرفی روش تدریس در شیمی یازدهم.', '۱۲ دقیقه'],
            ['جلسه رایگان پیوند شیمیایی', 'uploads/videos/free-bonding.mp4', 'جلسه رایگان دوره شیمی یازدهم.', '۱۸ دقیقه'],
            ['نمونه حل تست استوکیومتری', 'uploads/videos/stoichiometry-sample.mp4', 'حل چند تست منتخب از مبحث مول و نسبت‌ها.', '۱۵ دقیقه'],
        ];
        $stmt = $pdo->prepare('INSERT INTO videos (title, video_url, description, duration) VALUES (?, ?, ?, ?)');
        foreach ($videos as $video) {
            $stmt->execute($video);
        }
    }

    $courseCount = (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    if ($courseCount === 0) {
        $courses = seed_courses_data();
        $courseStmt = $pdo->prepare('INSERT INTO courses (title, slug, badge, grade, level, hours, short_desc, full_desc, price, sessions_count, exams_count, image, teacher_id, sample_video_id, free_video_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $learnStmt = $pdo->prepare('INSERT INTO course_learnings (course_id, title, description, sort_order) VALUES (?, ?, ?, ?)');
        $featureStmt = $pdo->prepare('INSERT INTO course_features (course_id, text, sort_order) VALUES (?, ?, ?)');

        foreach ($courses as $course) {
            [$title, $slug, $badge, $grade, $level, $hours, $short, $full, $price, $sessions, $exams, $image, $teacherId, $sampleVideoId, $freeVideoId, $learnings, $features] = $course;
            $courseStmt->execute([$title, $slug, $badge, $grade, $level, $hours, $short, $full, $price, $sessions, $exams, $image, $teacherId, $sampleVideoId, $freeVideoId]);
            $courseId = (int)$pdo->lastInsertId();
            foreach ($learnings as $i => $learning) {
                [$ltitle, $ldesc] = array_pad(explode('|', $learning, 2), 2, '');
                $learnStmt->execute([$courseId, trim($ltitle), trim($ldesc), $i + 1]);
            }
            foreach ($features as $i => $feature) {
                $featureStmt->execute([$courseId, $feature, $i + 1]);
            }
        }

        $featured = $pdo->prepare('INSERT INTO featured_courses (course_id, sort_order) VALUES (?, ?)');
        foreach ([1, 2, 3] as $i => $courseId) {
            $featured->execute([$courseId, $i + 1]);
        }

        seed_enrollments($pdo, [1 => 845, 2 => 620, 3 => 980, 4 => 410, 5 => 260, 6 => 310]);
    }
}

function seed_courses_data(): array
{
    return [
        ['شیمی یازدهم؛ از مفهوم تا تست', 'shimi-11', 'پرفروش', 'یازدهم', 'تمرین و تثبیت', 42, 'یک مسیر کامل برای فهم فصل‌ها، حل نمونه سوال و رسیدن به تست‌های استاندارد بدون عجله و پراکندگی.', 'این دوره برای دانش‌آموزی ساخته شده که می‌خواهد شیمی یازدهم را از مفهوم شروع کند و بعد سراغ تست و تمرین جدی برود. هر فصل با توضیح ساده، مثال حل‌شده، تمرین مرحله‌ای و آزمون کوتاه همراه است.', 1290000, 24, 12, 'images/course-shimi-2.png', 1, 1, 2, ['ساختار اتم و آرایش الکترونی|عددهای کوانتومی و آرایش الکترونی را بدون حفظ‌کاری یاد می‌گیری.', 'روندهای جدول تناوبی|می‌فهمی شعاع، انرژی یونش و الکترونگاتیوی چرا تغییر می‌کنند.', 'پیوند شیمیایی|پیوند یونی، کووالانسی و شکل مولکول‌ها با مثال‌های قابل لمس توضیح داده می‌شوند.'], ['دسترسی بلندمدت به ویدیوها', '۲۴ جلسه آموزشی منظم', '۱۲ آزمون اختصاصی با پاسخنامه', 'پشتیبانی آموزشی مدرس']],
        ['شیمی دوازدهم با تمرین جدی', 'shimi-12', 'جدید', 'دوازدهم', 'تمرین و تثبیت', 56, 'درس‌ها کوتاه، مثال‌ها هدفمند و تمرین‌ها طوری چیده شده‌اند که برای امتحان نهایی و کنکور آماده شوی.', 'در این دوره مباحث شیمی دوازدهم با تمرکز روی فهم مفهومی، حل تمرین، جمع‌بندی نکته‌ها و آزمون‌های کوتاه آموزش داده می‌شوند.', 1590000, 30, 14, 'images/course-shimi-3.png', 2, 1, 2, ['تعادل و اسید و باز|مفاهیم اصلی را با مثال‌های مرحله‌ای یاد می‌گیری.', 'الکتروشیمی|واکنش‌ها، سلول‌ها و نکته‌های مهم تستی بررسی می‌شوند.', 'جمع‌بندی امتحان نهایی|برای پاسخ‌گویی دقیق‌تر به سوالات تشریحی آماده می‌شوی.'], ['دسترسی بلندمدت به ویدیوها', '۳۰ جلسه آموزشی', '۱۴ آزمون اختصاصی', 'برنامه پیشنهادی مطالعه']],
        ['جمع‌بندی کنکور شیمی', 'konkur-chemistry', 'کنکور', 'کنکور', 'جمع‌بندی و تست', 64, 'مرور فصل‌های مهم، تست‌های منتخب و نکته‌هایی که در زمان کم، بیشترین اثر را روی نتیجه می‌گذارند.', 'این دوره برای روزهای جمع‌بندی طراحی شده و به‌جای پراکندگی، روی مرور هدفمند فصل‌ها، تست‌های پرتکرار، تحلیل دام‌های تستی و برنامه‌ریزی زمان تمرکز دارد.', 2490000, 28, 18, 'images/course-konkour.png', 1, 3, 3, ['مرور سریع فصل‌های مهم|مباحث پرتکرار با اولویت کنکور مرور می‌شوند.', 'تست‌های منتخب|تست‌ها بر اساس اهمیت و دام آموزشی انتخاب شده‌اند.', 'تحلیل اشتباهات|یاد می‌گیری چرا یک گزینه غلط وسوسه‌کننده است.'], ['دسترسی تا پایان کنکور', '۲۸ جلسه جمع‌بندی', '۱۸ آزمون زمان‌دار', 'تحلیل تست و پاسخنامه']],
        ['شیمی دهم از صفر مطمئن', 'shimi-10', 'پایه‌ای', 'دهم', 'شروع از پایه', 38, 'برای شروعی تمیز و بی‌استرس؛ مفاهیم پایه را ساده می‌سازیم و بعد کم‌کم سراغ سوال‌های جدی‌تر می‌رویم.', 'دوره شیمی دهم برای دانش‌آموزی مناسب است که می‌خواهد پایه را درست بسازد و از همان ابتدا با روش مفهومی جلو برود.', 990000, 20, 8, 'images/course-shimi-1.png', 3, 1, 2, ['مفاهیم پایه شیمی|از ماده و اتم تا مولکول‌ها را ساده یاد می‌گیری.', 'حل تمرین مرحله‌ای|تمرین‌ها از ساده به متوسط چیده شده‌اند.', 'آمادگی امتحان مدرسه|با نمونه سوال‌های استاندارد آماده می‌شوی.'], ['دسترسی بلندمدت', '۲۰ جلسه آموزشی', '۸ آزمون کوتاه']],
        ['استوکیومتری بدون ترس از عددها', 'stoichiometry', 'محاسباتی', 'یازدهم', 'تمرین و تثبیت', 24, 'محاسبات مولی، نسبت‌ها و معادله‌ها را قدم‌به‌قدم حل می‌کنیم تا مسئله‌ها از حالت مبهم خارج شوند.', 'در این دوره روش حل مسئله‌های استوکیومتری با الگوریتم ساده و قابل تکرار آموزش داده می‌شود.', 790000, 14, 7, 'images/course-stoichiometry.png', 2, 3, 3, ['مفهوم مول|مول و تعداد ذره‌ها را کاربردی یاد می‌گیری.', 'نسبت‌های واکنش|از معادله واکنش به جواب مسئله می‌رسی.', 'حل تست زمان‌دار|سرعت و دقتت در مسئله‌های عددی بیشتر می‌شود.'], ['۱۴ جلسه محاسباتی', '۷ آزمون', 'حل تمرین فراوان']],
        ['ترمودینامیک و تعادل شیمیایی', 'thermo-equilibrium', 'مفهومی', 'دوازدهم', 'تمرین و تثبیت', 32, 'مفاهیم انرژی، آنتالپی و تعادل را با مثال‌های قابل لمس یاد می‌گیری تا در تست‌ها سردرگم نشوی.', 'دوره‌ای برای فهم بهتر انرژی، گرما، آنتالپی، تعادل و کاربردهای تستی و تشریحی آن‌ها.', 990000, 18, 8, 'images/course-thermo.png', 3, 1, 2, ['آنتالپی و انرژی|ارتباط انرژی و واکنش‌ها را می‌فهمی.', 'تعادل شیمیایی|جابجایی تعادل و عوامل مؤثر را یاد می‌گیری.', 'تمرین مفهومی|با سوال‌های مفهومی و ترکیبی تمرین می‌کنی.'], ['۱۸ جلسه آموزشی', '۸ آزمون', 'پاسخنامه تشریحی']],
    ];
}

function seed_enrollments(PDO $pdo, array $counts): void
{
    $stmt = $pdo->prepare('INSERT INTO enrollments (course_id, student_name, student_contact, paid_amount) VALUES (?, ?, ?, 1)');
    foreach ($counts as $courseId => $count) {
        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([$courseId, 'دانش‌آموز نمونه ' . $i, 'sample' . $i . '@example.com']);
        }
    }
}
