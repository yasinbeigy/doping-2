<?php
require_once __DIR__ . '/app/layout.php';
$courses = featured_courses();
page_start('دوپینگ شیمی | آموزش شیمی از مفهوم تا تست', 'home');
?>
<section class="hero">
  <div class="hero-decoration"></div><div class="hero-decoration-2"></div>
  <div class="container hero-inner">
    <div class="hero-content">
      <h1>شیمی رو طوری یاد بگیر که سر جلسه خودت راه‌حل رو بسازی</h1>
      <p>اینجا قرار نیست فقط چند فرمول حفظ کنی و بروی سراغ تست بعدی. از مفهوم شروع می‌کنیم، با مثال جلو می‌رویم و آخر هر بخش با آزمون‌های کوتاه می‌فهمی دقیقاً کجای مسیر ایستاده‌ای.</p>
      <div class="hero-actions"><a href="courses.php" class="btn btn-primary btn-lg">دیدن دوره‌ها</a><a href="student-panel.php" class="btn btn-outline btn-lg">پیام به مشاور</a></div>
      <div class="hero-stats"><div class="hero-stat"><strong><?= fa_num(array_sum(array_map(fn($c)=>course_students_count((int)$c['id']), published_courses()))) ?>+</strong><span>دانش‌آموز همراه</span></div><div class="hero-stat"><strong><?= fa_num(array_sum(array_column(published_courses(), 'hours'))) ?>+</strong><span>ساعت آموزش</span></div><div class="hero-stat"><strong>۴.۹</strong><span>میانگین رضایت</span></div></div>
    </div>
    <div class="hero-visual"><img src="images/hero.png" alt="تصویر دست‌کشیده ابزارهای آزمایشگاه شیمی" class="hero-image"></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>چرا بچه‌ها با دوپینگ شیمی راحت‌تر جلو می‌روند؟</h2>
      <p>چون مسیر یادگیری فقط دیدن ویدیو نیست؛ باید بفهمی، تمرین کنی، اشتباه کنی و سریع بازخورد بگیری.</p>
    </div>
    <div class="features-grid">
      <article class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><use href="images/icons.svg#graduation"></use></svg></div>
        <h3>مفهوم قبل از فرمول</h3>
        <p>هر فصل را از دلیل و ریشه‌اش شروع می‌کنیم تا فرمول‌ها برایت معنی داشته باشند.</p>
      </article>
      <article class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><use href="images/icons.svg#clipboard"></use></svg></div>
        <h3>تمرین‌های مرحله‌ای</h3>
        <p>سوال‌ها از ساده به جدی چیده شده‌اند تا ذهنت آرام‌آرام مسلط شود.</p>
      </article>
      <article class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><use href="images/icons.svg#chart"></use></svg></div>
        <h3>کارنامه تحلیلی</h3>
        <p>بعد از آزمون فقط نمره نمی‌بینی؛ دقیق می‌فهمی کدام مبحث تمرین می‌خواهد.</p>
      </article>
      <article class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><use href="images/icons.svg#teacher"></use></svg></div>
        <h3>مشاوره و پیگیری</h3>
        <p>از پنل دانش‌آموز می‌توانی به مشاور پیام بدهی و جواب بگیری.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header"><h2>دوره‌های منتخب صفحه اصلی</h2><p>این سه دوره از بخش مدیریت قابل انتخاب و تغییر هستند.</p></div>
    <div class="courses-grid"><?php foreach ($courses as $course) render_course_card($course); ?></div>
    <div class="text-center mt-4"><a href="courses.php" class="btn btn-primary btn-lg">مشاهده همه دوره‌ها</a></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="split-cta">
      <div class="split-cta-content"><span class="eyebrow">آزمون آنلاین اختصاصی</span><h2>قبل از اینکه دوباره بخوانی، ببین دقیقاً کجا مشکل داری</h2><p>آزمون‌های کوتاه و جامع کمک می‌کنند وقتت را روی همان بخش‌هایی بگذاری که واقعاً نیاز به تمرین دارند.</p><a href="exam.html" class="btn btn-primary btn-lg">شرکت در آزمون رایگان</a></div>
      <div class="split-cta-image"><img src="images/online-exam.png" alt="تصویر آزمون آنلاین"></div>
    </div>
  </div>
</section>

<section class="section section-alt home-testimonials">
  <div class="container"><div class="section-header"><h2>نظر دانش‌آموزها</h2><p>چند تجربه کوتاه از بچه‌هایی که با دوپینگ شیمی منظم‌تر درس خواندند.</p></div>
    <div class="testimonials-grid"><article class="testimonial-card"><p class="quote">«برای اولین بار حس کردم شیمی فقط حفظ کردن نیست.»</p><div class="testimonial-author"><div class="avatar">س</div><div><h5>سارا احمدی</h5><span>پایه دوازدهم</span></div></div></article><article class="testimonial-card"><p class="quote">«آزمون‌های کوتاه خیلی کمکم کرد بفهمم کجا ضعف دارم.»</p><div class="testimonial-author"><div class="avatar">ع</div><div><h5>علی رضایی</h5><span>پایه یازدهم</span></div></div></article><article class="testimonial-card"><p class="quote">«توضیح‌ها خشک و کتابی نبود؛ محاسبات برایم ساده‌تر شد.»</p><div class="testimonial-author"><div class="avatar">م</div><div><h5>مریم کریمی</h5><span>دانش‌آموز کنکوری</span></div></div></article></div>
  </div>
</section>
<?php page_end(); ?>
