<?php
require __DIR__ . '/../../backend/bootstrap.php';
if (empty($_SESSION['account_id'])) { header('Location: login.html'); exit; }
try { admin(); } catch (Throwable $e) { http_response_code(503); exit('Không thể kiểm tra quyền. Hãy kiểm tra MySQL.'); }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chỉnh sửa giao diện | LeaFood</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.22.8/dist/css/grapes.min.css">
  <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
</head>
<body>
  <header class="editor-toolbar">
    <strong>LeaFood · Chỉnh sửa trang</strong>
    <button id="assets" type="button" disabled>Asset Manager</button>
    <button id="save" disabled>Lưu nháp</button><button id="publish" disabled>Xuất bản trang</button>
    <button id="reset" disabled>Khôi phục mặc định</button>
    <a href="index.php" target="_blank" rel="noopener">Xem trang chủ ↗</a>
    <button id="logout">Đăng xuất</button>
  </header>
  <p id="editor-status" role="status">Đang tải trình chỉnh sửa…</p>
  <div id="editor"></div>
  <script src="https://cdn.jsdelivr.net/npm/grapesjs@0.22.8/dist/grapes.min.js"></script>
  <script src="../js/admin.js?v=<?= filemtime(__DIR__ . '/../js/admin.js') ?>"></script>
</body>
</html>
