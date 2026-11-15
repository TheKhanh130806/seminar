<?php
declare(strict_types=1);
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
function db(): PDO {
    static $pdo;
    return $pdo ??= new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=cua_hang_thuc_pham;charset=utf8mb4',
        getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
}
function json_response(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function check_csrf(): void {
    if (!hash_equals(csrf(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) json_response(['error' => 'Phiên không hợp lệ. Hãy tải lại trang.'], 403);
}
function admin(): array {
    if (empty($_SESSION['account_id'])) json_response(['error' => 'Bạn cần đăng nhập tài khoản quản trị.'], 403);
    $stmt = db()->prepare('SELECT ma_tai_khoan, vai_tro FROM tai_khoan WHERE ma_tai_khoan = ?');
    $stmt->execute([$_SESSION['account_id'] ?? 0]);
    $user = $stmt->fetch();
    if (!$user || $user['vai_tro'] !== 'quan_tri') json_response(['error' => 'Bạn cần đăng nhập tài khoản quản trị.'], 403);
    return $user;
}
