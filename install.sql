-- install.sql
-- نصب دیتابیس MySQL برای سایت دوپینگ شیمی
--
-- روش استفاده در هاست اشتراکی:
-- 1) در پنل هاست، یک دیتابیس MySQL بسازید.
-- 2) وارد phpMyAdmin همان دیتابیس شوید.
-- 3) این فایل را Import کنید.
--
-- اگر روی لوکال هستید و اجازه ساخت دیتابیس دارید، می‌توانید این دو خط را از کامنت خارج کنید:
-- CREATE DATABASE IF NOT EXISTS `doping_shimi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `doping_shimi`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(190) NOT NULL,
  `role` VARCHAR(190) NOT NULL,
  `bio` MEDIUMTEXT NULL,
  `photo` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(190) NOT NULL,
  `video_url` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `duration` VARCHAR(80) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(190) NOT NULL UNIQUE,
  `badge` VARCHAR(80) NULL,
  `grade` VARCHAR(80) NULL,
  `level` VARCHAR(120) NULL,
  `hours` INT UNSIGNED NOT NULL DEFAULT 0,
  `short_desc` TEXT NULL,
  `full_desc` MEDIUMTEXT NULL,
  `price` INT UNSIGNED NOT NULL DEFAULT 0,
  `sessions_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `exams_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NULL,
  `teacher_id` INT UNSIGNED NULL,
  `sample_video_id` INT UNSIGNED NULL,
  `free_video_id` INT UNSIGNED NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_courses_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_courses_sample_video` FOREIGN KEY (`sample_video_id`) REFERENCES `videos`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_courses_free_video` FOREIGN KEY (`free_video_id`) REFERENCES `videos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_learnings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(190) NOT NULL,
  `description` TEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT `fk_learnings_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_features` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `text` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT `fk_features_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `featured_courses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT `fk_featured_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `student_name` VARCHAR(190) NULL,
  `student_contact` VARCHAR(190) NULL,
  `paid_amount` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  INDEX `idx_enrollments_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advisor_threads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(190) NOT NULL,
  `student_contact` VARCHAR(190) NOT NULL,
  `grade` VARCHAR(80) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_advisor_contact` (`student_contact`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advisor_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `thread_id` INT UNSIGNED NOT NULL,
  `sender` ENUM('student','advisor') NOT NULL,
  `body` MEDIUMTEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_advisor_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `advisor_threads`(`id`) ON DELETE CASCADE,
  INDEX `idx_advisor_messages_thread_id` (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ادمین پیش‌فرض
-- نام کاربری: admin
-- رمز عبور: admin123
-- هش زیر با SHA2 ساخته شده و admin-login.php آن را پشتیبانی می‌کند.
INSERT INTO `admins` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', SHA2('admin123', 256))
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

INSERT INTO `teachers` (`id`, `name`, `role`, `bio`, `photo`) VALUES
(1, 'استاد محمدی', 'مدرس شیمی و برنامه‌ریز کنکور', 'تمرکز اصلی استاد محمدی روی ساده‌سازی مفاهیم و تبدیل درس شیمی به مسیر قابل تمرین است. در دوره‌ها، توضیح مفهومی با حل مثال و آزمون مرحله‌ای همراه می‌شود.', 'images/teacher-portrait.png'),
(2, 'استاد رحیمی', 'متخصص محاسبات و شیمی دوازدهم', 'استاد رحیمی برای مباحثی مثل استوکیومتری، تعادل و محاسبات، مسیر حل را مرحله‌به‌مرحله توضیح می‌دهد تا دانش‌آموز بداند از کجا شروع کند.', ''),
(3, 'استاد کاظمی', 'مدرس مفاهیم پایه شیمی', 'استاد کاظمی روی شروع اصولی شیمی و رفع ابهام‌های پایه تمرکز دارد و مباحث را با مثال‌های ساده و قابل لمس توضیح می‌دهد.', '')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `role` = VALUES(`role`), `bio` = VALUES(`bio`), `photo` = VALUES(`photo`);

INSERT INTO `videos` (`id`, `title`, `video_url`, `description`, `duration`) VALUES
(1, 'نمونه درس آرایش الکترونی', 'uploads/videos/sample-electron.mp4', 'نمونه ویدیو برای معرفی روش تدریس در شیمی یازدهم.', '۱۲ دقیقه'),
(2, 'جلسه رایگان پیوند شیمیایی', 'uploads/videos/free-bonding.mp4', 'جلسه رایگان دوره شیمی یازدهم.', '۱۸ دقیقه'),
(3, 'نمونه حل تست استوکیومتری', 'uploads/videos/stoichiometry-sample.mp4', 'حل چند تست منتخب از مبحث مول و نسبت‌ها.', '۱۵ دقیقه')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `video_url` = VALUES(`video_url`), `description` = VALUES(`description`), `duration` = VALUES(`duration`);

INSERT INTO `courses` (`id`, `title`, `slug`, `badge`, `grade`, `level`, `hours`, `short_desc`, `full_desc`, `price`, `sessions_count`, `exams_count`, `image`, `teacher_id`, `sample_video_id`, `free_video_id`, `is_published`) VALUES
(1, 'شیمی یازدهم؛ از مفهوم تا تست', 'shimi-11', 'پرفروش', 'یازدهم', '', 42, 'یک مسیر کامل برای فهم فصل‌ها، حل نمونه سوال و رسیدن به تست‌های استاندارد بدون عجله و پراکندگی.', 'این دوره برای دانش‌آموزی ساخته شده که می‌خواهد شیمی یازدهم را از مفهوم شروع کند و بعد سراغ تست و تمرین جدی برود. هر فصل با توضیح ساده، مثال حل‌شده، تمرین مرحله‌ای و آزمون کوتاه همراه است.', 1290000, 24, 12, 'images/course-shimi-2.png', 1, 1, 2, 1),
(2, 'شیمی دوازدهم با تمرین جدی', 'shimi-12', 'جدید', 'دوازدهم', '', 56, 'درس‌ها کوتاه، مثال‌ها هدفمند و تمرین‌ها طوری چیده شده‌اند که برای امتحان نهایی و کنکور آماده شوی.', 'در این دوره مباحث شیمی دوازدهم با تمرکز روی فهم مفهومی، حل تمرین، جمع‌بندی نکته‌ها و آزمون‌های کوتاه آموزش داده می‌شوند.', 1590000, 30, 14, 'images/course-shimi-3.png', 2, 1, 2, 1),
(3, 'جمع‌بندی کنکور شیمی', 'konkur-chemistry', 'کنکور', 'کنکور', '', 64, 'مرور فصل‌های مهم، تست‌های منتخب و نکته‌هایی که در زمان کم، بیشترین اثر را روی نتیجه می‌گذارند.', 'این دوره برای روزهای جمع‌بندی طراحی شده و به‌جای پراکندگی، روی مرور هدفمند فصل‌ها، تست‌های پرتکرار، تحلیل دام‌های تستی و برنامه‌ریزی زمان تمرکز دارد.', 2490000, 28, 18, 'images/course-konkour.png', 1, 3, 3, 1),
(4, 'شیمی دهم از صفر مطمئن', 'shimi-10', 'پایه‌ای', 'دهم', '', 38, 'برای شروعی تمیز و بی‌استرس؛ مفاهیم پایه را ساده می‌سازیم و بعد کم‌کم سراغ سوال‌های جدی‌تر می‌رویم.', 'دوره شیمی دهم برای دانش‌آموزی مناسب است که می‌خواهد پایه را درست بسازد و از همان ابتدا با روش مفهومی جلو برود.', 990000, 20, 8, 'images/course-shimi-1.png', 3, 1, 2, 1),
(5, 'استوکیومتری بدون ترس از عددها', 'stoichiometry', 'محاسباتی', 'یازدهم', '', 24, 'محاسبات مولی، نسبت‌ها و معادله‌ها را قدم‌به‌قدم حل می‌کنیم تا مسئله‌ها از حالت مبهم خارج شوند.', 'در این دوره روش حل مسئله‌های استوکیومتری با الگوریتم ساده و قابل تکرار آموزش داده می‌شود.', 790000, 14, 7, 'images/course-stoichiometry.png', 2, 3, 3, 1),
(6, 'ترمودینامیک و تعادل شیمیایی', 'thermo-equilibrium', 'مفهومی', 'دوازدهم', '', 32, 'مفاهیم انرژی، آنتالپی و تعادل را با مثال‌های قابل لمس یاد می‌گیری تا در تست‌ها سردرگم نشوی.', 'دوره‌ای برای فهم بهتر انرژی، گرما، آنتالپی، تعادل و کاربردهای تستی و تشریحی آن‌ها.', 990000, 18, 8, 'images/course-thermo.png', 3, 1, 2, 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `badge` = VALUES(`badge`), `grade` = VALUES(`grade`), `level` = VALUES(`level`), `hours` = VALUES(`hours`), `short_desc` = VALUES(`short_desc`), `full_desc` = VALUES(`full_desc`), `price` = VALUES(`price`), `sessions_count` = VALUES(`sessions_count`), `exams_count` = VALUES(`exams_count`), `image` = VALUES(`image`), `teacher_id` = VALUES(`teacher_id`), `sample_video_id` = VALUES(`sample_video_id`), `free_video_id` = VALUES(`free_video_id`), `is_published` = VALUES(`is_published`);

DELETE FROM `course_learnings` WHERE `course_id` IN (1,2,3,4,5,6);
INSERT INTO `course_learnings` (`course_id`, `title`, `description`, `sort_order`) VALUES
(1, 'ساختار اتم و آرایش الکترونی', 'عددهای کوانتومی و آرایش الکترونی را بدون حفظ‌کاری یاد می‌گیری.', 1),
(1, 'روندهای جدول تناوبی', 'می‌فهمی شعاع، انرژی یونش و الکترونگاتیوی چرا تغییر می‌کنند.', 2),
(1, 'پیوند شیمیایی', 'پیوند یونی، کووالانسی و شکل مولکول‌ها با مثال‌های قابل لمس توضیح داده می‌شوند.', 3),
(2, 'تعادل و اسید و باز', 'مفاهیم اصلی را با مثال‌های مرحله‌ای یاد می‌گیری.', 1),
(2, 'الکتروشیمی', 'واکنش‌ها، سلول‌ها و نکته‌های مهم تستی بررسی می‌شوند.', 2),
(2, 'جمع‌بندی امتحان نهایی', 'برای پاسخ‌گویی دقیق‌تر به سوالات تشریحی آماده می‌شوی.', 3),
(3, 'مرور سریع فصل‌های مهم', 'مباحث پرتکرار با اولویت کنکور مرور می‌شوند.', 1),
(3, 'تست‌های منتخب', 'تست‌ها بر اساس اهمیت و دام آموزشی انتخاب شده‌اند.', 2),
(3, 'تحلیل اشتباهات', 'یاد می‌گیری چرا یک گزینه غلط وسوسه‌کننده است.', 3),
(4, 'مفاهیم پایه شیمی', 'از ماده و اتم تا مولکول‌ها را ساده یاد می‌گیری.', 1),
(4, 'حل تمرین مرحله‌ای', 'تمرین‌ها از ساده به متوسط چیده شده‌اند.', 2),
(4, 'آمادگی امتحان مدرسه', 'با نمونه سوال‌های استاندارد آماده می‌شوی.', 3),
(5, 'مفهوم مول', 'مول و تعداد ذره‌ها را کاربردی یاد می‌گیری.', 1),
(5, 'نسبت‌های واکنش', 'از معادله واکنش به جواب مسئله می‌رسی.', 2),
(5, 'حل تست زمان‌دار', 'سرعت و دقتت در مسئله‌های عددی بیشتر می‌شود.', 3),
(6, 'آنتالپی و انرژی', 'ارتباط انرژی و واکنش‌ها را می‌فهمی.', 1),
(6, 'تعادل شیمیایی', 'جابجایی تعادل و عوامل مؤثر را یاد می‌گیری.', 2),
(6, 'تمرین مفهومی', 'با سوال‌های مفهومی و ترکیبی تمرین می‌کنی.', 3);

DELETE FROM `course_features` WHERE `course_id` IN (1,2,3,4,5,6);
INSERT INTO `course_features` (`course_id`, `text`, `sort_order`) VALUES
(1, 'دسترسی بلندمدت به ویدیوها', 1), (1, '۲۴ جلسه آموزشی منظم', 2), (1, '۱۲ آزمون اختصاصی با پاسخنامه', 3), (1, 'پشتیبانی آموزشی مدرس', 4),
(2, 'دسترسی بلندمدت به ویدیوها', 1), (2, '۳۰ جلسه آموزشی', 2), (2, '۱۴ آزمون اختصاصی', 3), (2, 'برنامه پیشنهادی مطالعه', 4),
(3, 'دسترسی تا پایان کنکور', 1), (3, '۲۸ جلسه جمع‌بندی', 2), (3, '۱۸ آزمون زمان‌دار', 3), (3, 'تحلیل تست و پاسخنامه', 4),
(4, 'دسترسی بلندمدت', 1), (4, '۲۰ جلسه آموزشی', 2), (4, '۸ آزمون کوتاه', 3),
(5, '۱۴ جلسه محاسباتی', 1), (5, '۷ آزمون', 2), (5, 'حل تمرین فراوان', 3),
(6, '۱۸ جلسه آموزشی', 1), (6, '۸ آزمون', 2), (6, 'پاسخنامه تشریحی', 3);

DELETE FROM `featured_courses`;
INSERT INTO `featured_courses` (`course_id`, `sort_order`) VALUES
(1, 1), (2, 2), (3, 3);

-- چند خرید پرداخت‌شده نمونه؛ تعداد خریداران از همین جدول و شرط paid_amount > 0 محاسبه می‌شود.
INSERT INTO `enrollments` (`id`, `course_id`, `student_name`, `student_contact`, `paid_amount`) VALUES
(1, 1, 'دانش‌آموز نمونه ۱', 'sample1@example.com', 1290000),
(2, 1, 'دانش‌آموز نمونه ۲', 'sample2@example.com', 1290000),
(3, 1, 'دانش‌آموز نمونه ۳', 'sample3@example.com', 1290000),
(4, 2, 'دانش‌آموز نمونه ۴', 'sample4@example.com', 1590000),
(5, 2, 'دانش‌آموز نمونه ۵', 'sample5@example.com', 1590000),
(6, 3, 'دانش‌آموز نمونه ۶', 'sample6@example.com', 2490000),
(7, 3, 'دانش‌آموز نمونه ۷', 'sample7@example.com', 2490000),
(8, 3, 'دانش‌آموز نمونه ۸', 'sample8@example.com', 2490000),
(9, 4, 'دانش‌آموز نمونه ۹', 'sample9@example.com', 990000),
(10, 5, 'دانش‌آموز نمونه ۱۰', 'sample10@example.com', 790000),
(11, 6, 'دانش‌آموز نمونه ۱۱', 'sample11@example.com', 990000)
ON DUPLICATE KEY UPDATE `course_id` = VALUES(`course_id`), `student_name` = VALUES(`student_name`), `student_contact` = VALUES(`student_contact`), `paid_amount` = VALUES(`paid_amount`);

INSERT INTO `advisor_threads` (`id`, `student_name`, `student_contact`, `grade`, `status`) VALUES
(1, 'علی رضایی', '09123456789', 'یازدهم', 'open')
ON DUPLICATE KEY UPDATE `student_name` = VALUES(`student_name`), `student_contact` = VALUES(`student_contact`), `grade` = VALUES(`grade`), `status` = VALUES(`status`);

INSERT INTO `advisor_messages` (`id`, `thread_id`, `sender`, `body`) VALUES
(1, 1, 'student', 'سلام، برای شروع پیوند شیمیایی از کدام جلسه شروع کنم؟'),
(2, 1, 'advisor', 'سلام، اول جلسه رایگان پیوند شیمیایی را ببین و بعد آزمون کوتاه همان مبحث را بزن.')
ON DUPLICATE KEY UPDATE `thread_id` = VALUES(`thread_id`), `sender` = VALUES(`sender`), `body` = VALUES(`body`);

ALTER TABLE `admins` AUTO_INCREMENT = 2;
ALTER TABLE `teachers` AUTO_INCREMENT = 4;
ALTER TABLE `videos` AUTO_INCREMENT = 4;
ALTER TABLE `courses` AUTO_INCREMENT = 7;
ALTER TABLE `course_learnings` AUTO_INCREMENT = 100;
ALTER TABLE `course_features` AUTO_INCREMENT = 100;
ALTER TABLE `featured_courses` AUTO_INCREMENT = 4;
ALTER TABLE `enrollments` AUTO_INCREMENT = 12;
ALTER TABLE `advisor_threads` AUTO_INCREMENT = 2;
ALTER TABLE `advisor_messages` AUTO_INCREMENT = 3;

SET FOREIGN_KEY_CHECKS = 1;
