<!-- Report Thread Modal -->
<div class="modal fade" id="reportThreadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content report-modal-content">
            <!-- Header -->
            <div class="report-modal-header">
                <div>
                    <h5 class="report-modal-title mb-1">Báo cáo bài viết</h5>
                    <p class="report-modal-subtitle mb-0">Lý do bạn báo cáo bài viết của <span id="reportModalAuthorName" class="text-white fw-semibold">tác giả</span>?</p>
                </div>
                <button type="button" class="btn-close-report" data-bs-dismiss="modal" aria-label="Close">✕</button>
            </div>

            <!-- Body: Options list -->
            <div class="report-modal-body">
                <input type="hidden" id="reportModalThreadId" value="">
                
                <label class="report-option-label">
                    <input type="radio" name="reportReason" value="spam" checked class="report-radio">
                    <div class="report-option-info">
                        <span class="report-option-title">Spam hoặc quảng cáo phiền toái</span>
                        <span class="report-option-desc">Nội dung quảng cáo hàng loạt, lừa đảo hoặc dẫn dụ</span>
                    </div>
                </label>

                <label class="report-option-label">
                    <input type="radio" name="reportReason" value="hate_speech" class="report-radio">
                    <div class="report-option-info">
                        <span class="report-option-title">Ngôn từ thù ghét hoặc quấy rối</span>
                        <span class="report-option-desc">Công kích cá nhân, xúc phạm hoặc đe dọa bạo lực</span>
                    </div>
                </label>

                <label class="report-option-label">
                    <input type="radio" name="reportReason" value="false_info" class="report-radio">
                    <div class="report-option-info">
                        <span class="report-option-title">Thông tin sai lệch hoặc tin giả</span>
                        <span class="report-option-desc">Lan truyền tin thất thiệt gây hại cho cộng đồng</span>
                    </div>
                </label>

                <label class="report-option-label">
                    <input type="radio" name="reportReason" value="sensitive" class="report-radio">
                    <div class="report-option-info">
                        <span class="report-option-title">Nội dung nhạy cảm, bạo lực</span>
                        <span class="report-option-desc">Hình ảnh hoặc câu từ phản cảm không phù hợp</span>
                    </div>
                </label>

                <label class="report-option-label">
                    <input type="radio" name="reportReason" value="other" class="report-radio">
                    <div class="report-option-info">
                        <span class="report-option-title">Lý do khác</span>
                        <span class="report-option-desc">Các vi phạm tiêu chuẩn cộng đồng khác</span>
                    </div>
                </label>
            </div>

            <!-- Footer -->
            <div class="report-modal-footer">
                <button type="button" class="btn-report-cancel" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn-report-submit" id="btnSubmitReport">Gửi báo cáo</button>
            </div>
        </div>
    </div>
</div>

<style>
.report-modal-content {
    background: #181818;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
    overflow: hidden;
}
.report-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 20px 22px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}
.report-modal-title {
    color: #ffffff;
    font-size: 16.5px;
    font-weight: 700;
}
.report-modal-subtitle {
    color: #8e8e8e;
    font-size: 13px;
}
.btn-close-report {
    background: transparent;
    border: none;
    color: #888888;
    font-size: 18px;
    padding: 4px;
    cursor: pointer;
}
.btn-close-report:hover {
    color: #ffffff;
}
.report-modal-body {
    padding: 14px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.report-option-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #121212;
    border: 1px solid rgba(255, 255, 255, 0.06);
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.report-option-label:hover {
    background: #202020;
    border-color: rgba(255, 255, 255, 0.15);
}
.report-radio {
    margin-top: 3px;
    accent-color: #ff3b30;
    cursor: pointer;
}
.report-option-info {
    display: flex;
    flex-direction: column;
}
.report-option-title {
    color: #f3f5f7;
    font-size: 14px;
    font-weight: 600;
}
.report-option-desc {
    color: #888888;
    font-size: 12px;
    margin-top: 2px;
}
.report-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
}
.btn-report-cancel {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #d1d5db;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-report-cancel:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}
.btn-report-submit {
    background: #ff3b30;
    border: none;
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 9999px;
    cursor: pointer;
    transition: background 0.15s;
}
.btn-report-submit:hover {
    background: #e0281d;
}
</style>
