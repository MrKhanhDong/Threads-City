# Threads City — blog cá nhân

Website **Laravel 13 / PHP 8.3+ / Blade / MySQL 8**, trắng–đen, nền trắng chủ đạo. Một chủ blog viết bài dài; độc giả đọc, lưu bài và bình luận. **Không phải mạng xã hội**: không follow, like, repost hoặc feed cá nhân hóa. Website ưu tiên desktop; không có ứng dụng mobile.

## Chạy ngay trên máy hiện tại

MySQL đang dùng cổng 3306. Database **threads_city_blog** đã được tạo, migrate và nạp dữ liệu mẫu; database cũ và `../sql/database_test.sql` giữ nguyên.

```powershell
cd D:\HocTap\LTMNM\Doan\code
powershell -ExecutionPolicy Bypass -File .\start.ps1
```

Mở http://127.0.0.1:8000. Script dùng PHP trong PATH hoặc runtime cục bộ `../.tools/php/php.exe`. Runtime cục bộ không nằm trong Git.

| Tài khoản demo    | Mật khẩu     | Quyền               |
| ----------------- | ------------ | ------------------- |
| admin@blog.local  | Admin@12345  | Chủ blog / quản trị |
| reader@blog.local | Reader@12345 | Độc giả             |

Chỉ dùng tài khoản trên để học và demo. Đổi mật khẩu ở **Tài khoản của tôi** trước khi cho người khác truy cập. Seeder bị chặn khi APP_ENV=production.

## Cài lại trên máy khác

Yêu cầu PHP 8.3+, Composer 2, MySQL 8; extension ctype, curl, dom, fileinfo, filter, hash, intl, mbstring, openssl, pdo_mysql, session, tokenizer, xml. `pdo_sqlite` cần cho bộ test mặc định. Không cần Node/npm để chạy website.

Trong php.ini, đặt `upload_max_filesize=6M` và `post_max_size=8M` (hoặc cao hơn), rồi khởi động lại PHP/Apache. Runtime local đã được cấu hình để form nhận ảnh bìa tối đa 5 MB.

1. Chạy `composer install` trong thư mục code.
2. **Nếu chưa có .env**, sao chép `.env.example` thành `.env`; chạy `php artisan key:generate`.
3. Tạo database riêng bằng MySQL:

```sql
CREATE DATABASE threads_city_blog
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Sửa DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD trong `.env` theo máy bạn. Nên dùng user MySQL riêng, không dùng root khi triển khai.
5. Chạy:

```powershell
php artisan config:clear
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Dùng **migrate** để nâng schema; không chạy `migrate:fresh` trên dữ liệu đang sử dụng. Seeder có thể chạy lại, không ghi đè mật khẩu/bài đã sửa.

Với Apache/XAMPP/Laragon, document root phải là **public/**, không phải gốc repo. PHP 8.2 không đủ. Upload qua route `/media/...`, không cần `storage:link`; không tạo symlink public/storage vì sẽ bỏ qua kiểm tra ảnh của bài nháp.

### Tùy chọn import SQL

`database/sql/threads_city_blog.sql` là bản xuất MySQL mới, gồm schema và dữ liệu demo. Chỉ import vào **database mới, trống**, được chọn trước trong phpMyAdmin hoặc MySQL CLI; không import chồng lên dữ liệu hiện có. File không chứa DROP DATABASE / DROP TABLE. Sau import không cần seed lại, nhưng vẫn cần cấu hình .env và APP_KEY.

**Không dùng SQL cũ `../sql/database_test.sql` cho phiên bản này:** tên khóa/cột khác, có bảng mạng xã hội và lệnh DROP DATABASE. Xem [đối chiếu schema](docs/DATABASE.md).

## Chức năng đã triển khai

- Trang chủ bài nổi bật; danh sách bài có phân trang, tìm trong tiêu đề/nội dung/thẻ, lọc chuyên mục/thẻ.
- Bài dài, mục lục từ dòng `## Tiêu đề`, bài liên quan, đếm lượt xem một lần/bài/phiên/ngày (giờ Việt Nam); admin đọc/xem trước không cộng lượt.
- Giới thiệu chủ blog, avatar, tên blog và thông tin liên hệ tùy chỉnh.
- Đăng ký độc giả, đăng nhập/ghi nhớ, đăng xuất, sửa tài khoản/đổi mật khẩu, quên/đặt lại mật khẩu.
- Đọc sau lưu trong MySQL theo tài khoản, không dùng localStorage.
- Bình luận thật; độc giả chỉ xem bình luận đã duyệt và lời nhắn của chính mình.
- Khu quản trị bảo vệ bằng session và quyền admin; độc giả nhận 403.
- CRUD bài viết, chuyên mục, thẻ; slug tự tạo và duy nhất; nháp/xuất bản/lưu trữ; bài nổi bật; tìm/lọc thư viện.
- Upload/thay/xóa ảnh bìa và avatar; giới hạn dung lượng và loại ảnh. Ảnh bìa upload của bài chưa công khai không phục vụ cho khách.
- Duyệt/từ chối/xóa bình luận, xem/khóa/mở khóa độc giả; khóa tài khoản chặn phiên ở request tiếp theo.
- Thùng rác bài viết: xóa mềm giữ nội dung, ảnh, thẻ, bình luận, bookmark và lịch sử lượt xem; tìm kiếm/phân trang; khôi phục về **bản nháp**, không tự xuất bản lại. Xóa vĩnh viễn chỉ trong thùng rác, cần tích ô xác nhận; dữ liệu liên quan và ảnh upload được dọn.
- Dashboard biểu đồ lượt xem theo ngày với bộ lọc **7/30/90 ngày**, ngày trống bằng 0, bảng số liệu đọc được, top 5 bài đọc nhiều trong kỳ và bình luận chờ duyệt. FK và transaction giữ dữ liệu nhất quán.
- Form hoạt động khi tắt JavaScript; JS chỉ thêm preview, đếm từ, xác nhận xóa và sao chép liên kết.
- 4 bài công khai, 1 bản nháp, 3 chuyên mục và bình luận mẫu để demo.

Nội dung bài dùng **văn bản thuần + tiêu đề ##**, không phải trình soạn WYSIWYG hay Markdown đầy đủ. HTML luôn được escape.

### Nâng cấp thùng rác và thống kê

Backup MySQL và `storage/app/public`, rồi chạy `php artisan migrate` khi pull phiên bản mới. Migration `2026_10_11_000002_add_post_trash_and_daily_views` chỉ thêm `posts.deleted_at` và bảng `post_daily_views`, không xóa bài hiện có. Không dùng `migrate:fresh`.

Thùng rác: `/admin/bai-viet/thung-rac`. Dashboard: `/admin?range=7` (hoặc 30/90). Bài trong thùng rác bị ẩn khỏi trang đọc, Đọc sau, hàng chờ bình luận và thống kê; khôi phục giữ lịch sử nhưng cần xuất bản lại để độc giả đọc được. Slug vẫn được giữ chỗ để khôi phục an toàn.

Lượt xem theo ngày bắt đầu ghi từ lúc nâng cấp; **không bịa lịch sử ngày từ số lượt tích lũy cũ**. Số tích lũy và số trong kỳ có nhãn riêng. Không lưu IP hay dữ liệu định danh độc giả trong bảng thống kê. Xóa vĩnh viễn không có thao tác hoàn tác.

## Email đặt lại mật khẩu

Mặc định **MAIL_MAILER=log** cho môi trường local: Laravel ghi email/liên kết reset vào `storage/logs/laravel.log`, **chưa gửi email ra Internet**. Khi demo, mở log trên máy của bạn để lấy link mới nhất; không chia sẻ log/token.

Để gửi email thật, cấu hình SMTP trong .env (MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS và TLS theo nhà cung cấp), rồi `php artisan config:clear`. Không commit mật khẩu SMTP. Token hết hạn sau 60 phút và chỉ dùng một lần.

## Kiểm thử

Bộ PHPUnit có **68 tests / 544 assertions**, đã chạy thành công trên SQLite in-memory và MySQL 8.0.46. Có **8 bài test HTTP/DOM** bổ sung: session/CSRF thực, đăng bài–duyệt–thùng rác–khôi phục nháp–xuất bản lại–xóa vĩnh viễn, biểu đồ/bảng 7/30/90 ngày, preview an toàn, form native, confirmation, clipboard và xem trước upload. Đây **không** phải screenshot test.

```powershell
php artisan test
php artisan route:list
php artisan view:cache
composer validate --strict
```

Nếu PHP không có trong PATH, thay `php` bằng `& '..\.tools\php\php.exe'`. Composer cục bộ: `& '..\.tools\php\php.exe' '..\.tools\composer.phar' validate --strict`.

### Test MySQL riêng

Tạo database **threads_city_blog_test**, rồi:

```powershell
powershell -ExecutionPolicy Bypass -File .\test-mysql.ps1
```

Script nhận HostName/Port/UserName, lấy mật khẩu từ biến môi trường DB_PASSWORD khi cần. **Test sẽ xóa và dựng lại các bảng của database test này**; không đặt dữ liệu quan trọng vào đó. TestCase từ chối database khác hoặc DB_URL; tuyệt đối không dùng database ứng dụng cho RefreshDatabase.

### HTTP/DOM

Cần Node 18+ và server local đang chạy với tài khoản demo chưa đổi:

```powershell
npm.cmd ci --ignore-scripts
npm.cmd run test:ui
```

Test tạo một bài và bình luận tạm trong database demo, dọn bài bằng finally; không chạy trên production. Trình duyệt tự động của môi trường Codex hiện lỗi khởi tạo sandbox, nên **chưa có screenshot QA**. Hãy dùng [checklist demo](docs/DEMO.md) để rà giao diện desktop thực tế.

## Tài liệu đồ án và cấu trúc

- [Báo cáo kỹ thuật / phạm vi đồ án](docs/PROJECT_REPORT.md)
- [Schema MySQL, ERD, đối chiếu SQL cũ](docs/DATABASE.md)
- [Kiến trúc MVC và sơ đồ lớp](docs/ARCHITECTURE.md)
- [Kịch bản demo / kiểm tra trước khi nộp](docs/DEMO.md)

```text
app/Http/Controllers/      Trang đọc, xác thực, độc giả và quản trị
app/Http/Middleware/       Phân quyền và chặn tài khoản khóa
app/Http/Requests/         Validation bài viết
app/Models/                User, Post (SoftDeletes), PostDailyView, Category, Tag, Comment
app/Repositories/          Truy vấn blog; DemoBlogRepository chỉ phục vụ seeder
app/Services/              PostWriter, PostViewRecorder, BlogAnalytics
app/Support/ArticleText    Tách đoạn / mục lục / thời gian đọc
database/migrations/      Schema là nguồn chuẩn
database/seeders/          Dữ liệu demo, idempotent
database/sql/              SQL export cùng schema
resources/views/          Blade public/auth/account/admin
public/css, public/js/    Giao diện trắng–đen, progressive enhancement
tests/                    Unit, Feature, HTTP/DOM
```

Dependency chốt trong composer.lock/package-lock.json. Bản PHP thuần trước vẫn có trong lịch sử Git (`b160577`). Nhánh bàn giao bản Laravel/backend và hai chức năng mới: **Dat**.

## Trước khi triển khai hoặc nộp

Đồ án đã triển khai phạm vi **blog cá nhân** kể trên; chưa thể khẳng định đạt toàn bộ rubric khi chưa có đề/các tiêu chí chấm chính thức. Tự điền thông tin trường, nhóm, giảng viên vào báo cáo, thay nội dung demo và kiểm tra screenshot trên máy bạn.

Triển khai Internet: APP_ENV=production, APP_DEBUG=false, HTTPS, SESSION_SECURE_COOKIE=true, user MySQL riêng, SMTP thật, backup database + storage/app/public; không công khai .env, logs hoặc thư mục vendor. Không dùng `artisan serve` làm server production.
