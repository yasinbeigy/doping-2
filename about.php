<?php
require_once __DIR__ . '/app/layout.php';
$teachers = db()->query('SELECT * FROM teachers ORDER BY id')->fetchAll();
page_start('درباره ما | دوپینگ شیمی', 'about');
?>
<section class="page-banner"><div class="container"><h1>درباره دوپینگ شیمی</h1><p>مدرس‌ها از پنل مدیریت قابل اضافه و ویرایش هستند.</p></div></section>
<section class="section"><div class="container"><div class="section-header"><h2>تیم آموزشی</h2><p>اطلاعات این بخش مستقیم از بخش مدرسین در پنل مدیریت خوانده می‌شود.</p></div><div class="testimonials-grid team-grid">
<?php foreach ($teachers as $teacher): ?>
  <article class="testimonial-card team-card">
    <?php if (!empty($teacher['photo'])): ?><img class="teacher-admin-photo" src="<?= e($teacher['photo']) ?>" alt="<?= e($teacher['name']) ?>"><?php else: ?><div class="avatar large-avatar"><?= e(first_grapheme($teacher['name'], 'م')) ?></div><?php endif; ?>
    <h5><?= e($teacher['name']) ?></h5><p class="team-role"><?= e($teacher['role']) ?></p><p><?= e($teacher['bio']) ?></p>
  </article>
<?php endforeach; ?>
</div></div></section>
<?php page_end(); ?>
