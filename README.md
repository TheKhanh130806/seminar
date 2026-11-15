# IS207.R12 — Seminar GrapesJS với PHP

**Môn học:** IS207.R12  
**Giảng viên hướng dẫn:** Vũ Minh Sang  
**Đề tài seminar:** Tích hợp GrapesJS vào website PHP

## Thành viên và phân công công việc

| STT | Thành viên | Nội dung phân công |
| --- | --- | --- |
| 1 | Kiên | Tổng quan và giới thiệu; làm slide |
| 2 | Kiệt | Chức năng cơ bản và ứng dụng; cài đặt |
| 3 | Khôi | Kiến trúc và nguyên lý hoạt động |
| 4 | Khánh | Chuẩn bị Code và chạy Demo |

## Giới thiệu đề tài

Seminar giới thiệu cách tích hợp **GrapesJS** vào website PHP để xây dựng trình chỉnh sửa giao diện trực quan. Người quản trị có thể kéo thả các khối nội dung, chỉnh sửa chữ, hình ảnh và kiểu dáng ngay trên trình duyệt, sau đó lưu bản nháp hoặc xuất bản lên trang chủ.

Dự án sử dụng website cửa hàng thực phẩm **LeaFood** làm ví dụ minh họa. GrapesJS được tải qua CDN, frontend gửi project JSON cùng HTML/CSS đến backend PHP, còn MySQL quản lý tài khoản và quyền truy cập trang quản trị.

### Mục tiêu

- Tìm hiểu cách khởi tạo GrapesJS qua CDN và cấu hình các khối kéo thả.
- Minh họa việc chỉnh sửa giao diện và lấy mã HTML/CSS trên trình duyệt.
- Kết nối trình chỉnh sửa với PHP để lưu, tải lại và xuất bản nội dung.
- Sử dụng đăng nhập, phân quyền quản trị và CSRF token để kiểm soát thao tác lưu.

### Nội dung demo

1. Đăng nhập bằng tài khoản quản trị và mở trình chỉnh sửa.
2. Kéo thả khối nội dung, sửa chữ, ảnh hoặc kiểu dáng; xem giao diện ở kích thước máy tính, máy tính bảng và điện thoại.
3. Bấm **Lưu nháp** để gửi project JSON, HTML và CSS về PHP; mở lại editor để tiếp tục chỉnh sửa.
4. Bấm **Xuất bản trang**, sau đó tải lại trang chủ để xem nội dung đã áp dụng.
5. Minh họa **Khôi phục mặc định** khi cần quay về mẫu giao diện gốc.

Phạm vi hiện tại: nội dung thiết kế được lưu trong file `storage/content.php` trên máy chủ; MySQL chỉ lưu tài khoản và vai trò. Lưu thiết kế vào MySQL và upload ảnh lên máy chủ là các hướng phát triển tiếp theo.

## Chạy dự án

1. Bật MySQL trong XAMPP. Dùng phpMyAdmin hoặc MySQL CLI để import `database/schema.sql`. File này tạo database, bảng tài khoản và tài khoản admin demo. Nếu bảng `tai_khoan` cũ chưa có cột `vai_tro`, chạy SQL sau trước khi import:

   ```sql
   USE cua_hang_thuc_pham;
   ALTER TABLE tai_khoan
     ADD COLUMN vai_tro ENUM('khach_hang', 'quan_tri') NOT NULL DEFAULT 'khach_hang';
   ```

2. Mặc định PHP kết nối `127.0.0.1:3306`, database `cua_hang_thuc_pham`, tài khoản `root`, mật khẩu trống (XAMPP local). Có thể đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD` trong terminal trước khi chạy.
3. Sau khi import SQL, đăng nhập bằng tài khoản quản trị demo:

   - Email: `admin@gmail.com`
   - Mật khẩu: `mk123456789101112`

   Mật khẩu trong SQL được lưu dưới dạng bcrypt. Nếu email này đã tồn tại, đoạn `INSERT ... ON DUPLICATE KEY UPDATE` sẽ cập nhật mật khẩu và quyền `quan_tri`. Chỉ sửa file SQL chưa làm thay đổi database đang chạy; để cập nhật admin hiện có, chạy đoạn `INSERT` này trong phpMyAdmin. Tài khoản demo dùng cho môi trường local.

   Nếu muốn tạo thêm quản trị viên với email khác, chạy từ PowerShell ở thư mục dự án:

   ```powershell
   & F:\xampp\php\php.exe backend/create-admin.php
   ```

   Nhập email mới và mật khẩu ít nhất 12 ký tự khi được hỏi. Lệnh này chỉ thêm tài khoản, không ghi đè hoặc nâng quyền tài khoản cũ.

4. Chạy máy chủ phát triển PHP (không cần di chuyển dự án vào htdocs):

   ```powershell
   & F:\xampp\php\php.exe -S localhost:8001 -t F:\web\seminar
   ```

5. Mở `http://localhost:8001/frontend/html/index.php`. Đăng nhập qua `http://localhost:8001/frontend/html/login.html`. Tài khoản quản trị được chuyển đến `admin.php`.

## Chỉnh sửa giao diện (Full Trang)

- GrapesJS 0.22.8 được import qua jsDelivr, cần Internet để tải thư viện. Không cần Node.js.
- Hỗ trợ chỉnh sửa **toàn bộ trang chủ** trên một khung nhìn duy nhất: nhấp đúp sửa chữ (header, menu, banner, danh mục, thẻ sản phẩm, khuyến mãi, giới thiệu, footer), chọn thành phần để sửa kiểu dáng, hoặc dùng trình quản lý khối để kéo thả chữ, ảnh, liên kết, khối nội dung, thẻ banner và thẻ sản phẩm.
- Ảnh dùng URL; chưa có chức năng upload ảnh lên máy chủ.
- **Lưu nháp** gửi project JSON, HTML và CSS của toàn trang về `backend/editor.php` để lưu trên máy chủ và mở lại, chưa đổi trang công khai. **Xuất bản trang** lưu và áp dụng toàn bộ trang vào `index.php`.
- **Khôi phục mặc định** thay toàn bộ trang trong editor bằng mẫu gốc từ `index.html`, đồng thời xóa CSS và lịch sử chỉnh sửa trong editor. Cần bấm **Lưu nháp** hoặc **Xuất bản trang** để lưu; thao tác khôi phục tự nó không đổi dữ liệu trên máy chủ hay trang công khai.
- Các khung chức năng động bắt buộc (`#productGrid`, `#cartPanel`, `#cartItems`, `#cartCount`, `#cartTotal`, `#panelCartTotal`, `#closeCart`, `#overlay`) được khóa xóa (`removable: false`) trong editor và được backend kiểm tra trước khi lưu để đảm bảo tính năng tìm kiếm, lọc danh mục và giỏ hàng luôn hoạt động. Các thẻ `.product-card` bên trong `#productGrid` vẫn có thể chỉnh sửa tên, giá, emoji hoặc nhân bản trực tiếp.
- `index.html` là mẫu gốc; `index.php` hiển thị nội dung đã xuất bản.
- Dữ liệu giao diện lưu trong `storage/content.php`; MySQL hiện dùng cho tài khoản và phân quyền, chưa lưu nội dung editor. Cần quyền ghi thư mục `storage`. Không sửa file này thủ công. Hiện phù hợp một quản trị viên chỉnh sửa tại một thời điểm.
- API đọc/lưu yêu cầu session có quyền `quan_tri` được kiểm tra lại từ database; thao tác ghi cần CSRF token. Backend tự kiểm tra quyền, không dựa vào JavaScript.
- Chỉ cấp quyền quản trị cho người tin cậy: họ có quyền xuất bản HTML/CSS. Máy chủ PHP tích hợp và tài khoản MySQL root chỉ dùng phát triển local.

Tài liệu: https://grapesjs.com/docs/getting-started.html và https://grapesjs.com/docs/modules/Storage.html

## Kiểm tra hồi quy

```powershell
& F:\xampp\php\php.exe tests/editor-content.php
& F:\xampp\php\php.exe -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -d opcache.file_update_protection=0 tests/editor-content.php
```

Kiểm tra dùng file lưu trữ tạm, không thay đổi nội dung đang xuất bản hay tài khoản database.
