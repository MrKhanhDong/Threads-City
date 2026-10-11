# Báo cáo kỹ thuật — Website blog cá nhân Threads City

Bản nội dung kỹ thuật để nhóm biên tập vào mẫu báo cáo môn Lập trình mã nguồn mở. Chưa tự điền tên trường, mã sinh viên, giảng viên hoặc kết quả chấm khi chưa được cung cấp.

## 1. Bài toán và mục tiêu

Xây dựng website blog cá nhân cho một tác giả ghi lại hành trình học lập trình và trải nghiệm. Độc giả tìm bài theo chuyên mục/thẻ, lưu đọc sau và trao đổi trong bình luận được kiểm duyệt. Chủ blog quản lý nội dung, thông tin cá nhân và độc giả.

Giao diện web tối giản trắng–đen, trắng chủ đạo; desktop là nền tảng chính. Không xây ứng dụng mobile và không mô phỏng mạng xã hội Threads.

## 2. Công nghệ mã nguồn mở

PHP 8.3+, Laravel 13, Blade, MySQL 8, CSS/JavaScript thuần; Composer quản lý dependency và lock version. PHPUnit kiểm thử backend; jsdom và Node test runner kiểm thử DOM/HTTP tùy chọn. Laravel cung cấp routing, container, ORM Eloquent, migration, session authentication, validation, CSRF và password broker.

MySQL dùng InnoDB/utf8mb4; tránh phụ thuộc npm build frontend để sinh viên có thể chạy PHP trực tiếp. Runtime portable của máy phát triển chỉ là tiện ích local, không đi kèm Git.

## 3. Tác nhân và use case

| Tác nhân       | Chức năng                                                                                                                               |
| -------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Khách          | Đọc trang chủ, tìm/lọc bài, đọc chi tiết, xem chuyên mục/giới thiệu, đăng ký/đăng nhập/reset mật khẩu                                   |
| Độc giả        | Chức năng khách + sửa tài khoản, đổi mật khẩu, lưu/bỏ lưu bài, bình luận, xóa lời nhắn của mình, đăng xuất                              |
| Chủ blog/admin | CRUD bài/chuyên mục/thẻ, nháp/xuất bản/lưu trữ, upload ảnh, preview, duyệt/xóa bình luận, xem/khóa độc giả, cài đặt blog, xem dashboard |

Độc giả không có quyền đăng bài, tự duyệt bình luận hoặc sửa bài của tác giả. Admin không thể khóa chính mình. Không có giao diện cấp quyền admin công khai.

## 4. Phân tích dữ liệu và thiết kế

Thiết kế 9 bảng nghiệp vụ/xác thực: users, categories, tags, posts, post_tag, comments, bookmarks, password_reset_tokens, post_daily_views; thêm migrations do Laravel quản lý. Các quan hệ 1–N/N–N và ràng buộc được trình bày trong [DATABASE.md](DATABASE.md). Sơ đồ lớp/lớp dịch vụ và luồng MVC trong [ARCHITECTURE.md](ARCHITECTURE.md).

Schema cũ mang yếu tố mạng xã hội nên không import trực tiếp. Tách database mới để bảo vệ dữ liệu cũ, giữ SQL gốc phục vụ đối chiếu khi nhận sơ đồ chốt của nhóm.

## 5. Cài đặt chức năng

### Nội dung và giao diện

Trang chủ bài nổi bật và bài mới; danh sách có phân trang/tìm/lọc; chi tiết bài có mục lục, thời gian đọc, lượt xem và bài liên quan. Avatar, bio, trang giới thiệu, tên blog có thể sửa ở quản trị.

Editor dùng văn bản thuần và dòng ## để tạo tiêu đề mục, hỗ trợ xem trước an toàn không chạy HTML. Bài có category, nhiều tag, một ảnh bìa, trạng thái và cờ nổi bật. Slug tự sinh hoặc nhập riêng, unique; title dài vẫn tạo slug đúng độ dài cột.

### Tài khoản và độc giả

Đăng ký tạo reader với mật khẩu hash; đăng nhập/ghi nhớ/đăng xuất dùng session. Đổi mật khẩu yêu cầu mật khẩu hiện tại. Reset dùng token hết hạn 60 phút, dùng một lần. Khóa độc giả ngăn đăng nhập và chặn các request phiên đang có.

Đọc sau lưu ở database theo tài khoản. Bình luận pending mặc định; trước khi duyệt chỉ chủ lời nhắn xem được. User_id/status không lấy từ dữ liệu form độc giả.

### Quản trị và dữ liệu

Dashboard có biểu đồ/bảng lượt xem theo ngày, lọc 7/30/90 ngày, top bài trong kỳ và bình luận chờ duyệt; ghi một lượt/bài/phiên/ngày, không tính admin/preview, không bịa lịch sử lượt cũ. Quản lý bài, category, tag, comment, reader, thông tin blog.

Thùng rác dùng xóa mềm, giữ ảnh và các quan hệ; khôi phục về nháp để tránh công khai ngoài ý muốn. Xóa vĩnh viễn chỉ từ thùng rác với xác nhận, cascade dữ liệu liên quan; category có bài kể cả trong thùng rác không thể xóa; xóa tag không xóa post. Upload có validation, filename ngẫu nhiên, cleanup ảnh khi thay/xóa vĩnh viễn, không public ảnh bìa của bài chưa công khai.

## 6. Kiểm thử và kết quả

Môi trường kiểm thử: PHP 8.5.11 chạy dependency tương thích PHP 8.3+, Laravel 13.35.0, MySQL 8.0.46; Windows. Bộ test SQLite dùng in-memory; bộ test MySQL chỉ được phép thao tác threads_city_blog_test.

Kết quả gần nhất ghi ở README và [DEMO.md](DEMO.md). Bao phủ:

- Public rendering, empty/404, lọc/search/pagination, HTML escaping, lượt xem.
- Register/login/logout, duplicate email, password strength/hash, rate limit, reset one-time, session revoke.
- Bảo vệ tất cả màn hình và thao tác admin khỏi reader/guest.
- Draft/publish/archive, slug unique/transliteration, category/tag validation.
- Upload ảnh, file giả JPEG/PHP và SVG bị chặn, path traversal, draft cover privacy.
- Comment ownership/moderation, bookmarks theo tài khoản/idempotency.
- FK cascade, category restrict, seeder idempotent không ghi đè title/password.
- Thùng rác/khôi phục/xóa vĩnh viễn, giữ ảnh/quan hệ/lịch sử, slug giữ chỗ và phân quyền.
- Daily views qua nửa đêm Việt Nam, chống cộng khi tải lại, phạm vi ngày, top bài trong kỳ, loại thùng rác, dữ liệu cũ không bịa lịch sử.
- HTTP thực kiểm tra CSRF/session và chu trình đăng bài–lưu–bình luận–duyệt–xóa mềm–khôi phục nháp–xuất bản lại–xóa vĩnh viễn; DOM kiểm tra chart/bảng ngày, preview, copy, confirmation, native forms.

**Giới hạn kiểm chứng:** công cụ trình duyệt tự động bị lỗi sandbox, chưa có screenshot/layout QA. Nhóm cần mở desktop và chụp màn hình trang đọc/quản trị theo checklist. Không dùng kết quả DOM để khẳng định pixel-perfect với Figma.

## 7. Giới hạn thực tế và cấu hình còn phụ thuộc nhóm

- MAIL_MAILER=log ở local: reset đã triển khai, nhưng gửi email ra Internet cần tài khoản SMTP thật.
- Chưa deploy hosting/domain/HTTPS; có hướng dẫn production trong README.
- Không tích hợp API Meta/Threads; không có tính năng mạng xã hội.
- Không có audit trail quản trị, analytics nâng cao, WYSIWYG hoặc upload nhiều ảnh nội dung; ngoài scope blog tối giản hiện tại.
- Sơ đồ lớp/schema gốc nhóm sẽ gửi cần đối chiếu và thống nhất; không tự di trú tài khoản/dữ liệu thật cũ.
- Chưa nhận rubric nên không khẳng định đạt 100% yêu cầu môn; cần đối chiếu các chức năng bắt buộc của đề.

## 8. Hướng phát triển

Bổ sung Markdown đã sanitize hoặc WYSIWYG an toàn, ảnh nội dung, audit log, backup định kỳ, SMTP/HTTPS production. Nếu đề yêu cầu test/browser screenshot hoặc format báo cáo riêng, nhóm điền/chuẩn hóa theo rubric sau khi nhận mẫu.

## 9. Kết luận

Phiên bản hiện tại có frontend Laravel và backend MySQL hoạt động theo định hướng một chủ blog, với phân quyền và dữ liệu thật. Các chức năng cốt lõi có kiểm thử tự động, tài liệu cài đặt và kịch bản demo; những giới hạn triển khai và kiểm chứng được nêu rõ, không sử dụng chức năng giả để báo hoàn thành.
