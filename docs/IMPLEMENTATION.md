# Trạng thái triển khai và nghiệm thu

Nguồn: word/doanltmnm.docx. Website blog cá nhân Laravel/Blade/MySQL Laragon, không app mobile, không luồng đăng bài/follow/tin nhắn của mạng xã hội. Giao diện theo Word: nền kem, xanh rêu, vàng nhạt, public/admin tách layout.

Cập nhật theo yêu cầu mới: **bình luận mới và reply hiển thị ngay, không duyệt trước**. Ngày 14/10/2026 bổ sung dashboard tổng bình luận/reply và link xử lý pending cũ nếu còn; số liệu lấy từ DB, loại bài trong thùng rác như danh sách quản trị. Bình luận pending cũ không tự công khai; nội dung hidden/spam không tự công khai khi độc giả sửa. Source Laravel đã bàn giao vào Threads-City/Dat, thay toàn bộ nội dung code cũ nhưng giữ lịch sử Git; đợt bàn giao ngày 14/10/2026 gồm cập nhật thiết lập mật khẩu Google, dashboard, tiếng Việt, reply phẳng/tải thêm và chuông thông báo; chưa deploy website public.

## Kết quả đã chạy

- MySQL Laragon 8.4.3 trên 3307, website moc_blog, kiểm thử moc_blog_test. Server MySQL 3306 không thay đổi.
- Ba migration bổ sung đã áp dụng: post_likes, mặc định comments.status=approved và notifications; không reset dữ liệu website.
- PHPUnit toàn bộ ngày 14/10/2026 sau bổ sung reply tải thêm và chuông thông báo, bật integration SMTP local: **147 tests / 1.648 assertions đạt**, không warning/risky.
- Suite có 147 tests; loại local-mail còn 144 tests. Ba tests local-mail là opt-in, 36 assertions.
- JavaScript: **18 tests đạt**, kiểm tra reply tải lười/ẩn bớt, SEO preview và thông báo kiểm tra form/lỗi mạng tiếng Việt.
- Đã dùng HTTP thật gửi form đăng ký sai mật khẩu: lỗi xác nhận không khớp và thiếu chữ cái hiển thị tiếng Việt, không còn The password field. Các bộ tests kiểm tra cả form/JSON, upload, quyền, CSRF 419, rate limit 429 giữ Retry-After, link xác minh, lỗi MySQL, bảo vệ thông tin nội bộ và email/phân trang tiếng Việt.
- Route: **71 routes**, gồm POST/DELETE thích bài.
- Blade cache thành công; **103 compiled views PHP lint đạt** sau cập nhật reply và chuông thông báo ngày 14/10/2026. Sitemap HTTP thật parse XML hợp lệ trong đợt kiểm tra trước.
- Node 22.22 / Vite build thành công; TinyMCE self-host, font Lora thường/nghiêng và CSS/JS đã build lại.
- Trong đợt kiểm thử trước khi khởi tạo Git, formatter app/config/database/tests/routes/bootstrap đã chạy và đạt.
- HTTP Apache thật: public, bài viết, auth, sitemap/robots, font, CSS/JS và TinyMCE trả 200; nút thích và link hộp thư local có trong HTML thật.
- /.env, /composer.json, /word/doanltmnm.docx: 404. URL backup SQL và database Mailpit trong storage: 403, không truy cập được qua web.
- Extension Laravel 2.0.1 đã cài theo xác nhận; workspace *.blade.php nhận là Blade, PHP executable đúng Laragon; không tắt validation PHP. Cần Reload Window để kiểm tra trực quan IDE.
- Mailpit riêng có sẵn của Laragon đang chạy: SMTP **127.0.0.1:11025**, UI/API **127.0.0.1:18025**; không gửi Internet, không thay đổi Mailpit khác.
- Đã dùng SMTP thật tới Mailpit trong integration tests: đăng ký → nhận email → link ký xác minh → bình luận ngay; quên mật khẩu → nhận link → reset một lần.
- Tài khoản Google: Hồ sơ có nút gửi link thiết lập/đặt lại mật khẩu riêng cho website; gửi đúng email của người đăng nhập, bỏ qua email giả mạo từ form. Link mở được trong phiên đăng nhập hiện tại, reset thành công đăng xuất; cả email/mật khẩu và Google dùng cùng tài khoản, giữ bookmark và google_id. Không đổi schema/mật khẩu cũ khi chỉ gửi yêu cầu. Form đổi mật khẩu bằng mật khẩu hiện tại vẫn có cho người đã thiết lập.
- Gmail SMTP đã xác thực SSL/credentials thành công; **người dùng xác nhận nhận được thư thật ngày 14/10/2026**. Đây là xác nhận của người dùng, không phải test tự động gửi Internet. Credentials chỉ nằm trong .env riêng, không nằm trong source.
- Backup SQL 38.040 bytes, restore vào moc_blog_restore_20261013_2246: **23/23 bảng khớp số dòng và CHECKSUM EXTENDED**. Main DB giữ nguyên; bản sao riêng được giữ lại.
- Backup nằm ở storage/app/private/backups/verified-20261013-2246; trước migration ở pre-update-20261013-223706.
- Đã diễn tập backup/restore JPEG cover và PNG avatar trên filesystem/database test riêng: upload qua backend, copy file backup, mô phỏng mất file, restore, SHA-256 và kích thước ảnh khớp, metadata còn nguyên, HTTP ảnh mở lại được; ảnh draft vẫn bị chặn với Reader. Không khôi phục đè dữ liệu website thật. README có lệnh PowerShell sao lưu/restore vào thư mục mới và đối chiếu hash.

Tests chỉ refresh DB moc_blog_test với chốt kiểm tra kết nối trước khi chạy. Không dùng SQLite cho dữ liệu website. File lưu thư của công cụ Mailpit là nội bộ công cụ, không phải database ứng dụng.

## Phạm vi automated tests

| Bộ kiểm thử | Nội dung chính |
| --- | --- |
| DatabaseSchemaTest | FK/quan hệ/unique, Unicode/emoji, soft delete, cascade/restrict, public scope, views và seeder lặp |
| SecurityTest | XSS/CSS nguy hiểm, định dạng editor, mục lục, transaction rollback, version/slug/author |
| PublicBlogTest | Public/draft/schedule, tìm/lọc, slug 301, metadata, XML sitemap/robots, bình luận và 404 |
| AdminWorkflowTest | Phân quyền, CRUD/preview/publish/schedule/trash/autosave, invalid form giữ nội dung, upload/media, taxonomy, report/moderation, dashboard/settings/khóa độc giả; tổng comment/reply/pending cũ và trash; backup/restore ảnh/avatar bằng file thật trong filesystem test |
| ReaderTest | Bình luận hiện ngay/reply/depth, ownership, report, bookmark, profile/avatar/password/locked session |
| InstantCommentsTest | Guest thấy bình luận mới ngay, reply phẳng tải theo nhóm, đúng người được trả lời, trạng thái cũ giữ nguyên, không republish hidden/spam, giữ text khi reply validation lỗi, chặn reply khác bài/cha ẩn |
| CommentThreadsTest | 9 tests: reply hơn 10 cấp nhưng hiển thị phẳng; tải 5 mục/cursor không trùng; ancestor hidden/cross-post/private; thông báo chỉ người được trả lời, không tự báo/không gửi mail; ownership/read state/deep link đúng trang; pending riêng tư; xóa cha/trash/xóa reply; giữ nội dung retry |
| PostLikesTest | Một lượt/người, request lặp không tạo trùng, chống giả user, bỏ thích của chính mình, middleware, chỉ bài public, state/count/soft-delete/cascade |
| SeoPreviewTest | Metadata/cover/OG và fallback server, chống HTML injection, old input sai kiểu, phân quyền |
| CmsTest | Register/login/logout, rate limit, CSRF, signed verification, reset một lần, session revocation, mail fallback |
| OAuthTest | Google mock: Reader/link email verified, từ chối admin/locked/unverified, invalid state/thiếu cấu hình |
| GooglePasswordSetupTest | UI Hồ sơ Google/local; gửi đúng email; token hash/hết hạn/dùng một lần; chống giả email và CSRF; mail lỗi/throttle; mở link khi đang đăng nhập; đăng nhập lại qua mật khẩu hoặc Google |
| VietnameseMessagesTest | Bản dịch đủ quy tắc Laravel; mật khẩu/email/bình luận/upload; JSON từng trường; quyền/link ký/CSRF/rate limit; MySQL cụ thể; 500 không lộ nội bộ; email/phân trang tiếng Việt |
| FontAssetsTest | Đủ glyph tiếng Việt trong Lora thường/nghiêng self-host |
| LocalMailTest (opt-in) | SMTP thật đến Mailpit localhost, đọc đúng thư test qua API, verify signed link, reset token dùng một lần, Google reader thiết lập mật khẩu không cần đăng xuất trước |
| JavaScript SEO preview | Logic live preview và DOM text-only; không thay thế kiểm tra browser thật |

Google trong test được mô phỏng. LocalMailTest gửi thật qua SMTP **local**, không chứng minh SMTP Internet hoặc Google hoạt động. PHPUnit/Node không chạy tương tác TinyMCE trong browser.

## Trạng thái bàn giao

| Trạng thái | Phạm vi |
| --- | --- |
| Đã lập trình và kiểm thử | Schema/seeders; auth/phân quyền; CRUD/trash/schedule; media; taxonomy; comment/reply hiển thị ngay; moderation/report; like/bookmark/profile; dashboard; metadata/SEO preview; sanitize/validation/session |
| Đã chạy thực tế local | HTTP Apache, SMTP Mailpit, DB migration và backup/restore 23 bảng, assets/font/editor self-host, extension Blade |
| Cần nghiệm thu trực quan | Public/admin responsive, font sau Ctrl+F5, TinyMCE/paste Word/upload, autosave/two-tab conflict, share/clipboard và preview SEO qua thao tác browser |
| Đã được người dùng xác nhận | Gmail gửi tới hộp thư thật trên máy hiện tại; không thay thế việc cấu hình SMTP riêng ở máy khác |
| Cần cấu hình ở máy mới/khi deploy | Google OAuth client/consent; Gmail SMTP với App Password riêng; domain/HTTPS khi deploy |
| Ngoài phạm vi hiện tại | App mobile, social feed/follow/chat, PowerPaste premium; deploy website public |

**Bản local đã bổ sung mục chức năng còn thiếu được phát hiện khi đối chiếu Word, có thể đưa cho người khác test; chưa đánh dấu nghiệm thu trực quan cuối cùng.** Môi trường hiện không có công cụ browser/Computer Use cần thiết để điều khiển trình duyệt, nên không giả kết quả thao tác TinyMCE, paste Word, giao diện hay hai tab. Chưa chạy kiểm thử tải; diễn tập ảnh bằng dữ liệu test đã đạt, không phải khôi phục production.

## Route quan trọng

| Phạm vi | URL / route |
| --- | --- |
| Public | / ; /bai-viet ; /bai-viet/{slug} |
| Lọc / thông tin | /danh-muc/{slug} ; /the/{slug} ; /tac-gia ; /gioi-thieu ; /lien-he |
| Auth | /dang-nhap ; /dang-ky ; /dang-xuat |
| Email / reset | /xac-minh-email ; /email/verify/{id}/{hash} ; /quen-mat-khau ; /dat-lai-mat-khau/{token} |
| Google | /auth/google ; /auth/google/callback |
| Độc giả | /tai-khoan ; /bai-viet-da-luu ; /bai-viet/{id}/luu ; /bai-viet/{id}/thich |
| Bình luận / reply | POST /bai-viet/{id}/binh-luan (parent_id khi reply), PATCH/DELETE /binh-luan/{id} |
| Tải thêm reply | GET /binh-luan/{id}/tra-loi?after={cursor}; focus={comment_id} để mở đúng reply |
| Thông báo reply | GET /thong-bao; POST /thong-bao/{uuid}/mo và /thong-bao/doc-tat-ca; auth, ownership và CSRF |
| Báo cáo | /binh-luan/{id}/bao-cao |
| Ảnh | /media/{id} ; /avatar/{id} |
| Dashboard | /admin?days=7 hoặc 30/90 |
| CMS | /admin/posts ; /admin/posts/create ; /admin/posts/{id}/edit ; /preview ; /autosave |
| Thùng rác | /admin/posts?status=trash ; /admin/trash/{id}/restore ; DELETE /admin/trash/{id} |
| Quản trị | /admin/media ; /admin/taxonomies/categories ; /admin/taxonomies/tags ; /admin/comments ; /admin/users ; /admin/settings |
| SEO | /sitemap.xml ; /robots.txt |

Ghi dữ liệu dùng POST/PATCH/PUT/DELETE, có CSRF và middleware; không thêm quyền đăng bài cho Reader.

## Checklist bạn test trong browser

1. Mở Doan.test, Ctrl+F5. Kiểm tra dấu tiếng Việt, chữ nghiêng, nav/tìm/lọc/phân trang/404 ở desktop và cửa sổ thu nhỏ.
2. Login admin demo; tạo danh mục/thẻ, viết bài TinyMCE: heading, font, màu, list, bảng, link, ALT/chú thích. Chèn ảnh thật và thử ảnh/SVG/PHP/quá lớn.
3. Lưu nháp/mở lại/preview để kiểm tra định dạng và ảnh; autosave 45 giây; thử hai tab cùng sửa để kiểm tra xung đột.
4. Chọn cover/OG; sửa title/SEO/description/slug/canonical để xem preview cập nhật. Đây là mô phỏng; không công khai nội dung chưa lưu.
5. Publish, mở cửa sổ khách kiểm tra URL/nội dung/ảnh/mục lục/metadata; đổi slug để kiểm tra redirect cũ.
6. Schedule giờ Việt Nam; trash/restore nháp; force delete có xác nhận.
7. Login reader demo: thích/bỏ thích, lưu/bỏ lưu. Gửi comment và reply tiếp hơn 10 lượt; mở phiên guest, nhấn Xem trả lời/Xem thêm 5 trả lời/Ẩn bớt, xác nhận chỉ một mức thụt lề và đúng người được trả lời. Dùng hai tài khoản kiểm tra chuông, số chưa đọc, click đến đúng reply và đánh dấu tất cả đã đọc. Pending cũ không tự công khai.
8. Sửa/xóa comment của mình; thử của người khác phải bị chặn. HTML/script chỉ hiển thị chữ. Reply validation lỗi phải giữ text trong đúng form.
9. Report → admin ẩn/spam/xử lý; sửa comment đã ẩn không tự công khai. Khóa reader phải logout phiên.
10. Với cấu hình Gmail hiện tại: đăng ký mới → mở Inbox/Spam của email đăng ký → bấm thư xác minh → bình luận ngay; quên mật khẩu → thư thật → reset. Nếu đã chuyển MAIL_HOST về Mailpit để test thì mở http://127.0.0.1:18025 thay Inbox. Thử link sửa/hết hạn/dùng lại.
11. Profile/avatar/password/logout/login. Với tài khoản Google: gửi liên kết thiết lập mật khẩu trong Hồ sơ, mở thư khi vẫn đang đăng nhập, đặt mật khẩu rồi thử cả email/mật khẩu và Google; kiểm tra bookmark/bình luận còn nguyên. Với tài khoản đã có mật khẩu: thử form đổi mật khẩu hiện tại, mật khẩu cũ sai phải bị chặn. Guest đọc bài rồi xem dashboard 7/30/90, không tăng khi reload cùng phiên/ngày hoặc admin đọc.
12. Google thật chỉ test sau khi có credentials; dùng localhost:8000 theo README, không trộn host với Doan.test.
13. Khi upload ảnh thật, backup đủ DB/media/avatar/APP_KEY an toàn, restore bản sao riêng trước khi dùng. Không import đè DB website.
14. VS Code: Developer: Reload Window, mở lại sitemap.blade.php kiểm tra language mode Blade; không tắt lỗi PHP để che lỗi thật.

Tài khoản demo, hướng dẫn Mailpit/restart và cài máy khác ở README. MAIL_PREVIEW_URL chỉ dùng localhost ở local; không để nó trên production. Các thư mail-test-...@example.test là test SMTP, không phải tài khoản website hiện tại.

Không commit .env, bản SQL, file hộp thư hay credentials. Source bàn giao tại Threads-City, nhánh Dat; đợt cập nhật ngày 14/10/2026 gồm thiết lập mật khẩu Google, dashboard, thông báo tiếng Việt, reply tải thêm/ẩn bớt, chuông thông báo và các tests tương ứng. Các nhánh khác không thay đổi và code cũ vẫn có trong lịch sử commit. Hướng dẫn clone/cài máy khác và Gmail SMTP ở README. Người dùng xác nhận Gmail nhận thư thật; hiện luồng xác minh/reset qua link, chưa có OTP.
