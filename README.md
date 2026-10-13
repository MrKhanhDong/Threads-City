# Mộc Journal — đồ án blog cá nhân

Website PHP/Laravel + Blade + MySQL của Laragon, theo [word/doanltmnm.docx](word/doanltmnm.docx). Chỉ làm website, không làm app mobile. Admin/tác giả viết bài; Reader đọc, thích/lưu bài, bình luận và trả lời, không đăng bài như mạng xã hội. Theo yêu cầu cập nhật 13/10/2026, bình luận mới hiển thị ngay, không cần duyệt.

## Mở bản hiện tại

Project: `D:/linh_tinh/laragon/www/Doan`. Bật Apache và MySQL trong Laragon rồi mở **http://Doan.test**. Trang quản trị: **http://Doan.test/admin**.

Virtual host tại `D:/linh_tinh/laragon/etc/apache2/sites-enabled/Doan.test.conf` phải trỏ DocumentRoot và Directory vào **Doan/public**, không phải thư mục gốc project. Đã kiểm tra URL thực tế trả về trang chủ/đăng nhập, CSS/JS và TinyMCE; `/.env`, `/composer.json`, `/word/doanltmnm.docx` trả 404. Sau khi đổi virtual host, Reload Apache trong Laragon.

Đã triển khai các màn hình và luồng cốt lõi, có thể bắt đầu test local. Email local qua Mailpit và backup/restore DB đã được chạy thử. **Chưa nghiệm thu cuối cùng**: thao tác editor/giao diện trong browser chưa được kiểm tra trực quan; Google OAuth và SMTP gửi ra Internet chưa kết nối dịch vụ thật. Source bàn giao tại [Threads-City, nhánh Dat](https://github.com/MrKhanhDong/Threads-City/tree/Dat) theo yêu cầu mới; không triển khai website public. Chi tiết trong [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md).

## Chức năng

- Public: trang chủ, bài nổi bật, danh sách/chi tiết, tìm kiếm, lọc danh mục/thẻ, phân trang, tác giả, giới thiệu, liên hệ qua email, chính sách và điều khoản.
- CMS: tạo/sửa bài, TinyMCE self-host, ảnh bìa/ảnh nội dung, danh mục/thẻ, lưu nháp, preview bản đã lưu, xuất bản/lên lịch, tự lưu nháp đã tạo sau 45 giây, phát hiện xung đột phiên bản.
- Thùng rác: xóa mềm, khôi phục về nháp, xác nhận trước khi xóa vĩnh viễn.
- Thư viện ảnh: upload JPEG/PNG/WebP, ALT, sao chép URL, không xóa ảnh đang được dùng kể cả bài trong thùng rác. Ảnh nháp không công khai.
- Độc giả: đăng ký/đăng nhập, xác minh email, đặt lại mật khẩu, hồ sơ/avatar, đổi mật khẩu, bookmark, thích/bỏ thích (mỗi người một lượt), bình luận hiển thị ngay, reply 2 cấp, sửa/xóa bình luận của mình và báo cáo.
- Quản trị: ẩn/spam/công khai lại bình luận, xử lý báo cáo, khóa/mở độc giả, cài đặt blog. Bình luận mới không cần chờ duyệt; bình luận pending từ bản cũ không tự công khai.
- Dashboard: lượt đọc theo ngày 7/30/90 ngày, bài đọc nhiều, báo cáo chưa xử lý, thống kê trạng thái bài.
- SEO: metadata, canonical, Open Graph/X Card, preview tìm kiếm/chia sẻ khi nhập trong editor, BlogPosting JSON-LD, sitemap chỉ chứa bài công khai, robots, redirect slug cũ, mục lục.
- Bảo vệ: phân quyền, CSRF, giới hạn request, mật khẩu hash, kiểm tra upload, lọc HTML/CSS nguy hiểm, thu hồi phiên sau đổi mật khẩu.

Lên lịch được áp dụng bằng điều kiện thời gian khi truy vấn; không cần cron chỉ để bài xuất hiện đúng thời điểm. Một lượt đọc/phiên/bài/ngày, không tính admin; không phải phép đếm người duy nhất hoặc hệ thống chống bot.

## Môi trường

Đã chạy trên PHP 8.3.30, Laravel 13.35, Composer 2.9.4, Node.js 22.22.0, MySQL 8.4.3. Chọn PHP >=8.3 và Node 22.22 hoặc phiên bản tương thích yêu cầu Vite trong package-lock.

Mở Terminal của Laragon tại project. Kiểm tra `php -v`, `composer --version`, `node -v`. PHP cần PDO MySQL, mbstring, openssl, fileinfo, GD, DOM/XML; Composer cần zip hoặc công cụ giải nén tương ứng. Nếu dùng đúng bản PHP hiện tại nhưng zip chưa bật:

```powershell
php -d extension=zip D:/linh_tinh/laragon/bin/composer/composer.phar install
```

## Database trong Laragon

- MySQL Laragon: `127.0.0.1:3307`, user local `root`, mật khẩu trống.
- Website: **moc_blog**; kiểm thử: **moc_blog_test**; charset utf8mb4.
- MySQL cài riêng tại 3306 không bị thay đổi và không dùng cho đồ án.
- Server chỉ lắng nghe localhost.
- Cổng client/server trong `bin/mysql/mysql-8.4.3-winx64/my.ini` và `usr/laragon.ini` đã đồng bộ 3307; MySQL X 33070.

Trong HeidiSQL/Database của Laragon chọn cổng 3307; sửa phiên kết nối nếu nó còn ghi 3306. Kiểm tra:

```sql
SELECT @@version, @@port, @@basedir, DATABASE();
CREATE DATABASE IF NOT EXISTS moc_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS moc_blog_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`@@basedir` phải trỏ vào Laragon. Schema/ERD: [docs/DATABASE.md](docs/DATABASE.md). Không cần tạo bảng tay.

## Cài trên máy khác

Lấy project mới vào thư mục riêng để không ghi đè bản cũ hoặc `.env` của bạn:

```sh
git clone --branch Dat --single-branch https://github.com/MrKhanhDong/Threads-City.git Threads-City-Laravel
cd Threads-City-Laravel
```

Trong PowerShell:

```powershell
Copy-Item -LiteralPath .env.example -Destination .env
```

Chỉnh DB theo MySQL Laragon trên máy đó. 3307 chỉ là cổng máy hiện tại. Tạo database **mới, trống** cho project này (ví dụ moc_blog); không trỏ vào database của project cũ nếu schema khác. `.env`, database thật, uploads, vendor và assets build không được đưa lên Git; migrations/seeders và lockfiles được bàn giao đầy đủ. Không ghi đè `.env` đã cấu hình hoặc tạo lại APP_KEY của hệ thống đang sử dụng.

```sh
composer install
php artisan key:generate
php artisan config:clear
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
npm ci --include=optional --ignore-scripts
npm run build
```

Seeder chỉ chạy ở local/testing, chạy lại không ghi đè nội dung/cài đặt/mật khẩu đã sửa. Không chạy `migrate:fresh` hoặc `migrate:reset` trên database website vì các lệnh đó xóa dữ liệu.

Ảnh nằm trong `storage/app/private/media` và `storage/app/private/avatars`, được phục vụ qua controller kiểm tra quyền. **Không cần storage:link** cho ảnh này; không chuyển media nháp vào thư mục public.

Nếu chưa dùng virtual host, chạy:

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Mở http://localhost:8000. Đổi APP_URL tương ứng và `php artisan config:clear` khi thay host.

## Tài khoản demo local

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin/tác giả | admin@blog.local | Admin@12345 |
| Reader đã xác minh | reader@blog.local | Reader@12345 |

Demo mới gồm 4 bài công khai, 1 bản nháp, 3 danh mục, 7 thẻ và bình luận/reply công khai. Bản hiện tại giữ nguyên 1 bình luận pending từ phiên bản trước; không tự công khai nội dung cũ hay tạo lượt xem giả.

Để tạo tác giả an toàn thay vì dùng demo:

```sh
php artisan blog:admin ten@example.com --name="Tên tác giả"
```

Mật khẩu nhập ẩn, không truyền trên command line; lệnh không tự nâng quyền tài khoản reader có sẵn. Đổi mật khẩu demo trước khi triển khai thật.

## Editor và upload

TinyMCE được copy từ node_modules vào `public/vendor/tinymce` khi build. Không phụ thuộc CDN. Heading 1–6, font/cỡ chữ, đậm/nghiêng/gạch dưới, màu chữ/nền, căn lề, danh sách, liên kết, bảng, ảnh/ALT/chú thích và full screen.

Upload tối đa 5 MB/ảnh, avatar 2 MB, không cho SVG/PHP. Client đợi upload xong trước khi lưu, báo lỗi và giữ editor nếu ảnh chưa upload được; backend kiểm tra MIME/kích thước/quan hệ và lọc HTML. Theo [hướng dẫn upload TinyMCE](https://www.tiny.cloud/docs/tinymce/latest/upload-images/).

Tự lưu chỉ bật cho bài nháp đã lưu lần đầu, có thể tắt. Preview CMS là **bản đã lưu**, không phải nội dung chưa lưu trong editor. Paste Word dùng hỗ trợ chuẩn của editor, không cài plugin PowerPaste trả phí.

Preview SEO/chia sẻ cập nhật khi nhập title/slug/excerpt/SEO/OG/canonical và chọn ảnh. Thông tin chưa lưu không xuất bản; Google/nền tảng chia sẻ có thể hiển thị khác. Font tiêu đề và editor dùng Lora bản thường/nghiêng self-host, có đầy đủ glyph tiếng Việt; license tại public/fonts/lora/OFL.txt.

## VS Code / Blade

Đã cài [extension Laravel chính thức](https://github.com/laravel/vs-code-extension) 2.0.1 và thêm .vscode/settings.json để *.blade.php được nhận là Blade; giữ kiểm tra PHP. Chạy **Developer: Reload Window** nếu tab vẫn hiện PHP. Đường dẫn PHP trong workspace dùng bản Laragon hiện tại; chỉnh lại khi chuyển máy.

sitemap.blade.php đã tách chuỗi khai báo XML để tránh parser PHP nhận nhầm thẻ mở. Sitemap HTTP thật parse XML hợp lệ và PHP đã biên dịch của toàn bộ Blade lint thành công. Cần nhìn lại IDE sau reload, chưa xác nhận trực quan mọi cảnh báo.

`TINYMCE_LICENSE_KEY=gpl` dùng chế độ GPL; xem [điều kiện cấp phép](https://www.tiny.cloud/docs/tinymce/latest/license-key/) trước khi phân phối. Không coi license MIT của Laravel là giấy phép thay thế cho TinyMCE.

## Google OAuth

Đã viết Socialite và kiểm thử bằng mock, **chưa xác minh với Google thật**.

1. Tạo OAuth consent screen và OAuth client loại Web application trong Google Cloud; thêm tài khoản test nếu ứng dụng ở chế độ Testing.
2. Khi test local, dùng **http://localhost:8000**, không đăng ký callback HTTP Doan.test. Google cho phép ngoại lệ HTTP với localhost; [quy tắc redirect URI](https://developers.google.com/identity/protocols/oauth2/web-server#uri-validation).
3. Đăng ký đúng callback `http://localhost:8000/auth/google/callback`; đặt APP_URL cùng host và cấu hình:
   - GOOGLE_CLIENT_ID: client ID thật.
   - GOOGLE_CLIENT_SECRET: secret thật, chỉ trong .env.
   - GOOGLE_REDIRECT_URI: callback khớp tuyệt đối với Google.
4. `php artisan config:clear`, chạy Artisan serve, truy cập và bắt đầu đăng nhập từ localhost:8000, không trộn cookie/session với Doan.test.

Không tự liên kết tài khoản local chưa xác minh email; không cho admin đăng nhập Google; tài khoản bị khóa bị từ chối. Google chỉ tạo Reader. Khi chưa cấu hình, nút Google báo rõ và vẫn cho đăng nhập email.

## SMTP / xác minh email / reset mật khẩu

File .env.example mặc định `MAIL_MAILER=array`: không gửi thư, không ghi token ra log, giao diện báo SMTP chưa cấu hình. Bản local hiện tại đã dùng **Mailpit SMTP 127.0.0.1:11025**, hộp thư thử **http://127.0.0.1:18025**. Thư chỉ được nhận trên máy, không gửi ra Internet. SMTP và UI của phiên Mailpit này chỉ nghe localhost; không thay đổi Mailpit khác của Laragon.

Đăng ký email bất kỳ để test, mở hộp thư thử, chọn thư và bấm liên kết xác minh. Sau đó có thể bình luận, trả lời, thích/lưu bài. Thử Quên mật khẩu theo cùng cách. Đây là xác minh qua link ký thật và reset token dùng một lần, không phải nút bỏ qua xác minh.

Nếu phiên Mailpit riêng chưa chạy, khởi động một lần trong PowerShell trên máy hiện tại:

```powershell
Start-Process -FilePath 'D:/linh_tinh/laragon/bin/mailpit/1.22.3/mailpit.exe' -ArgumentList @('--listen','127.0.0.1:18025','--smtp','127.0.0.1:11025','--database','D:/linh_tinh/laragon/www/Doan/storage/app/private/mailpit/messages.db','--max','0','--quiet') -WindowStyle Hidden
```

Trên máy khác: dùng Mailpit của máy đó, chọn cổng rảnh, tạo thư mục lưu thư riêng và sửa đường dẫn/cổng. Đặt MAIL_MAILER=smtp, MAIL_SCHEME=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=11025, MAIL_USERNAME=null, MAIL_PASSWORD=null, MAIL_PREVIEW_URL=http://127.0.0.1:18025; config:clear sau khi sửa. Không bật relay/forward ra SMTP ngoài và không mở cổng hộp thư cho LAN/Internet. Nếu dịch vụ chưa chạy, UI báo gửi thư thất bại; tài khoản vừa đăng ký vẫn được giữ.

Để gửi thư Internet, đặt MAIL_MAILER=smtp và điền MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME; xóa MAIL_PREVIEW_URL. MAIL_SCHEME dùng theo nhà cung cấp (smtp hoặc smtps), dùng credentials/app password phù hợp. Chạy `php artisan config:clear`, đăng ký tài khoản test, kiểm tra thư xác minh và reset. MAIL_TIMEOUT mặc định 10 giây. Gửi mail Internet chưa được xác minh vì chưa có credentials.

### Gửi thư thật bằng Gmail

1. Bật xác minh 2 bước cho Gmail gửi thư và tạo [App Password](https://support.google.com/accounts/answer/185833) nếu tài khoản hỗ trợ. Không dùng mật khẩu đăng nhập Google thông thường.
2. Chỉ sửa file **.env trên máy chạy website**, thay các giá trị mẫu bên dưới bằng Gmail gửi thư và App Password thật. Không gửi App Password trong chat, không commit Git; Google OAuth Client Secret cũng giữ riêng.

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your-sender@gmail.com
MAIL_PASSWORD="REPLACE_WITH_YOUR_APP_PASSWORD"
MAIL_FROM_ADDRESS=your-sender@gmail.com
MAIL_FROM_NAME="Mộc Journal"
MAIL_PREVIEW_URL=
MAIL_TIMEOUT=10
```

Đây là profile SMTP SSL theo [hướng dẫn Gmail](https://knowledge.workspace.google.com/admin/gmail/send-email-from-a-printer-scanner-or-app?hl=en), không phải cấu hình Google OAuth. Chạy `php artisan config:clear`, đăng ký bằng email thật bạn kiểm soát, kiểm tra thư xác minh (cả Spam) và thử Quên mật khẩu. Chỉ xác nhận gửi Internet thành công sau khi nhận được thư thật.

Bạn của bạn pull/clone vẫn đăng nhập được bằng tài khoản demo sau migrate/seed. Đăng ký mới cần SMTP hoạt động để nhận thư xác minh. Mỗi máy chạy website phải tự cấu hình credentials an toàn hoặc dùng cùng một server đã cấu hình; Git không chuyển mật khẩu Gmail. Khi test local, liên kết trong thư trỏ tới APP_URL của máy đó (localhost hoặc virtual host), không phải một website đã deploy.

Hiện xác minh và reset dùng **liên kết**, chưa có luồng nhập mã OTP. Gmail chỉ là nhà cung cấp gửi thư, không tự biến luồng xác minh thành OTP.

Không dùng log mailer để đưa link xác minh/reset cho người dùng. Không commit secrets, token hay .env.

## Kiểm thử

Kết quả thực tế: **110 PHP tests / 934 assertions đạt**, gồm 2 integration tests gửi SMTP thật vào Mailpit local; **6 JavaScript tests đạt**. Không warning/risky; Vite build và Blade cache đạt, 117 compiled views lint đạt, 67 routes. Kết quả npm audit trước đó không báo lỗ hổng; không phải bảo đảm an toàn tuyệt đối.

```sh
php vendor/bin/phpunit --exclude-group local-mail --no-progress --fail-on-risky
php artisan route:list --except-vendor
npm test
npm run build
```

`phpunit.xml` force MySQL 3307 / **moc_blog_test**, không SQLite. TestCase chặn refresh nếu driver/database không đúng. Trên máy khác cần chỉnh kết nối kiểm thử nhưng giữ tên database riêng moc_blog_test. **Tests tạo lại bảng chỉ trong database test**; không để dữ liệu quan trọng trong đó.

Suite không cần Mailpit: 108 tests / 917 assertions. Để chạy cả 2 test SMTP local, bật Mailpit riêng như trên rồi dùng PowerShell:

```powershell
$env:PAO_DISABLE='true'
$env:RUN_LOCAL_MAIL_TESTS='1'
php vendor/bin/phpunit --no-progress --fail-on-risky --display-all-issues
```

Không bật RUN_LOCAL_MAIL_TESTS nếu chưa chạy Mailpit; nhóm local-mail sẽ skip nếu chạy suite mặc định mà chưa opt-in. Các thư integration có người nhận mail-test-...@example.test và chỉ liên quan DB test; không xóa thư khác trong Mailpit.

Đã kiểm tra HTTP thật trên Apache/Laragon, chưa kiểm tra trực quan qua browser hoặc Google/SMTP Internet thật. Checklist nghiệm thu ở docs/IMPLEMENTATION.md.

## Backup và restore

Sao lưu cả DB, `storage/app/private/media`, `storage/app/private/avatars`, và giữ .env/APP_KEY an toàn. Tạo thư mục backup riêng trước khi chạy, chọn tên file mới để không ghi đè bản cũ:

```powershell
& 'D:/linh_tinh/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe' --host=127.0.0.1 --port=3307 --user=root --single-transaction --default-character-set=utf8mb4 --no-tablespaces --result-file=D:/backup/moc_blog_2026-10-13.sql moc_blog
```

Khôi phục vào **database mới** trước, không import thẳng đè website đang có dữ liệu:

```sql
CREATE DATABASE moc_blog_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```powershell
& 'D:/linh_tinh/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe' --host=127.0.0.1 --port=3307 --user=root --default-character-set=utf8mb4 moc_blog_restore --execute="SOURCE D:/backup/moc_blog_2026-10-13.sql"
```

Khôi phục hai thư mục ảnh, kiểm tra bản restore trước khi đổi DB_DATABASE. Nếu MySQL có mật khẩu, dùng `-p` để nhập ẩn, không ghi mật khẩu vào lệnh.

Đã diễn tập DB ngày 13/10/2026: backup storage/app/private/backups/verified-20261013-2246/moc_blog.sql (38.040 bytes), restore vào **moc_blog_restore_20261013_2246**. Đối chiếu số dòng và CHECKSUM EXTENDED của **23/23 bảng đều khớp**; DB website không bị ghi đè. SHA-256: 3869E9C2F160E9392FC4E76A445FA386721310FF6CCB3CF3030706E04DB44FD3.

Bản trước migration còn ở backups/pre-update-20261013-223706. Backup và DB restore được giữ lại riêng, không commit Git hoặc đặt trong public. Thư mục media/avatar thực tế hiện rỗng nên chưa diễn tập khôi phục ảnh có dữ liệu; cần sao lưu đủ ảnh khi bắt đầu upload. Giữ .env/APP_KEY an toàn riêng, không đưa vào source bàn giao hoặc hộp thư thử.

## Lỗi khởi chạy thường gặp

- DB 2002/1049/1045: kiểm tra MySQL Laragon chạy, port 3307, DB đã tạo, user/password; website trả trang hướng dẫn 503 thay vì dữ liệu giả.
- 500/APP_KEY trống ở máy mới: key:generate chỉ khi chưa có key; kiểm tra quyền storage và bootstrap/cache.
- 419: đăng nhập lại, tải lại form; kiểm tra host cookie và APP_URL. Không tắt CSRF.
- Vite manifest/editor thiếu: dùng Node của Laragon 22.22, npm ci gồm optional dependencies và npm run build. Thiếu binding Rolldown: npm install --include=optional --ignore-scripts rồi build, không cần xóa lockfile.
- URL /admin không chạy: DocumentRoot phải là public và Apache AllowOverride All, Reload Laragon.
- Thay .env chưa có hiệu lực: php artisan config:clear.
- Google/SMTP chưa cấu hình: dùng login email/demo đã xác minh; không báo chức năng bên ngoài đã hoạt động.

Triển khai thật phải APP_DEBUG=false, HTTPS, SESSION_SECURE_COOKIE=true, tài khoản DB riêng ít quyền, mật khẩu mạnh, backup định kỳ và review bảo mật. Source bàn giao qua nhánh Dat; chưa triển khai website public. Code cũ trên Dat được thay bằng snapshot Laravel mới trong một commit kế tiếp, không xóa lịch sử hay sửa các nhánh khác.
