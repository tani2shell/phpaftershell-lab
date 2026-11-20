<?php
require __DIR__ . '/lib/portal.php';
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('REPORT_DIR', __DIR__ . '/reports');
Portal::start();
$token = $_SESSION['portal_token'] ??= bin2hex(random_bytes(4));
$docs  = Portal::documents();
$reps  = Portal::reports();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>AfterShell DocPortal</title>
<style>
 body{font-family:system-ui,sans-serif;max-width:720px;margin:2rem auto;color:#222}
 .box{border:1px solid #ddd;border-radius:8px;padding:1rem;margin:1rem 0}
 ul{margin:.3rem 0 .3rem 1.2rem}
 code{background:#f4f4f4;padding:.1rem .3rem;border-radius:3px}
</style>
</head>
<body>
<h1>AfterShell DocPortal</h1>
<p>Shared document portal. Upload a document; reports are generated automatically.</p>

<div class="box">
<h2>Upload a document</h2>
<form action="upload.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="PHP_SESSION_UPLOAD_PROGRESS" value="<?= htmlspecialchars($token) ?>">
  <input type="file" name="document">
  <button type="submit">Upload</button>
</form>
</div>

<div class="box">
<h2>Documents</h2>
<?php if (!$docs): ?><p>No documents yet.</p><?php endif; ?>
<ul><?php foreach ($docs as $d): ?><li><?= htmlspecialchars($d['name']) ?> (<?= $d['size'] ?> bytes)</li><?php endforeach; ?></ul>
</div>

<div class="box">
<h2>Generated reports</h2>
<?php if (!$reps): ?><p>No reports yet.</p><?php endif; ?>
<ul><?php foreach ($reps as $r): ?><li><a href="reports/<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></a></li><?php endforeach; ?></ul>
</div>

<div class="box">
<p><a href="status.php">Session status</a></p>
</div>
</body>
</html>
