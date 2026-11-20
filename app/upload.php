<?php
require __DIR__ . '/lib/portal.php';
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('REPORT_DIR', __DIR__ . '/reports');
Portal::start();

$name = $_FILES['document']['name'] ?? '';
if (!$name || ($_FILES['document']['error'] ?? 1) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo "missing or bad upload\n";
    exit;
}
$safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
$dest = UPLOAD_DIR . '/' . $safe;
if (!move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
    http_response_code(500);
    echo "store failed\n";
    exit;
}
$_SESSION['documents'][$safe] = time();
echo "stored: $safe\n";
