<?php
require __DIR__ . '/bootstrap.php';

try {
    admin();
    $directory = dirname(__DIR__) . '/frontend/uploads/assets';
    $publicPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/backend/assets.php'))), '/');
    $publicPath .= '/frontend/uploads/assets';

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $assets = [];
        foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isFile() || !preg_match('/\.(?:jpe?g|png|gif|webp)$/i', $file->getFilename())) continue;
            $assets[] = ['src' => $publicPath . '/' . rawurlencode($file->getFilename()), 'name' => $file->getFilename()];
        }
        usort($assets, fn ($a, $b) => strnatcasecmp($b['name'], $a['name']));
        json_response(['assets' => $assets, 'csrf' => csrf()]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Phương thức không hợp lệ.'], 405);
    check_csrf();

    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 25000000) json_response(['error' => 'Tệp quá lớn. Giới hạn tổng là 25 MB.'], 413);
    $files = $_FILES['files'] ?? null;
    if (!$files || !is_array($files['name'] ?? null)) json_response(['error' => 'Không tìm thấy ảnh tải lên.'], 422);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $uploaded = [];
    foreach ($files['name'] as $index => $originalName) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) json_response(['error' => 'Tải ảnh thất bại. Mỗi ảnh tối đa 10 MB.'], 422);
        $tmp = $files['tmp_name'][$index];
        $size = (int) ($files['size'][$index] ?? 0);
        $mime = $finfo->file($tmp);
        if ($size < 1 || $size > 10 * 1024 * 1024 || !isset($allowed[$mime]) || @getimagesize($tmp) === false) {
            json_response(['error' => 'Chỉ hỗ trợ ảnh JPG, PNG, GIF hoặc WebP tối đa 10 MB.'], 422);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($tmp, $directory . DIRECTORY_SEPARATOR . $name)) json_response(['error' => 'Không thể lưu ảnh lên máy chủ.'], 500);
        $uploaded[] = ['src' => $publicPath . '/' . rawurlencode($name), 'name' => $name];
    }
    json_response(['data' => $uploaded]);
} catch (Throwable $e) {
    error_log((string) $e);
    json_response(['error' => 'Không thể truy cập thư viện ảnh. Kiểm tra quyền ghi thư mục uploads.'], 500);
}
