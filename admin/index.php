<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_teacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && csrf_check()) {
    db()->prepare('DELETE FROM submissions WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    header('Location: index.php');
    exit;
}

$titleSaved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_title' && csrf_check()) {
    $t = trim((string)($_POST['exercise_title'] ?? ''));
    set_setting('exercise_title', mb_substr($t, 0, 200));
    $_SESSION['flash_title'] = true;
    header('Location: index.php');
    exit;
}
if (!empty($_SESSION['flash_title'])) { $titleSaved = true; unset($_SESSION['flash_title']); }
$exerciseTitle = get_setting('exercise_title', '');

$q = trim((string)($_GET['q'] ?? ''));
$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR CONCAT(first_name," ",last_name) LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

$st = db()->prepare("SELECT COUNT(*) FROM submissions $where");
$st->execute($params);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$st = db()->prepare(
    "SELECT id, first_name, last_name, email, academic_year, class_code, exercise_title, created_at,
            (notes IS NOT NULL AND notes <> '') AS has_notes
     FROM submissions $where ORDER BY id DESC LIMIT $perPage OFFSET $offset"
);
$st->execute($params);
$rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>داشبورد معلم</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container wide">
  <div class="topbar">
    <h1>داشبورد معلم</h1>
    <a class="btn secondary" href="logout.php">خروج</a>
  </div>

  <div class="card title-box">
    <form method="post" class="title-form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_title">
      <label>عنوان تمرین فعلی (در فرم دانش‌آموزان نمایش داده می‌شود)
        <input type="text" name="exercise_title" maxlength="200" value="<?= e($exerciseTitle) ?>">
      </label>
      <button type="submit">ذخیره عنوان</button>
      <?php if ($titleSaved): ?><span class="saved">✅ ذخیره شد</span><?php endif; ?>
    </form>
  </div>

  <form method="get" class="search">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="جستجو بر اساس نام یا ایمیل...">
    <button type="submit">جستجو</button>
  </form>

  <p class="muted">تعداد پاسخ‌ها: <?= en_to_fa_digits((string)$total) ?></p>

  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>#</th><th>تمرین</th><th>نام و نام خانوادگی</th><th>ایمیل</th><th>سال/کلاس</th><th>زمان ارسال</th><th>توضیحات</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= en_to_fa_digits((string)$r['id']) ?></td>
        <td><?= e($r['exercise_title'] ?? '') ?: '—' ?></td>
        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td dir="ltr"><?= e($r['email']) ?></td>
        <td><?= en_to_fa_digits($r['academic_year'] . ' / ' . $r['class_code']) ?></td>
        <td><?= e(jalali_datetime($r['created_at'])) ?></td>
        <td><?= $r['has_notes'] ? '📝' : '—' ?></td>
        <td class="actions">
          <a class="btn" href="view.php?id=<?= (int)$r['id'] ?>">مشاهده</a>
          <form method="post" onsubmit="return confirm('این پاسخ حذف شود؟')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn danger">حذف</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8" class="muted center">هنوز پاسخی ثبت نشده است.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?= $i === $page ? 'cur' : '' ?>" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>"><?= en_to_fa_digits((string)$i) ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
