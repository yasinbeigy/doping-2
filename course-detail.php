<?php
require_once __DIR__ . '/app/layout.php';
$id = (int)($_GET['id'] ?? 0);
$course = course_by_id($id);
if (!$course || !(int)$course['is_published']) {
    http_response_code(404);
    page_start('دوره پیدا نشد | دوپینگ شیمی', 'courses');
    echo '<section class="section"><div class="container"><div class="empty-state">دوره موردنظر پیدا نشد.</div></div></section>';
    page_end();
    exit;
}
$learnings = course_learnings($id);
$features = course_features($id);
$students = course_students_count($id);
page_start($course['title'] . ' | دوپینگ شیمی', 'courses');
?>
<section class="page-banner"><div class="container"><h1><?= e($course['title']) ?></h1><p><?= e($course['short_desc']) ?></p></div></section>
<section class="section"><div class="container"><div class="course-detail-layout">
  <main class="course-detail-main">
    <div class="course-detail-hero image-hero"><img src="<?= e($course['image']) ?>" alt="<?= e($course['title']) ?>"></div>
    <div class="course-detail-body">
      <h2 class="course-detail-title"><?= e($course['title']) ?></h2>
      <div class="course-detail-meta"><span>امتیاز ۴.۹</span><span><?= fa_num((int)$course['hours']) ?> ساعت</span><span><?= fa_num((int)$course['sessions_count']) ?> جلسه</span><span><?= fa_num($students) ?> دانش‌آموز</span></div>
      <p class="lead-text"><?= nl2br(e($course['full_desc'])) ?></p>
      <section class="course-detail-section"><h3>در این دوره چه چیزهایی یاد می‌گیری؟</h3><div class="syllabus-list">
        <?php foreach ($learnings as $i => $learning): ?><div class="syllabus-item"><div class="syllabus-number"><?= fa_num($i+1) ?></div><div><strong><?= e($learning['title']) ?></strong><p><?= e($learning['description']) ?></p></div></div><?php endforeach; ?>
      </div></section>
      <section class="course-detail-section"><h3>نمونه ویدیو</h3><?= render_video($course['sample_video_url'], $course['sample_video_title']) ?></section>
      <section class="course-detail-section"><h3>جلسه رایگان</h3><?= render_video($course['free_video_url'], $course['free_video_title']) ?></section>
    </div>
  </main>
  <aside class="course-detail-sidebar">
    <div class="enrollment-card"><div class="enrollment-price"><?= toman((int)$course['price']) ?></div><button class="btn btn-primary btn-lg full-width">ثبت‌نام در دوره</button><a href="#" class="btn btn-outline full-width">دیدن جلسه رایگان</a><ul class="enrollment-features"><?php foreach ($features as $feature): ?><li><?= e($feature['text']) ?></li><?php endforeach; ?></ul></div>
    <div class="teacher-info-card"><div class="teacher-avatar"><?= e(first_grapheme($course['teacher_name'] ?? 'م', 'م')) ?></div><h4><?= e($course['teacher_name']) ?></h4><p class="teacher-role-small"><?= e($course['teacher_role']) ?></p><p><?= e($course['teacher_bio']) ?></p></div>
  </aside>
</div></div></section>
<?php page_end(); ?>
