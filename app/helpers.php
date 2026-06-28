<?php
require_once __DIR__ . '/db.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fa_num($value): string
{
    return strtr((string)$value, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
}

function toman($value): string
{
    return fa_num(number_format((int)$value)) . ' تومان';
}

function course_students_count(int $courseId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND paid_amount > 0');
    $stmt->execute([$courseId]);
    return (int)$stmt->fetchColumn();
}

function course_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT c.*, t.name AS teacher_name, t.role AS teacher_role, t.bio AS teacher_bio, t.photo AS teacher_photo,
      sv.title AS sample_video_title, sv.video_url AS sample_video_url, sv.description AS sample_video_description,
      fv.title AS free_video_title, fv.video_url AS free_video_url
      FROM courses c
      LEFT JOIN teachers t ON t.id = c.teacher_id
      LEFT JOIN videos sv ON sv.id = c.sample_video_id
      LEFT JOIN videos fv ON fv.id = c.free_video_id
      WHERE c.id = ?');
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    return $course ?: null;
}

function course_learnings(int $courseId): array
{
    $stmt = db()->prepare('SELECT * FROM course_learnings WHERE course_id = ? ORDER BY sort_order, id');
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

function course_features(int $courseId): array
{
    $stmt = db()->prepare('SELECT * FROM course_features WHERE course_id = ? ORDER BY sort_order, id');
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

function published_courses(?string $search = null, ?string $grade = null): array
{
    $where = ['c.is_published = 1'];
    $params = [];
    if ($search) {
        $where[] = '(c.title LIKE ? OR c.short_desc LIKE ? OR c.full_desc LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    if ($grade && $grade !== 'all') {
        $where[] = 'c.grade = ?';
        $params[] = $grade;
    }
    $sql = 'SELECT c.*, t.name AS teacher_name FROM courses c LEFT JOIN teachers t ON t.id = c.teacher_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.created_at DESC, c.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function featured_courses(): array
{
    $sql = 'SELECT c.*, t.name AS teacher_name FROM featured_courses f JOIN courses c ON c.id = f.course_id LEFT JOIN teachers t ON t.id = c.teacher_id WHERE c.is_published = 1 ORDER BY f.sort_order, f.id LIMIT 3';
    $courses = db()->query($sql)->fetchAll();
    if (count($courses) < 3) {
        $fallback = db()->query('SELECT c.*, t.name AS teacher_name FROM courses c LEFT JOIN teachers t ON t.id = c.teacher_id WHERE c.is_published = 1 ORDER BY c.id DESC LIMIT 3')->fetchAll();
        return $fallback;
    }
    return $courses;
}

function upload_image(string $field, string $targetDir): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $tmp = $_FILES[$field]['tmp_name'];
    $mime = mime_content_type($tmp) ?: '';
    if (!isset($allowed[$mime])) {
        return null;
    }
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }
    $name = uniqid('img_', true) . '.' . $allowed[$mime];
    $path = rtrim($targetDir, '/') . '/' . $name;
    if (move_uploaded_file($tmp, $path)) {
        return $path;
    }
    return null;
}

function slugify(string $text): string
{
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', trim($text));
    $slug = trim((string)$slug, '-');
    return $slug ?: 'course-' . time();
}

function render_video(?string $url, ?string $title = null): string
{
    if (!$url) {
        return '<div class="chart-placeholder">ویدیویی انتخاب نشده است.</div>';
    }
    $safeUrl = e($url);
    $safeTitle = e($title ?: 'ویدیو');
    if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
        return '<video class="video-player" controls preload="metadata"><source src="' . $safeUrl . '">مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.</video>';
    }
    return '<a class="video-link-box" href="' . $safeUrl . '" target="_blank" rel="noopener">مشاهده ' . $safeTitle . '</a>';
}

function first_grapheme(string $text, string $fallback = '؟'): string
{
    if (preg_match('/^./us', trim($text), $m)) {
        return $m[0];
    }
    return $fallback;
}
