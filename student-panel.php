<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/app/layout.php';
$pdo = db();

// ===== Student Auth =====
$student = null;
if (!empty($_SESSION['student_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch();
}

// ===== Handle Auth Actions =====
$auth_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_action'])) {
    if ($_POST['auth_action'] === 'login') {
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM students WHERE phone = ?');
        $stmt->execute([$phone]);
        $found = $stmt->fetch();
        if ($found && password_verify($password, $found['password_hash'])) {
            $_SESSION['student_id'] = (int)$found['id'];
            $_SESSION['student_name'] = $found['full_name'];
            header('Location: student-panel.php'); exit;
        } else $auth_error = 'شماره موبایل یا رمز عبور اشتباه است.';
    }
    if ($_POST['auth_action'] === 'register') {
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $grade = trim($_POST['grade'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($name && $phone && $password) {
            $check = $pdo->prepare('SELECT id FROM students WHERE phone = ?');
            $check->execute([$phone]);
            if ($check->fetch()) $auth_error = 'این شماره موبایل قبلاً ثبت‌نام کرده است.';
            else {
                $stmt = $pdo->prepare('INSERT INTO students (full_name, phone, grade, password_hash) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $phone, $grade, password_hash($password, PASSWORD_DEFAULT)]);
                $_SESSION['student_id'] = (int)$pdo->lastInsertId();
                $_SESSION['student_name'] = $name;
                header('Location: student-panel.php'); exit;
            }
        } else $auth_error = 'لطفاً تمام فیلدهای اجباری را پر کنید.';
    }
}

// ===== Handle Panel Actions =====
$notice = '';
if ($student && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panel_action'])) {
    if ($_POST['panel_action'] === 'send_message') {
        $body = trim($_POST['message'] ?? '');
        if ($body) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id FROM advisor_threads WHERE student_contact = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$student['phone']]);
            $threadId = (int)$stmt->fetchColumn();
            if (!$threadId) {
                $stmt = $pdo->prepare('INSERT INTO advisor_threads (student_name, student_contact, grade, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
                $stmt->execute([$student['full_name'], $student['phone'], $student['grade']]);
                $threadId = (int)$pdo->lastInsertId();
            }
            $stmt = $pdo->prepare('INSERT INTO advisor_messages (thread_id, sender, body) VALUES (?, "student", ?)');
            $stmt->execute([$threadId, $body]);
            $pdo->prepare('UPDATE advisor_threads SET status="open", updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$threadId]);
            $pdo->commit();
            $notice = 'پیام شما ارسال شد.';
        }
    }
    if ($_POST['panel_action'] === 'update_profile') {
        $name = trim($_POST['full_name'] ?? $student['full_name']);
        $grade = trim($_POST['grade'] ?? $student['grade']);
        $pdo->prepare('UPDATE students SET full_name = ?, grade = ? WHERE id = ?')->execute([$name, $grade, $student['id']]);
        $_SESSION['student_name'] = $name;
        $notice = 'اطلاعات شما به‌روزرسانی شد.';
        $student['full_name'] = $name; $student['grade'] = $grade;
    }
    if ($_POST['panel_action'] === 'logout') { session_destroy(); header('Location: student-panel.php'); exit; }
}

// ===== Panel Data =====
$tab = $_GET['tab'] ?? 'dashboard';
$enrollments = []; $threads = []; $messages = []; $exam_results = [];
if ($student) {
    $stmt = $pdo->prepare('SELECT e.*, c.title AS course_title, c.image AS course_image, c.hours, c.sessions_count, c.grade AS course_grade FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.student_contact = ? AND e.paid_amount > 0 ORDER BY e.created_at DESC');
    $stmt->execute([$student['phone']]); $enrollments = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT * FROM advisor_threads WHERE student_contact = ? ORDER BY updated_at DESC');
    $stmt->execute([$student['phone']]); $threads = $stmt->fetchAll();
    if (!empty($threads)) {
        $stmt = $pdo->prepare('SELECT * FROM advisor_messages WHERE thread_id = ? ORDER BY id');
        $stmt->execute([$threads[0]['id']]); $messages = $stmt->fetchAll();
    }
    $stmt = $pdo->prepare('SELECT * FROM student_exams WHERE student_id = ? ORDER BY created_at DESC LIMIT 10');
    $stmt->execute([$student['id']]); $exam_results = $stmt->fetchAll();
    $total_courses = count($enrollments);
    $total_hours = array_sum(array_column($enrollments, 'hours'));
    $total_messages = $pdo->prepare('SELECT COUNT(*) FROM advisor_messages m JOIN advisor_threads t ON t.id = m.thread_id WHERE t.student_contact = ?');
    $total_messages->execute([$student['phone']]); $msg_count = (int)$total_messages->fetchColumn();
}

page_start('پنل دانش‌آموز | دوپینگ شیمی');

if (!$student):
?><!-- ═══ AUTH PAGE ═══ -->
<section class="auth-section auth-clean" style="min-height:100vh;">
  <div class="container" style="max-width:480px;margin:0 auto;padding:40px 20px;">
    <div class="auth-card" style="border-radius:24px;padding:32px;">
      <?php if ($auth_error): ?><div style="background:#FEF2F2;color:#DC2626;padding:12px 16px;border-radius:12px;margin-bottom:20px;font-weight:700;font-size:0.9rem;border:1px solid rgba(220,38,38,0.15);"><?= e($auth_error) ?></div><?php endif; ?>

      <!-- Login -->
      <div id="studentLoginForm">
        <div style="text-align:center;margin-bottom:28px;">
          <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,var(--color-primary),var(--color-accent));display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 16px;box-shadow:0 8px 24px rgba(37,99,235,0.2);">🎓</div>
          <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:6px;">ورود به پنل</h1>
          <p style="color:var(--color-text-secondary);font-size:0.9rem;">با شماره موبایل و رمز عبور وارد شو</p>
        </div>
        <form method="post" style="display:flex;flex-direction:column;gap:16px;">
          <input type="hidden" name="auth_action" value="login">
          <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">شماره موبایل</label><input type="tel" name="phone" placeholder="09123456789" required style="width:100%;min-height:50px;padding:12px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.95rem;outline:none;transition:all 0.2s;"></div>
          <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">رمز عبور</label><input type="password" name="password" placeholder="••••••••" required style="width:100%;min-height:50px;padding:12px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.95rem;outline:none;transition:all 0.2s;"></div>
          <button type="submit" style="width:100%;min-height:50px;border-radius:14px;border:none;background:var(--color-primary);color:#fff;font-weight:800;font-size:1rem;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 14px rgba(37,99,235,0.25);">ورود به پنل</button>
        </form>
        <div style="display:flex;align-items:center;gap:12px;margin:20px 0 14px;color:var(--color-text-muted);font-size:0.85rem;font-weight:800;"><span style="flex:1;height:1px;background:var(--color-border);"></span><span>حساب نداری؟</span><span style="flex:1;height:1px;background:var(--color-border);"></span></div>
        <button onclick="showStudentRegister()" style="width:100%;min-height:48px;border-radius:14px;border:1px solid var(--color-border);background:transparent;color:var(--color-text);font-weight:800;font-size:0.92rem;cursor:pointer;transition:all 0.2s;">ثبت‌نام در دوپینگ شیمی</button>
      </div>

      <!-- Register (hidden) -->
      <div id="studentRegisterForm" style="display:none;">
        <div style="text-align:center;margin-bottom:24px;">
          <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,var(--color-primary),var(--color-accent));display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 16px;box-shadow:0 8px 24px rgba(37,99,235,0.2);">🎓</div>
          <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:6px;">ثبت‌نام</h1>
          <p style="color:var(--color-text-secondary);font-size:0.9rem;">اطلاعاتت رو وارد کن تا پنل بسازی</p>
        </div>
        <form method="post" style="display:flex;flex-direction:column;gap:14px;">
          <input type="hidden" name="auth_action" value="register">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">نام و نام خانوادگی</label><input type="text" name="full_name" placeholder="علی رضایی" required style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.9rem;outline:none;transition:all 0.2s;"></div>
            <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">شماره موبایل</label><input type="tel" name="phone" placeholder="09123456789" required style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.9rem;outline:none;transition:all 0.2s;"></div>
          </div>
          <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">پایه تحصیلی</label><select name="grade" required style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.9rem;outline:none;"><option value="">انتخاب پایه</option><option value="دهم">پایه دهم</option><option value="یازدهم">پایه یازدهم</option><option value="دوازدهم">پایه دوازدهم</option><option value="کنکور">کنکور</option></select></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">رمز عبور</label><input type="password" name="password" placeholder="حداقل ۶ کاراکتر" minlength="6" required style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.9rem;outline:none;transition:all 0.2s;"></div>
            <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">تکرار رمز</label><input type="password" name="password_confirm" placeholder="تکرار رمز" required style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-family);font-size:0.9rem;outline:none;" oninput="this.setCustomValidity(this.value!==this.form.password.value?'یکسان نیست':'')"></div>
          </div>
          <button type="submit" style="width:100%;min-height:50px;border-radius:14px;border:none;background:var(--color-primary);color:#fff;font-weight:800;font-size:1rem;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 14px rgba(37,99,235,0.25);">ثبت‌نام</button>
        </form>
        <div style="display:flex;align-items:center;gap:12px;margin:18px 0 14px;color:var(--color-text-muted);font-size:0.85rem;font-weight:800;"><span style="flex:1;height:1px;background:var(--color-border);"></span><span>قبلاً ثبت‌نام کردی؟</span><span style="flex:1;height:1px;background:var(--color-border);"></span></div>
        <button onclick="showStudentLogin()" style="width:100%;min-height:48px;border-radius:14px;border:1px solid var(--color-border);background:transparent;color:var(--color-text);font-weight:800;font-size:0.92rem;cursor:pointer;">ورود به حساب</button>
      </div>
    </div>
  </div>
</section>
<script>function showStudentRegister(){document.getElementById('studentLoginForm').style.display='none';document.getElementById('studentRegisterForm').style.display='block';}function showStudentLogin(){document.getElementById('studentLoginForm').style.display='block';document.getElementById('studentRegisterForm').style.display='none';}</script>

<?php else: ?><!-- ═══ PANEL ═══ -->
<section style="padding:0 0 60px;background:var(--color-surface);min-height:100vh;">
  <!-- Top Bar -->
  <div style="background:#fff;border-bottom:1px solid var(--color-border);position:sticky;top:84px;z-index:40;">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;height:64px;gap:16px;">
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--color-primary),var(--color-accent));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1rem;"><?= e(first_grapheme($student['full_name'])) ?></div>
        <div><strong style="font-size:0.95rem;"><?= e($student['full_name']) ?></strong><span style="display:block;font-size:0.75rem;color:var(--color-text-muted);">پایه <?= e($student['grade'] ?: 'نامشخص') ?></span></div>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <nav style="display:flex;gap:4px;background:var(--color-surface);padding:4px;border-radius:12px;">
          <a href="?tab=dashboard" style="padding:7px 14px;border-radius:10px;font-weight:800;font-size:0.82rem;color:<?= $tab==='dashboard'?'#fff':'' ?>;background:<?= $tab==='dashboard'?'var(--color-primary)':'transparent' ?>;<?= $tab!=='dashboard'?'color:var(--color-text-secondary)':'' ?>;">داشبورد</a>
          <a href="?tab=courses" style="padding:7px 14px;border-radius:10px;font-weight:800;font-size:0.82rem;color:<?= $tab==='courses'?'#fff':'' ?>;background:<?= $tab==='courses'?'var(--color-primary)':'transparent' ?>;<?= $tab!=='courses'?'color:var(--color-text-secondary)':'' ?>;">دوره‌ها</a>
          <a href="?tab=advisor" style="padding:7px 14px;border-radius:10px;font-weight:800;font-size:0.82rem;color:<?= $tab==='advisor'?'#fff':'' ?>;background:<?= $tab==='advisor'?'var(--color-primary)':'transparent' ?>;<?= $tab!=='advisor'?'color:var(--color-text-secondary)':'' ?>;">مشاور</a>
          <a href="?tab=exams" style="padding:7px 14px;border-radius:10px;font-weight:800;font-size:0.82rem;color:<?= $tab==='exams'?'#fff':'' ?>;background:<?= $tab==='exams'?'var(--color-primary)':'transparent' ?>;<?= $tab!=='exams'?'color:var(--color-text-secondary)':'' ?>;">آزمون‌ها</a>
          <a href="?tab=profile" style="padding:7px 14px;border-radius:10px;font-weight:800;font-size:0.82rem;color:<?= $tab==='profile'?'#fff':'' ?>;background:<?= $tab==='profile'?'var(--color-primary)':'transparent' ?>;<?= $tab!=='profile'?'color:var(--color-text-secondary)':'' ?>;">حساب</a>
        </nav>
        <form method="post" style="display:inline;"><input type="hidden" name="panel_action" value="logout"><button type="submit" style="padding:7px 14px;border-radius:10px;border:1px solid var(--color-border);background:#fff;color:var(--color-text-muted);font-weight:800;font-size:0.8rem;cursor:pointer;">خروج</button></form>
      </div>
    </div>
  </div>

  <?php if ($notice): ?><div class="container" style="margin-top:20px;"><div style="background:var(--color-accent-light);color:var(--color-accent-dark);padding:14px 18px;border-radius:14px;font-weight:800;font-size:0.9rem;border:1px solid rgba(16,185,129,0.2);display:flex;align-items:center;gap:10px;"><span style="font-size:1.2rem;">✓</span><?= e($notice) ?></div></div><?php endif; ?>

  <div class="container" style="margin-top:24px;">
    <!-- ═══ DASHBOARD ═══ -->
    <?php if ($tab === 'dashboard'): ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
      <div style="background:#fff;border-radius:18px;padding:22px;border:1px solid var(--color-border);box-shadow:var(--shadow-sm);"><div style="width:44px;height:44px;border-radius:14px;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:14px;">📚</div><strong style="display:block;font-size:1.8rem;font-weight:900;"><?= fa_num($total_courses) ?></strong><span style="font-size:0.85rem;color:var(--color-text-muted);font-weight:700;">دوره ثبت‌نامی</span></div>
      <div style="background:#fff;border-radius:18px;padding:22px;border:1px solid var(--color-border);box-shadow:var(--shadow-sm);"><div style="width:44px;height:44px;border-radius:14px;background:var(--color-accent-light);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:14px;">⏱️</div><strong style="display:block;font-size:1.8rem;font-weight:900;"><?= fa_num($total_hours) ?></strong><span style="font-size:0.85rem;color:var(--color-text-muted);font-weight:700;">ساعت آموزش</span></div>
      <div style="background:#fff;border-radius:18px;padding:22px;border:1px solid var(--color-border);box-shadow:var(--shadow-sm);"><div style="width:44px;height:44px;border-radius:14px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:14px;">💬</div><strong style="display:block;font-size:1.8rem;font-weight:900;"><?= fa_num($msg_count) ?></strong><span style="font-size:0.85rem;color:var(--color-text-muted);font-weight:700;">پیام به مشاور</span></div>
      <div style="background:#fff;border-radius:18px;padding:22px;border:1px solid var(--color-border);box-shadow:var(--shadow-sm);"><div style="width:44px;height:44px;border-radius:14px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:14px;">📝</div><strong style="display:block;font-size:1.8rem;font-weight:900;"><?= fa_num(count($exam_results)) ?></strong><span style="font-size:0.85rem;color:var(--color-text-muted);font-weight:700;">آزمون</span></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--color-border);"><h3 style="font-size:1.05rem;font-weight:800;">آخرین دوره‌ها</h3><a href="?tab=courses" style="font-size:0.82rem;font-weight:800;color:var(--color-primary);">مشاهده همه</a></div>
        <?php if ($enrollments): foreach (array_slice($enrollments,0,4) as $en): ?>
        <a href="course-detail.php?id=<?= (int)$en['course_id'] ?>" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;margin-bottom:6px;background:var(--color-surface);transition:all 0.2s;">
          <div style="width:40px;height:40px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:var(--shadow-sm);flex-shrink:0;">📖</div>
          <div><strong style="display:block;font-size:0.88rem;font-weight:800;color:var(--color-text);"><?= e($en['course_title']) ?></strong><span style="font-size:0.78rem;color:var(--color-text-muted);">پایه <?= e($en['course_grade']) ?> · <?= fa_num((int)$en['hours']) ?> ساعت</span></div>
        </a>
        <?php endforeach; else: ?>
        <div style="text-align:center;padding:30px 0;color:var(--color-text-muted);font-size:0.9rem;">هنوز دوره‌ای ثبت‌نام نکردی.<br><a href="courses.php" style="color:var(--color-primary);font-weight:900;">مشاهده دوره‌ها</a></div>
        <?php endif; ?>
      </div>
      <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--color-border);"><h3 style="font-size:1.05rem;font-weight:800;">پیام‌های مشاور</h3><a href="?tab=advisor" style="font-size:0.82rem;font-weight:800;color:var(--color-primary);">مشاهده همه</a></div>
        <?php if ($messages): foreach (array_slice($messages,-3) as $msg): ?>
        <div style="padding:10px 14px;margin:4px 0;border-radius:12px;font-size:0.85rem;line-height:1.7;background:<?= $msg['sender']==='advisor'?'var(--color-accent-light)':'var(--color-primary-light)' ?>;color:<?= $msg['sender']==='advisor'?'var(--color-accent-dark)':'var(--color-primary-dark)' ?>"><strong style="font-weight:900;"><?= $msg['sender']==='advisor'? 'مشاور' : 'شما' ?>:</strong> <?= e(mb_substr($msg['body'],0,100)) ?>...</div>
        <?php endforeach; else: ?>
        <div style="text-align:center;padding:30px 0;color:var(--color-text-muted);font-size:0.9rem;">هنوز پیامی نداری.<br><a href="?tab=advisor" style="color:var(--color-primary);font-weight:900;">ارسال پیام</a></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ═══ COURSES ═══ -->
    <?php if ($tab === 'courses'): ?>
    <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
      <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">دوره‌های من</h3>
      <?php if ($enrollments): ?>
      <div class="courses-grid"><?php foreach ($enrollments as $en){$c=course_by_id((int)$en['course_id']);if($c)render_course_card($c);} ?></div>
      <?php else: ?>
      <div style="text-align:center;padding:60px 20px;"><div style="font-size:3rem;margin-bottom:16px;">📚</div><p style="color:var(--color-text-secondary);margin-bottom:16px;">هنوز در هیچ دوره‌ای ثبت‌نام نکردی.</p><a href="courses.php" class="btn btn-primary">مشاهده دوره‌ها</a></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ ADVISOR ═══ -->
    <?php if ($tab === 'advisor'): ?>
    <div style="display:grid;grid-template-columns:0.9fr 1.1fr;gap:20px;">
      <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
        <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">ارسال پیام</h3>
        <form method="post" style="display:flex;flex-direction:column;gap:14px;">
          <input type="hidden" name="panel_action" value="send_message">
          <textarea name="message" required rows="5" placeholder="سوال یا پیامت رو بنویس..." style="width:100%;min-height:120px;padding:12px 14px;border-radius:14px;border:1px solid var(--color-border);font-family:var(--font-family);font-size:0.92rem;outline:none;resize:vertical;line-height:1.8;"></textarea>
          <button type="submit" style="padding:12px 24px;border-radius:14px;border:none;background:var(--color-primary);color:#fff;font-weight:800;font-size:0.95rem;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.2);">ارسال پیام</button>
        </form>
      </div>
      <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
        <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">گفتگو با مشاور</h3>
        <?php if ($messages): ?>
        <div style="max-height:400px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding:4px;">
          <?php foreach ($messages as $msg): ?>
          <div style="padding:12px 16px;border-radius:14px;line-height:1.8;font-size:0.9rem;max-width:85%;<?= $msg['sender']==='advisor'?'align-self:flex-start;background:var(--color-accent-light);color:var(--color-accent-dark)':'align-self:flex-end;background:var(--color-primary-light);color:var(--color-primary-dark)' ?>">
            <strong style="font-weight:900;"><?= $msg['sender']==='advisor'?'مشاور':'شما' ?>:</strong> <?= nl2br(e($msg['body'])) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($threads): ?><div style="margin-top:12px;padding:10px 14px;border-radius:10px;background:var(--color-surface);font-size:0.82rem;color:var(--color-text-muted);font-weight:700;">وضعیت: <?= $threads[0]['status']==='open'?'🟡 در انتظار پاسخ':'🟢 پاسخ داده شد' ?></div><?php endif; ?>
        <?php else: ?>
        <div style="text-align:center;padding:50px 0;color:var(--color-text-muted);font-size:0.9rem;">هنوز پیامی ارسال نشده.<br><br>اولین پیامت رو برای مشاور بفرست!</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ═══ EXAMS ═══ -->
    <?php if ($tab === 'exams'): ?>
    <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
      <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">نتایج آزمون‌ها</h3>
      <?php if ($exam_results): ?>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:600px;">
          <thead><tr style="background:var(--color-surface);"><th style="padding:12px 14px;text-align:right;font-size:0.85rem;color:var(--color-text-secondary);font-weight:800;">عنوان</th><th style="padding:12px 14px;text-align:right;font-size:0.85rem;color:var(--color-text-secondary);font-weight:800;">سوالات</th><th style="padding:12px 14px;text-align:right;font-size:0.85rem;color:var(--color-text-secondary);font-weight:800;">درست</th><th style="padding:12px 14px;text-align:right;font-size:0.85rem;color:var(--color-text-secondary);font-weight:800;">غلط</th><th style="padding:12px 14px;text-align:right;font-size:0.85rem;color:var(--color-text-secondary);font-weight:800;">نمره</th><th style="padding:12px 14px;text-align:right;font-size:0.85rem;color:var(--color-text-secondary);font-weight:800;">تاریخ</th></tr></thead>
          <tbody><?php foreach ($exam_results as $ex): ?><tr style="border-bottom:1px solid var(--color-border);"><td style="padding:12px 14px;font-weight:700;"><?= e($ex['exam_title']) ?></td><td style="padding:12px 14px;"><?= fa_num((int)$ex['total_questions']) ?></td><td style="padding:12px 14px;color:var(--color-accent);font-weight:800;"><?= fa_num((int)$ex['correct_count']) ?></td><td style="padding:12px 14px;color:#DC2626;font-weight:800;"><?= fa_num((int)$ex['wrong_count']) ?></td><td style="padding:12px 14px;font-weight:900;color:var(--color-primary);"><?= fa_num((int)$ex['score']) ?></td><td style="padding:12px 14px;color:var(--color-text-muted);font-size:0.85rem;"><?= e($ex['created_at']) ?></td></tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:60px 20px;"><div style="font-size:3rem;margin-bottom:16px;">📝</div><p style="color:var(--color-text-secondary);margin-bottom:16px;">هنوز در آزمونی شرکت نکردی.</p><a href="exam.html" class="btn btn-primary">شرکت در آزمون</a></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ PROFILE ═══ -->
    <?php if ($tab === 'profile'): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
        <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">ویرایش اطلاعات</h3>
        <form method="post" style="display:flex;flex-direction:column;gap:14px;">
          <input type="hidden" name="panel_action" value="update_profile">
          <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">نام و نام خانوادگی</label><input type="text" name="full_name" value="<?= e($student['full_name']) ?>" required style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);font-family:var(--font-family);font-size:0.9rem;outline:none;"></div>
          <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">پایه تحصیلی</label><select name="grade" style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);font-family:var(--font-family);font-size:0.9rem;outline:none;"><option value="دهم" <?= $student['grade']==='دهم'?'selected':'' ?>>پایه دهم</option><option value="یازدهم" <?= $student['grade']==='یازدهم'?'selected':'' ?>>پایه یازدهم</option><option value="دوازدهم" <?= $student['grade']==='دوازدهم'?'selected':'' ?>>پایه دوازدهم</option><option value="کنکور" <?= $student['grade']==='کنکور'?'selected':'' ?>>کنکور</option></select></div>
          <div><label style="display:block;font-weight:800;font-size:0.88rem;margin-bottom:6px;color:var(--color-text);">شماره موبایل</label><input type="tel" value="<?= e($student['phone']) ?>" disabled style="width:100%;min-height:48px;padding:11px 14px;border-radius:14px;border:1px solid var(--color-border);background:var(--color-surface-2);font-family:var(--font-family);font-size:0.9rem;color:var(--color-text-muted);"></div>
          <button type="submit" style="padding:12px 24px;border-radius:14px;border:none;background:var(--color-primary);color:#fff;font-weight:800;font-size:0.95rem;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.2);">ذخیره</button>
        </form>
      </div>
      <div style="background:#fff;border-radius:18px;padding:24px;border:1px solid var(--color-border);">
        <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">خلاصه فعالیت</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:12px;background:var(--color-surface);"><span style="color:var(--color-text-secondary);font-weight:700;font-size:0.88rem;">تاریخ عضویت</span><strong style="font-weight:900;font-size:0.9rem;"><?= e($student['created_at']) ?></strong></div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:12px;background:var(--color-surface);"><span style="color:var(--color-text-secondary);font-weight:700;font-size:0.88rem;">دوره‌ها</span><strong style="font-weight:900;font-size:0.9rem;"><?= fa_num($total_courses) ?></strong></div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:12px;background:var(--color-surface);"><span style="color:var(--color-text-secondary);font-weight:700;font-size:0.88rem;">پیام‌ها</span><strong style="font-weight:900;font-size:0.9rem;"><?= fa_num($msg_count) ?></strong></div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:12px;background:var(--color-surface);"><span style="color:var(--color-text-secondary);font-weight:700;font-size:0.88rem;">آزمون‌ها</span><strong style="font-weight:900;font-size:0.9rem;"><?= fa_num(count($exam_results)) ?></strong></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; page_end(); ?>