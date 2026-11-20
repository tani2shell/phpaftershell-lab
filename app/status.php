<?php
require __DIR__ . '/lib/portal.php';
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('REPORT_DIR', __DIR__ . '/reports');
Portal::start();

/*
 * Session status panel: shows the names and value kinds of the current
 * server-side session records for this visitor.
 */
$kinds = [];
foreach ($_SESSION as $k => $v) {
    $kinds[$k] = gettype($v) === 'object' ? 'object' : gettype($v);
}
ksort($kinds);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Status — DocPortal</title>
<style>body{font-family:system-ui,sans-serif;max-width:640px;margin:2rem auto}
table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:.3rem .6rem;font-size:.9rem}</style>
</head><body>
<h1>Session status</h1>
<table><tr><th>record</th><th>kind</th></tr>
<?php foreach ($kinds as $k => $t): ?>
<tr><td><?= htmlspecialchars($k) ?></td><td><?= $t ?></td></tr>
<?php endforeach; ?>
</table>
<p><a href="index.php">back</a></p>
</body></html>
