<?php
session_start();
require_once __DIR__ . '/app/helpers.php';
$pdo = db();
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}
if (empty($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

function redirect_admin(string $tab, string $msg = ''): void
{
    header('Location: admin.php?tab=' . urlencode($tab) . ($msg ? '&msg=' . urlencode($msg) : ''));
    exit;
}

function parse_lines(string $text, bool $withDescription = false): array
{
    $rows = [];
    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if ($withDescription) {
            [$title, $desc] = array_pad(explode('/', $line, 2), 2, '');
            $rows[] = [trim($title), trim($desc)];
        } else {
            $rows[] = $line;
        }
    }
    return $rows;
}

function unique_course_slug(string $title, int $ignoreId = 0): string
{
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    $pdo = db();
    while (true) {
        if ($ignoreId > 0) {
            $stmt = $pdo->prepare('SELECT id FROM courses WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function save_course_items(PDO $pdo, int $courseId, string $learningsText, string $featuresText): void
{
    $pdo->prepare('DELETE FROM course_learnings WHERE course_id=?')->execute([$courseId]);
    $pdo->prepare('DELETE FROM course_features WHERE course_id=?')->execute([$courseId]);
    $learnStmt = $pdo->prepare('INSERT INTO course_learnings (course_id, title, description, sort_order) VALUES (?, ?, ?, ?)');
    foreach (parse_lines($learningsText, true) as $i => [$title, $desc]) {
        if ($title !== '') $learnStmt->execute([$courseId, $title, $desc, $i + 1]);
    }
    $featureStmt = $pdo->prepare('INSERT INTO course_features (course_id, text, sort_order) VALUES (?, ?, ?)');
    foreach (parse_lines($featuresText) as $i => $text) {
        $featureStmt->execute([$courseId, $text, $i + 1]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_featured') {
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM featured_courses');
        $stmt = $pdo->prepare('INSERT INTO featured_courses (course_id, sort_order) VALUES (?, ?)');
        foreach (array_values($_POST['featured'] ?? []) as $i => $courseId) {
            if ((int)$courseId > 0) $stmt->execute([(int)$courseId, $i + 1]);
        }
        $pdo->commit();
        redirect_admin('dashboard', 'دوره‌های صفحه اصلی ذخیره شدند.');
    }

    if ($action === 'save_course') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = unique_course_slug($title, $id);
        $image = $_POST['existing_image'] ?? '';
        $uploaded = upload_image('image', 'uploads/courses');
        if ($uploaded) $image = $uploaded;
        $data = [
            $title, $slug, trim($_POST['badge'] ?? ''), trim($_POST['grade'] ?? ''), '', (int)($_POST['hours'] ?? 0),
            trim($_POST['short_desc'] ?? ''), trim($_POST['full_desc'] ?? ''), (int)($_POST['price'] ?? 0), (int)($_POST['sessions_count'] ?? 0), (int)($_POST['exams_count'] ?? 0),
            $image, ($_POST['teacher_id'] ?? '') !== '' ? (int)$_POST['teacher_id'] : null, ($_POST['sample_video_id'] ?? '') !== '' ? (int)$_POST['sample_video_id'] : null, ($_POST['free_video_id'] ?? '') !== '' ? (int)$_POST['free_video_id'] : null, isset($_POST['is_published']) ? 1 : 0
        ];
        if ($id) {
            $sql = 'UPDATE courses SET title=?, slug=?, badge=?, grade=?, level=?, hours=?, short_desc=?, full_desc=?, price=?, sessions_count=?, exams_count=?, image=?, teacher_id=?, sample_video_id=?, free_video_id=?, is_published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?';
            $pdo->prepare($sql)->execute([...$data, $id]);
            $courseId = $id;
        } else {
            $sql = 'INSERT INTO courses (title, slug, badge, grade, level, hours, short_desc, full_desc, price, sessions_count, exams_count, image, teacher_id, sample_video_id, free_video_id, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $pdo->prepare($sql)->execute($data);
            $courseId = (int)$pdo->lastInsertId();
        }
        save_course_items($pdo, $courseId, $_POST['learnings'] ?? '', $_POST['features'] ?? '');
        redirect_admin('courses', 'دوره ذخیره شد.');
    }

    if ($action === 'delete_course') {
        $pdo->prepare('DELETE FROM courses WHERE id=?')->execute([(int)$_POST['id']]);
        redirect_admin('courses', 'دوره حذف شد.');
    }

    if ($action === 'record_enrollment') {
        $pdo->prepare('INSERT INTO enrollments (course_id, student_name, student_contact, paid_amount) VALUES (?, ?, ?, ?)')->execute([(int)$_POST['course_id'], trim($_POST['student_name'] ?? 'خریدار'), trim($_POST['student_contact'] ?? ''), (int)($_POST['paid_amount'] ?? 0)]);
        redirect_admin('courses', 'خرید پرداخت‌شده ثبت شد و تعداد کاربران به‌روزرسانی شد.');
    }

    if ($action === 'save_teacher') {
        $id = (int)($_POST['id'] ?? 0);
        $photo = $_POST['existing_photo'] ?? '';
        $uploaded = upload_image('photo', 'uploads/teachers');
        if ($uploaded) $photo = $uploaded;
        $data = [trim($_POST['name'] ?? ''), trim($_POST['role'] ?? ''), trim($_POST['bio'] ?? ''), $photo];
        if ($id) {
            $pdo->prepare('UPDATE teachers SET name=?, role=?, bio=?, photo=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$data, $id]);
        } else {
            $pdo->prepare('INSERT INTO teachers (name, role, bio, photo) VALUES (?, ?, ?, ?)')->execute($data);
        }
        redirect_admin('teachers', 'مدرس ذخیره شد.');
    }

    if ($action === 'delete_teacher') {
        $pdo->prepare('DELETE FROM teachers WHERE id=?')->execute([(int)$_POST['id']]);
        redirect_admin('teachers', 'مدرس حذف شد.');
    }

    if ($action === 'save_video') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [trim($_POST['title'] ?? ''), trim($_POST['video_url'] ?? ''), trim($_POST['description'] ?? ''), trim($_POST['duration'] ?? '')];
        if ($id) {
            $pdo->prepare('UPDATE videos SET title=?, video_url=?, description=?, duration=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$data, $id]);
        } else {
            $pdo->prepare('INSERT INTO videos (title, video_url, description, duration) VALUES (?, ?, ?, ?)')->execute($data);
        }
        redirect_admin('videos', 'ویدیو ذخیره شد.');
    }

    if ($action === 'delete_video') {
        $pdo->prepare('DELETE FROM videos WHERE id=?')->execute([(int)$_POST['id']]);
        redirect_admin('videos', 'ویدیو حذف شد.');
    }

    if ($action === 'advisor_reply') {
        $threadId = (int)$_POST['thread_id'];
        $body = trim($_POST['body'] ?? '');
        if ($body !== '') {
            $pdo->prepare('INSERT INTO advisor_messages (thread_id, sender, body) VALUES (?, "advisor", ?)')->execute([$threadId, $body]);
            $pdo->prepare('UPDATE advisor_threads SET status="open", updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$threadId]);
        }
        redirect_admin('advisor', 'پاسخ مشاور ارسال شد.');
    }

    if ($action === 'close_thread') {
        $pdo->prepare('UPDATE advisor_threads SET status="closed", updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int)$_POST['thread_id']]);
        redirect_admin('advisor', 'گفتگو بسته شد.');
    }
}

$tab = $_GET['tab'] ?? 'dashboard';
$courses = $pdo->query('SELECT c.*, t.name AS teacher_name FROM courses c LEFT JOIN teachers t ON t.id=c.teacher_id ORDER BY c.id DESC')->fetchAll();
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY id DESC')->fetchAll();
$videos = $pdo->query('SELECT * FROM videos ORDER BY id DESC')->fetchAll();
$msg = $_GET['msg'] ?? '';
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>پنل مدیریت | دوپینگ شیمی</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/backend.css"></head><body class="admin-body">
<header class="admin-top">
  <div class="admin-top-title"><strong>پنل مدیریت</strong></div>
  <button class="admin-menu-toggle" type="button" aria-label="باز کردن منوی مدیریت" aria-expanded="false" aria-controls="adminSidebar">
    <span></span><span></span><span></span>
  </button>
</header>
<main class="admin-shell"><aside class="admin-sidebar" id="adminSidebar"><a class="<?= $tab==='dashboard'?'active':'' ?>" href="admin.php?tab=dashboard">صفحه اصلی</a><a class="<?= $tab==='courses' || $tab==='course_edit'?'active':'' ?>" href="admin.php?tab=courses">دوره‌ها</a><a class="<?= $tab==='teachers'?'active':'' ?>" href="admin.php?tab=teachers">مدرسین</a><a class="<?= $tab==='videos'?'active':'' ?>" href="admin.php?tab=videos">ویدیوها</a><a class="<?= $tab==='advisor'?'active':'' ?>" href="admin.php?tab=advisor">مشاور</a><a class="admin-logout-link" href="admin.php?logout=1">خروج</a></aside><section class="admin-content"><?php if ($msg): ?><div class="success-box"><?= e($msg) ?></div><?php endif; ?>
<?php if ($tab === 'dashboard'): ?>
  <h1>دوره‌های منتخب صفحه اصلی</h1><p class="admin-muted">سه دوره‌ای که در صفحه اصلی نمایش داده می‌شوند را انتخاب کنید.</p>
  <?php $featured = $pdo->query('SELECT course_id FROM featured_courses ORDER BY sort_order')->fetchAll(PDO::FETCH_COLUMN); ?>
  <form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="save_featured">
    <?php for ($i=0; $i<3; $i++): ?><label><span>جایگاه <?= fa_num($i+1) ?></span><select name="featured[]"><?php foreach ($courses as $course): ?><option value="<?= (int)$course['id'] ?>" <?= (($featured[$i] ?? null)==$course['id'])?'selected':'' ?>><?= e($course['title']) ?></option><?php endforeach; ?></select></label><?php endfor; ?>
    <button class="btn btn-primary">ذخیره انتخاب‌ها</button>
  </form>
<?php elseif ($tab === 'courses'): ?>
  <div class="admin-heading"><div><h1>مدیریت دوره‌ها</h1><p>اضافه، حذف و ویرایش کامل دوره‌ها.</p></div><a class="btn btn-primary" href="admin.php?tab=course_edit">دوره جدید</a></div>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>دوره</th><th>پایه</th><th>مدرس</th><th>ساعت</th><th>جلسه</th><th>خریداران</th><th>قیمت</th><th>عملیات</th></tr></thead><tbody><?php foreach ($courses as $course): ?><tr class="course-row"><td><?= e($course['title']) ?></td><td><?= e($course['grade']) ?></td><td><?= e($course['teacher_name']) ?></td><td><?= fa_num($course['hours']) ?></td><td><?= fa_num($course['sessions_count']) ?></td><td><?= fa_num(course_students_count((int)$course['id'])) ?></td><td><?= toman($course['price']) ?></td><td class="admin-actions"><a href="admin.php?tab=course_edit&id=<?= (int)$course['id'] ?>">ویرایش</a><form method="post" onsubmit="return confirm('حذف شود؟')"><input type="hidden" name="action" value="delete_course"><input type="hidden" name="id" value="<?= (int)$course['id'] ?>"><button>حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div>
<?php elseif ($tab === 'course_edit'):
  $id = (int)($_GET['id'] ?? 0); $course = $id ? course_by_id($id) : null;
  $learningsText = $course ? implode("\n", array_map(fn($l)=>$l['title'].' / '.$l['description'], course_learnings($id))) : '';
  $featuresText = $course ? implode("\n", array_map(fn($f)=>$f['text'], course_features($id))) : "دسترسی بلندمدت به ویدیوها\nجلسه آموزشی منظم\nآزمون اختصاصی با پاسخنامه";
?>
  <h1><?= $course ? 'ویرایش دوره' : 'دوره جدید' ?></h1>
  <form method="post" enctype="multipart/form-data" class="admin-card admin-form wide"><input type="hidden" name="action" value="save_course"><input type="hidden" name="id" value="<?= (int)($course['id'] ?? 0) ?>"><input type="hidden" name="existing_image" value="<?= e($course['image'] ?? '') ?>">
    <div class="admin-grid-2"><label><span>عنوان</span><input name="title" required value="<?= e($course['title'] ?? '') ?>"></label><label><span>بج</span><input name="badge" value="<?= e($course['badge'] ?? '') ?>"></label><label><span>پایه</span><input name="grade" value="<?= e($course['grade'] ?? '') ?>"></label><label><span>ساعت</span><input type="number" name="hours" value="<?= e($course['hours'] ?? 0) ?>"></label><label><span>قیمت تومان</span><input type="number" name="price" value="<?= e($course['price'] ?? 0) ?>"></label><label><span>تعداد جلسات</span><input type="number" name="sessions_count" value="<?= e($course['sessions_count'] ?? 0) ?>"></label><label><span>تعداد آزمون‌ها</span><input type="number" name="exams_count" value="<?= e($course['exams_count'] ?? 0) ?>"></label><label><span>تصویر دوره</span><input type="file" name="image" accept="image/*"></label></div>
    <label><span>توضیح کوتاه</span><textarea name="short_desc" rows="3"><?= e($course['short_desc'] ?? '') ?></textarea></label><label><span>توضیح کامل</span><textarea name="full_desc" rows="7"><?= e($course['full_desc'] ?? '') ?></textarea></label>
    <div class="admin-grid-3"><label><span>مدرس</span><select name="teacher_id"><option value="">بدون مدرس</option><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>" <?= (($course['teacher_id'] ?? '')==$t['id'])?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></label><label><span>نمونه ویدیو</span><select name="sample_video_id"><option value="">انتخاب نشده</option><?php foreach ($videos as $v): ?><option value="<?= (int)$v['id'] ?>" <?= (($course['sample_video_id'] ?? '')==$v['id'])?'selected':'' ?>><?= e($v['title']) ?></option><?php endforeach; ?></select></label><label><span>ویدیوی جلسه رایگان</span><select name="free_video_id"><option value="">انتخاب نشده</option><?php foreach ($videos as $v): ?><option value="<?= (int)$v['id'] ?>" <?= (($course['free_video_id'] ?? '')==$v['id'])?'selected':'' ?>><?= e($v['title']) ?></option><?php endforeach; ?></select></label></div>
    <label><span>چیزهایی که در دوره یاد می‌گیری - هر خط: عنوان / توضیح</span><textarea name="learnings" rows="6"><?= e($learningsText) ?></textarea></label><label><span>تیک‌های پایین کارت ثبت‌نام - هر خط یک مورد</span><textarea name="features" rows="5"><?= e($featuresText) ?></textarea></label><label class="admin-check"><input type="checkbox" name="is_published" <?= !isset($course) || (int)($course['is_published'] ?? 1) ? 'checked' : '' ?>> منتشر باشد</label><button class="btn btn-primary">ذخیره دوره</button>
  </form>
<?php elseif ($tab === 'teachers'): ?>
  <h1>مدرسین</h1><div class="admin-grid-2"><form method="post" enctype="multipart/form-data" class="admin-card admin-form"><input type="hidden" name="action" value="save_teacher"><h2>مدرس جدید</h2><label><span>نام</span><input name="name" required></label><label><span>عنوان/نقش مدرس</span><input name="role" required></label><label><span>توضیح کامل</span><textarea name="bio"></textarea></label><label><span>عکس اختیاری</span><input type="file" name="photo" accept="image/*"></label><button class="btn btn-primary">ذخیره مدرس</button></form><div class="admin-list"><?php foreach ($teachers as $t): ?><div class="admin-card mini"><h3><?= e($t['name']) ?></h3><p><?= e($t['role']) ?></p><form method="post" onsubmit="return confirm('حذف مدرس؟')"><input type="hidden" name="action" value="delete_teacher"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button>حذف</button></form></div><?php endforeach; ?></div></div>
<?php elseif ($tab === 'videos'): ?>
  <h1>دیتابیس ویدیوها</h1><div class="admin-grid-2"><form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="save_video"><label><span>عنوان ویدیو</span><input name="title" required></label><label><span>آدرس فایل/لینک ویدیو</span><input name="video_url" placeholder="uploads/videos/file.mp4 یا لینک" required></label><label><span>مدت</span><input name="duration" placeholder="۱۲ دقیقه"></label><label><span>توضیح</span><textarea name="description"></textarea></label><button class="btn btn-primary">ذخیره ویدیو</button></form><div class="admin-list"><?php foreach ($videos as $v): ?><div class="admin-card mini"><h3><?= e($v['title']) ?></h3><p><?= e($v['video_url']) ?></p><form method="post" onsubmit="return confirm('حذف ویدیو؟')"><input type="hidden" name="action" value="delete_video"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><button>حذف</button></form></div><?php endforeach; ?></div></div>
<?php elseif ($tab === 'advisor'): ?>
  <h1>پیام‌های مشاور</h1><?php $threads=$pdo->query('SELECT * FROM advisor_threads ORDER BY updated_at DESC, id DESC')->fetchAll(); foreach ($threads as $thread): ?><div class="admin-card chat-admin"><h2><?= e($thread['student_name']) ?> <small><?= e($thread['student_contact']) ?> - <?= e($thread['grade']) ?></small></h2><?php $m=$pdo->prepare('SELECT * FROM advisor_messages WHERE thread_id=? ORDER BY id'); $m->execute([$thread['id']]); foreach ($m->fetchAll() as $msg): ?><div class="chat-bubble <?= $msg['sender']==='advisor'?'advisor':'student' ?>"><strong><?= $msg['sender']==='advisor'?'مشاور':'دانش‌آموز' ?>:</strong> <?= nl2br(e($msg['body'])) ?></div><?php endforeach; ?><form method="post" class="admin-form reply-form"><input type="hidden" name="action" value="advisor_reply"><input type="hidden" name="thread_id" value="<?= (int)$thread['id'] ?>"><textarea name="body" rows="3" placeholder="پاسخ مشاور..."></textarea><button class="btn btn-primary">ارسال پاسخ</button></form><form method="post"><input type="hidden" name="action" value="close_thread"><input type="hidden" name="thread_id" value="<?= (int)$thread['id'] ?>"><button>بستن گفتگو</button></form></div><?php endforeach; if (!$threads): ?><div class="empty-state">هنوز پیامی ارسال نشده است.</div><?php endif; ?>
<?php endif; ?></section></main><div class="admin-menu-backdrop" data-admin-menu-close></div><script src="js/admin.js"></script></body></html>
