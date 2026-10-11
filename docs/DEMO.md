# Checklist chạy demo và nộp đồ án

## Chuẩn bị

- MySQL đang chạy, database threads_city_blog đã migrate/seed.
- Chạy start.ps1, mở http://127.0.0.1:8000.
- Mở hai cửa sổ trình duyệt riêng (thường + ẩn danh) để tách admin/độc giả.
- Tài khoản demo trong README; nếu đổi password, dùng password mới.
- Không chiếu .env, log email reset hoặc APP_KEY trước lớp.

## Kịch bản demo 8–10 phút

1. **Khách:** trang chủ trắng–đen, menu bài viết/chuyên mục/giới thiệu, bài nổi bật. Tìm PHP, lọc Lập trình, chọn thẻ, đọc bài và mục lục.
2. **Độc giả:** đăng ký tài khoản mới; lưu bài, tải lại và kiểm tra Đọc sau. Gửi bình luận; thông báo chờ duyệt, lời nhắn chỉ tài khoản đó xem được.
3. **Phân quyền:** độc giả mở /admin → 403; cửa sổ chưa login mở /admin → chuyển đăng nhập.
4. **Admin:** đăng nhập, xem dashboard dữ liệu thật. Tạo chuyên mục/thẻ mới.
5. **Viết bài:** điền title/excerpt/content với ## tiêu đề, chọn category/tag, tải ảnh. Preview, lưu draft; mở URL public bằng cửa sổ khách → 404.
6. **Xuất bản:** đổi status published, lưu; khách đọc được bài, có ảnh/mục lục/thẻ. Sửa tiêu đề, lưu và tải lại kiểm tra.
7. **Duyệt:** chuyển sang Bình luận, duyệt lời nhắn của độc giả; khách đọc được. Từ chối/xóa một lời nhắn thử nếu cần.
8. **Quản lý:** mở Độc giả, khóa một tài khoản thử; cửa sổ độc giả bị chuyển đăng nhập ở request tiếp theo. Mở khóa.
9. **Cài đặt:** đổi tên blog/bio/avatar, xem trang chủ/giới thiệu thay đổi. Đổi mật khẩu cần current password.
10. **Thùng rác:** đưa bài thử vào thùng rác; khách nhận 404, Đọc sau ẩn bài. Khôi phục → bản nháp; kiểm tra ảnh/nội dung/thẻ còn nguyên, xuất bản lại → bookmark/bình luận vẫn còn.
11. **Thống kê:** đọc bài ở cửa sổ khách, tải lại không cộng thêm trong ngày; admin đọc/preview không cộng. Dashboard chọn 7/30/90 ngày, xem bảng số liệu, top bài và bình luận chờ duyệt. Dữ liệu ngày bắt đầu từ lúc nâng cấp.
12. **Dọn demo:** đưa lại bài thử vào thùng rác, mở Xóa vĩnh viễn, tích xác nhận; kiểm tra cascade comments/bookmarks/tags/daily views và dọn ảnh upload. Không xóa bài/nội dung thật của nhóm.

Reset password local: yêu cầu link và mở email trong log tại máy mình. Link có token nhạy cảm, không chia sẻ/chụp log; SMTP thật cần cấu hình.

## Kiểm tra hình ảnh bằng trình duyệt thật

Công cụ screenshot của môi trường phát triển bị lỗi sandbox; checklist này **chưa được đánh dấu là đã qua**.

- [ ] Desktop 1366×768 và 1920×1080: không cuộn ngang toàn trang, main/sidebar cân đối.
- [ ] Trắng chủ đạo, chữ/icon đen–xám; ảnh bìa hiển thị grayscale trong layout hiện tại.
- [ ] Tên blog/title/bio dài xuống dòng, không đè nút đăng nhập/đọc sau.
- [ ] Editor và bảng quản trị đọc được; bảng dài có vùng cuộn riêng.
- [ ] Biểu đồ đen–trắng đọc được, bảng ngày khớp biểu đồ; Thùng rác rõ khác biệt khôi phục/xóa vĩnh viễn.
- [ ] Form lỗi, flash thành công, trạng thái nháp/duyệt nhìn rõ.
- [ ] Tab keyboard, focus rõ, skip link đến nội dung; dialog đóng bằng nút/Escape.
- [ ] Thu nhỏ cửa sổ không làm mất form/nút; không yêu cầu app mobile.
- [ ] Không có like/follow/repost/feed hoặc nút chức năng giả.
- [ ] Tắt JavaScript vẫn đăng nhập/lưu bài/gửi form được.
- [ ] Chụp trang chủ, chi tiết bài, dashboard, editor, bình luận, ERD để đưa vào báo cáo.

## Kiểm thử tự động

- PHPUnit: 68 tests, 544 assertions trên SQLite in-memory và MySQL riêng.
- HTTP/DOM: 8 tests. Luồng HTTP thật qua MySQL với CSRF, login, draft/publish/bookmark/comment/approval/trash/restore/permanent delete; chart và bảng 7/30/90 ngày.
- SQL export đã được import thử vào database mới, trống: 5 bài, 2 tài khoản, 3 chuyên mục, 8 thẻ, 2 bình luận, không thiếu tác giả. Không import chồng lên app database.
- Composer validate, PHP syntax, view cache, route list, JS syntax là kiểm tra bổ sung; kết quả cập nhật trong lần bàn giao.
- Không coi test tự động là bảo đảm đạt toàn bộ rubric chưa nhận.

## Hồ sơ trước khi nộp

- [ ] Đối chiếu đề/rubric chính thức: chức năng bắt buộc, framework/version, database.
- [ ] Điền trường/lớp/nhóm/mã sinh viên/giảng viên; biên tập báo cáo Markdown vào mẫu được yêu cầu.
- [ ] Thống nhất sơ đồ lớp và database của nhóm với docs/DATABASE.md.
- [ ] Thay dữ liệu mẫu/ảnh/tiểu sử bằng nội dung của nhóm, xác nhận quyền dùng ảnh.
- [ ] Chạy cài đặt sạch trên máy khác và thực hiện demo theo kịch bản.
- [ ] Nộp source + composer.lock + SQL mới + tài liệu; không nộp .env/log/token/userdata.
- [ ] Bàn giao GitHub ở nhánh Dat theo yêu cầu chủ repo; không đẩy mật khẩu hoặc runtime portable.
