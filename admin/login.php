<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_teacher()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['login_fail'] = $_SESSION['login_fail'] ?? 0;
    $_SESSION['login_lock'] = $_SESSION['login_lock'] ?? 0;

    if (time() < $_SESSION['login_lock']) {
        $error = 'تلاش‌های ناموفق زیاد بود. چند دقیقه بعد دوباره امتحان کنید.';
    } elseif (!csrf_check()) {
        $error = 'نشست منقضی شده است. صفحه را دوباره بارگذاری کنید.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');

        $st = db()->prepare('SELECT id, password_hash FROM teachers WHERE username = ? LIMIT 1');
        $st->execute([$u]);
        $row = $st->fetch();

        $hash = $row['password_hash'] ?? '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidin';
        if ($row && password_verify($p, $hash)) {
            session_regenerate_id(true);
            $_SESSION['teacher_id'] = (int)$row['id'];
            $_SESSION['login_fail'] = 0;
            if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE teachers SET password_hash=? WHERE id=?')
                    ->execute([password_hash($p, PASSWORD_DEFAULT), $row['id']]);
            }
            header('Location: index.php');
            exit;
        }
        sleep(1);
        $_SESSION['login_fail']++;
        if ($_SESSION['login_fail'] >= 5) {
            $_SESSION['login_lock'] = time() + 300;
            $_SESSION['login_fail'] = 0;
        }
        $error = 'نام کاربری یا رمز عبور اشتباه است.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود معلم</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container narrow">
  <h1>ورود معلم</h1>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>نام کاربری <input type="text" name="username" dir="ltr" required autofocus></label>
    <label>رمز عبور <input type="password" name="password" dir="ltr" required></label>
    <button type="submit">ورود</button>
  </form>
</main>
</body>
</html>
