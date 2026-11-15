<?php
declare(strict_types=1);
const CONTENT_FILE = __DIR__ . '/../storage/content.php';
function read_content(string $path = CONTENT_FILE): array {
    if (!is_file($path)) return ['draft' => [], 'published' => []];
    $content = require $path;
    if (!is_array($content)) throw new RuntimeException('Invalid content storage');
    return $content;
}
function save_content(array $content, string $path = CONTENT_FILE): void {
    $temp = tempnam(dirname($path), 'content-');
    if ($temp === false) throw new RuntimeException('Cannot create temporary file');
    try {
        // PHP trả dữ liệu, không xuất nội dung khi truy cập trực tiếp qua HTTP.
        if (file_put_contents($temp, '<?php return ' . var_export($content, true) . ';', LOCK_EX) === false || !rename($temp, $path)) throw new RuntimeException('Cannot save content');
        clearstatcache(true, $path);
        if (function_exists('opcache_invalidate')) opcache_invalidate($path, true);
    } finally { if (is_file($temp)) unlink($temp); }
}
