# LeaFood — PHP và GrapesJS

## Chạy dự án

1. Bật MySQL trong XAMPP. Import `database/schema.sql`; nếu bảng cũ chưa có vai trò thì chạy `database/migrations/001_them_vai_tro.sql` một lần.
2. Mặc định PHP kết nối `127.0.0.1:3306`, database `cua_hang_thuc_pham`, tài khoản `root`, mật khẩu trống (XAMPP local). Có thể đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD` trong terminal trước khi chạy.
3. Từ PowerShell ở thư mục dự án, tạo tài khoản quản trị:

   ```powershell
   & F:\xampp\php\php.exe backend/create-admin.php
   ```

   Nhập email và mật khẩu mới khi được hỏi. Không dùng tài khoản có sẵn: lệnh chỉ thêm tài khoản, không ghi đè hoặc nâng quyền tài khoản cũ.

4. Chạy máy chủ phát triển PHP (không cần di chuyển dự án vào htdocs):

   ```powershell
   & F:\xampp\php\php.exe -S localhost:8001 -t F:\web\seminar
   ```

5. Mở `http://localhost:8001/frontend/html/index.php`. Đăng nhập qua `http://localhost:8001/frontend/html/login.html`. Tài khoản quản trị được chuyển đến `admin.php`.

## Chỉnh sửa giao diện (Full Trang)

- GrapesJS 0.22.8 được import qua jsDelivr, cần Internet để tải thư viện. Không cần Node.js.
- Hỗ trợ chỉnh sửa **toàn bộ trang chủ** trên một khung nhìn duy nhất: nhấp đúp sửa chữ (header, menu, banner, danh mục, thẻ sản phẩm, khuyến mãi, giới thiệu, footer), chọn thành phần để sửa kiểu dáng, hoặc dùng trình quản lý khối để kéo thả chữ, ảnh, liên kết, khối nội dung, thẻ banner và thẻ sản phẩm.
- Ảnh dùng URL; chưa có chức năng upload ảnh lên máy chủ.
- **Lưu nháp** lưu project JSON của toàn trang để mở lại, chưa đổi trang công khai. **Xuất bản trang** lưu và áp dụng toàn bộ trang vào `index.php`.
- **Khôi phục mặc định** thay toàn bộ trang trong editor bằng mẫu gốc từ `index.html`, đồng thời xóa CSS và lịch sử chỉnh sửa trong editor. Cần bấm **Lưu nháp** hoặc **Xuất bản trang** để lưu; thao tác khôi phục tự nó không đổi dữ liệu trên máy chủ hay trang công khai.
- Các khung chức năng động bắt buộc (`#productGrid`, `#cartPanel`, `#cartItems`, `#cartCount`, `#cartTotal`, `#panelCartTotal`, `#closeCart`, `#overlay`) được khóa xóa (`removable: false`) trong editor và được backend kiểm tra trước khi lưu để đảm bảo tính năng tìm kiếm, lọc danh mục và giỏ hàng luôn hoạt động. Các thẻ `.product-card` bên trong `#productGrid` vẫn có thể chỉnh sửa tên, giá, emoji hoặc nhân bản trực tiếp.
- `index.html` là mẫu gốc; `index.php` hiển thị nội dung đã xuất bản.
- Dữ liệu giao diện lưu trong `storage/content.php`, không thêm bảng database. Cần quyền ghi thư mục `storage`. Không sửa file này thủ công. Hiện phù hợp một quản trị viên chỉnh sửa tại một thời điểm.
- API đọc/lưu yêu cầu session có quyền `quan_tri` được kiểm tra lại từ database; thao tác ghi cần CSRF token. Backend tự kiểm tra quyền, không dựa vào JavaScript.
- Chỉ cấp quyền quản trị cho người tin cậy: họ có quyền xuất bản HTML/CSS. Máy chủ PHP tích hợp và tài khoản MySQL root chỉ dùng phát triển local.

Tài liệu: https://grapesjs.com/docs/getting-started.html và https://grapesjs.com/docs/modules/Storage.html

## Kiểm tra hồi quy

```powershell
& F:\xampp\php\php.exe tests/editor-content.php
& F:\xampp\php\php.exe -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -d opcache.file_update_protection=0 tests/editor-content.php
```

Kiểm tra dùng file lưu trữ tạm, không thay đổi nội dung đang xuất bản hay tài khoản database.
