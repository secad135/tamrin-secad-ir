<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$old = ['first_name'=>'','last_name'=>'','email'=>'','code'=>'','notes'=>''];
$success = false;
$exerciseTitle = get_setting('exercise_title', 'تمرین');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'نشست شما منقضی شده است. لطفاً صفحه را دوباره بارگذاری کنید.';
    }
    // honeypot برای ربات‌ها
    if (!empty($_POST['website'])) { exit; }

    $old['first_name'] = trim((string)($_POST['first_name'] ?? ''));
    $old['last_name']  = trim((string)($_POST['last_name'] ?? ''));
    $old['email']      = trim(fa_to_en_digits((string)($_POST['email'] ?? '')));
    $old['code']       = (string)($_POST['code'] ?? '');
    $old['notes']      = trim((string)($_POST['notes'] ?? ''));
    $year  = (int)($_POST['academic_year'] ?? 0);
    $class = (string)($_POST['class_code'] ?? '');

    if ($old['first_name'] === '' || mb_strlen($old['first_name']) > 100) $errors[] = 'نام را درست وارد کنید.';
    if ($old['last_name']  === '' || mb_strlen($old['last_name'])  > 100) $errors[] = 'نام خانوادگی را درست وارد کنید.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) $errors[] = 'ایمیل معتبر وارد کنید.';
    if ($year !== ACADEMIC_YEAR || $class !== CLASS_CODE) $errors[] = 'سال تحصیلی یا کلاس نامعتبر است.';
    if (trim($old['code']) === '') $errors[] = 'کادر کد نباید خالی باشد.';
    if (strlen($old['code']) > 200000) $errors[] = 'حجم کد بیش از حد مجاز است.';
    if (mb_strlen($old['notes']) > 5000) $errors[] = 'متن توضیحات بیش از ۵۰۰۰ کاراکتر است.';

    // جلوگیری از ارسال‌های پشت سر هم (حداقل ۵ ثانیه)
    if (!$errors && isset($_SESSION['last_submit']) && time() - $_SESSION['last_submit'] < 5) {
        $errors[] = 'لطفاً چند ثانیه صبر کنید و دوباره تلاش کنید.';
    }

    if (!$errors) {
        try {
            $st = db()->prepare(
                'INSERT INTO submissions (first_name,last_name,email,academic_year,class_code,exercise_title,code,notes,ip_address)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $old['first_name'], $old['last_name'], $old['email'],
                $year, $class, $exerciseTitle, $old['code'],
                $old['notes'] !== '' ? $old['notes'] : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $_SESSION['last_submit'] = time();
            $_SESSION['flash_ok'] = true;
            header('Location: index.php');   // الگوی PRG: جلوگیری از ارسال مجدد با رفرش
            exit;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $errors[] = 'خطا در ثبت پاسخ. لطفاً دوباره تلاش کنید.';
        }
    }
}

if (!empty($_SESSION['flash_ok'])) {
    $success = true;
    unset($_SESSION['flash_ok']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(SITE_TITLE) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
  <h1><?= e(SITE_TITLE) ?></h1>
  <?php if ($exerciseTitle !== ''): ?><h2 class="exercise-title"><?= e($exerciseTitle) ?></h2><?php endif; ?>

  <?php if ($success): ?>
    <div class="alert ok">✅ پاسخ شما با موفقیت ثبت شد.</div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert err"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div style="display:none"><input type="text" name="website" tabindex="-1"></div>

    <div class="row">
      <label>نام
        <input type="text" name="first_name" maxlength="100" required value="<?= e($old['first_name']) ?>">
      </label>
      <label>نام خانوادگی
        <input type="text" name="last_name" maxlength="100" required value="<?= e($old['last_name']) ?>">
      </label>
    </div>

    <label>ایمیل
      <input type="email" name="email" dir="ltr" maxlength="190" required value="<?= e($old['email']) ?>">
    </label>

    <div class="row">
      <label>سال تحصیلی
        <select name="academic_year"><option value="<?= ACADEMIC_YEAR ?>" selected><?= en_to_fa_digits((string)ACADEMIC_YEAR) ?></option></select>
      </label>
      <label>کلاس درسی
        <select name="class_code"><option value="<?= e(CLASS_CODE) ?>" selected><?= en_to_fa_digits(CLASS_CODE) ?></option></select>
      </label>
    </div>

    <label>کد برنامه
      <textarea name="code" class="code" dir="ltr" rows="16" spellcheck="false" required
        placeholder="کد خود را اینجا بنویسید یا Paste کنید..."><?= e($old['code']) ?></textarea>
    </label>

    <label>توضیحات / موارد خاص (اختیاری)
      <textarea name="notes" rows="5" maxlength="5000"><?= e($old['notes']) ?></textarea>
    </label>

    <button type="submit">ارسال پاسخ</button>
  </form>
</main>
<script>
// امکان استفاده از کلید Tab در کادر کد
document.querySelector('textarea.code').addEventListener('keydown', function (ev) {
  if (ev.key === 'Tab') {
    ev.preventDefault();
    const s = this.selectionStart, t = this.selectionEnd;
    this.value = this.value.substring(0, s) + '    ' + this.value.substring(t);
    this.selectionStart = this.selectionEnd = s + 4;
  }
});
</script>
</body>
</html>
