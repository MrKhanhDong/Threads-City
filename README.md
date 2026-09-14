# Personal Blog UI (PHP)

Giao diện web desktop mô phỏng Figma **Meta Threads Integration**, được viết bằng PHP thuần, HTML, CSS và JavaScript. Phiên bản này dùng dữ liệu mẫu; chưa kết nối database và chưa xử lý xác thực thật.

## Yêu cầu

- PHP 8.0 trở lên.
- Có thể dùng XAMPP, Laragon hoặc PHP CLI.

## Chạy dự án

### XAMPP/Laragon

1. Đặt thư mục dự án trong `htdocs` (XAMPP) hoặc `www` (Laragon).
2. Bật Apache.
3. Mở `http://localhost/Doan/` (đổi `Doan` theo tên thư mục thực tế).

### PHP CLI

```bash
php -S localhost:8000
```

Sau đó mở `http://localhost:8000`.

## Các màn hình đã có

- Trang chủ với feed và tab “Dành cho bạn / Mới nhất”.
- Tìm kiếm và lọc theo chủ đề.
- Danh sách chủ đề, bài viết đã lưu.
- Trang cá nhân và chỉnh sửa hồ sơ.
- Chi tiết bài viết và bình luận lồng nhau.
- Modal đăng nhập/đăng ký, tạo bài viết.
- Khu quản trị: tổng quan, biểu đồ và bảng CRUD cho bài viết, chủ đề, thẻ, bình luận, người dùng.

Các thao tác thích, lưu, theo dõi, đăng nhập, bình luận, xóa/khóa trong admin hiện chỉ thay đổi giao diện phía trình duyệt.

## Cấu trúc

```text
app/
  data/mock_data.php       Dữ liệu mẫu, sẽ thay bằng dữ liệu từ database
  views/pages/             Các màn hình
  views/partials/          Thành phần dùng chung
public/
  css/style.css
  js/app.js
  images/
index.php                  Front controller và định tuyến trang
```

Khi có sơ đồ lớp và database, phần `app/data/mock_data.php` có thể được thay bằng Model/Repository mà không cần đổi cấu trúc giao diện.
