# PHP File Manager

Website PHP + MySQL + Bootstrap dùng để upload và quản lý tập tin, có đăng nhập, phân quyền, link public, tải ZIP, thùng rác, quota dung lượng và activity logs.

## Kiến Trúc

Project được tổ chức theo mô hình MVC đơn giản:

```text
app/
├── bootstrap.php
└── config.php
src/
├── Controllers/
├── Core/
├── Models/
└── Views/
public/
├── index.php
├── login.php
├── files.php
└── ...
```

- `public/`: các route/entrypoint, chỉ gọi controller.
- `src/Controllers`: nhận request, gọi model và trả view/response.
- `src/Models`: xử lý dữ liệu và thao tác database.
- `src/Views`: giao diện Bootstrap.
- `src/Core`: database, authentication, CSRF, helper và view renderer.

## Route Chính

- `/login.php`: đăng nhập.
- `/forgot_password.php`: yêu cầu link đặt lại mật khẩu qua email.
- `/reset_password.php`: đặt lại mật khẩu bằng token.
- `/files.php`: danh sách file, upload, tìm kiếm và lọc.
- `/trash.php`: thùng rác file đã xóa.
- `/profile.php`: thông tin tài khoản và đổi mật khẩu.
- `/users.php`: quản lý user cho admin.
- `/logs.php`: activity logs cho admin.

## Yêu Cầu

- PHP 8.1+
- MySQL 5.7+ hoặc MariaDB
- PHP extensions: `pdo_mysql`, `fileinfo`, `zip`
- PHP extension tùy chọn: `gd` để tạo thumbnail

## Cài Đặt Nhanh

1. Tạo database MySQL.
2. Import file `database/schema.sql`.
3. Tạo file `.env` từ `.env.example` và sửa thông tin cấu hình:

```bash
cp .env.example .env
```

Trên Windows có thể copy file `.env.example` thành `.env` thủ công.

4. Tạo admin đầu tiên:

```bash
php database/seed_admin.php admin@example.com 123456 "Admin"
```

5. Chạy PHP built-in server từ thư mục project:

```bash
php -S localhost:8000 -t public
```

6. Mở `http://localhost:8000`.

## Role

- `admin`: xem, tải, nén ZIP, xóa, khôi phục, quản lý toàn bộ file và quản lý user.
- `user`: upload, xem, tải, nén ZIP và xóa file của chính mình.

## Tính Năng

- Login/logout bằng session.
- Upload nhiều file trong một lần.
- Hỗ trợ ảnh JPG/PNG/GIF/WebP/AVIF/BMP, PDF, TXT, CSV, JSON, XML, YAML, Markdown, RTF, ZIP/RAR/7Z/TAR/GZ, Microsoft Office, OpenDocument, audio, video và font cơ bản.
- Kiểm tra MIME thật và dung lượng từng file.
- Quota dung lượng theo user. Tài khoản admin dùng `storage_limit = NULL` để biểu thị không giới hạn.
- Tạo thumbnail cho file ảnh nếu server có extension `gd`.
- Xem file trực tiếp qua `view.php` nếu trình duyệt hỗ trợ MIME tương ứng.
- Link public trực tiếp bằng token.
- Bật/tắt link public cho từng file.
- Tải file gốc.
- Chọn nhiều file và tải ZIP.
- Chọn nhiều file và xóa hàng loạt.
- Soft delete, thùng rác, khôi phục và xóa vĩnh viễn.
- File trong thùng rác quá 7 ngày sẽ bị xóa vĩnh viễn.
- Tìm kiếm/lọc file theo tên file, ngày upload và user.
- Admin tạo/sửa user, khóa user, đổi role, reset mật khẩu và chỉnh quota.
- Log các hành động thay đổi dữ liệu như upload, xóa, khôi phục, bật/tắt link public, tạo/sửa user và đổi mật khẩu.
- User đổi mật khẩu cá nhân trong trang Profile.
- User có thể yêu cầu link quên mật khẩu qua email; token được lưu dạng hash và có thời hạn.

## Database

File import database đầy đủ nằm tại:

```text
database/schema.sql
```

File này đã bao gồm toàn bộ bảng/cột/index cần thiết cho source hiện tại:

- `users`
- `files`, bao gồm cột `file_type` để phân loại `image`, `document`, `archive`, `spreadsheet`, `presentation`, `audio`, `video`, `font`
- `password_resets`, lưu hash token đặt lại mật khẩu
- `activity_logs`

Các cột thời gian như `created_at`, `updated_at`, `deleted_at`, `expires_at`, `used_at` được lưu bằng Unix timestamp. Khi hiển thị trên giao diện, hệ thống format lại theo dạng `dd/mm/YYYY HH:ii:ss` theo timezone cấu hình trong `APP_TIMEZONE`.

Không cần chạy migration riêng.

## Dọn Thùng Rác Tự Động

Mặc định file trong thùng rác quá 7 ngày sẽ bị xóa vĩnh viễn. Có 2 cơ chế:

- Khi user vào trang Files hoặc Trash, hệ thống sẽ tự dọn các file quá hạn.
- Có thể chạy script CLI để gắn cron/job trên server:

```bash
php database/cleanup_trash.php
```

Số ngày giữ file trong thùng rác có thể chỉnh trong `.env`:

```env
TRASH_RETENTION_DAYS="7"
```

## Cấu Hình `.env`

Các tham số chính:

```env
APP_NAME="PHP File Manager"
APP_URL="http://localhost:8000"
APP_TIMEZONE="Asia/Ho_Chi_Minh"

DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_DATABASE="php_file_manager"
DB_USERNAME="root"
DB_PASSWORD=""

UPLOAD_MAX_SIZE_MB="10"
UPLOAD_MAX_FILES_PER_UPLOAD="10"
DEFAULT_USER_STORAGE_LIMIT_MB="500"
TRASH_RETENTION_DAYS="7"

MAIL_FROM_EMAIL="no-reply@example.com"
MAIL_FROM_NAME="PHP File Manager"
MAIL_HOST="smtp.gmail.com"
MAIL_PORT="587"
MAIL_USERNAME=""
MAIL_PASSWORD=""
MAIL_ENCRYPTION="tls"
PASSWORD_RESET_MINUTES="30"
```

Chức năng quên mật khẩu gửi mail qua SMTP. Nếu dùng Gmail, cần bật 2-Step Verification và tạo App Password, sau đó đặt `MAIL_USERNAME` là email Gmail và `MAIL_PASSWORD` là App Password. Với SMTP SSL port 465, đặt `MAIL_ENCRYPTION="ssl"`.

## Ghi Chú Bảo Mật

- File upload được lưu ngoài thư mục `public`.
- Mỗi thao tác xem/tải/xóa đều kiểm tra quyền ở backend.
- File được đổi tên khi lưu để tránh trùng tên và tránh lộ tên gốc.
- Các form POST dùng CSRF token.
