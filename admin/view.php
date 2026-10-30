<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_teacher();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM submissions WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { http_response_code(404); exit('پاسخ پیدا نشد.'); }

$prev = db()->prepare('SELECT id FROM submissions WHERE id < ? ORDER BY id DESC LIMIT 1'); $prev->execute([$id]); $prevId = $prev->fetchColumn();
$next = db()->prepare('SELECT id FROM submissions WHERE id > ? ORDER BY id ASC LIMIT 1');  $next->execute([$id]); $nextId = $next->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>پاسخ شماره <?= (int)$r['id'] ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="container wide">
  <div class="topbar">
    <h1>پاسخ شماره <?= en_to_fa_digits((string)$r['id']) ?></h1>
    <a class="btn secondary" href="index.php">← بازگشت به فهرست</a>
  </div>

  <div class="card">
    <p><strong>تمرین:</strong> <?= e($r['exercise_title'] ?? '') ?: '—' ?></p>
    <p><strong>دانش‌آموز:</strong> <?= e($r['first_name'] . ' ' . $r['last_name']) ?></p>
    <p><strong>ایمیل:</strong> <span dir="ltr"><?= e($r['email']) ?></span></p>
    <p><strong>سال تحصیلی / کلاس:</strong> <?= en_to_fa_digits($r['academic_year'] . ' / ' . $r['class_code']) ?></p>
    <p><strong>زمان ارسال:</strong> <?= e(jalali_datetime($r['created_at'])) ?></p>
  </div>

  <h2>کد ارسال‌شده</h2>
  <pre class="codeview" dir="ltr"><?= e($r['code']) ?></pre>

  <h2>توضیحات / موارد خاص</h2>
  <div class="card notes"><?= $r['notes'] ? nl2br(e($r['notes'])) : '<span class="muted">— ندارد —</span>' ?></div>

  <div class="pager">
    <?php if ($nextId): ?><a href="view.php?id=<?= (int)$nextId ?>">← پاسخ بعدی</a><?php endif; ?>
    <?php if ($prevId): ?><a href="view.php?id=<?= (int)$prevId ?>">پاسخ قبلی →</a><?php endif; ?>
  </div>
</main>
</body>
</html>
