<?php
// Chỉ chạy từ terminal, không cho tạo quản trị viên qua trình duyệt.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/bootstrap.php';
try {
    $email = strtolower(trim(readline('Email admin: ')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email khong hop le.');
    echo "Mat khau se hien khi nhap trong terminal.\n";
    $password = readline('Mat khau (it nhat 12 ky tu): ');
    if (strlen($password) < 12) throw new RuntimeException('Mat khau qua ngan.');
    $stmt = db()->prepare("INSERT INTO tai_khoan (email, mat_khau_bam, vai_tro) VALUES (?, ?, 'quan_tri')");
    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
    echo "Da tao tai khoan quan tri.\n";
} catch (Throwable $e) { fwrite(STDERR, "Khong tao duoc admin: " . $e->getMessage() . "\n"); exit(1); }
