<?php
require __DIR__ . '/bootstrap.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') json_response(['csrf' => csrf()]);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Phương thức không hợp lệ.'], 405);
    check_csrf();
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (($data['action'] ?? '') === 'logout') {
        $_SESSION = [];
        session_destroy();
        json_response(['ok' => true]);
    }
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') json_response(['error' => 'Vui lòng nhập email và mật khẩu hợp lệ.'], 422);
    $stmt = db()->prepare('SELECT ma_tai_khoan, mat_khau_bam, vai_tro FROM tai_khoan WHERE email = ?');
    $stmt->execute([$email]);
    $account = $stmt->fetch();
    if (!$account || !password_verify($password, $account['mat_khau_bam'])) json_response(['error' => 'Email hoặc mật khẩu không đúng.'], 401);
    session_regenerate_id(true);
    $_SESSION['account_id'] = $account['ma_tai_khoan'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    json_response(['role' => $account['vai_tro']]);
} catch (JsonException $e) {
    json_response(['error' => 'Dữ liệu không hợp lệ.'], 400);
} catch (Throwable $e) {
    error_log((string) $e);
    json_response(['error' => 'Không thể kết nối hệ thống. Kiểm tra MySQL và cấu hình database.'], 500);
}
