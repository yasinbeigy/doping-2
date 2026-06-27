<?php
require_once __DIR__ . '/app/layout.php';
$search = trim($_GET['q'] ?? '');
$grade = $_GET['grade'] ?? 'all';
$courses = published_courses($search ?: null, $grade ?: 'all');
page_start('دوره‌ها | دوپینگ شیمی', 'courses');
?>
<section class="page-banner"><div class="container"><h1>دوره‌های آموزشی شیمی</h1><p>دوره‌ها از پنل مدیریت قابل اضافه، حذف و ویرایش هستند.</p></div></section>
<section class="section"><div class="container">
  <form class="filters-bar filters-modern backend-public-filter" method="get">
    <div class="filters-intro"><span>جستجوی دوره</span><small>عنوان، پایه یا توضیح را جستجو کن</small></div>
    <label class="search-field search-field-modern"><input type="text" class="search-input" name="q" value="<?= e($search) ?>" placeholder="جستجو در دوره‌ها..."></label>
    <select class="filter-select" name="grade">
      <?php foreach (['all'=>'همه پایه‌ها','دهم'=>'پایه دهم','یازدهم'=>'پایه یازدهم','دوازدهم'=>'پایه دوازدهم','کنکور'=>'کنکور'] as $value=>$label): ?>
        <option value="<?= e($value) ?>" <?= $grade===$value?'selected':'' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
  </form>
  <div class="courses-grid"><?php foreach ($courses as $course) render_course_card($course); ?></div>
  <?php if (!$courses): ?><div class="empty-state">دوره‌ای با این فیلتر پیدا نشد.</div><?php endif; ?>
</div></section>
<?php page_end(); ?>
