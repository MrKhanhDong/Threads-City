@props(['media'])
<div class="modal fade" id="cover-picker" tabindex="-1" aria-labelledby="cover-picker-title" aria-hidden="true" data-cover-picker data-upload-url="{{ route('admin.media.store') }}">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cover-picker-title">Chọn ảnh bìa</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="nav nav-tabs mb-3" role="tablist" aria-label="Nguồn ảnh">
                    <button type="button" class="nav-link active" id="cover-library-tab" data-cover-library-tab data-bs-toggle="tab" data-bs-target="#cover-library-pane" role="tab" aria-controls="cover-library-pane" aria-selected="true">Thư viện ảnh</button>
                    <button type="button" class="nav-link" id="cover-upload-tab" data-bs-toggle="tab" data-bs-target="#cover-upload-pane" role="tab" aria-controls="cover-upload-pane" aria-selected="false">Tải ảnh lên</button>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="cover-library-pane" role="tabpanel" aria-labelledby="cover-library-tab" tabindex="0">
                        <label for="cover-search" class="form-label">Tìm ảnh theo tên</label>
                        <input type="search" id="cover-search" class="form-control mb-3" data-cover-search placeholder="Nhập tên ảnh…">
                        <div class="cover-library-grid" data-cover-library>
                            @foreach($media as $image)
                                <button type="button" class="cover-library-item" data-cover-item data-id="{{ $image->id }}" data-url="{{ $image->url() }}" data-name="{{ $image->original_name }}" data-alt="{{ $image->alt }}" aria-pressed="false">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt }}" loading="lazy">
                                    <span>{{ $image->original_name }}</span>
                                </button>
                            @endforeach
                        </div>
                        <p class="muted" data-cover-empty @if($media->isNotEmpty()) hidden @endif>Chưa có ảnh phù hợp. Chuyển sang tab Tải ảnh lên để thêm ảnh mới.</p>
                    </div>
                    <div class="tab-pane fade" id="cover-upload-pane" role="tabpanel" aria-labelledby="cover-upload-tab" tabindex="0">
                        <label for="cover-file" class="form-label">Chọn ảnh từ máy</label>
                        <input type="file" id="cover-file" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" data-cover-file aria-describedby="cover-file-help">
                        <p class="muted small" id="cover-file-help">JPEG, PNG, WebP · tối đa 5 MB. Ảnh tải lên sẽ tự lưu vào thư viện.</p>
                        <label for="cover-alt" class="form-label">Mô tả ảnh (ALT)</label>
                        <input type="text" id="cover-alt" class="form-control mb-3" maxlength="255" data-cover-alt placeholder="Mô tả ngắn về ảnh">
                        <button type="button" class="btn btn-forest" data-cover-upload>Tải ảnh lên thư viện</button>
                    </div>
                </div>
                <p class="small mt-3 mb-0" data-cover-status role="status" aria-live="polite"></p>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="small text-break" data-cover-selection>Chưa chọn ảnh.</span>
                <button type="button" class="btn btn-forest" data-cover-confirm data-bs-dismiss="modal" disabled>Dùng làm ảnh bìa</button>
            </div>
        </div>
    </div>
</div>
