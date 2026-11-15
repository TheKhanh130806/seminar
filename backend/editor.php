<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/content.php';
require __DIR__ . '/editor-render.php';
try {
    admin();
    $content = read_content();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') json_response(['page' => $content['draft']['page'] ?? null, 'sections' => $content['draft'], 'csrf' => csrf()]);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Phương thức không hợp lệ.'], 405);
    check_csrf();
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 2000000) json_response(['error' => 'Nội dung quá lớn.'], 413);
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $section = $data['section'] ?? 'page';
    if (!in_array($section, ['page', 'hero', 'promotions', 'about'], true) || !is_array($data['project'] ?? null) || !is_string($data['html'] ?? null) || !is_string($data['css'] ?? null) || !in_array($data['action'] ?? '', ['save', 'publish'], true)) json_response(['error' => 'Dữ liệu không hợp lệ.'], 422);
    // Validate before persisting either the project or the public output.
    render_editor_section($section, $data);
    $content['draft'][$section] = ['project' => $data['project'], 'html' => $data['html'], 'css' => $data['css']];
    if ($data['action'] === 'publish') $content['published'][$section] = $content['draft'][$section];
    save_content($content);
    json_response(['ok' => true]);
} catch (InvalidArgumentException $e) { json_response(['error' => $e->getMessage()], 422);
} catch (JsonException $e) { json_response(['error' => 'Dữ liệu không hợp lệ.'], 400);
} catch (Throwable $e) { error_log((string) $e); json_response(['error' => 'Không thể tải/lưu giao diện. Kiểm tra database và quyền ghi thư mục storage.'], 500); }
