-- LeaFood Organic — MySQL 8+
-- Chạy bằng MySQL CLI: mysql -u root -p < database/schema.sql
-- Hoặc import file này bằng phpMyAdmin.

CREATE DATABASE IF NOT EXISTS cua_hang_thuc_pham
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cua_hang_thuc_pham;

CREATE TABLE IF NOT EXISTS tai_khoan (
  ma_tai_khoan BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(254) NOT NULL,
  mat_khau_bam VARCHAR(255) NOT NULL,
  vai_tro ENUM('khach_hang', 'quan_tri') NOT NULL DEFAULT 'khach_hang',
  ngay_tao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ma_tai_khoan),
  UNIQUE KEY duy_nhat_email (email)
) ENGINE=InnoDB;

-- Tài khoản quản trị cho demo; mật khẩu được băm bằng bcrypt.
-- Nếu email đã tồn tại, cập nhật mật khẩu và quyền quản trị.
INSERT INTO tai_khoan (email, mat_khau_bam, vai_tro)
VALUES (
  'admin@gmail.com',
  '$2y$10$B9KafP8riorLJ0PHKiLijurtnb9iqdrsr/9aI2LGQ4F03oCX/hXki',
  'quan_tri'
)
ON DUPLICATE KEY UPDATE
  mat_khau_bam = VALUES(mat_khau_bam),
  vai_tro = VALUES(vai_tro);

-- Backend cần loại bỏ khoảng trắng ở hai đầu và chuyển địa chỉ thư điện tử
-- sang chữ thường trước khi lưu và tìm kiếm.
-- Chỉ lưu mật khẩu đã băm bằng Argon2id hoặc bcrypt từ backend.
-- Khi đăng nhập: tìm tài khoản theo email bằng truy vấn có tham số,
-- rồi dùng hàm verify của thư viện băm để kiểm tra mật khẩu.
-- Không so sánh mật khẩu dạng văn bản trực tiếp trong SQL.
-- vai_tro: khach_hang = khách hàng, quan_tri = quản trị viên.
-- Backend phải kiểm tra vai trò trước khi thực hiện chức năng quản trị.
-- Khi đăng ký, backend tự gán khach_hang, không nhận vai trò từ trình duyệt.
-- Nếu bảng đã tồn tại, chạy migrations/001_them_vai_tro.sql thay vì import lại.
