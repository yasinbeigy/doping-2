<?php
require_once __DIR__ . '/helpers.php';

function site_header(string $active = ''): void
{
    $items = [
        'home' => ['index.php', 'صفحه اصلی'],
        'courses' => ['courses.php', 'دوره‌ها'],
        'about' => ['about.php', 'درباره ما'],
    ];
    ?>
    <header class="site-header">
      <div class="container header-inner header-modern">
        <a href="index.php" class="logo brand-logo" aria-label="دوپینگ شیمی">
          <img src="images/logo.svg" alt="" class="logo-mark">
          <span class="logo-copy">
            <span class="logo-title">دوپینگ شیمی</span>
            <span class="logo-tagline">یادگیری مفهومی، تمرین هدفمند</span>
          </span>
        </a>
        <nav class="main-nav" aria-label="منوی اصلی"><ul>
          <?php foreach ($items as $key => [$href, $label]): ?>
            <li><a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul></nav>
        <div class="header-actions">
          <a href="login.html" class="btn btn-ghost header-login">ورود</a>
          <a href="register.html" class="btn btn-primary header-cta">ثبت‌نام</a>
        </div>
        <button class="mobile-menu-btn" aria-label="باز کردن منو" aria-expanded="false"><span></span><span></span><span></span></button>
      </div>
    </header>
    <?php
}

function site_footer(): void
{
    ?>
    <footer class="site-footer footer-modern">
      <div class="footer-glow footer-glow-one"></div>
      <div class="footer-glow footer-glow-two"></div>
      <div class="container">
        <div class="footer-cta-card">
          <div>
            <span class="footer-kicker">دوپینگ شیمی</span>
            <h2>شیمی را از حالت حفظی بیرون بیاور</h2>
            <p>دوره‌ها طوری چیده شده‌اند که اول مفهوم را بفهمی، بعد با تمرین و آزمون مطمئن شوی مسیر را درست آمده‌ای.</p>
          </div>
          <div class="footer-cta-actions">
            <a href="courses.php" class="btn btn-primary">مشاهده دوره‌ها</a>
            <a href="about.php" class="btn btn-outline footer-outline">آشنایی با تیم</a>
          </div>
        </div>
        <div class="footer-grid footer-modern-grid">
          <div class="footer-brand">
            <a href="index.php" class="logo footer-logo">
              <img src="images/logo.svg" alt="" class="logo-mark">
              <span class="logo-copy"><span class="logo-title">دوپینگ شیمی</span><span class="logo-tagline">برای فهمیدن، نه فقط حفظ کردن</span></span>
            </a>
            <p>یک مسیر ساده و منظم برای دانش‌آموزهایی که می‌خواهند شیمی را با توضیح روشن، مثال کافی و تمرین هدفمند یاد بگیرند.</p>
          </div>
          <div class="footer-column"><h4>مسیر یادگیری</h4><ul><li><a href="courses.php">همه دوره‌ها</a></li><li><a href="student-panel.php">پیام به مشاور</a></li><li><a href="exam.html">آزمون آنلاین</a></li></ul></div>
          <div class="footer-column"><h4>مدیریت</h4><ul><li><a href="admin-login.php">ورود ادمین</a></li><li><a href="about.php">مدرسین</a></li><li><a href="terms.html">قوانین آموزشی</a></li></ul></div>
          <div class="footer-column footer-contact-card"><h4>ارتباط با ما</h4><ul><li><a href="mailto:support@dopingshimi.ir">support@dopingshimi.ir</a></li><li><a href="tel:02112345678">۰۲۱-۱۲۳۴۵۶۷۸</a></li><li><a href="#">اینستاگرام دوپینگ شیمی</a></li></ul></div>
        </div>
        <div class="footer-bottom"><p>تمام حقوق محفوظ است © ۱۴۰۵ دوپینگ شیمی</p><p>طراحی شده برای یادگیری آرام، دقیق و نتیجه‌محور.</p></div>
      </div>
    </footer>
    <?php
}

function page_start(string $title, string $active = ''): void
{
    ?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= e($title) ?></title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/backend.css"></head><body><?php site_header($active);
}

function page_end(): void
{
    site_footer();
    ?><script src="js/main.js"></script><script type="module" src="js/motion.js"></script></body></html><?php
}

function render_course_card(array $course, bool $detailsButton = true): void
{
    $students = course_students_count((int)$course['id']);
    ?>
    <article class="course-card">
      <div class="course-thumb"><img src="<?= e($course['image'] ?: 'images/course-shimi-1.png') ?>" alt="<?= e($course['title']) ?>"><span class="course-badge"><?= e($course['badge'] ?: 'دوره') ?></span></div>
      <div class="course-body">
        <div class="course-meta"><span>ساعت: <?= fa_num((int)$course['hours']) ?></span><span>پایه: <?= e($course['grade']) ?></span><span>دانش‌آموز: <?= fa_num($students) ?></span></div>
        <h3 class="course-title"><?= e($course['title']) ?></h3>
        <p class="course-desc"><?= e($course['short_desc']) ?></p>
        <div class="course-teacher"><div class="teacher-avatar"><?= e(first_grapheme($course['teacher_name'] ?? 'م', 'م')) ?></div><span><?= e($course['teacher_name'] ?? 'مدرس دوره') ?></span></div>
        <div class="course-footer"><span class="course-price"><?= toman((int)$course['price']) ?></span><span class="course-rating">امتیاز ۴.۹</span></div>
        <?php if ($detailsButton): ?><a href="course-detail.php?id=<?= (int)$course['id'] ?>" class="btn btn-primary btn-sm course-link">مشاهده جزئیات</a><?php endif; ?>
      </div>
    </article>
    <?php
}
